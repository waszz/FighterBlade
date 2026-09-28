<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->integer('tiempo_recuperacion')->default(30); // Agrega la columna con valor por defecto
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropColumn('tiempo_recuperacion');
    });
}
};
