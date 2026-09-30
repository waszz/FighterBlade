<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mazmorra: una fila por personaje con su energía (rayitos) y la mazmorra que está haciendo
// (dificultad, sus 5 rivales —4 enemigos y el jefe— y por cuál va). Ver App\Models\Mazmorra
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mazmorras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personaje_id')->unique()->constrained('personajes')->cascadeOnDelete();
            $table->unsignedInteger('energia')->default(100);
            $table->date('energia_dia')->nullable();   // último día en que se recargó a 100
            $table->date('compra_dia')->nullable();    // último día en que compró +100 con esmeraldas
            $table->string('dificultad')->nullable();  // normal | dificil | pesadilla (null = sin mazmorra en curso)
            $table->json('rivales')->nullable();       // [{post_id, escenario}] × 5 (el último es el jefe)
            $table->unsignedTinyInteger('paso')->default(0);   // rival que le toca (0..4)
            $table->boolean('en_pelea')->default(false);       // está peleando con el rival del paso
            $table->unsignedInteger('jefes_derrotados')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mazmorras');
    }
};
