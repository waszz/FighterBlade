<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Guardar una parte de set en el clan da prestigio una sola vez por objeto (si no, se sacaba y se volvía a guardar)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objetos', function (Blueprint $table) {
            $table->boolean('prestigio_clan_dado')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('objetos', function (Blueprint $table) {
            $table->dropColumn('prestigio_clan_dado');
        });
    }
};
