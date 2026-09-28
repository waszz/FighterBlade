<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedBigInteger('ciudad_id')->nullable()->after('user_id');

            // Si tenés la tabla 'ciudades' y querés agregar la llave foránea:
            $table->foreign('ciudad_id')->references('id')->on('ciudades')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            // Para revertir, primero quitás la foreign key
            $table->dropForeign(['ciudad_id']);
            $table->dropColumn('ciudad_id');
        });
    }
};