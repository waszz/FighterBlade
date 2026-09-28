<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPvpPvePointsToPersonajesTable extends Migration
{
    public function up()
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedInteger('pvp_puntos')->default(0);
            $table->unsignedInteger('pve_puntos')->default(0);
        });
    }

    public function down()
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['pvp_puntos', 'pve_puntos']);
        });
    }
}
