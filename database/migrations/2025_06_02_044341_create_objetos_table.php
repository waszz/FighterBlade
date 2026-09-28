<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('objetos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personaje_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->string('tipo'); // equipo, entrenamiento, accesorio, consumible, etc.
            $table->unsignedTinyInteger('nivel')->default(1);
            $table->json('stats')->nullable(); // fuerza, velocidad, defensa, etc.
            $table->string('imagen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objetos');
    }
};