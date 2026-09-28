<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatosCombateToPeleasTable extends Migration
{
    public function up()
    {
        Schema::table('peleas', function (Blueprint $table) {
            $table->json('datos_combate')->nullable()->after('realizada_en');
        });
    }

    public function down()
    {
        Schema::table('peleas', function (Blueprint $table) {
            $table->dropColumn('datos_combate');
        });
    }
}
