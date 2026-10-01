<?php

use App\Models\Post;
use App\Support\PoderesStats;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

// El editor de sets guardaba los stats con los poderes que suben stats ya aplicados (ej. Enja: resistencia 130 con
// Súper Resistencia ×1,75 quedaba en 228) y la pelea los volvía a aplicar: contaban dos veces y la Anulación de poder
// no los podía sacar. Desde ahora se guardan sin poderes (base + partes); esto corrige los que ya estaban así.
// Solo se toca un set si sus stats coinciden con "partes + poderes" (y no con "partes solas"). Antes guarda una copia
// en storage/app/respaldos/ y el rollback la vuelve a poner.
return new class extends Migration
{
    const RESPALDO = 'respaldos/sets-stats-sin-poderes.json';
    const STATS = ['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'];

    public function up(): void
    {
        $dec = fn ($v) => is_array($v) ? $v : (json_decode($v ?? '[]', true) ?: []);
        $cerca = fn ($a, $b) => collect(self::STATS)->every(fn ($s) => abs((float) ($a[$s] ?? 0) - (float) ($b[$s] ?? 0)) <= 1);

        $cambios = [];
        foreach (Post::conRivales()->with('poderes')->get() as $post) {
            $sin = [];
            foreach (self::STATS as $s) {
                $sin[$s] = 5 + (int) ($dec($post->ajustes_manuales_equipo)[$s] ?? 0)
                    + (int) ($dec($post->ajustes_manuales_entrenamiento)[$s] ?? 0)
                    + (int) ($dec($post->ajustes_manuales_accesorio)[$s] ?? 0);
            }
            $guardado = $dec($post->stats);
            // Como lo calculaba el editor: primero los multiplicadores (sin redondear) y después el resto; y como la pelea
            $soloMultiplicadores = $sin;
            foreach ($post->poderes as $poder) {
                foreach ($dec($poder->modificadores) as $mod) {
                    if (($mod['tipo'] ?? '') === 'multiplicador_stat' && isset($soloMultiplicadores[strtolower($mod['stat'] ?? '')])) {
                        $soloMultiplicadores[strtolower($mod['stat'])] *= (float) ($mod['factor'] ?? 1);
                    }
                }
            }
            $conPoderes = PoderesStats::aplicar($sin, $post->poderes);
            $estaHorneado = ! $cerca($guardado, $sin)
                && ($cerca($guardado, $conPoderes) || $cerca($guardado, PoderesStats::aplicar($soloMultiplicadores, collect())) || $cerca($guardado, $soloMultiplicadores));
            if ($estaHorneado && $conPoderes != $sin) {
                $cambios[$post->id] = ['antes' => $post->getRawOriginal('stats'), 'despues' => $sin];
            }
        }

        if (! Storage::exists(self::RESPALDO)) {
            Storage::put(self::RESPALDO, json_encode(array_map(fn ($c) => $c['antes'], $cambios), JSON_PRETTY_PRINT));
        }
        foreach ($cambios as $id => $c) {
            Post::withoutGlobalScopes()->whereKey($id)->update(['stats' => json_encode($c['despues'])]);
        }
    }

    public function down(): void
    {
        if (! Storage::exists(self::RESPALDO)) {
            return;
        }
        foreach (json_decode(Storage::get(self::RESPALDO), true) as $id => $stats) {
            Post::withoutGlobalScopes()->whereKey($id)->update(['stats' => $stats]);
        }
        Storage::delete(self::RESPALDO);
    }
};
