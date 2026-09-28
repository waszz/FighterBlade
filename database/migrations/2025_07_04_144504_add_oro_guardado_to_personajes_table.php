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
            // Agregar la columna 'oro_guardado' con valor por defecto 0
            $table->unsignedBigInteger('oro_guardado')->default(0)->after('oro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            // Eliminar la columna en caso de rollback
            $table->dropColumn('oro_guardado');
        });
    }
};
