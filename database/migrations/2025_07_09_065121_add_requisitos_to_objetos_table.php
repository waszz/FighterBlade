<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequisitosToObjetosTable extends Migration
{
    public function up()
    {
        Schema::table('objetos', function (Blueprint $table) {
            $table->json('requisitos_equipo')->nullable();
            $table->json('requisitos_entrenamiento')->nullable();
            $table->json('requisitos_accesorio')->nullable();
        });
    }

    public function down()
    {
        Schema::table('objetos', function (Blueprint $table) {
            $table->dropColumn(['requisitos_equipo', 'requisitos_entrenamiento', 'requisitos_accesorio']);
        });
    }
}
