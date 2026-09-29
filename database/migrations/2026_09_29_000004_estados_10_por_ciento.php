<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Los poderes que dejan un estado al rival (Aturdido, Congelado, Envenenado, Desangrado, Paralizado, Quemado)
// pasan a tener todos 10% de chance, y la descripción dice ese 10%
return new class extends Migration
{
    const CHANCE = 10;

    public function up(): void
    {
        foreach (DB::table('poderes')->get(['id', 'descripcion', 'modificadores']) as $poder) {
            $mods = json_decode($poder->modificadores ?? '[]', true);
            if (! is_array($mods)) {
                continue;
            }
            $cambio = false;
            foreach ($mods as &$mod) {
                if (($mod['tipo'] ?? null) === 'estado' && isset($mod['chance'])) {
                    $mod['chance'] = self::CHANCE;
                    $cambio = true;
                }
            }
            unset($mod);
            if (! $cambio) {
                continue;
            }
            DB::table('poderes')->where('id', $poder->id)->update([
                'modificadores' => json_encode($mods, JSON_UNESCAPED_UNICODE),
                'descripcion'   => preg_replace('/\d+% de chances de dejar/u', self::CHANCE . '% de chances de dejar', (string) $poder->descripcion),
            ]);
        }
    }

    public function down(): void
    {
        // Sin vuelta atrás: las chances anteriores eran distintas por poder (ver el seeder de la versión anterior)
    }
};
