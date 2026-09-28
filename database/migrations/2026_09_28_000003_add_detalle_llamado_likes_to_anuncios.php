<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anuncios', function (Blueprint $table) {
            $table->string('detalle', 120)->nullable()->after('titulo'); // línea destacada (ej. "Martes 29/09 16:00")
            $table->string('llamado', 80)->nullable()->after('texto');   // llamado a la acción (ej. "¡Anotate YA!")
        });

        // "Me gusta" de los jugadores en cada anuncio (uno por cuenta)
        Schema::create('anuncio_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anuncio_id')->constrained('anuncios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['anuncio_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncio_likes');
        Schema::table('anuncios', function (Blueprint $table) {
            $table->dropColumn(['detalle', 'llamado']);
        });
    }
};
