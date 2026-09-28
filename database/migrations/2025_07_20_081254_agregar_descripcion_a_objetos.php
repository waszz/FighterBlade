<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AgregarDescripcionAObjetos extends Migration
{
    public function up()
    {
        Schema::table('objetos', function (Blueprint $table) {
            $table->string('descripcion')->nullable()->after('imagen');
        });
    }

    public function down()
    {
        Schema::table('objetos', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });
    }
}
