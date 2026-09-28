<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Escalera de misiones: cada una es una pelea contra un rival en un escenario
        Schema::create('misiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('orden')->unique();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete(); // rival (es_enemigo = 3)
            $table->string('escenario');                  // gif del fondo (en storage/app/public/posts)
            $table->unsignedInteger('recompensa_oro');
            $table->unsignedInteger('recompensa_diamantes');
            $table->timestamps();
        });

        // Misiones completadas por cada personaje (se ganan una sola vez)
        Schema::create('mision_personaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mision_id')->constrained('misiones')->cascadeOnDelete();
            $table->foreignId('personaje_id')->constrained('personajes')->cascadeOnDelete();
            $table->timestamp('completada_en');
            $table->unique(['mision_id', 'personaje_id']);
        });

        Schema::table('personajes', function (Blueprint $table) {
            // Misión que se está peleando ahora (null si no hay)
            $table->foreignId('mision_activa_id')->nullable()->constrained('misiones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mision_activa_id');
        });
        Schema::dropIfExists('mision_personaje');
        Schema::dropIfExists('misiones');
    }
};
