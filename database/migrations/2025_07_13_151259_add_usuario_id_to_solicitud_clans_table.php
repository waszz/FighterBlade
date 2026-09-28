<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('solicitud_clans', function (Blueprint $table) {
        $table->id();

        // Relación al usuario que solicita
        $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');

        // Relación al clan solicitado
        $table->foreignId('clan_id')->constrained('clans')->onDelete('cascade');

        // Estado de la solicitud
        $table->enum('estado', ['pendiente', 'aceptado', 'rechazado'])->default('pendiente');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_clans', function (Blueprint $table) {
            //
        });
    }
};
