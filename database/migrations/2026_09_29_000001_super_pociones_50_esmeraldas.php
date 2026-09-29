<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Extras: las Super Pociones pasan a costar 50 esmeraldas (antes 15)
return new class extends Migration
{
    const SUPER_POCIONES = [
        'Poción de Super Fuerza',
        'Poción de Super Defensa',
        'Poción de Super Ataque',
        'Poción de Super Energia',
        'Poción de Super Resistencia',
        'Poción de Super Velocidad',
    ];

    public function up(): void
    {
        DB::table('mercado_pociones')->whereIn('nombre', self::SUPER_POCIONES)->update(['precio' => 50]);
    }

    public function down(): void
    {
        DB::table('mercado_pociones')->whereIn('nombre', self::SUPER_POCIONES)->update(['precio' => 15]);
    }
};
