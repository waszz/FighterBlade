<?php

use App\Models\Objeto;
use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

// Sets de nivel 50 en adelante: sus partes dan solo los stats de su tipo de daño, rotando por nivel
// (ver Post::STATS_POR_TIPO y Post::aplicarStatsPorTipo). Antes guarda una copia de lo que tenían los sets y las partes
// ya dropeadas en storage/app/respaldos/, y el rollback la vuelve a poner.
return new class extends Migration
{
    const RESPALDO = 'respaldos/sets-50-stats-por-tipo.json';
    const PARTES = ['equipo', 'entrenamiento', 'accesorio'];

    public function up(): void
    {
        $sets = Post::where('nivel', '>=', Post::NIVEL_STATS_POR_TIPO)->get()->filter(fn ($p) => $p->statsPorTipo());

        $respaldo = ['sets' => [], 'objetos' => []];
        foreach ($sets as $set) {
            foreach (self::PARTES as $parte) {
                $respaldo['sets'][$set->id]['ajustes_manuales_' . $parte] = $set->{'ajustes_manuales_' . $parte};
                $respaldo['sets'][$set->id]['requisitos_' . $parte] = $set->{'requisitos_' . $parte};
            }
        }
        foreach (Objeto::whereIn('origen_post_id', $sets->pluck('id'))->whereIn('tipo', self::PARTES)->get() as $objeto) {
            $respaldo['objetos'][$objeto->id] = [
                'stats' => $objeto->getRawOriginal('stats'),
                'requisitos_' . $objeto->tipo => $objeto->getRawOriginal('requisitos_' . $objeto->tipo),
            ];
        }
        // No se pisa un respaldo anterior (si la migración se corre de nuevo, queda el de antes del primer cambio)
        if (! Storage::exists(self::RESPALDO)) {
            Storage::put(self::RESPALDO, json_encode($respaldo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        foreach ($sets as $set) {
            $set->aplicarStatsPorTipo();
        }
    }

    public function down(): void
    {
        if (! Storage::exists(self::RESPALDO)) {
            return;
        }
        $respaldo = json_decode(Storage::get(self::RESPALDO), true);
        foreach ($respaldo['sets'] ?? [] as $id => $campos) {
            $set = Post::withoutGlobalScopes()->find($id);
            if ($set) {
                $set->forceFill($campos)->saveQuietly();
            }
        }
        foreach ($respaldo['objetos'] ?? [] as $id => $campos) {
            Objeto::whereKey($id)->update($campos);
        }
        Storage::delete(self::RESPALDO);
    }
};
