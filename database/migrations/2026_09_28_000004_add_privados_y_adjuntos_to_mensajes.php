<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            // texto | gif | objeto | pelea
            $table->string('tipo', 10)->default('texto')->after('contenido');
            // Privado: a qué personaje va (null = chat general)
            $table->foreignId('destinatario_id')->nullable()->after('personaje_id')->constrained('personajes')->cascadeOnDelete();
            // Lo compartido guardado tal como era al compartirlo (se sigue viendo aunque después se venda o se borre);
            // en los gif, la ruta del archivo
            $table->json('adjunto')->nullable()->after('tipo');
            // Privados: cuándo lo leyó el destinatario
            $table->timestamp('leido_en')->nullable()->after('adjunto');
            $table->index(['destinatario_id', 'leido_en']);
        });
    }

    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropIndex(['destinatario_id', 'leido_en']);
            $table->dropConstrainedForeignId('destinatario_id');
            $table->dropColumn(['tipo', 'adjunto', 'leido_en']);
        });
    }
};
