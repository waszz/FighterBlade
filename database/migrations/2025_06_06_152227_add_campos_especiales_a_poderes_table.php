<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('poderes', function (Blueprint $table) {
        $table->boolean('anula_todos_los_efectos')->default(false)->after('descripcion');
        $table->boolean('anula_poderes_enemigo')->default(false)->after('anula_todos_los_efectos');
        // Agrega aquí otros campos que quieras usar
    });
}

public function down()
{
    Schema::table('poderes', function (Blueprint $table) {
        $table->dropColumn([
            'anula_todos_los_efectos',
            'anula_poderes_enemigo',
            // Y los otros que agregues
        ]);
    });
}
};
