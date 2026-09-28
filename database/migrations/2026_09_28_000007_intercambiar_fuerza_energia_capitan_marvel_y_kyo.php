<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Capitán Marvel y Kyo Kusanagi ahora son físicos (pegan con Fuerza), pero sus stats estaban pensados para
// elemental (mucha Energía, poca Fuerza). Se intercambian Fuerza y Energía en el set, sus partes, sus requisitos
// y las partes que ya tienen los jugadores, para que peguen igual que antes.
return new class extends Migration
{
    private const SETS = ['Capitán Marvel', 'Kyo Kusanagi'];

    private function intercambiar(?string $json): ?string
    {
        $datos = json_decode($json ?? '', true);
        if (! is_array($datos)) {
            return $json;
        }
        $fuerza = $datos['fuerza'] ?? null;
        $energia = $datos['energia'] ?? null;
        unset($datos['fuerza'], $datos['energia']);
        if ($energia !== null) {
            $datos['fuerza'] = $energia;
        }
        if ($fuerza !== null) {
            $datos['energia'] = $fuerza;
        }
        return json_encode($datos);
    }

    private function aplicar(): void
    {
        $ids = DB::table('posts')->whereIn('titulo', self::SETS)->pluck('id');
        $columnasPost = ['stats', 'ajustes_manuales_equipo', 'ajustes_manuales_entrenamiento', 'ajustes_manuales_accesorio',
                         'requisitos_equipo', 'requisitos_entrenamiento', 'requisitos_accesorio'];
        foreach (DB::table('posts')->whereIn('id', $ids)->get() as $post) {
            $cambios = [];
            foreach ($columnasPost as $columna) {
                if (property_exists($post, $columna)) {
                    $cambios[$columna] = $this->intercambiar($post->$columna);
                }
            }
            DB::table('posts')->where('id', $post->id)->update($cambios);
        }

        $columnasObjeto = ['stats', 'requisitos_equipo', 'requisitos_entrenamiento', 'requisitos_accesorio'];
        foreach (DB::table('objetos')->whereIn('origen_post_id', $ids)->get() as $objeto) {
            $cambios = [];
            foreach ($columnasObjeto as $columna) {
                $cambios[$columna] = $this->intercambiar($objeto->$columna);
            }
            DB::table('objetos')->where('id', $objeto->id)->update($cambios);
        }
    }

    // Intercambiar dos veces deja todo como estaba: up y down hacen lo mismo
    public function up(): void
    {
        $this->aplicar();
    }

    public function down(): void
    {
        $this->aplicar();
    }
};
