<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comment_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // usuario al que se notifica
            $table->foreignId('comentario_id')->constrained('comentarios')->onDelete('cascade'); // comentario que generó la notificación
            $table->boolean('leido')->default(false); // si la notificación fue leída
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_notifications');
    }
};
