<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// - La Poción de Búsqueda (drop) ya no sale en los cofres: en el mercado pasa a 250 esmeraldas (antes 150)
// - Los cofres ya guardados actualizan su descripción
return new class extends Migration
{
    public function up(): void
    {
        DB::table('mercado_pociones')->where('nombre', 'Poción de Búsqueda')->update(['precio' => 250]);
        DB::table('objetos')->where('tipo', 'cofre')->where('descripcion', 'like', '%pociones de drop, de oro%')
            ->update(['descripcion' => DB::raw("REPLACE(descripcion, 'pociones de drop, de oro', 'pociones de oro')")]);
    }

    public function down(): void
    {
        DB::table('mercado_pociones')->where('nombre', 'Poción de Búsqueda')->update(['precio' => 150]);
    }
};
