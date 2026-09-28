<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Ajuste manual del tamaño del set en las peleas (se multiplica sobre la escala automática)
            $table->decimal('gif_escala', 4, 2)->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('gif_escala');
        });
    }
};
