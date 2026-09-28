<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->json('requisitos_equipo')->nullable();
        $table->json('requisitos_entrenamiento')->nullable();
        $table->json('requisitos_accesorio')->nullable();
    });
}

public function down(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn('requisitos_equipo');
        $table->dropColumn('requisitos_entrenamiento');
        $table->dropColumn('requisitos_accesorio');
    });
}

};
