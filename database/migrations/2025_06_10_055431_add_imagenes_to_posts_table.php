<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up()
{
    Schema::table('posts', function (Blueprint $table) {
        $table->string('imagen1')->nullable();
        $table->string('imagen2')->nullable();
        $table->string('imagen3')->nullable();
    });
}

public function down()
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn(['imagen1', 'imagen2', 'imagen3']);
    });
}
};
