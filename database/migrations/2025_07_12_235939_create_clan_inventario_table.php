<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('clan_inventario', function (Blueprint $table) {
        $table->id();
        
        // Relación con clans
        $table->foreignId('clan_id')->constrained('clans')->onDelete('cascade');
        
        // Relación con objetos
        $table->foreignId('objeto_id')->constrained('objetos')->onDelete('cascade');
        
        // Cantidad del objeto en inventario
        $table->integer('cantidad')->default(1);
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clan_inventario');
    }
};
