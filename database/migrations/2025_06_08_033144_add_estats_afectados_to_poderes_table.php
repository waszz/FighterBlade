<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('poderes', function (Blueprint $table) {
            $table->json('stats_afectados')->nullable();
        });
    }

    public function down()
    {
        Schema::table('poderes', function (Blueprint $table) {
            $table->dropColumn('stats_afectados');
        });
    }
};
