<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Crea las ciudades desde la carpeta de ciudades (una subcarpeta lvlN con un gif por nivel).
// Uso: php artisan db:seed --class=CiudadesSeeder   (carpeta configurable con CIUDADES_PATH en el .env)
// "inicio" no se toca y las que ya existen (mismo nivel) se saltean.
class CiudadesSeeder extends Seeder
{
    const NOMBRES = [
        5   => 'LA REVUELTA',
        10  => 'EL BARCO',
        15  => 'JARDÍN LLUVIOSO',
        20  => 'LAS CASCADAS',
        25  => 'PUENTE CHINO',
        30  => 'DESIERTO',
        35  => 'MÉXICO',
        40  => 'BAR SANTANA',
        45  => 'FESTIVAL',
        50  => 'PICASSO',
        55  => 'LONDRES',
        60  => 'PALACIO ÁGUILA',
        65  => 'SANTUARIO CHINO',
        70  => 'MANSIÓN',
        75  => 'HONG KONG',
        80  => 'TAILANDIA',
        85  => 'CIUDAD DESTRUIDA',
        90  => 'CHINA',
        95  => 'MUSEO JURÁSICO',
        100 => 'DOJO DEL DRAGÓN FINAL',
    ];

    public function run(): void
    {
        $base = env('CIUDADES_PATH', 'C:/Users/TERA/Desktop/fb/ciudades');
        if (! File::isDirectory($base)) {
            $this->command->error("No existe la carpeta de ciudades: $base");
            return;
        }

        $destino = storage_path('app/public/posts');
        File::ensureDirectoryExists($destino);
        $userId = DB::table('users')->orderBy('id')->value('id');

        $creadas = 0;
        foreach (self::NOMBRES as $nivel => $nombre) {
            if (Ciudad::where('nivel', $nivel)->exists()) {
                continue;
            }

            $gif = collect(File::files("$base/lvl$nivel"))->first(fn ($f) => strtolower($f->getExtension()) === 'gif');
            if (! $gif) {
                $this->command->warn("Sin gif, se saltea: lvl$nivel");
                continue;
            }

            $nombreGif = "ciudad-lvl$nivel.gif";
            File::copy($gif->getPathname(), "$destino/$nombreGif");

            Ciudad::create([
                'nombre'  => $nombre,
                'nivel'   => $nivel,
                'gif'     => $nombreGif,
                'user_id' => $userId,
            ]);
            $creadas++;
        }

        $this->command->info("Ciudades creadas: $creadas");
    }
}
