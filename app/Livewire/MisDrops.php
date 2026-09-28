<?php

namespace App\Livewire;

use App\Models\Ciudad;
use App\Models\Pelea;
use App\Models\Post;
use Livewire\Component;

// Mis Drops: lo que al personaje le cayó explorando, agrupado por ciudad y con los minutos de la exploración.
// Sale del historial de peleas (datos_combate.drop); misiones, torre, cazas y PvP no cuentan.
class MisDrops extends Component
{
    public $personajeId;
    public string $busqueda = '';

    // Minutos de cada parte en las peleas viejas que no guardaban los minutos (5 → equipo, 10 → entrenamiento, 15 → accesorio)
    private const MINUTOS_PARTE = ['equipo' => 5, 'entrenamiento' => 10, 'accesorio' => 15];

    private const NOMBRE_TIPO = [
        'equipo' => 'Equipo', 'entrenamiento' => 'Entrenamiento', 'accesorio' => 'Accesorio', 'pocion' => 'Poción',
    ];

    public function mount($personajeId)
    {
        $this->personajeId = $personajeId;
    }

    public function render()
    {
        $ciudades = Ciudad::orderBy('nivel')->get()->keyBy('id');
        $ciudadPorNivel = $ciudades->keyBy('nivel');
        $nivelSet = [];
        $imagenEnemigo = [];

        $grupos = [];
        $peleas = Pelea::where('personaje_id', $this->personajeId)->orderByDesc('realizada_en')->get(['id', 'enemigo_id', 'datos_combate', 'realizada_en']);
        foreach ($peleas as $pelea) {
            $datos = $pelea->datos_combate ?? [];
            $drop = $datos['drop'] ?? null;
            if (! is_array($drop) || empty($drop['nombre'])) {
                continue;
            }

            // Solo exploración (las peleas viejas no guardaban el origen: se deduce)
            $origen = $datos['origen'] ?? (
                ! empty($datos['enemigo_es_personaje']) ? 'pvp'
                : (! empty($datos['escenario_mision']) || in_array($drop['tipo'] ?? '', ['cofre', 'joya'], true) ? 'mision' : 'explorar')
            );
            if ($origen !== 'explorar') {
                continue;
            }

            $tipo = $drop['tipo'] ?? 'objeto';
            $esParte = isset(self::MINUTOS_PARTE[$tipo]);

            // Ciudad: la guardada; si no, la del nivel del set (una ciudad de nivel N tiene enemigos de nivel N+5)
            $ciudad = $ciudades[$datos['ciudad_id'] ?? 0] ?? null;
            if (! $ciudad && $esParte && ! empty($drop['origen_post_id'])) {
                $nivelSet[$drop['origen_post_id']] ??= Post::conRivales()->whereKey($drop['origen_post_id'])->value('nivel');
                $ciudad = $ciudadPorNivel[($nivelSet[$drop['origen_post_id']] ?? 0) - 5] ?? null;
            }

            $minutos = $datos['minutos'] ?? null;
            if (! $minutos && $esParte) {
                $minutos = $ciudad && (int) $ciudad->nivel === 0 ? Explorar::MINUTOS_ZONA_INICIAL : self::MINUTOS_PARTE[$tipo];
            }

            $claveCiudad = $ciudad->id ?? 0;
            $grupos[$claveCiudad] ??= ['ciudad' => $ciudad, 'items' => [], 'total' => 0];
            // Enemigo que lo tiró (con su foto del set)
            $enemigoId = $pelea->enemigo_id;
            $imagenEnemigo[$enemigoId] ??= $enemigoId ? Post::conRivales()->whereKey($enemigoId)->value('imagen') : null;
            $clave = $tipo . '|' . $drop['nombre'] . '|' . ($datos['nombre_enemigo'] ?? $enemigoId);
            $item = &$grupos[$claveCiudad]['items'][$clave];
            $item ??= [
                'nombre'  => $drop['nombre'],
                'tipo'    => self::NOMBRE_TIPO[$tipo] ?? ucfirst($tipo),
                'imagen'  => ! empty($drop['imagen']) ? ($tipo === 'pocion' ? 'images/' : 'storage/posts/') . $drop['imagen'] : null,
                'enemigo' => $datos['nombre_enemigo'] ?? 'Enemigo',
                'enemigoImagen' => $imagenEnemigo[$enemigoId] ? 'storage/' . $imagenEnemigo[$enemigoId] : null,
                'minutos' => [],
                'veces'   => 0,
                'ultima'  => $pelea->realizada_en,
            ];
            $item['veces']++;
            if ($minutos) {
                $item['minutos'][$minutos] = true;
            }
            unset($item);
            $grupos[$claveCiudad]['total']++;
        }

        // Buscador: por ciudad, objeto, tipo, enemigo o minutos ("10 min")
        $buscar = mb_strtolower(trim($this->busqueda));
        foreach ($grupos as $clave => &$grupo) {
            foreach ($grupo['items'] as &$item) {
                $item['minutos'] = array_keys($item['minutos']);
                sort($item['minutos']);
            }
            unset($item);

            if ($buscar !== '' && ! str_contains(mb_strtolower($grupo['ciudad']->nombre ?? 'ciudad desconocida'), $buscar)) {
                $grupo['items'] = array_filter($grupo['items'], fn ($i) => str_contains(
                    mb_strtolower($i['nombre'] . ' ' . $i['tipo'] . ' ' . $i['enemigo'] . ' ' . implode(' min ', $i['minutos']) . ' min'), $buscar
                ));
                if (! $grupo['items']) {
                    unset($grupos[$clave]);
                }
            }
        }
        unset($grupo);

        // Ciudades por nivel; las que no se pudieron ubicar, al final
        uasort($grupos, fn ($a, $b) => ($a['ciudad']->nivel ?? 999) <=> ($b['ciudad']->nivel ?? 999));

        return view('livewire.mis-drops', ['grupos' => $grupos]);
    }
}
