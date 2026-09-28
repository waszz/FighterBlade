<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historial de giros del casino
        Schema::create('casino_tiradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personaje_id')->constrained('personajes')->cascadeOnDelete();
            $table->json('rodillos');
            $table->string('moneda');              // oro, diamante
            $table->unsignedInteger('apuesta');
            $table->unsignedInteger('premio');
            $table->json('especial')->nullable();  // {clave, texto} si salió un premio especial
            $table->timestamps();

            $table->index(['personaje_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casino_tiradas');
    }
};
