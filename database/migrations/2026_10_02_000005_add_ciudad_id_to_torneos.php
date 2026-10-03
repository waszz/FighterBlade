<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// La zona del torneo: una ciudad al azar, la misma para todos (la escena de tu set y las peleas)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('torneos', function (Blueprint $table) {
            $table->foreignId('ciudad_id')->nullable()->after('estado')->constrained('ciudades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('torneos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ciudad_id');
        });
    }
};
