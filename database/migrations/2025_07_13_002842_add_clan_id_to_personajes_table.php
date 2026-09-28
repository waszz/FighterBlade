<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->unsignedBigInteger('clan_id')->nullable()->after('user_id');

        $table->foreign('clan_id')->references('id')->on('clans')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropForeign(['clan_id']);
        $table->dropColumn('clan_id');
    });
}

};
