<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{   public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('exploracion_inicio')->nullable()->after('user_id');
            $table->integer('exploracion_duracion')->nullable()->after('exploracion_inicio');
            $table->unsignedBigInteger('enemigo_id')->nullable()->after('exploracion_duracion');

            $table->foreign('enemigo_id')->references('id')->on('posts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['enemigo_id']);
            $table->dropColumn(['exploracion_inicio', 'exploracion_duracion', 'enemigo_id']);
        });
    }
};
