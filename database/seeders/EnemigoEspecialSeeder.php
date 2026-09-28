<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Crea el enemigo especial de bienvenida (Wolverine): aparece a los personajes hasta nivel 3 para que suban rápido.
// Es un set oculto (es_enemigo = 2) con stats bajos y sin poderes.
// Uso: php artisan db:seed --class=EnemigoEspecialSeeder   (carpeta configurable con ESPECIAL_PATH)
class EnemigoEspecialSeeder extends SetsSeeder
{
    const TITULO = 'Wolverine';
    const TIPO = 'fisico';

    // Stats en 0: victoria segura para los personajes de nivel 1 a 3
    const STATS = ['fuerza' => 0, 'resistencia' => 0, 'ataque' => 0, 'defensa' => 0, 'velocidad' => 0, 'energia' => 0];

    public function run(): void
    {
        $dir = env('ESPECIAL_PATH', 'C:/Users/TERA/Desktop/fb/iniciales/wolverine');
        if (! File::isDirectory($dir)) {
            $this->command->error("No existe la carpeta: $dir");
            return;
        }
        if (Post::conRivales()->where('es_enemigo', Post::ENEMIGO_ESPECIAL)->exists()) {
            $this->command->info('El enemigo especial ya existe.');
            return;
        }

        $destino = storage_path('app/public/posts');
        $archivos = $this->archivos($dir);
        $copiar = function (?string $origen, string $sufijo) use ($destino) {
            if (! $origen) {
                return null;
            }
            $ext = strtolower(pathinfo($origen, PATHINFO_EXTENSION));
            File::copy($origen, "$destino/especial-wolverine_{$sufijo}.{$ext}");
            return "especial-wolverine_{$sufijo}.{$ext}";
        };

        $imagen = $copiar($archivos['imagen'] ?? $archivos['gif'], 'img');
        $gifs = [];
        foreach (array_keys(self::RESPALDO) as $campo) {
            $gifs[$campo] = 'posts/' . ($archivos[$campo] ? $copiar($archivos[$campo], $campo) : $imagen);
        }

        $post = Post::create([
            'titulo'  => self::TITULO,
            'nivel'   => 1,
            'tipo'    => self::TIPO,
            'user_id' => DB::table('users')->orderBy('id')->value('id'),
            'imagen'  => 'posts/' . $imagen,
            'stats'   => self::STATS,
        ] + $gifs);
        $post->forceFill(['publicado' => false, 'es_enemigo' => Post::ENEMIGO_ESPECIAL, 'costo' => 0])->saveQuietly();

        $this->command->info("Enemigo especial creado: {$post->titulo} (id {$post->id})");
    }
}
