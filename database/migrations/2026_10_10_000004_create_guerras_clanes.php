<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Guerra de clanes de los domingos (ver App\Models\GuerraClanes)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guerras_clanes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->foreignId('ciudad_id')->constrained('ciudades')->cascadeOnDelete();
            $table->string('estado', 20)->default('en_curso'); // en_curso, terminada
            $table->json('puntos')->nullable();                // {clan_id: prestigio de la guerra}
            $table->foreignId('ganador_clan_id')->nullable()->constrained('clans')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('guerra_viajeros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guerra_clanes_id')->constrained('guerras_clanes')->cascadeOnDelete();
            $table->foreignId('personaje_id')->constrained('personajes')->cascadeOnDelete();
            $table->foreignId('ciudad_origen_id')->nullable()->constrained('ciudades')->nullOnDelete();
            $table->timestamps();
            $table->unique(['guerra_clanes_id', 'personaje_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guerra_viajeros');
        Schema::dropIfExists('guerras_clanes');
    }
};
