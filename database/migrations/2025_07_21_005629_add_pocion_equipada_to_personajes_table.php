<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->unsignedBigInteger('pocion_equipada')->nullable(); // Agregar columna para la poción equipada
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropColumn('pocion_equipada'); // Eliminar columna si revertimos la migración
    });
}
};
