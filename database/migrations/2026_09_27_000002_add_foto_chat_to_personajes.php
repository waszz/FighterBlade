<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Foto que muestra el chat: la de un set desbloqueado elegido en el Inventario (null = automática, la del set con el que pelea)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedBigInteger('foto_chat_post_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn('foto_chat_post_id');
        });
    }
};
