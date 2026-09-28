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
        Schema::table('poderes', function (Blueprint $table) {
            // Los poderes son un catálogo global; la relación con posts vive en el pivote poder_post.
            $table->foreignId('post_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poderes', function (Blueprint $table) {
            $table->foreignId('post_id')->nullable(false)->change();
        });
    }
};
