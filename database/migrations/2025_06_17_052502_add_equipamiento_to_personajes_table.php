<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEquipamientoToPersonajesTable extends Migration
{
    public function up()
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedBigInteger('equipo_id')->nullable()->after('id');
            $table->unsignedBigInteger('entrenamiento_id')->nullable()->after('equipo_id');
            $table->unsignedBigInteger('accesorio_id')->nullable()->after('entrenamiento_id');

            $table->foreign('equipo_id')->references('id')->on('objetos')->onDelete('set null');
            $table->foreign('entrenamiento_id')->references('id')->on('objetos')->onDelete('set null');
            $table->foreign('accesorio_id')->references('id')->on('objetos')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->dropForeign(['entrenamiento_id']);
            $table->dropForeign(['accesorio_id']);

            $table->dropColumn(['equipo_id', 'entrenamiento_id', 'accesorio_id']);
        });
    }
}
