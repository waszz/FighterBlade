<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Torre: pisos con un rival (set normal o especial) y una zona; se sube de a un piso (nivel 5 a 100)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('torre_pisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('piso')->unique();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('escenario');     // imagen en storage/posts (ciudad o escenario especial)
            $table->unsignedTinyInteger('nivel');
            $table->timestamps();
        });

        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedInteger('torre_piso')->default(0);          // piso más alto superado
            $table->unsignedInteger('torre_piso_activo')->nullable();   // piso que está peleando
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn(['torre_piso', 'torre_piso_activo']);
        });
        Schema::dropIfExists('torre_pisos');
    }
};
