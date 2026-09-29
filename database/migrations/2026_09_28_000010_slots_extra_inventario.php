<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lugares del inventario comprados con oro (de a 3, hasta llegar a 200). Ver Personaje::capacidadInventario()
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedSmallInteger('slots_extra')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn('slots_extra');
        });
    }
};
