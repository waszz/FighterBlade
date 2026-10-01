<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Eventos del calendario que carga el admin (torneos, jefes, mantenimiento...). Los automáticos (renovación del
// mercado, buffs globales) no se guardan acá: el calendario los arma solo (ver App\Livewire\Calendario)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 80);
            $table->string('tipo', 20)->default('otro'); // ver App\Models\Evento::TIPOS
            $table->string('descripcion', 300)->nullable();
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->timestamps();
            $table->index('inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
