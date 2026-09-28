<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->unsignedBigInteger('enemigo_actual_id')->nullable()->after('user_id');
        $table->foreign('enemigo_actual_id')->references('id')->on('posts')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropForeign(['enemigo_actual_id']);
        $table->dropColumn('enemigo_actual_id');
    });
}

};
