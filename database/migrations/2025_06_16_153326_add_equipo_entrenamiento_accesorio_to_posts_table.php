<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEquipoEntrenamientoAccesorioToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('equipo_nombre')->nullable()->after('gif_victoria');
            $table->string('entrenamiento_nombre')->nullable()->after('equipo_nombre');
            $table->string('accesorio_nombre')->nullable()->after('entrenamiento_nombre');

            $table->string('equipo_imagen')->nullable()->after('accesorio_nombre');
            $table->string('entrenamiento_imagen')->nullable()->after('equipo_imagen');
            $table->string('accesorio_imagen')->nullable()->after('entrenamiento_imagen');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'equipo_nombre', 
                'entrenamiento_nombre', 
                'accesorio_nombre',
                'equipo_imagen', 
                'entrenamiento_imagen', 
                'accesorio_imagen'
            ]);
        });
    }
}