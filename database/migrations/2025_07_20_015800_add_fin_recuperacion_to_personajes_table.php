<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinRecuperacionToPersonajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personajes', function (Blueprint $table) {
            // Añadimos el campo 'fin_recuperacion'
            $table->timestamp('fin_recuperacion')->nullable()->after('fin_exploracion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personajes', function (Blueprint $table) {
            // Si necesitamos revertir la migración, eliminamos el campo
            $table->dropColumn('fin_recuperacion');
        });
    }
}
