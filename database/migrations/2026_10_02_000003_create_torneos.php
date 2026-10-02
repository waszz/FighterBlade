<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Torneo de los viernes y sábados (ver App\Models\Torneo)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('torneos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();                 // día del torneo (hora local del juego)
            $table->string('estado', 20)->default('inscripcion'); // inscripcion, en_curso, terminado, cancelado
            $table->unsignedInteger('ronda')->default(0);     // última ronda jugada
            $table->dateTime('proxima_ronda_at')->nullable();
            $table->foreignId('ganador_id')->nullable()->constrained('personajes')->nullOnDelete();
            $table->string('premio_set')->nullable();         // set que se llevó el ganador (texto)
            $table->timestamps();
        });

        Schema::create('torneo_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->foreignId('personaje_id')->constrained('personajes')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete(); // el set que le tocó
            $table->unsignedTinyInteger('vidas')->default(2);
            $table->unsignedInteger('victorias')->default(0);
            $table->unsignedInteger('derrotas')->default(0);
            $table->unsignedInteger('eliminado_en_ronda')->nullable();
            $table->timestamps();
            $table->unique(['torneo_id', 'personaje_id']);
        });

        Schema::create('torneo_peleas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->unsignedInteger('ronda');
            $table->foreignId('a_id')->constrained('torneo_participantes')->cascadeOnDelete();
            $table->foreignId('b_id')->nullable()->constrained('torneo_participantes')->cascadeOnDelete(); // null: pasa sin pelear
            $table->foreignId('ganador_id')->nullable()->constrained('torneo_participantes')->cascadeOnDelete();
            $table->json('detalle')->nullable();               // golpes y daño total de cada uno
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('torneo_peleas');
        Schema::dropIfExists('torneo_participantes');
        Schema::dropIfExists('torneos');
    }
};
