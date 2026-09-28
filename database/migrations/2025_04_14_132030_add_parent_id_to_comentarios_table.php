<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comentarios', function (Blueprint $table) {
            if (!Schema::hasColumn('comentarios', 'parent_id')) {
                $table->foreignId('parent_id')
                      ->nullable()
                      ->constrained('comentarios')
                      ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comentarios', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
