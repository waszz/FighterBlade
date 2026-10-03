<?php

namespace App\Support;

use Illuminate\Support\Collection;

// Pelea del torneo entre dos participantes, resuelta sola (las llaves avanzan sin que nadie apriete Atacar).
// Usa las mismas reglas de base que las peleas del juego (ver App\Livewire\Explorar):
//  - Pega uno por ronda, sorteado con peso según velocidad + nivel.
//  - Especial (velocidad > 30, 40%) ×1,2; crítico (fuerza > 30, 40%) ×1,5; si no, golpe normal.
//  - Daño físico = (fuerza / 2 + ataque), elemental = (energía / 2 + ataque), híbrido los dos × 0,65; × (1 + 2% por nivel), ±20%.
//  - Si el ataque no supera la defensa del otro, lo bloquea (el crítico se mide con la fuerza; parejos, dados).
//  - Contraataque (defensa > 30) y rebote (resistencia > 30): 0,5% por punto, hasta 50%.
//  - Poderes: daño directo, reducción de daño por tipo (los que suben stats ya vienen en los stats).
// Gana el que hizo más daño en total.
class SimuladorTorneo
{
    const RONDAS = 5; // como las peleas del juego
    const DANIO_POR_NIVEL = 0.02;
    const FACTOR_HIBRIDO = 0.65;
    const MINIMO_CONTRA_REBOTE = 30;

