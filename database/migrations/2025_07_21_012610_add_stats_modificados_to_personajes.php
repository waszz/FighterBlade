<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->text('stats_modificados')->nullable(); // Agregar columna para stats modificados
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropColumn('stats_modificados'); // Eliminar columna si se revierte la migración
    });
}
};
