<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->integer('oro')->default(0);
        $table->integer('diamante')->default(0);
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropColumn(['oro', 'diamante']);
    });
}
};
