<?php

namespace App\Support;

// Poderes que suben stats (los puntos amarillos que vienen de un poder). Lo usan el panel de atributos y la pelea,
// así lo que se ve es lo que se pelea:
//  - "optimizar_stats" (ENERGIZADO, GOLPES VELOCES, OFENSIVO EXPERTO, MOLE, TÉCNICAS CERTERAS): de los stats que nombra,
//    el segundo más alto sube el mayor × factor
//  - "multiplicador_stat" (SUPER ATAQUE, DEFENSA, ENERGÍA, FUERZA, RESISTENCIA, VELOCIDAD): multiplica su stat
class PoderesStats
{
    // Mismo orden en que se aplicaban en el panel
    const OPTIMIZAR = ['ENERGIZADO', 'GOLPES VELOCES', 'OFENSIVO EXPERTO', 'MOLE', 'TÉCNICAS CERTERAS'];
    const MULTIPLICAR = [
        'SUPER ATAQUE'      => 'ataque',
        'SUPER DEFENSA'     => 'defensa',
        'SUPER ENERGÍA'     => 'energia',
        'SUPER FUERZA'      => 'fuerza',
        'SUPER RESISTENCIA' => 'resistencia',
        'SUPER VELOCIDAD'   => 'velocidad',
    ];

    public static function aplicar(array $stats, $poderes): array
    {
        $poderes = collect($poderes ?? []);

        foreach (self::OPTIMIZAR as $nombre) {
            foreach (self::conNombre($poderes, $nombre) as $poder) {
                foreach (self::modificadores($poder) as $mod) {
                    if (($mod['tipo'] ?? '') !== 'optimizar_stats') {
                        continue;
                    }
                    $valores = [];
                    foreach ($mod['stats'] ?? [] as $stat) {
                        $valores[$stat] = $stats[$stat] ?? 0;
                    }
                    if (count($valores) < 2) {
                        continue;
                    }
                    arsort($valores);
                    [$claveMayor, $claveMenor] = array_keys($valores);
                    $nuevo = intval(round($valores[$claveMenor] + $valores[$claveMayor] * ($mod['factor'] ?? 0)));
                    if ($nuevo > $valores[$claveMenor]) {
                        $stats[$claveMenor] = $nuevo;
                    }
                }
            }
        }

        foreach (self::MULTIPLICAR as $nombre => $statDelPoder) {
            foreach (self::conNombre($poderes, $nombre) as $poder) {
                foreach (self::modificadores($poder) as $mod) {
                    if (($mod['tipo'] ?? '') === 'multiplicador_stat'
                        && strtolower($mod['stat'] ?? '') === $statDelPoder
                        && isset($stats[$statDelPoder])) {
                        $stats[$statDelPoder] = intval(round($stats[$statDelPoder] * ($mod['factor'] ?? 1)));
                    }
                }
            }
        }

        return $stats;
    }

    private static function conNombre($poderes, string $nombre)
    {
        return $poderes->filter(fn ($p) => mb_strtoupper($p['nombre'] ?? '') === $nombre);
    }

    private static function modificadores($poder): array
    {
        $mods = $poder['modificadores'] ?? [];
        return is_array($mods) ? $mods : (json_decode($mods, true) ?: []);
    }
}
