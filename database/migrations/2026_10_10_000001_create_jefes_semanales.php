<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Jefe de la semana (ver App\Models\JefeSemanal): un set y una ciudad al azar por semana; cada jugador tiene 3 intentos
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jefes_semanales', function (Blueprint $table) {
            $table->id();
            $table->date('semana')->unique(); // lunes de la semana (hora local del juego)
            $table->foreignId('ciudad_id')->constrained('ciudades')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('jefe_intentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jefe_semanal_id')->constrained('jefes_semanales')->cascadeOnDelete();
            $table->foreignId('personaje_id')->constrained('personajes')->cascadeOnDelete();
            $table->unsignedTinyInteger('intentos_usados')->default(0);
            $table->boolean('derrotado')->default(false);
            $table->boolean('en_pelea')->default(false);
            $table->timestamps();
            $table->unique(['jefe_semanal_id', 'personaje_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jefe_intentos');
        Schema::dropIfExists('jefes_semanales');
    }
};
