<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
{
    Schema::table('peleas', function (Blueprint $table) {
        $table->string('ciudad_actual')->nullable()->after('datos_combate');
    });
}

public function down()
{
    Schema::table('peleas', function (Blueprint $table) {
        $table->dropColumn('ciudad_actual');
    });
}
};
