<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->string('direccion')->default('derecha');
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropColumn('direccion');
    });
}
};
