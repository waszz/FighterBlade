<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('buffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personaje_id')->nullable()->constrained()->nullOnDelete(); // null = global
            $table->string('tipo'); // xp, oro, drop
            $table->string('frase')->nullable();
            $table->boolean('global')->default(false);
            $table->integer('costo')->default(0);
            $table->timestamp('inicio');
            $table->timestamp('fin');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('buffs');
    }
};
