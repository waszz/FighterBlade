<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Entrenamiento con el maestro: dura 8 horas y al terminar da la mitad de la exp del nivel.
// (se llama "entreno" para no confundirlo con la parte del set "entrenamiento_id")
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->timestamp('entreno_fin')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn('entreno_fin');
        });
    }
};
