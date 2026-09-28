<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cazas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personaje_id')->constrained('personajes')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('ciudad_id')->nullable()->constrained('ciudades')->nullOnDelete();
            $table->string('rareza');            // comun, rara, legendaria
            $table->string('parte');             // equipo, entrenamiento, accesorio
            $table->timestamp('fin_rastreo');
            $table->string('estado')->default('rastreando'); // rastreando, lista, ganada, perdida
            $table->timestamps();

            $table->index(['personaje_id', 'estado']);
        });

        Schema::table('personajes', function (Blueprint $table) {
            // Cargas de caza: se gasta 1 por caza, se recupera 1 cada Caza::CARGA_HORAS hasta el máximo
            $table->unsignedInteger('caza_cargas')->default(3);
            $table->timestamp('caza_cargas_desde')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['caza_cargas', 'caza_cargas_desde']);
        });
        Schema::dropIfExists('cazas');
    }
};
