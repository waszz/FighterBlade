<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->unsignedBigInteger('viajando_a_id')->nullable()->after('viajando_hasta');
        $table->foreign('viajando_a_id')->references('id')->on('ciudades')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropForeign(['viajando_a_id']);
        $table->dropColumn('viajando_a_id');
    });
}

};
