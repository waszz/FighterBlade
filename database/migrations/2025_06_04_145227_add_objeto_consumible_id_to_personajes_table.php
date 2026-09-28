<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->unsignedBigInteger('objeto_consumible_id')->nullable()->after('nombre'); // o después de la columna que quieras
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropColumn('objeto_consumible_id');
    });
}
};
