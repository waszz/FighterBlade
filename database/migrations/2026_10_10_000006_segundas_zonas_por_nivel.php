<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Dos zonas por nivel (menos El Comienzo): una zona nueva por nivel, con escenarios de las Misiones y la Torre.
// Los sets del nivel se reparten entre las dos zonas para explorar (ver Ciudad::setsDeLaZona)
return new class extends Migration
{
    // nivel => [nombre, escenario]
    private const ZONAS = [
        5   => ['EL PARQUE', 'escenario-05.gif'],
        10  => ['PUERTO DORADO', 'escenario-06.gif'],
        15  => ['BOSQUE PROFUNDO', 'escenario-21.gif'],
        20  => ['VENECIA', 'escenario-03.gif'],
        25  => ['TEMPLO DE OTOÑO', 'escenario-09.gif'],
        30  => ['ALDEA ABANDONADA', 'escenario-10.gif'],
        35  => ['NEO GEO LAND', 'escenario-02.gif'],
        40  => ['MERCADO NOCTURNO', 'escenario-17.gif'],
        45  => ['MERCADO COREANO', 'escenario-25.gif'],
        50  => ['CALLES DE SEÚL', 'escenario-13.gif'],
        55  => ['PANDA GIRL', 'escenario-22.gif'],
        60  => ['TEMPLO IMPERIAL', 'escenario-14.gif'],
        65  => ['BAMBÚ LUNAR', 'escenario-04.gif'],
        70  => ['SALÓN REAL', 'escenario-16.gif'],
        75  => ['CIUDAD EN LLAMAS', 'escenario-01.gif'],
        80  => ['COSTA EN RUINAS', 'escenario-11.gif'],
        85  => ['PASO SUBTERRÁNEO', 'escenario-23.gif'],
        90  => ['DOJO EN LLAMAS', 'escenario-08.gif'],
        95  => ['CRUCERO LUNAR', 'escenario-27.gif'],
        100 => ['ALTAR DEL DRAGÓN', 'escenario-19.gif'],
    ];

    public function up(): void
    {
        Schema::table('ciudades', function (Blueprint $table) {
            $table->unsignedTinyInteger('variante')->default(0); // 0: la zona original del nivel, 1: la segunda
        });

        foreach (self::ZONAS as $nivel => [$nombre, $gif]) {
            if (DB::table('ciudades')->where('nivel', $nivel)->where('variante', 1)->exists()) {
                continue;
            }
            DB::table('ciudades')->insert([
                'nombre' => $nombre, 'gif' => $gif, 'nivel' => $nivel, 'variante' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ciudades')->where('variante', 1)->delete();
        Schema::table('ciudades', function (Blueprint $table) {
            $table->dropColumn('variante');
        });
    }
};
