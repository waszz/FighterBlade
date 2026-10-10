<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Jefe de la semana: la ciudad desde la que viajó gratis a su zona (al vencerlo vuelve ahí)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jefe_intentos', function (Blueprint $table) {
            $table->foreignId('ciudad_origen_id')->nullable()->after('personaje_id')->constrained('ciudades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jefe_intentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ciudad_origen_id');
        });
    }
};
