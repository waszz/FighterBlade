<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MercadoPocion;

class MercadoPocionesSeeder extends Seeder
{
    public function run()
    {
        MercadoPocion::create([
        'nombre' => 'Poción de Recuperación',
        'tipo' => 'pocion',
        'imagen' => 'pocion-recuperacion.png',
        'nivel' => 1,
        'requisitos' => [],
        'stats' => [
            'usos_restantes' => 1,
            'usos_totales' => 1,
            'multiplicador' => 15,
            'afecta' => 'recuperacion',
        ],
        'descripcion' => 'Recuperación de 15 segundos',
        'precio' => 150,
        'moneda' => 'oro',
    ]);

    MercadoPocion::create([
        'nombre' => 'Poción de Búsqueda',
        'tipo' => 'pocion',
        'imagen' => 'pocion-busqueda.png',
        'nivel' => 1,
        'requisitos' => [],
        'stats' => [
            'usos_restantes' => 1,
            'usos_totales' => 1,
            'multiplicador' => 1,
            'afecta' => 'drop_partes',
        ],
        'descripcion' => '100% de probabilidad de drop',
        'precio' => 150,
        'moneda' => 'diamante',
    ]);

       MercadoPocion::create([
        'nombre' => 'Poción de Super Defensa',
        'tipo' => 'pocion',
        'imagen' => 'pocion-super-defensa.png',
        'nivel' => 1,
        'requisitos' => [],
        'stats' => [
            'usos_restantes' => 1,
            'usos_totales' => 1,
            'multiplicador' => 2,
            'afecta' => 'defensa',
        ],
        'descripcion' => 'Multiplica la defensa por 2',
        'precio' => 15,
        'moneda' => 'diamante',
    ]);

     MercadoPocion::create([
        'nombre' => 'Poción de Super Resistencia',
        'tipo' => 'pocion',
        'imagen' => 'pocion-super-resistencia.png',
        'nivel' => 1,
        'requisitos' => [],
        'stats' => [
            'usos_restantes' => 1,
            'usos_totales' => 1,
            'multiplicador' => 2,
            'afecta' => 'resistencia',
        ],
        'descripcion' => 'Multiplica la resistencia por 2',
        'precio' => 15,
        'moneda' => 'diamante',
    ]);

     MercadoPocion::create([
        'nombre' => 'Poción de Super Velocidad',
        'tipo' => 'pocion',
        'imagen' => 'pocion-super-velocidad.png',
        'nivel' => 1,
        'requisitos' => [],
        'stats' => [
            'usos_restantes' => 1,
            'usos_totales' => 1,
            'multiplicador' => 2,
            'afecta' => 'velocidad',
        ],
        'descripcion' => 'Multiplica la velocidad por 2',
        'precio' => 15,
        'moneda' => 'diamante',
    ]);

     MercadoPocion::create([
        'nombre' => 'Poción de Super Fuerza',
        'tipo' => 'pocion',
        'imagen' => 'pocion-super-fuerza.png',
        'nivel' => 1,
        'requisitos' => [],
        'stats' => [
            'usos_restantes' => 1,
            'usos_totales' => 1,
            'multiplicador' => 2,
            'afecta' => 'fuerza',
        ],
        'descripcion' => 'Multiplica la fuerza por 2',
        'precio' => 15,
        'moneda' => 'diamante',
    ]);

     MercadoPocion::create([
        'nombre' => 'Poción de Super Energia',
        'tipo' => 'pocion',
        'imagen' => 'pocion-super-energia.png',
        'nivel' => 1,
        'requisitos' => [],
        'stats' => [
            'usos_restantes' => 1,
            'usos_totales' => 1,
            'multiplicador' => 2,
            'afecta' => 'energia',
        ],
        'descripcion' => 'Multiplica la energia por 2',
        'precio' => 15,
        'moneda' => 'diamante',
    ]);

     MercadoPocion::create([
        'nombre' => 'Poción de Super Ataque',
        'tipo' => 'pocion',
        'imagen' => 'pocion-super-ataque.png',
        'nivel' => 1,
        'requisitos' => [],
        'stats' => [
            'usos_restantes' => 1,
            'usos_totales' => 1,
            'multiplicador' => 2,
            'afecta' => 'ataque',
        ],
        'descripcion' => 'Multiplica el ataque por 2',
        'precio' => 15,
        'moneda' => 'diamante',
    ]);

        // Agregá más pociones si querés acá
    }
}
