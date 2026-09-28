<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('estados_temporales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('personaje_id')->constrained()->onDelete('cascade');
    $table->string('estado'); // ejemplo: "Congelado"
    $table->integer('porcentaje')->nullable(); // 10 para -10%
    $table->json('stats_afectados')->nullable(); // ej: ["energia", "velocidad"]
    $table->timestamp('expira_en');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_temporales');
    }
};
