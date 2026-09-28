<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Puede ver el oro, las esmeraldas y los stats de los demás jugadores en los modales (los admins siempre)
            $table->boolean('ver_datos_jugadores')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ver_datos_jugadores');
        });
    }
};
