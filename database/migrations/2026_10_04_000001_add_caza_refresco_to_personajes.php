<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Caza: refrescar el tablero de presas (propio del jugador), hasta Caza::REFRESCOS_POR_DIA veces por día
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedInteger('caza_refresco_semilla')->default(0);   // 0: el tablero de todos
            $table->unsignedInteger('caza_refresco_rotacion')->nullable();  // en qué rotación se refrescó
            $table->date('caza_refrescos_fecha')->nullable();              // día (hora local) de los refrescos usados
            $table->unsignedTinyInteger('caza_refrescos_usados')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['caza_refresco_semilla', 'caza_refresco_rotacion', 'caza_refrescos_fecha', 'caza_refrescos_usados']);
        });
    }
};
