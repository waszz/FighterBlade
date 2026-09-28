<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('poderes', function (Blueprint $table) {
       
        $table->string('estado_especial')->nullable(); // o float si usás decimales
    });
}

public function down()
{
    Schema::table('poderes', function (Blueprint $table) {
        $table->dropColumn(['estado_especial']);
    });
}
};
