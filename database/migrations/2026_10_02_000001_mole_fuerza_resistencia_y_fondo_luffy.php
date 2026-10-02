<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// - MOLE: ahora optimiza FUE y RES (antes DEF y RES). Lo leen el panel y la pelea (PoderesStats) y crear/editar set.
// - Luffy (nivel 80): la defensa y la derrota tenían el fondo verde azulado (12, 87, 80) sin transparencia;
//   ese color pasa a ser el transparente del gif (copia del original en storage/app/gifs-originales)
return new class extends Migration
{
    private const MOLE_ANTES = [
        'descripcion'   => 'Optimiza DEF y RES sumándole al menor el valor del mayor multiplicado por 0.8',
        'modificadores' => [['tipo' => 'optimizar_stats', 'stats' => ['defensa', 'resistencia'], 'factor' => 0.8]],
    ];

    private const MOLE_AHORA = [
        'descripcion'   => 'Optimiza FUE y RES sumándole al menor el valor del mayor multiplicado por 0.8',
        'modificadores' => [['tipo' => 'optimizar_stats', 'stats' => ['fuerza', 'resistencia'], 'factor' => 0.8]],
    ];

    public function up(): void
    {
        $this->mole(self::MOLE_AHORA);

        $luffy = DB::table('posts')->where('titulo', 'Luffy')->where('nivel', 80)->first();
        foreach (['gif_defensa', 'gif_derrota'] as $campo) {
            $this->fondoTransparente($luffy->$campo ?? null);
        }
    }

    public function down(): void
    {
        $this->mole(self::MOLE_ANTES);
    }

    private function mole(array $datos): void
    {
        DB::table('poderes')->where('nombre', 'MOLE')->update([
            'descripcion'   => $datos['descripcion'],
            'modificadores' => json_encode($datos['modificadores']),
        ]);
    }

    // Gif de un solo cuadro sin transparencia: el color de la esquina pasa a ser el transparente
    private function fondoTransparente(?string $ruta): void
    {
        $disco = Storage::disk('public');
        if (! $ruta || ! $disco->exists($ruta) || ! function_exists('imagecreatefromgif')) {
            return;
        }
        $img = @imagecreatefromgif($disco->path($ruta));
        if (! $img || imagecolortransparent($img) >= 0) {
            return;
        }
        $fondo = imagecolorat($img, 0, 0);
        [$r, $g, $b] = array_values(array_slice(imagecolorsforindex($img, $fondo), 0, 3));
        if (abs($r - 12) > 12 || abs($g - 87) > 12 || abs($b - 80) > 12) {
            return; // no es el fondo verde azulado (ya lo arreglaron a mano)
        }
        $copia = storage_path('app/gifs-originales/' . $ruta);
        if (! file_exists($copia)) {
            @mkdir(dirname($copia), 0775, true);
            copy($disco->path($ruta), $copia);
        }
        imagecolortransparent($img, $fondo);
        imagegif($img, $disco->path($ruta));
    }
};
