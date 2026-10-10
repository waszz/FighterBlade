<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Torre y Misiones: al terminarlas se puede empezar de nuevo (vuelta) con rivales más fuertes (ver Explorar::FACTOR_POR_VUELTA)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedSmallInteger('torre_vuelta')->default(0);
            $table->unsignedSmallInteger('misiones_vuelta')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['torre_vuelta', 'misiones_vuelta']);
        });
    }
};
