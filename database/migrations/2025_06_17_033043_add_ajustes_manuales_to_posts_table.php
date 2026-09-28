<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
{
    Schema::table('posts', function (Blueprint $table) {
        $table->json('ajustes_manuales_equipo')->nullable();
        $table->json('ajustes_manuales_entrenamiento')->nullable();
        $table->json('ajustes_manuales_accesorio')->nullable();
    });
}

public function down()
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn([
          'ajustes_manuales_equipo',
          'ajustes_manuales_entrenamiento',
          'ajustes_manuales_accesorio',
        ]);
    });
}
};
