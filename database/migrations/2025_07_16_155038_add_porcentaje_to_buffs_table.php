<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buffs', function (Blueprint $table) {
            $table->integer('porcentaje')->default(5)->after('costo');
        });
    }

    public function down(): void
    {
        Schema::table('buffs', function (Blueprint $table) {
            $table->dropColumn('porcentaje');
        });
    }
};
