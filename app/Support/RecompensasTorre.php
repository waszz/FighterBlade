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
        'oro'      => ['nombre' => 'Poción de Oro',       'imagen' => 'pocion-oro.png',       'afecta' => 'oro',         'descripcion' => 'Otorga 100 de oro'],
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

    public static function joya(int $nivel): array
    {
        $escalon = self::escalon($nivel);
        $i = min($escalon, count(self::NOMBRES_JOYA)) - 1;
        $total = (int) round($nivel * self::PUNTOS_JOYA_POR_NIVEL);
        [$a, $b] = array_rand(array_flip(self::STATS), 2);
        $primero = (int) round($total * random_int(40, 60) / 100);
        $stats = array_fill_keys(self::STATS, 0);
        $stats[$a] = $primero;
        $stats[$b] = $total - $primero;

        return [
            'tipo'        => 'joya',
            'nombre'      => self::NOMBRES_JOYA[$i] . " (Nv $nivel)",
            'stats'       => $stats,
            'imagen'      => 'torre/joya' . ($i + 1) . '.png',
            'nivel'       => $nivel,
            'requisitos'  => [],
            'origen_post_id' => null,
            'descripcion' => 'Joya: se equipa en el lugar de la joya del inventario.',
        ];
    }

    public static function cofre(int $nivel): array
    {
        $escalon = self::escalon($nivel);

        return [
            'tipo'        => 'cofre',
            'nombre'      => "Cofre (Nv $nivel)",
            'stats'       => [],
            'imagen'      => 'torre/cofre' . min($escalon, 9) . '.png',
            'nivel'       => $nivel,
            'requisitos'  => [],
            'origen_post_id' => null,
            'descripcion' => 'Abrilo desde el inventario pagando ' . number_format(self::costoAbrirCofre($nivel), 0, ',', '.') . ' de oro: 3 pociones de drop, de oro o de esmeraldas, o un set completo.',
        ];
    }

    // Oro que cuesta abrir un cofre: 100 por nivel (el de nivel 10 cuesta 1000, el de 100 cuesta 10000)
    const ORO_ABRIR_COFRE_POR_NIVEL = 100;

    public static function costoAbrirCofre(int $nivel): int
    {
        return max(1000, $nivel * self::ORO_ABRIR_COFRE_POR_NIVEL);
    }

    // Abre un cofre: crea su contenido en el inventario, borra el cofre y devuelve un texto con lo que tocó
    public static function abrirCofre(Objeto $cofre, Personaje $personaje): string
    {
        $nivel = (int) ($cofre->nivel ?? 20);
        $opcion = ['drop', 'oro', 'diamante', 'set'][random_int(0, 3)];

        if ($opcion === 'set') {
            // Un set normal del nivel del cofre (o el más cercano por debajo)
            $set = Post::where('nivel', '<=', $nivel)->orderByDesc('nivel')->inRandomOrder()->first()
                ?? Post::inRandomOrder()->first();
            $set = Post::where('nivel', $set->nivel)->inRandomOrder()->first();
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
        return $texto;
    }
}
