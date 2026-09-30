<?php

use App\Models\Mision;
use App\Models\Post;
use Database\Seeders\MisionesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

// Misiones con menos premio: oro = nivel del rival × 50 (la primera 250) y esmeraldas de 20 a 200 (antes ×100 y 30 → 300).
// Usa las mismas cuentas que MisionesSeeder. Antes guarda una copia en storage/app/respaldos/ y el rollback la vuelve a poner.
return new class extends Migration
{
    const RESPALDO = 'respaldos/misiones-recompensas.json';

    public function up(): void
    {
        $misiones = Mision::orderBy('orden')->get();

        if (! Storage::exists(self::RESPALDO)) {
            Storage::put(self::RESPALDO, json_encode(
                $misiones->mapWithKeys(fn ($m) => [$m->id => ['oro' => $m->recompensa_oro, 'diamantes' => $m->recompensa_diamantes]])->all(),
                JSON_PRETTY_PRINT
            ));
        }

        $total = $misiones->count();
        foreach ($misiones->values() as $i => $mision) {
            $nivel = (int) (Post::conRivales()->whereKey($mision->post_id)->value('nivel') ?? 1);
            $mision->update([
                'recompensa_oro'       => $nivel * MisionesSeeder::ORO_POR_NIVEL,
                'recompensa_diamantes' => MisionesSeeder::diamantes($i, $total),
            ]);
        }
    }

    public function down(): void
    {
        if (! Storage::exists(self::RESPALDO)) {
            return;
        }
        foreach (json_decode(Storage::get(self::RESPALDO), true) as $id => $premio) {
            Mision::whereKey($id)->update(['recompensa_oro' => $premio['oro'], 'recompensa_diamantes' => $premio['diamantes']]);
        }
        Storage::delete(self::RESPALDO);
    }
};
