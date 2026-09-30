<?php

use App\Models\Objeto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Las pociones pasan a tener 5 usos (menos las de Oro, Esmeraldas y Búsqueda): las que ya están en los inventarios y las de la
// tienda de pociones (1/1 → 5/5). Antes guarda una copia en storage/app/respaldos/ y el rollback la vuelve a poner.
return new class extends Migration
{
    const RESPALDO = 'respaldos/pociones-cinco-usos.json';

    public function up(): void
    {
        $objetos = DB::table('objetos')->where(fn ($q) => $q->where('pocion', true)->orWhere('tipo', 'pocion'))->get(['id', 'stats']);
        $tienda  = DB::table('mercado_pociones')->get(['id', 'stats']);

        if (! Storage::exists(self::RESPALDO)) {
            Storage::put(self::RESPALDO, json_encode([
                'objetos'          => $objetos->pluck('stats', 'id')->all(),
                'mercado_pociones' => $tienda->pluck('stats', 'id')->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        foreach (['objetos' => $objetos, 'mercado_pociones' => $tienda] as $tabla => $filas) {
            foreach ($filas as $fila) {
                $stats = json_decode($fila->stats ?? '[]', true);
                if (is_string($stats)) { // guardadas dos veces como JSON
                    $stats = json_decode($stats, true);
                }
                if (! is_array($stats)) {
                    continue;
                }
                $nuevos = Objeto::conUsosDePocion($stats);
                if ($nuevos !== $stats) {
                    DB::table($tabla)->where('id', $fila->id)->update(['stats' => json_encode($nuevos, JSON_UNESCAPED_UNICODE)]);
                }
            }
        }
    }

    public function down(): void
    {
        if (! Storage::exists(self::RESPALDO)) {
            return;
        }
        foreach (json_decode(Storage::get(self::RESPALDO), true) as $tabla => $filas) {
            foreach ($filas as $id => $stats) {
                DB::table($tabla)->where('id', $id)->update(['stats' => $stats]);
            }
        }
        Storage::delete(self::RESPALDO);
    }
};