    /**
     * @param array $a ['nombre', 'tipo' (fisico|elemental|hibrido), 'stats' => [...], 'poderes' => Collection,
     *                  'gifs' => ['base', 'ataque', 'critico', 'especial', 'defensa']]
     * @return array ['ganador' => 'a'|'b', 'danio' => ['a' => int, 'b' => int], 'golpes' => [...],
     *                'rondas' => las acciones con el mismo formato que las peleas del juego ("a" = personaje, "b" = enemigo),
     *                para verlas con la misma pantalla (App\Livewire\MisPeleas / partials.resultado-pelea)]
     */
    public static function pelear(array $a, array $b, int $nivel): array
    {
        $lados = ['a' => $a, 'b' => $b];
        $danio = ['a' => 0, 'b' => 0];
        $extraTot = ['a' => 0, 'b' => 0]; // daño de poderes (Quemar, Sangrado...): va aparte, como en el juego
        $golpes = [];
        $rondas = [];
        $lado = fn ($quien) => $quien === 'a' ? 'personaje' : 'enemigo';
        $gif = fn ($quien, $cual) => $lados[$quien]['gifs'][$cual] ?? $lados[$quien]['gifs']['base'] ?? null;
        $mult = 1 + $nivel * self::DANIO_POR_NIVEL;

        for ($r = 1; $r <= self::RONDAS; $r++) {
            $pesoA = max(1, ($a['stats']['velocidad'] ?? 0) + $nivel);
            $pesoB = max(1, ($b['stats']['velocidad'] ?? 0) + $nivel);
            $at = mt_rand(1, $pesoA + $pesoB) <= $pesoA ? 'a' : 'b';
            $de = $at === 'a' ? 'b' : 'a';
            $A = $lados[$at];
            $D = $lados[$de];
            $sa = $A['stats'];
            $sd = $D['stats'];

            $tipoAtaque = match (true) {
                ($sa['velocidad'] ?? 0) > 30 && mt_rand(1, 100) <= 40 => 'especial',
                ($sa['fuerza'] ?? 0) > 30 && mt_rand(1, 100) <= 40     => 'critico',
                default                                                 => 'normal',
            };

            [$fis, $ele] = self::danioBase($A['tipo'], $sa, $mult);
            $factor = ['critico' => 1.5, 'especial' => 1.2, 'normal' => 1.0][$tipoAtaque];
            $fis = (int) round($fis * $factor);
            $ele = (int) round($ele * $factor);
            $extra = self::danioDirecto($A['poderes'], $sa, $mult);

            // ¿Lo bloquea? Ataque contra defensa (los dos suben 3% por nivel: a mismo nivel se comparan directo)
            $defensa = $sd['defensa'] ?? 0;
            $bloquea = ($sa['ataque'] ?? 0) <= $defensa;
            $dados = null;
            if ($tipoAtaque === 'critico') {
                $fuerza = $sa['fuerza'] ?? 0;
                if ($fuerza > $defensa * 1.1) {
                    $bloquea = false;
                } elseif ($defensa > $fuerza * 1.1) {
                    $bloquea = true;
                } else {
                    do {
                        [$x, $y] = [random_int(1, 6), random_int(1, 6)];
                    } while ($x === $y);
                    $bloquea = $y > $x;
                    $dados = ['atacante' => $x, 'defensor' => $y, 'nombre_atacante' => $A['nombre'], 'nombre_defensor' => $D['nombre']];
                }
            }

            $nombreTipo = ['especial' => 'un especial', 'critico' => 'un crítico', 'normal' => 'un golpe'][$tipoAtaque];
            $chanceContra = $defensa > self::MINIMO_CONTRA_REBOTE ? min(50, $defensa * 0.5) : 0;
            $contra = mt_rand(1, 10000) <= $chanceContra * 100;

            if ($bloquea || $contra) {
                $texto = "{$D['nombre']} bloquea " . $nombreTipo . " de {$A['nombre']}";
                if ($contra) {
                    // Contraataque: la mitad de un golpe normal del que bloqueó (según su tipo) y sus poderes de daño
                    [$cf, $ce] = self::danioBase($D['tipo'], $sd, $mult);
                    $cf = self::reducir((int) round($cf * 0.5), $A['poderes'], 'fisico');
                    $ce = self::reducir((int) round($ce * 0.5), $A['poderes'], 'elemental');
                    $extraC = self::danioDirecto($D['poderes'], $sd, $mult);
                    $danio[$de] += $cf + $ce + $extraC;
                    $extraTot[$de] += $extraC;
                    $golpes[] = ['quien' => $de, 'tipo' => 'contraataque', 'danio' => $cf + $ce + $extraC, 'texto' => $texto . ' y contraataca: ' . ($cf + $ce + $extraC) . ' de daño.'];
                    $rondas[] = ['ronda' => $r, 'atacante' => $lado($de), 'danio' => 0, 'gif' => $gif($de, 'defensa'),
                        'tipo_ataque' => 'bloqueo', 'texto_tipo_danio' => "{$D['nombre']} bloquea el golpe.", 'dados' => $dados];
                    $rondas[] = ['ronda' => $r, 'atacante' => $lado($de), 'danio' => $cf + $ce, 'danio_fisico' => $cf, 'danio_elemental' => $ce,
                        'gif' => $gif($de, 'ataque'), 'tipo_ataque' => 'contraataque', 'texto_tipo_danio' => "{$D['nombre']} realiza un contraataque!"];
                } else {
                    // Bloqueado: el golpe no entra, pero el daño de los poderes sí (como en el juego)
                    $danio[$at] += $extra;
                    $extraTot[$at] += $extra;
                    $golpes[] = ['quien' => $at, 'tipo' => 'bloqueo', 'danio' => $extra, 'texto' => $texto . '.' . ($extra ? " ($extra de poderes)" : '')];
                    $rondas[] = ['ronda' => $r, 'atacante' => $lado($at), 'danio' => 0, 'danio_fisico' => 0, 'danio_elemental' => 0,
                        'gif' => $gif($de, 'defensa'), 'tipo_ataque' => 'bloqueo', 'texto_tipo_danio' => "{$D['nombre']} se defiende y bloquea el ataque.", 'dados' => $dados];
                }
                continue;
            }

            $sinReducir = $fis + $ele;
            $total = self::reducir($fis, $D['poderes'], 'fisico') + self::reducir($ele, $D['poderes'], 'elemental');

            // Rebote: resiste el golpe entero y se lo devuelve
            $res = $sd['resistencia'] ?? 0;
            $chanceRebote = $res > self::MINIMO_CONTRA_REBOTE ? min(50, $res * 0.5) : 0;
            if ($total > 0 && mt_rand(1, 10000) <= $chanceRebote * 100) {
                $danio[$de] += $sinReducir;
                $danio[$at] += $extra;
                $extraTot[$at] += $extra;
                $golpes[] = ['quien' => $de, 'tipo' => 'rebote', 'danio' => $sinReducir,
                    'texto' => "{$D['nombre']} resiste " . $nombreTipo . " de {$A['nombre']} y le rebota $sinReducir de daño."];
                $rondas[] = ['ronda' => $r, 'atacante' => $lado($at), 'danio' => 0, 'danio_fisico' => 0, 'danio_elemental' => 0,
                    'gif' => $gif($at, $tipoAtaque === 'normal' ? 'ataque' : $tipoAtaque), 'tipo_ataque' => $tipoAtaque,
                    'texto_tipo_danio' => "{$D['nombre']} resiste el golpe y no recibe daño.", 'dados' => $dados];
                $rondas[] = ['ronda' => $r, 'atacante' => $lado($de), 'danio' => $sinReducir, 'gif' => $gif($de, 'base'), 'tipo_ataque' => 'rebote',
                    'texto_tipo_danio' => "{$D['nombre']} resiste el golpe y le rebota $sinReducir de daño a {$A['nombre']}."];
                continue;
            }

            $danio[$at] += $total + $extra;
            $extraTot[$at] += $extra;
            $golpes[] = ['quien' => $at, 'tipo' => $tipoAtaque, 'danio' => $total + $extra,
                'texto' => "{$A['nombre']} pega " . $nombreTipo . ': ' . ($total + $extra) . ' de daño' . ($extra ? " ($extra de poderes)" : '') . '.'];
            $fisFinal = self::reducir($fis, $D['poderes'], 'fisico');
            $rondas[] = ['ronda' => $r, 'atacante' => $lado($at), 'danio' => $total,
                'danio_fisico' => $fisFinal, 'danio_elemental' => $total - $fisFinal,
                'gif' => $gif($at, $tipoAtaque === 'normal' ? 'ataque' : $tipoAtaque), 'tipo_ataque' => $tipoAtaque,
                'texto_tipo_danio' => 'Físico: ' . $fisFinal . ' / Elemental: ' . ($total - $fisFinal), 'dados' => $dados];
        }

        // Absorber salud / Robar vida (lo que hizo) y Regenerar (lo que recibió): se descuenta del daño que le hicieron,
        // sin tocar el daño de poderes. Igual que al final de las peleas del juego
        $hecho = $danio;
        $absorcion = ['a' => 0, 'b' => 0];
        foreach (['a' => 'b', 'b' => 'a'] as $yo => $otro) {
            $regenerable = max(0, $danio[$otro] - $extraTot[$otro]);
            if ($regenerable <= 0) {
                continue;
            }
            foreach ($lados[$yo]['poderes'] as $poder) {
                foreach (self::mods($poder) as $mod) {
                    if (($mod['tipo'] ?? '') === 'porcentaje_regeneracion' && ($mod['stat_base'] ?? '') === 'danio_ejercido') {
                        $absorcion[$yo] += (int) round($hecho[$yo] * ($mod['valor'] ?? 0) / 100);
                    } elseif (($mod['tipo'] ?? '') === 'regeneracion' && ($mod['base'] ?? '') === 'danio_recibido') {
                        $absorcion[$yo] += (int) round($regenerable * ($mod['porcentaje'] ?? 0) / 100);
                    }
                }
            }
            $absorcion[$yo] = min($regenerable, $absorcion[$yo]);
            $danio[$otro] = $regenerable - $absorcion[$yo] + $extraTot[$otro];
        }

        $ganador = match (true) {
            $danio['a'] > $danio['b'] => 'a',
            $danio['b'] > $danio['a'] => 'b',
            default                   => mt_rand(0, 1) ? 'a' : 'b', // empate exacto: se sortea
        };

        return ['ganador' => $ganador, 'danio' => $danio, 'extra' => $extraTot, 'absorcion' => $absorcion, 'golpes' => $golpes, 'rondas' => $rondas];
    }

