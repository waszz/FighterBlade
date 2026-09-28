<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->string('tipo')->nullable(); // Guerrero, Mago, etc.
            $table->integer('nivel')->default(1);
            $table->string('imagen')->nullable(); // Imagen del personaje
            $table->string('gif')->nullable(); // Animación opcional
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personajes');
    }
};