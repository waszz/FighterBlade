<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->unsignedInteger('pvp_ganadas')->default(0);
            $table->unsignedInteger('pvp_perdidas')->default(0);
            $table->unsignedInteger('pve_ganadas')->default(0);
            $table->unsignedInteger('pve_perdidas')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn([
                'pvp_ganadas',
                'pvp_perdidas',
                'pve_ganadas',
                'pve_perdidas',
            ]);
        });
    }
};
