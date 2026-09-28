<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cosméticos de Extras: efectos de chat, efectos de nombre y auras (catálogo en App\Support\Cosmeticos)
return new class extends Migration
{
    public function up(): void
    {
        // Lo que compró cada personaje (compra permanente)
        Schema::create('personaje_cosmeticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personaje_id')->constrained('personajes')->cascadeOnDelete();
            $table->string('clave', 40);
            $table->timestamps();
            $table->unique(['personaje_id', 'clave']);
        });

        // Lo que tiene puesto (uno de cada tipo)
        Schema::table('personajes', function (Blueprint $table) {
            $table->string('efecto_chat', 40)->nullable();
            $table->string('efecto_nombre', 40)->nullable();
            $table->string('aura', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['efecto_chat', 'efecto_nombre', 'aura']);
        });
        Schema::dropIfExists('personaje_cosmeticos');
    }
};
