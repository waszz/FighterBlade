<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Animaciones cuyo gif viene mirando a la izquierda (se giran en las peleas), ej: ["gif", "gif_ataque"]
            $table->json('gifs_girados')->nullable();
        });

        // Los sets marcados enteros como "mira a la izquierda" pasan a tener todas sus animaciones giradas
        $campos = ['gif', 'gif_ataque', 'gif_critico', 'gif_especial', 'gif_defensa', 'gif_derrota', 'gif_victoria'];
        DB::table('posts')->where('orientacion_gif', 'izquierda')->update(['gifs_girados' => json_encode($campos)]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('gifs_girados');
        });
    }
};
