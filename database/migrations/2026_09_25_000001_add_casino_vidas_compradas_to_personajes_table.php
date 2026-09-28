<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            // Vidas de casino compradas con diamantes (no se recargan solas)
            $table->unsignedInteger('casino_vidas_comun')->default(0);
            $table->unsignedInteger('casino_vidas_super')->default(0);
            $table->unsignedInteger('casino_vidas_ultra')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['casino_vidas_comun', 'casino_vidas_super', 'casino_vidas_ultra']);
        });
    }
};
