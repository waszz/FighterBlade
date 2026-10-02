<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cada pelea del torneo queda también como una pelea normal (Mis Peleas → PvP) para verla con la misma pantalla de rondas
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('torneo_peleas', function (Blueprint $table) {
            $table->foreignId('pelea_id')->nullable()->after('ganador_id')->constrained('peleas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('torneo_peleas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pelea_id');
        });
    }
};
