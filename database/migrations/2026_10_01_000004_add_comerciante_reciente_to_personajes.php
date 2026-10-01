<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El comerciante no sale dos veces seguidas: queda marcado cuando aparece y la marca se borra en la próxima
// exploración (que termina con un enemigo)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->boolean('comerciante_reciente')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn('comerciante_reciente');
        });
    }
};
