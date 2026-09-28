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
        Schema::create('clans', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('tag', 5)->unique();
            $table->string('imagen')->nullable();
            $table->unsignedBigInteger('fundador_id');
            $table->integer('prestigio')->default(0);
            $table->timestamps();

            $table->foreign('fundador_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clans');
    }
};
