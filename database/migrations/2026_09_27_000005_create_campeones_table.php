<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Campeones: personajes que llegaron al nivel máximo (100). Se anota la primera vez (Personaje::booted).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personaje_id')->unique()->constrained('personajes')->cascadeOnDelete();
            $table->timestamp('alcanzado_en');
            $table->timestamps();
        });

        // Los que ya son nivel 100: se toma su última actualización como fecha
        foreach (DB::table('personajes')->where('nivel', '>=', 100)->get(['id', 'updated_at']) as $pj) {
            DB::table('campeones')->insert([
                'personaje_id' => $pj->id,
                'alcanzado_en' => $pj->updated_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campeones');
    }
};
