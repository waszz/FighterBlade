<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Daño directo de los poderes: todos al 15% de una tirada de su stat, DIRECT DAMAGE al 50% y ESPINAS (devuelve un %
// del daño recibido) al 20%. La descripción dice el % nuevo.
return new class extends Migration
{
    const NUEVOS = ['CONGELAR' => 15, 'HEMORRAGIA' => 15, 'QUEMAR' => 15, 'SANGRADO' => 15, 'DIRECT DAMAGE' => 50, 'ESPINAS' => 20];
    const ANTERIORES = ['CONGELAR' => 25, 'HEMORRAGIA' => 35, 'QUEMAR' => 25, 'SANGRADO' => 25, 'DIRECT DAMAGE' => 75, 'ESPINAS' => 45];

    public function up(): void
    {
        $this->cambiar(self::NUEVOS);
    }

    public function down(): void
    {
        $this->cambiar(self::ANTERIORES);
    }

    private function cambiar(array $porcentajes): void
    {
        foreach (DB::table('poderes')->get(['id', 'nombre', 'descripcion', 'modificadores']) as $poder) {
            $nuevo = $porcentajes[mb_strtoupper($poder->nombre)] ?? null;
            $mods = json_decode($poder->modificadores ?? '[]', true);
            if ($nuevo === null || ! is_array($mods)) {
                continue;
            }
            foreach ($mods as &$mod) {
                if (($mod['tipo'] ?? null) === 'daño_directo') {
                    $mod['porcentaje'] = $nuevo;
                }
            }
            unset($mod);
            DB::table('poderes')->where('id', $poder->id)->update([
                'modificadores' => json_encode($mods, JSON_UNESCAPED_UNICODE),
                'descripcion'   => preg_replace(
                    ['/equivalente al \d+%/u', '/Devuelve un \d+%/u'],
                    ["equivalente al {$nuevo}%", "Devuelve un {$nuevo}%"],
                    (string) $poder->descripcion
                ),
            ]);
        }
    }
};
