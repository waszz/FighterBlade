<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            // Jugador (personaje) contra el que se está peleando en PvP
            $table->foreignId('enemigo_actual_personaje_id')->nullable()->constrained('personajes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enemigo_actual_personaje_id');
        });
    }
};
