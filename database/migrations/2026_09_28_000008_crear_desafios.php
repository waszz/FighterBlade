<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Duelos (pelea amistosa aceptada) e intercambios entre jugadores.
// Se piden desde el modal de un jugador y el otro tiene 30 s para aceptar o rechazar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desafios', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);                 // duelo | intercambio
            $table->foreignId('de_id')->constrained('personajes')->cascadeOnDelete();
            $table->foreignId('para_id')->constrained('personajes')->cascadeOnDelete();
            // pendiente | aceptado | rechazado | expirado | cancelado | completado
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('expira_en')->nullable();
            // Duelo: la pelea que se jugó
            $table->unsignedBigInteger('pelea_id')->nullable();
            // Intercambio: lo que pone cada uno ({objetos: [ids], oro, diamante}) y si ya confirmó
            $table->json('oferta_de')->nullable();
            $table->json('oferta_para')->nullable();
            $table->boolean('listo_de')->default(false);
            $table->boolean('listo_para')->default(false);
            // El que pidió ya vio cómo terminó (para no volver a avisarle)
            $table->boolean('visto_de')->default(false);
            $table->timestamps();

            $table->index(['para_id', 'estado']);
            $table->index(['de_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desafios');
    }
};
