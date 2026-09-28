<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExploracionFieldsToPersonajesTable extends Migration
{
    public function up()
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->timestamp('exploracion_inicio')->nullable()->after('updated_at');
            $table->integer('exploracion_duracion')->nullable()->after('exploracion_inicio');
            $table->unsignedBigInteger('enemigo_id')->nullable()->after('exploracion_duracion');

            // Si quieres, agrega clave foránea para enemigo_id si tienes tabla enemigos
            // $table->foreign('enemigo_id')->references('id')->on('enemigos')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['exploracion_inicio', 'exploracion_duracion', 'enemigo_id']);
            // $table->dropForeign(['enemigo_id']); // si agregaste foreign key
        });
    }
}