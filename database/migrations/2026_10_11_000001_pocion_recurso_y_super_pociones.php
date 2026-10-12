<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// - Lugar aparte para la poción de oro o de esmeraldas (pocion_recurso_id), además de la poción de stats/super poción.
//   Las que ya estaban equipadas en el lugar de siempre se pasan al nuevo.
// - Super pociones del mercado: 150 esmeraldas (antes 50)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->foreignId('pocion_recurso_id')->nullable()->constrained('objetos')->nullOnDelete();
        });

        foreach (DB::table('personajes')->whereNotNull('objeto_consumible_id')->get(['id', 'objeto_consumible_id']) as $pj) {
            $stats = json_decode((string) DB::table('objetos')->where('id', $pj->objeto_consumible_id)->value('stats'), true);
            if (is_string($stats)) {
                $stats = json_decode($stats, true);
            }
            if (in_array($stats['afecta'] ?? null, ['oro', 'diamante'], true)) {
                DB::table('personajes')->where('id', $pj->id)->update(['pocion_recurso_id' => $pj->objeto_consumible_id, 'objeto_consumible_id' => null]);
            }
        }

        DB::table('mercado_pociones')->where('nombre', 'like', 'Poción de Super%')->update(['precio' => 150]);
    }

    public function down(): void
    {
        DB::table('mercado_pociones')->where('nombre', 'like', 'Poción de Super%')->update(['precio' => 50]);
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pocion_recurso_id');
        });
    }
};
