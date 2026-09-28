<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('poderes', function (Blueprint $table) {
       
        $table->integer('chance_activacion')->nullable(); // o float si usás decimales
    });
}

public function down()
{
    Schema::table('poderes', function (Blueprint $table) {
        $table->dropColumn(['chance_activacion']);
    });
}
};
