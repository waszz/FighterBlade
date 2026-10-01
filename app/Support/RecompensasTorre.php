<?php

namespace App\Support;

use App\Models\Objeto;
use App\Models\Personaje;
use App\Models\Post;

// Recompensas de la Torre:
// - todos los pisos: oro y el doble de exp que una pelea normal (Explorar)
// - pisos de nivel 20, 30, ..., 100: además un cofre o una joya (al azar), solo la primera vez
// Imágenes en storage/app/public/posts/torre (joya1..8, cofre1..9: una por escalón)
class RecompensasTorre
{
    const MULTIPLICADOR_EXP = 2;

    // Oro de cada piso: nivel del piso × esto (el piso 1, nivel 5, da 100; el último, nivel 100, da 2.000)
    const ORO_POR_NIVEL = 20;

    public static function oro(int $nivel): int
    {
        return $nivel * self::ORO_POR_NIVEL;
    }
    // Cada 10 niveles cae un cofre o una joya (Torre y Misiones)
    const NIVELES_ESPECIALES = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];

    // Joya: 2 stats de ese nivel, que suman nivel × este valor (a nivel 10 ≈ 18, a nivel 100 ≈ 175)
    const PUNTOS_JOYA_POR_NIVEL = 1.75;
    const NOMBRES_JOYA = ['Joya de Ámbar', 'Joya de Zafiro', 'Joya de Rubí', 'Joya de Amatista', 'Joya de Obsidiana', 'Joya de Esmeralda', 'Joya Sangrienta', 'Joya Maldita'];
    const STATS = ['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'];

    // Contenido posible de un cofre (uno al azar)
    const POCIONES_COFRE = [
        'drop'     => ['nombre' => 'Poción de Búsqueda',  'imagen' => 'pocion-busqueda.png',  'afecta' => 'drop_partes', 'descripcion' => '100% de probabilidad de drop'],
        'oro'      => ['nombre' => 'Poción de Oro',       'imagen' => 'pocion-oro.png',       'afecta' => 'oro',         'descripcion' => 'Duplica el oro de la próxima victoria'],
        'diamante' => ['nombre' => 'Poción de Esmeraldas', 'imagen' => 'pocion-diamantes.png', 'afecta' => 'diamante',    'descripcion' => 'Otorga 100 esmeraldas'],
    ];

    public static function esNivelEspecial(int $nivel): bool
    {
        return in_array($nivel, self::NIVELES_ESPECIALES, true);
    }

    // Escalón 1..10 (nivel 10 = 1, nivel 100 = 10). Hay 9 cofres y 8 joyas: los últimos escalones repiten la última imagen
    public static function escalon(int $nivel): int
    {
        return max(1, min(10, intdiv($nivel, 10)));
    }

    // Misiones que dan cofre o joya: la primera de la escalera que llega a cada nivel 10, 20, ..., 100 → [mision_id => nivel]
    public static function misionesConPremio(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        $misiones = \App\Models\Mision::with('rival')->orderBy('orden')->get();
        foreach (self::NIVELES_ESPECIALES as $nivel) {
            $mision = $misiones->first(fn ($m) => ($m->rival?->nivel ?? 0) >= $nivel);
            if ($mision && ! isset($cache[$mision->id])) {
                $cache[$mision->id] = $nivel;
            }
        }
        return $cache;
    }

    // Datos del drop (mismo formato que los drops de pelea) para un cofre o una joya de ese nivel
    public static function premio(int $nivel): array
    {
        return random_int(0, 1) === 0 ? self::cofre($nivel) : self::joya($nivel);
    }

    // Rareza de las joyas (anillos): cuántos stats trae y cuántos puntos (× los de una normal)
    const RAREZAS_JOYA = [
        'normal'     => ['nombre' => '',            'stats' => 2, 'puntos' => 1.0],
        'rara'       => ['nombre' => ' Rara',       'stats' => 3, 'puntos' => 1.5],
        'legendaria' => ['nombre' => ' Legendaria', 'stats' => 4, 'puntos' => 2.2],
    ];

    public static function joya(int $nivel, string $rareza = 'normal'): array
    {
        $datosRareza = self::RAREZAS_JOYA[$rareza] ?? self::RAREZAS_JOYA['normal'];
        $escalon = self::escalon($nivel);
        $i = min($escalon, count(self::NOMBRES_JOYA)) - 1;
        $total = (int) round($nivel * self::PUNTOS_JOYA_POR_NIVEL * $datosRareza['puntos']);

        // Los puntos se reparten entre sus stats (al azar, más o menos parejo)
        $elegidos = (array) array_rand(array_flip(self::STATS), $datosRareza['stats']);
        shuffle($elegidos);
        $pesos = array_map(fn () => random_int(40, 60), $elegidos);
        $stats = array_fill_keys(self::STATS, 0);
        $repartido = 0;
        foreach ($elegidos as $k => $stat) {
            $valor = $k === count($elegidos) - 1 ? $total - $repartido : (int) round($total * $pesos[$k] / array_sum($pesos));
            $stats[$stat] = $valor;
            $repartido += $valor;
        }

        return [
            'tipo'        => 'joya',
            'nombre'      => self::NOMBRES_JOYA[$i] . $datosRareza['nombre'] . " (Nv $nivel)",
            'stats'       => $stats,
            'imagen'      => 'torre/joya' . ($i + 1) . '.png',
            'nivel'       => $nivel,
            'requisitos'  => [],
            'origen_post_id' => null,
            'descripcion' => 'Joya: se equipa en el lugar de la joya del inventario.',
        ];
    }

    // Cofre. Con $dificultad (Normal, Difícil, Pesadilla) es un cofre de la Mazmorra: más chance de traer un set
    public static function cofre(int $nivel, ?string $dificultad = null): array
    {
        $escalon = self::escalon($nivel);
        $chanceSet = $dificultad ? (self::CHANCE_SET_COFRE_MAZMORRA[$dificultad] ?? null) : null;

        return [
            'tipo'        => 'cofre',
            'nombre'      => $dificultad ? "Cofre de Mazmorra {$dificultad} (Nv $nivel)" : "Cofre (Nv $nivel)",
            'stats'       => [],
            'imagen'      => 'torre/cofre' . min($escalon, 9) . '.png',
            'nivel'       => $nivel,
            'requisitos'  => [],
            'origen_post_id' => null,
            'descripcion' => 'Abrilo desde el inventario pagando ' . number_format(self::costoAbrirCofre($nivel), 0, ',', '.') . ' de oro: '
                . ($chanceSet ? "{$chanceSet}% de traer un set completo; si no, 3 pociones de drop, de oro o de esmeraldas." : '3 pociones de drop, de oro o de esmeraldas, o un set completo.'),
        ];
    }

    // Cofres de la Mazmorra: % de que traigan un set completo según la dificultad (un cofre común: 1 de 4)
    const CHANCE_SET_COFRE_MAZMORRA = ['Normal' => 35, 'Difícil' => 55, 'Pesadilla' => 75];

    // % de set de un cofre ya guardado (el de la Mazmorra lo dice su nombre); null = cofre común
    public static function chanceSetCofre(Objeto $cofre): ?int
    {
        foreach (self::CHANCE_SET_COFRE_MAZMORRA as $dificultad => $chance) {
            if (str_contains((string) $cofre->nombre, "Cofre de Mazmorra {$dificultad}")) {
                return $chance;
            }
        }
        return null;
    }

    // Oro que cuesta abrir un cofre: 100 por nivel (el de nivel 10 cuesta 1000, el de 100 cuesta 10000)
    const ORO_ABRIR_COFRE_POR_NIVEL = 100;

    public static function costoAbrirCofre(int $nivel): int
    {
        return max(1000, $nivel * self::ORO_ABRIR_COFRE_POR_NIVEL);
    }

    // Cofre de bienvenida: se regala con el primer personaje de cada cuenta, se abre gratis y trae un set de nivel 5
    const NOMBRE_COFRE_BIENVENIDA = 'Cofre de Bienvenida';
    const NIVEL_COFRE_BIENVENIDA = 5;

    public static function esCofreBienvenida(Objeto $cofre): bool
    {
        return $cofre->nombre === self::NOMBRE_COFRE_BIENVENIDA;
    }

    public static function costoCofre(Objeto $cofre): int
    {
        return self::esCofreBienvenida($cofre) ? 0 : self::costoAbrirCofre((int) ($cofre->nivel ?? 10));
    }

    public static function darCofreBienvenida(Personaje $personaje): void
    {
        Objeto::create([
            'personaje_id'   => $personaje->id,
            'nombre'         => self::NOMBRE_COFRE_BIENVENIDA,
            'tipo'           => 'cofre',
            'nivel'          => self::NIVEL_COFRE_BIENVENIDA,
            'stats'          => [],
            'imagen'         => 'torre/cofre1.png',
            'origen_post_id' => null,
            'descripcion'    => '¡Regalo de bienvenida! Abrilo gratis: trae un set completo de nivel ' . self::NIVEL_COFRE_BIENVENIDA . ' al azar.',
            'requisitos_equipo' => [], 'requisitos_entrenamiento' => [], 'requisitos_accesorio' => [],
        ]);
    }

    // Abre un cofre: crea su contenido en el inventario, borra el cofre y devuelve un texto con lo que tocó
    public static function abrirCofre(Objeto $cofre, Personaje $personaje): string
    {
        $nivel = (int) ($cofre->nivel ?? 20);
        $bienvenida = self::esCofreBienvenida($cofre);
        $chanceSet = self::chanceSetCofre($cofre);
        $opcion = match (true) {
            $bienvenida => 'set',
            // Cofre de la Mazmorra: su chance de set; si no, una de las 3 pociones
            $chanceSet !== null => random_int(1, 100) <= $chanceSet ? 'set' : ['drop', 'oro', 'diamante'][random_int(0, 2)],
            default => ['drop', 'oro', 'diamante', 'set'][random_int(0, 3)],
        };

        if ($opcion === 'set') {
            if ($bienvenida) {
                // Un set normal publicado de nivel 5 (sin los personajes iniciales ni los enemigos especiales)
                $set = Post::where('nivel', self::NIVEL_COFRE_BIENVENIDA)
                    ->where('publicado', true)
                    ->where('inicial', false)
                    ->where(fn ($q) => $q->whereNull('es_enemigo')->orWhere('es_enemigo', 0))
                    ->inRandomOrder()->first();
            }
            // Un set normal del nivel del cofre (o el más cercano por debajo)
            if (empty($set)) {
                $set = Post::where('nivel', '<=', $nivel)->orderByDesc('nivel')->inRandomOrder()->first()
                    ?? Post::inRandomOrder()->first();
                $set = Post::where('nivel', $set->nivel)->inRandomOrder()->first();
            }
            foreach (['equipo', 'entrenamiento', 'accesorio'] as $tipo) {
                $decodificar = fn ($v) => is_array($v) ? $v : (json_decode($v ?? '[]', true) ?: []);
                Objeto::create([
                    'personaje_id'             => $personaje->id,
                    'nombre'                   => $set->{$tipo . '_nombre'} ?: (ucfirst($tipo) . ' de ' . $set->titulo),
                    'tipo'                     => $tipo,
                    'nivel'                    => $set->nivel,
                    'stats'                    => $decodificar($set->{'ajustes_manuales_' . $tipo}),
                    'imagen'                   => $set->{$tipo . '_imagen'} ?: preg_replace('#^posts/#', '', (string) $set->imagen),
                    'origen_post_id'           => $set->id,
                    'requisitos_equipo'        => $tipo === 'equipo' ? $decodificar($set->requisitos_equipo) : [],
                    'requisitos_entrenamiento' => $tipo === 'entrenamiento' ? $decodificar($set->requisitos_entrenamiento) : [],
                    'requisitos_accesorio'     => $tipo === 'accesorio' ? $decodificar($set->requisitos_accesorio) : [],
                    'pocion'                   => false,
                    'usos_restantes'           => 1,
                    'usos_totales'             => 1,
                ]);
            }
            $texto = "¡El cofre tenía el set completo de {$set->titulo} (Nv {$set->nivel})!";
        } else {
            $p = self::POCIONES_COFRE[$opcion];
            for ($k = 0; $k < 3; $k++) {
                Objeto::create([
                    'personaje_id'   => $personaje->id,
                    'nombre'         => $p['nombre'],
                    'tipo'           => 'pocion',
                    'nivel'          => 1,
                    'stats'          => ['usos_restantes' => 1, 'usos_totales' => 1, 'multiplicador' => 1, 'afecta' => $p['afecta']],
                    'imagen'         => $p['imagen'],
                    'pocion'         => true,
                    'descripcion'    => $p['descripcion'],
                    'usos_restantes' => 1,
                    'usos_totales'   => 1,
                    'requisitos_equipo' => [], 'requisitos_entrenamiento' => [], 'requisitos_accesorio' => [],
                ]);
            }
            $texto = "¡El cofre tenía 3 {$p['nombre']}!";
        }

        $cofre->delete();
        // También queda en las notificaciones (la campanita), para ver después qué trajo
        \App\Models\NotificacionJuego::avisar($personaje->id, '🎁', "Abriste {$cofre->nombre}: " . preg_replace('/^¡El cofre tenía /u', '', rtrim($texto, '!')));
        return $texto;
    }
}
