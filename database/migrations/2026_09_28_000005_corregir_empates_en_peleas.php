<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Las peleas empatadas (el mismo daño de los dos lados) se guardaban como "derrota": se corrigen a "empate"
return new class extends Migration
{
    public function up(): void
    {
        DB::table('peleas')->where('resultado', 'derrota')->orderBy('id')
            ->chunkById(500, function ($peleas) {
                $empates = [];
                foreach ($peleas as $pelea) {
                    $datos = json_decode($pelea->datos_combate ?? '', true);
                    if (isset($datos['danio_personaje'], $datos['danio_enemigo'])
                        && (float) $datos['danio_personaje'] === (float) $datos['danio_enemigo']) {
                        $empates[] = $pelea->id;
                    }
                }
                if ($empates) {
                    DB::table('peleas')->whereIn('id', $empates)->update(['resultado' => 'empate']);
                }
            });
    }

    public function down(): void
    {
        DB::table('peleas')->where('resultado', 'empate')->update(['resultado' => 'derrota']);
    }
};
