<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->boolean('stats_guardados')->default(false)->after('stats'); // o ubicá 'after' según tu estructura
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn('stats_guardados');
        });
    }
};