    // [físico, elemental] de un golpe normal según el tipo del set
    private static function danioBase(string $tipo, array $s, float $mult): array
    {
        $tirar = fn ($base) => $base > 0 ? mt_rand((int) round($base * $mult * 0.8), (int) round($base * $mult * 1.2)) : 0;
        $fis = $tirar(($s['fuerza'] ?? 0) * 0.5 + ($s['ataque'] ?? 0));
        $ele = $tirar(($s['energia'] ?? 0) * 0.5 + ($s['ataque'] ?? 0));
        return match ($tipo) {
            'hibrido'   => [(int) round($fis * self::FACTOR_HIBRIDO), (int) round($ele * self::FACTOR_HIBRIDO)],
            'elemental' => [0, $ele],
            default     => [$fis, 0],
        };
    }

    // Daño extra de los poderes de "daño directo" (ej. Quemar: 15% del ataque)
    private static function danioDirecto(Collection $poderes, array $s, float $mult): int
    {
        $extra = 0;
        foreach ($poderes as $poder) {
            foreach (self::mods($poder) as $mod) {
                if (($mod['tipo'] ?? '') === 'daño_directo' && ($stat = $mod['stat_base'] ?? null) && isset($s[$stat])) {
                    $base = mt_rand((int) round($s[$stat] * $mult * 0.8), (int) round($s[$stat] * $mult * 1.2));
                    $extra += (int) round($base * ($mod['porcentaje'] ?? 0) / 100);
                }
            }
        }
        return $extra;
    }

    // Reducción de daño de los poderes del que recibe (Piel dura, Reducción elemental...)
    private static function reducir(int $danio, Collection $poderes, string $tipo): int
    {
        foreach ($poderes as $poder) {
            foreach (self::mods($poder) as $mod) {
                if (($mod['tipo'] ?? '') === 'reduccion_danio' && strtolower($mod['tipo_danio'] ?? '') === $tipo) {
                    $danio = (int) round($danio * (1 - min(100, max(0, (float) ($mod['porcentaje'] ?? 0))) / 100));
                }
            }
        }
        return $danio;
    }

    private static function mods($poder): array
    {
        $raw = $poder['modificadores'] ?? [];
        return is_array($raw) ? $raw : (json_decode($raw ?: '[]', true) ?: []);
    }
}
