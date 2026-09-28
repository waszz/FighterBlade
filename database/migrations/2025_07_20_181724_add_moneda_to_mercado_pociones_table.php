<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
{
    Schema::table('mercado_pociones', function (Blueprint $table) {
        $table->string('moneda')->default('oro')->after('precio');
    });
}

public function down()
{
    Schema::table('mercado_pociones', function (Blueprint $table) {
        $table->dropColumn('moneda');
    });
}
};
