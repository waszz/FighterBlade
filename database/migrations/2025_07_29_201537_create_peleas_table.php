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
 Schema::create('peleas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('personaje_id')->constrained()->onDelete('cascade');
    
    // 👇 Enemigo referenciado desde `posts`
    $table->foreignId('enemigo_id')->nullable()->constrained('posts')->nullOnDelete();

    $table->string('resultado'); // 'ganada' o 'perdida'
    $table->integer('exp_ganada')->default(0);
    $table->integer('oro_ganado')->default(0);
    $table->timestamp('realizada_en')->default(now());
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peleas');
    }
};
