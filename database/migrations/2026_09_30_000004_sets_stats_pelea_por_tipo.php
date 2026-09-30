<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

// Sets normales y variantes de la zona inicial: sus stats de pelea (como enemigos de exploración y de la Torre) siguen
// la regla de su tipo de daño, igual que sus partes (ver Post::aplicarStatsPelea). Antes guarda una copia en
// storage/app/respaldos/ y el rollback la vuelve a poner.
return new class extends Migration
{
    const RESPALDO = 'respaldos/sets-stats-pelea-por-tipo.json';

    public function up(): void
    {
        $sets = Post::conRivales()
            ->where(fn ($q) => $q->whereNull('es_enemigo')->orWhereNotIn('es_enemigo', [Post::ENEMIGO_ESPECIAL, Post::RIVAL_MISION]))
            ->get()
            ->filter(fn ($p) => $p->statsPeleaPorTipo());

        if (! Storage::exists(self::RESPALDO)) {
            Storage::put(self::RESPALDO, json_encode(
                $sets->mapWithKeys(fn ($p) => [$p->id => $p->getRawOriginal('stats')])->all(),
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ));
        }

        foreach ($sets as $set) {
            $set->aplicarStatsPelea();
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
