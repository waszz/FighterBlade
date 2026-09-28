<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedTinyInteger('casino_vidas')->default(3);
            // Desde cuándo corre la recarga de la próxima vida (null = vidas llenas)
            $table->timestamp('casino_vidas_desde')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['casino_vidas', 'casino_vidas_desde']);
        });
    }
};
