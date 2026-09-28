<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->unsignedBigInteger('post_id')->nullable()->after('id');
        $table->foreign('post_id')->references('id')->on('posts')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropForeign(['post_id']);
        $table->dropColumn('post_id');
    });
}
};
