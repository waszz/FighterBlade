<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->integer('experiencia')->default(0); // O el tipo que necesites
    });
}

public function down()
{
    Schema::table('personajes', function (Blueprint $table) {
        $table->dropColumn('experiencia');
    });
}
};
