<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Green Goblin: la derrota tenía el fondo lila (131, 131, 255) sin transparencia; ese color pasa a ser el
// transparente del gif (copia del original en storage/app/gifs-originales)
return new class extends Migration
{
    private const FONDO = [131, 131, 255];

    public function up(): void
    {
        $post = DB::table('posts')->where('titulo', 'Green Goblin')->first();
        $ruta = $post->gif_derrota ?? null;
        $disco = Storage::disk('public');
        if (! $ruta || ! $disco->exists($ruta) || ! function_exists('imagecreatefromgif')) {
            return;
        }
        $img = @imagecreatefromgif($disco->path($ruta));
        if (! $img || imagecolortransparent($img) >= 0) {
            return;
        }
        $fondo = imagecolorat($img, 0, 0);
        $c = imagecolorsforindex($img, $fondo);
        if (abs($c['red'] - self::FONDO[0]) > 12 || abs($c['green'] - self::FONDO[1]) > 12 || abs($c['blue'] - self::FONDO[2]) > 12) {
            return; // ya no tiene ese fondo (lo cambiaron a mano)
        }
        $copia = storage_path('app/gifs-originales/' . $ruta);
        if (! file_exists($copia)) {
            @mkdir(dirname($copia), 0775, true);
            copy($disco->path($ruta), $copia);
        }
        imagecolortransparent($img, $fondo);
        imagegif($img, $disco->path($ruta));
    }

    public function down(): void
    {
    }
};
