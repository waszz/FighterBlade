<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('imagen')->nullable(); // Agrega la columna imagen
        });
    }
    
    public function down()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('imagen'); // Elimina la columna imagen si se revierte la migración
        });
    }
};
