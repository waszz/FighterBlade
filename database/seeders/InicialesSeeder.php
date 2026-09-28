<?php

namespace Database\Seeders;

use App\Models\Poder;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Crea los 5 personajes iniciales (los que se eligen al crear un personaje, iguales para todos)
// desde la carpeta "iniciales". Son sets normales de nivel 5 marcados con inicial = true.
// Uso: php artisan db:seed --class=InicialesSeeder   (carpeta configurable con INICIALES_PATH)
class InicialesSeeder extends SetsSeeder
{
    const NIVEL = 5;

    // carpeta => [título, tipo]  (en este orden se muestran)
    const INICIALES = [
        'batman'    => ['Batman', 'fisico'],
        'spiderman' => ['Spider-Man', 'fisico'],
        'wolverine' => ['Wolverine', 'fisico'],
        'superman'  => ['Superman', 'hibrido'],
        'goku'      => ['Goku', 'hibrido'],
    ];

    public function run(): void
    {
        $base = env('INICIALES_PATH', 'C:/Users/TERA/Desktop/fb/iniciales');
        if (! File::isDirectory($base)) {
            $this->command->error("No existe la carpeta: $base");
            return;
        }

        $destino = storage_path('app/public/posts');
        $poderes = Poder::pluck('id', 'nombre')->mapWithKeys(fn ($id, $nombre) => [mb_strtoupper($nombre) => $id]);
        $userId = DB::table('users')->orderBy('id')->value('id');

        $creados = 0;
        foreach (self::INICIALES as $carpeta => [$titulo, $tipo]) {
            if (Post::where('inicial', true)->where('titulo', $titulo)->exists()) {
                continue;
            }

            $archivos = $this->archivos("$base/$carpeta");
            $prefijo = 'inicial-' . Str::slug($carpeta);
            $copiar = function (?string $origen, string $sufijo) use ($destino, $prefijo) {
                if (! $origen) {
                    return null;
                }
                $ext = strtolower(pathinfo($origen, PATHINFO_EXTENSION));
                $ext = $ext === 'jfif' ? 'jpg' : $ext;
                File::copy($origen, "$destino/{$prefijo}_{$sufijo}.{$ext}");
                return "{$prefijo}_{$sufijo}.{$ext}";
            };

            $imagen = $copiar($archivos['imagen'] ?? $archivos['gif'], 'img');
            $gifs = [];
            foreach (array_keys(self::RESPALDO) as $campo) {
                $gifs[$campo] = 'posts/' . ($archivos[$campo] ? $copiar($archivos[$campo], $campo) : $imagen);
            }

            $semilla = crc32('inicial-' . $carpeta);
            [$stats, $ajustes] = $this->stats($tipo, self::NIVEL, $semilla);

            $post = Post::create([
                'titulo'   => $titulo,
                'nivel'    => self::NIVEL,
                'tipo'     => $tipo,
                'user_id'  => $userId,
                'imagen'   => 'posts/' . $imagen,
                'stats'    => $stats,
                'stats_equipo'        => array_fill_keys(array_keys($stats), 0),
                'stats_entrenamiento' => array_fill_keys(array_keys($stats), 0),
                'stats_accesorio'     => array_fill_keys(array_keys($stats), 0),
                'ajustes_manuales_equipo'        => $ajustes['equipo'],
                'ajustes_manuales_entrenamiento' => $ajustes['entrenamiento'],
                'ajustes_manuales_accesorio'     => $ajustes['accesorio'],
                'equipo_nombre'        => "Equipo de $titulo",
                'entrenamiento_nombre' => "Entrenamiento de $titulo",
                'accesorio_nombre'     => "Accesorio de $titulo",
                'equipo_imagen'        => $imagen,
                'entrenamiento_imagen' => $imagen,
                'accesorio_imagen'     => $imagen,
            ] + $gifs);
            $post->forceFill(['publicado' => true, 'es_enemigo' => false, 'costo' => self::NIVEL * 200, 'inicial' => true])->saveQuietly();
            $post->poderes()->attach($this->poderes($tipo, self::NIVEL, $semilla, $poderes));

            // Requisitos y reparto de bonus por parte, igual que el resto de los sets
            foreach ($post->load('poderes')->calcularRequisitos() as $parte => $req) {
                $post->{'requisitos_' . $parte} = $req;
            }
            foreach ($post->repartirAjustes() as $parte => $ajuste) {
                $post->{'ajustes_manuales_' . $parte} = $ajuste;
            }
            $post->saveQuietly();

            $this->command->line("  $titulo ($tipo) " . json_encode($stats) . ' · ' . $post->poderes->pluck('nombre')->join(', '));
            $creados++;
        }

        $this->command->info("Iniciales creados: $creados");
    }
}
