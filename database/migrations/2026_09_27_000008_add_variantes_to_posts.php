<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Variantes de los sets de la zona inicial (Black / normal / Gold): cada una suelta una parte fija de su set
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('variante_de_post_id')->nullable(); // set original
            $table->string('variante_parte', 20)->nullable();             // equipo | entrenamiento | accesorio
            $table->string('filtro_gif')->nullable();                     // filtro CSS de color (Black / Gold)
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['variante_de_post_id', 'variante_parte', 'filtro_gif']);
        });
    }
};
