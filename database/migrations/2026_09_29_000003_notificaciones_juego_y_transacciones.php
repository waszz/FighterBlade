<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Notificaciones del juego (campanita arriba: recarga de esmeraldas, te atacaron, te compraron, intercambios)
// y registro de transacciones entre jugadores (compras del mercado e intercambios)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_juego', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personaje_id')->constrained('personajes')->cascadeOnDelete();
            $table->string('icono', 16)->nullable();
            $table->string('mensaje', 500);
            $table->boolean('leida')->default(false);
            $table->timestamps();
            $table->index(['personaje_id', 'leida']);
        });

        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20); // mercado | intercambio
            // Mercado: de = vendedor, para = comprador. Intercambio: de = el que lo pidió, para = el otro
            $table->foreignId('de_personaje_id')->nullable()->constrained('personajes')->nullOnDelete();
            $table->foreignId('para_personaje_id')->nullable()->constrained('personajes')->nullOnDelete();
            // Lo que entregó cada lado: {"de": {"objetos": [...], "oro": n, "diamante": n}, "para": {...}}
            $table->json('detalle');
            $table->timestamps();
            $table->index(['de_personaje_id', 'created_at']);
            $table->index(['para_personaje_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones');
        Schema::dropIfExists('notificaciones_juego');
    }
};
