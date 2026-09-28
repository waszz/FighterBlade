<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('gif_ataque')->nullable();
            $table->string('gif_defensa')->nullable();
            $table->string('gif_critico')->nullable();
            $table->string('gif_especial')->nullable();
            $table->string('gif_victoria')->nullable();
            $table->string('gif_derrota')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'gif_ataque',
                'gif_defensa',
                'gif_critico',
                'gif_especial',
                'gif_victoria',
                'gif_derrota',
            ]);
        });
    }
};