<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->string('orientacion_gif')->default('izquierda'); // o 'derecha' según tu caso
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->string('orientacion_gif')->nullable(); // si usas gifs en posts
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropColumn('orientacion_gif');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn('orientacion_gif');
    });
}
};
