<?php

use App\Models\Mision;
use Database\Seeders\MisionesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

// Misiones: las esmeraldas van de 8 (la primera) a 85 (la última), unas 4.000 en total (antes de 20 a 200, 9.460).
// El oro no cambia. Usa las mismas cuentas que MisionesSeeder. Antes guarda una copia en storage/app/respaldos/
// y el rollback la vuelve a poner.
return new class extends Migration
{
    const RESPALDO = 'respaldos/misiones-esmeraldas-4000.json';

    public function up(): void
    {
        $misiones = Mision::orderBy('orden')->get();

        if (! Storage::exists(self::RESPALDO)) {
            Storage::put(self::RESPALDO, json_encode($misiones->pluck('recompensa_diamantes', 'id')->all(), JSON_PRETTY_PRINT));
        }

        $total = $misiones->count();
        foreach ($misiones->values() as $i => $mision) {
            $mision->update(['recompensa_diamantes' => MisionesSeeder::diamantes($i, $total)]);
        }
    }

    public function down(): void
    {
        if (! Storage::exists(self::RESPALDO)) {
            return;
        }
        foreach (json_decode(Storage::get(self::RESPALDO), true) as $id => $diamantes) {
            Mision::whereKey($id)->update(['recompensa_diamantes' => $diamantes]);
        }
        Storage::delete(self::RESPALDO);
    }
};
