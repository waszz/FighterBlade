<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// DIRECT DAMAGE pegaba mucho: pasa del 50% al 35% de una tirada de energía. La descripción dice el % nuevo
return new class extends Migration
{
    public function up(): void
    {
        $this->cambiar(35);
    }

    public function down(): void
    {
        $this->cambiar(50);
    }

    private function cambiar(int $porcentaje): void
    {
        $poder = DB::table('poderes')->where('nombre', 'DIRECT DAMAGE')->first(['id', 'descripcion', 'modificadores']);
        $mods = $poder ? json_decode($poder->modificadores ?? '[]', true) : null;
        if (! is_array($mods)) {
            return;
        }
        foreach ($mods as &$mod) {
            if (($mod['tipo'] ?? null) === 'daño_directo') {
                $mod['porcentaje'] = $porcentaje;
            }
        }
        unset($mod);
        DB::table('poderes')->where('id', $poder->id)->update([
            'modificadores' => json_encode($mods, JSON_UNESCAPED_UNICODE),
            'descripcion'   => preg_replace('/equivalente al \d+%/u', "equivalente al {$porcentaje}%", (string) $poder->descripcion),
        ]);
    }
};
