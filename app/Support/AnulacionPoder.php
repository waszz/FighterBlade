<?php

namespace App\Support;

use Illuminate\Support\Collection;

// ANULACIÓN DE PODER: quien la tiene le anula al rival los poderes de su lista (la del modificador "anulacion_poder"
// del poder). Se aplica antes de calcular nada de la pelea: los stats, los buffs y los efectos usan los poderes que
// quedan. También la usa la vista de la pelea (para no mostrar "odia a" si la Enemistad fue anulada).
class AnulacionPoder
{
    const NOMBRE = 'ANULACIÓN DE PODER';

    // Poderes que anula quien tiene $poderes (vacío si no tiene Anulación de poder)
    public static function lista($poderes): array
    {
        foreach (collect($poderes ?? []) as $poder) {
            if (mb_strtoupper(self::campo($poder, 'nombre') ?? '') !== self::NOMBRE) {
                continue;
            }
            $mods = self::campo($poder, 'modificadores') ?? [];
            $mods = is_array($mods) ? $mods : (json_decode($mods, true) ?: []);
            foreach ($mods as $mod) {
                if (($mod['tipo'] ?? '') === 'anulacion_poder') {
                    return array_map('mb_strtoupper', $mod['poderes'] ?? []);
                }
            }
        }
        return [];
    }

    // Los poderes propios que quedan cuando el rival tiene Anulación de poder
    public static function filtrar($propios, $rival): Collection
    {
        $anulados = self::lista($rival);
        $propios = collect($propios ?? []);
        return $anulados
            ? $propios->reject(fn ($p) => in_array(mb_strtoupper(self::campo($p, 'nombre') ?? ''), $anulados, true))->values()
            : $propios->values();
    }

    // Nombres de los poderes propios que el rival anula (para mostrarlo)
    public static function anulados($propios, $rival): array
    {
        $anulados = self::lista($rival);
        return collect($propios ?? [])
            ->map(fn ($p) => self::campo($p, 'nombre'))
            ->filter(fn ($n) => $n && in_array(mb_strtoupper($n), $anulados, true))
            ->values()->all();
    }

    private static function campo($poder, string $campo)
    {
        return is_array($poder) ? ($poder[$campo] ?? null) : ($poder->$campo ?? null);
    }
}
