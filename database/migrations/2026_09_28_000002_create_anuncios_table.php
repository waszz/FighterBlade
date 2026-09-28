<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anuncios que los admins publican en el panel "Anuncios" de la ciudad
        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // autor (su foto va en el panel)
            $table->string('titulo', 120);
            $table->text('texto');
            $table->boolean('activo')->default(true); // oculto sin borrarlo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncios');
    }
};
