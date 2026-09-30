<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

// Rivales de misión (también pelean en la Torre): sus stats de pelea siguen la regla de su tipo de daño
// (ver Post::aplicarStatsPorTipoRival). Antes guarda una copia en storage/app/respaldos/ y el rollback la vuelve a poner.
return new class extends Migration
{
    const RESPALDO = 'respaldos/rivales-mision-stats-por-tipo.json';

    public function up(): void
    {
        $rivales = Post::conRivales()->where('es_enemigo', Post::RIVAL_MISION)->get();

        if (! Storage::exists(self::RESPALDO)) {
            Storage::put(self::RESPALDO, json_encode(
                $rivales->mapWithKeys(fn ($p) => [$p->id => $p->getRawOriginal('stats')])->all(),
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ));
        }

        foreach ($rivales as $rival) {
            $rival->aplicarStatsPorTipoRival();
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
