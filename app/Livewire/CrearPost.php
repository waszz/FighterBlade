<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\Poder;
use App\Models\Equipo;
use App\Models\Entrenamiento;
use App\Models\Accesorio;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Collection;

class CrearPost extends Component
{
    use WithFileUploads;

    public $gifAtaque, $gifDefensa, $gifCritico, $gifEspecial, $gifDerrota, $gifVictoria;
    public $titulo;
    public $gifPersonaje;
    public $imagen;
    public $imagen1, $imagen2, $imagen3;

    public $equipo_id, $entrenamiento_id, $accesorio_id;

    public $equipo_nombre, $entrenamiento_nombre, $accesorio_nombre;

    public $equipo_imagen, $entrenamiento_imagen, $accesorio_imagen;

    // Para previews (opcional)
    public $equipo_imagen_preview, $entrenamiento_imagen_preview, $accesorio_imagen_preview;

    public Collection $equipos;
    public Collection $entrenamientos;
    public Collection $accesorios;

    public $nivel = 5;

    public $statsEquipo = [];
    public $statsEntrenamiento = [];
    public $statsAccesorio = [];

    // Ajustes manuales independientes para cada parte:
    public $ajustesManualesEquipo = [];
    public $ajustesManualesEntrenamiento = [];
    public $ajustesManualesAccesorio = [];

    public $stats = [];
    public $statsDesglose = [];
    public $poderesDisponibles = [];
    public $poderesSeleccionados = [];
    public $requisitosEquipo = [];
    public $requisitosEntrenamiento = [];
    public $requisitosAccesorio = [];


    public $tipo = 'fisico';

    public $statsBase = [
        'fuerza' => 5,
        'resistencia' => 5,
        'ataque' => 5,
        'defensa' => 5,
        'velocidad' => 5,
        'energia' => 5,
    ];

    public function mount()
    {
        $this->poderesDisponibles = Poder::all();
        $this->equipos = Equipo::all();
        $this->entrenamientos = Entrenamiento::all();
        $this->accesorios = Accesorio::all();

        $keys = array_keys($this->statsBase);

        $this->statsEquipo = array_fill_keys($keys, 0);
        $this->statsEntrenamiento = array_fill_keys($keys, 0);
        $this->statsAccesorio = array_fill_keys($keys, 0);

        $this->ajustesManualesEquipo = array_fill_keys($keys, 0);
        $this->ajustesManualesEntrenamiento = array_fill_keys($keys, 0);
        $this->ajustesManualesAccesorio = array_fill_keys($keys, 0);

        $this->recalcularStats();
    }

    protected $rules = [
        'titulo' => 'required|string|min:1',
        'nivel' => 'required|integer|min:5|max:100',
        'tipo' => 'required|string|in:fisico,elemental,hibrido',
        'gifPersonaje' => 'nullable|mimes:gif|max:3072',
        'imagen' => 'nullable|image|mimes:jpg,jpeg,png|max:12000',
        'imagen1' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'imagen2' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'imagen3' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'poderesSeleccionados' => 'required|array|min:1|max:3',
        'poderesSeleccionados.*' => 'exists:poderes,id',
        'gifAtaque' => 'nullable|mimes:gif|max:3072',
        'gifDefensa' => 'nullable|mimes:gif|max:3072',
        'gifCritico' => 'nullable|mimes:gif|max:3072',
        'gifEspecial' => 'nullable|mimes:gif|max:3072',
        'gifDerrota' => 'nullable|mimes:gif|max:3072',
        'gifVictoria' => 'nullable|mimes:gif|max:3072',

        // Validaciones para las imágenes nuevas:
        'equipo_imagen' => 'nullable|image|max:2048',
        'entrenamiento_imagen' => 'nullable|image|max:2048',
        'accesorio_imagen' => 'nullable|image|max:2048',
        'requisitosEquipo' => 'nullable|array',
        'requisitosEquipo.*' => 'nullable|integer|min:0|max:100',

        'requisitosEntrenamiento' => 'nullable|array',
        'requisitosEntrenamiento.*' => 'nullable|integer|min:0|max:100',

        'requisitosAccesorio' => 'nullable|array',
        'requisitosAccesorio.*' => 'nullable|integer|min:0|max:100',
    ];

    // Métodos para manejar previews opcionales (puedes ajustar o eliminar si no usas)
    public function updatedEquipoImagen()
    {
        $this->equipo_imagen_preview = $this->equipo_imagen->temporaryUrl();
    }

    public function updatedEntrenamientoImagen()
    {
        $this->entrenamiento_imagen_preview = $this->entrenamiento_imagen->temporaryUrl();
    }

    public function updatedAccesorioImagen()
    {
        $this->accesorio_imagen_preview = $this->accesorio_imagen->temporaryUrl();
    }

    // ... Aquí siguen tus métodos incrementarStat, decrementarStat, updatedEquipoId, etc. (sin cambios)

    public function puntosTotales()
    {
        return $this->nivel * 5;
    }

    public function puntosUsados()
    {
        return array_sum(array_filter($this->ajustesManualesEquipo, fn($v) => $v > 0))
             + array_sum(array_filter($this->ajustesManualesEntrenamiento, fn($v) => $v > 0))
             + array_sum(array_filter($this->ajustesManualesAccesorio, fn($v) => $v > 0));
    }

    public function puntosRestantes()
    {
        return $this->puntosTotales() - $this->puntosUsados();
    }

public function incrementarStat($stat, $tipoAjuste, $cantidad = 1)
{
    if (!in_array($tipoAjuste, ['equipo', 'entrenamiento', 'accesorio'])) return;

    $ajustes = "ajustesManuales" . ucfirst($tipoAjuste);
    if (!isset($this->$ajustes[$stat])) return;

    $cantidadDisponible = min($cantidad, $this->puntosRestantes());

    if ($cantidadDisponible > 0) {
        $this->{$ajustes}[$stat] += $cantidadDisponible;
        $this->recalcularStats();
    }
}

public function decrementarStat($stat, $tipoAjuste, $cantidad = 1)
{
    if (!in_array($tipoAjuste, ['equipo', 'entrenamiento', 'accesorio'])) return;

    $ajustes = "ajustesManuales" . ucfirst($tipoAjuste);
    if (!isset($this->$ajustes[$stat])) return;

    $cantidadDisponible = min($cantidad, $this->{$ajustes}[$stat]);

    if ($cantidadDisponible > 0) {
        $this->{$ajustes}[$stat] -= $cantidadDisponible;
        $this->recalcularStats();
    }
}

public function obtenerRequisitosParte($parte)
{
    $variable = 'requisitos' . ucfirst($parte);  // ejemplo: requisitosEquipo

    $raw = $this->$variable ?? null;

    if (!$raw) {
        return [];
    }

    // Si ya es array, devolver directamente
    if (is_array($raw)) {
        return $raw;
    }

    // Si no es string, no se puede procesar
    if (!is_string($raw)) {
        return [];
    }

    // Limpiar doble escape y decodificar JSON
    $clean = trim($raw, '"');
    $clean = stripslashes($clean);

    $decoded = json_decode($clean, true);

    return is_array($decoded) ? $decoded : [];
}




    public function updatedPoderesSeleccionados()
    {
        $this->recalcularStats();
    }

    public function updatedEquipoId($id)
    {
        $equipo = $this->equipos->find($id);
        if ($equipo) {
            $this->statsEquipo = $this->normalizarStats($equipo->stats);
        } else {
            $this->statsEquipo = array_fill_keys(array_keys($this->statsBase), 0);
        }
        $this->recalcularStats();
    }

    public function updatedEntrenamientoId($id)
    {
        $entrenamiento = $this->entrenamientos->find($id);
        if ($entrenamiento) {
            $this->statsEntrenamiento = $this->normalizarStats($entrenamiento->stats);
        } else {
            $this->statsEntrenamiento = array_fill_keys(array_keys($this->statsBase), 0);
        }
        $this->recalcularStats();
    }

    public function updatedAccesorioId($id)
    {
        $accesorio = $this->accesorios->find($id);
        if ($accesorio) {
            $this->statsAccesorio = $this->normalizarStats($accesorio->stats);
        } else {
            $this->statsAccesorio = array_fill_keys(array_keys($this->statsBase), 0);
        }
        $this->recalcularStats();
    }

    private function normalizarStats($stats)
    {
        if (is_string($stats)) {
            $stats = json_decode($stats, true);
            if (!is_array($stats)) {
                $stats = [];
            }
        }
        $keys = array_keys($this->statsBase);
        $resultado = [];
        foreach ($keys as $key) {
            $resultado[$key] = $stats[$key] ?? 0;
        }
        return $resultado;
    }

    public function getPoderesSeleccionadosDetallesProperty()
    {
        return $this->poderesDisponibles->whereIn('id', array_map('intval', $this->poderesSeleccionados));
    }

    protected function recalcularStats()
    {
        $base = $this->statsBase;

        // Aplicar incrementos simples de poderes
        foreach ($this->poderesSeleccionadosDetalles as $poder) {
            $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];
            foreach ($mods as $mod) {
                if (!isset($mod['tipo']) || $mod['tipo'] === 'incremento_stat') {
                    $stat = strtolower($mod['stat'] ?? '');
                    $valor = $mod['valor'] ?? 0;
                    if (isset($base[$stat])) $base[$stat] += $valor;
                }
            }
        }

        // Sumar ajustes manuales de cada parte
        foreach ($base as $key => $valor) {
            $ajusteEquipo = $this->ajustesManualesEquipo[$key] ?? 0;
            $ajusteEntren = $this->ajustesManualesEntrenamiento[$key] ?? 0;
            $ajusteAccesorio = $this->ajustesManualesAccesorio[$key] ?? 0;

            $base[$key] = max(5, $valor + $ajusteEquipo + $ajusteEntren + $ajusteAccesorio);
        }

        // Sumar stats de equipo, entrenamiento y accesorio
        foreach ($base as $key => $valor) {
            $base[$key] += ($this->statsEquipo[$key] ?? 0) + ($this->statsEntrenamiento[$key] ?? 0) + ($this->statsAccesorio[$key] ?? 0);
        }

        // Aplicar multiplicadores de poderes
        foreach ($this->poderesSeleccionadosDetalles as $poder) {
            $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];
            foreach ($mods as $mod) {
                if (($mod['tipo'] ?? null) === 'multiplicador_stat') {
                    $stat = strtolower($mod['stat'] ?? '');
                    $factor = $mod['factor'] ?? 1;
                    if (isset($base[$stat])) $base[$stat] *= $factor;
                }
            }
        }

        // Asegurar mínimos y redondear
        foreach ($base as $key => $valor) {
            $base[$key] = max(5, round($valor));
        }
        // Aplicar poder ENERGIZADO si lo tiene el personaje
foreach ($this->poderesSeleccionadosDetalles as $poder) {
    if (strtoupper($poder->nombre) === 'ENERGIZADO') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $base[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores);
                    $mayor = reset($valores);
                    $claveMayor = key($valores);
                    $menor = end($valores);
                    $claveMenor = key($valores);

                    $base[$claveMenor] = round($menor + $mayor * $factor);
                }
            }
        }
    }
}

// Aplicar poder TÉCNICAS CERTERAS si está seleccionado
foreach ($this->poderesSeleccionadosDetalles as $poder) {
    if (strtoupper($poder->nombre) === 'TÉCNICAS CERTERAS') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $base[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores);
                    $mayor = reset($valores);
                    $claveMayor = key($valores);
                    $menor = end($valores);
                    $claveMenor = key($valores);

                    $base[$claveMenor] = round($menor + $mayor * $factor);
                }
            }
        }
    }
}


// Aplicar poder GOLPES VELOCES si lo tiene el personaje
foreach ($this->poderesSeleccionadosDetalles as $poder) {
    if (strtoupper($poder->nombre) === 'GOLPES VELOCES') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $base[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores);
                    $mayor = reset($valores);
                    $claveMayor = key($valores);
                    $menor = end($valores);
                    $claveMenor = key($valores);

                    $base[$claveMenor] = round($menor + $mayor * $factor);
                }
            }
        }
    }
}

// Aplicar poder MOLE si lo tiene el personaje
foreach ($this->poderesSeleccionadosDetalles as $poder) {
    if (strtoupper($poder->nombre) === 'MOLE') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $base[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores);
                    $mayor = reset($valores);
                    $claveMayor = key($valores);
                    $menor = end($valores);
                    $claveMenor = key($valores);

                    $base[$claveMenor] = round($menor + $mayor * $factor);
                }
            }
        }
    }
}

// Aplicar poder OFENSIVO EXPERTO si lo tiene el personaje
foreach ($this->poderesSeleccionadosDetalles as $poder) {
    if (strtoupper($poder->nombre) === 'OFENSIVO EXPERTO') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $base[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores);
                    $mayor = reset($valores);
                    $claveMayor = key($valores);
                    $menor = end($valores);
                    $claveMenor = key($valores);

                    $base[$claveMenor] = round($menor + $mayor * $factor);
                }
            }
        }
    }
}

// Aplicar poder SUPER ATAQUE si lo tiene el personaje
foreach ($this->poderesSeleccionadosDetalles as $poder) {
    if (strtoupper($poder->nombre) === 'SUPER ATAQUE') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'multiplicador_stat' &&
                ($mod['stat'] ?? '') === 'ataque' &&
                isset($base['ataque'])
            ) {
                $factor = $mod['factor'] ?? 1;
                $base['ataque'] = round($base['ataque'] * $factor);
            }
        }
    }
}



        $this->stats = $base;
    }

    public function getStatsDesgloseProperty()
    {
        $desglose = [];
        foreach ($this->statsBase as $key => $baseValor) {
            $ajusteEquipo = $this->ajustesManualesEquipo[$key] ?? 0;
            $ajusteEntren = $this->ajustesManualesEntrenamiento[$key] ?? 0;
            $ajusteAccesorio = $this->ajustesManualesAccesorio[$key] ?? 0;

            $equipo = $this->statsEquipo[$key] ?? 0;
            $entrenamiento = $this->statsEntrenamiento[$key] ?? 0;
            $accesorio = $this->statsAccesorio[$key] ?? 0;

            $total = $this->stats[$key] ?? $baseValor;

            $desglose[$key] = [
                'base' => $baseValor,
                'ajuste_equipo' => $ajusteEquipo,
                'ajuste_entrenamiento' => $ajusteEntren,
                'ajuste_accesorio' => $ajusteAccesorio,
                'equipo' => $equipo,
                'entrenamiento' => $entrenamiento,
                'accesorio' => $accesorio,
                'total' => $total,
            ];
        }
        return $desglose;
    }

    // Stats que se guardan en el set: base + lo que suman sus 3 partes, SIN los poderes. Los poderes que suben stats
    // (Súper Resistencia, Energizado...) los aplica la pelea; si se guardaban ya aplicados, contaban dos veces
    // (y la Anulación de poder no los podía sacar). En pantalla se siguen mostrando con los poderes
    private function statsSinPoderes(): array
    {
        $stats = [];
        foreach ($this->statsBase as $stat => $valorBase) {
            $stats[$stat] = (int) $valorBase
                + (int) ($this->ajustesManualesEquipo[$stat] ?? 0)
                + (int) ($this->ajustesManualesEntrenamiento[$stat] ?? 0)
                + (int) ($this->ajustesManualesAccesorio[$stat] ?? 0);
        }
        return $stats;
    }

    public function crearPost()
    {

        // dd($this->requisitosEquipo, $this->requisitosEntrenamiento, $this->requisitosAccesorio);
        $this->validate();

        $timestamp = time();

        $nombreGif = $this->gifPersonaje
            ? $this->gifPersonaje->storeAs('posts', $timestamp . '_gif.' . $this->gifPersonaje->getClientOriginalExtension(), 'public')
            : null;

        $nombreImagen = $this->imagen
            ? $this->imagen->storeAs('posts', $timestamp . '_img.' . $this->imagen->getClientOriginalExtension(), 'public')
            : null;

        $imagenes = [];
        foreach (['imagen1', 'imagen2', 'imagen3'] as $campo) {
            $imagenes[$campo] = $this->$campo
                ? $this->$campo->storeAs('posts', $timestamp . '_' . $campo . '.' . $this->$campo->getClientOriginalExtension(), 'public')
                : null;
        }

        $gifs = [];
        foreach (['gifAtaque', 'gifDefensa', 'gifCritico', 'gifEspecial', 'gifDerrota', 'gifVictoria'] as $gifCampo) {
            $gifs[$gifCampo] = $this->$gifCampo
                ? $this->$gifCampo->storeAs('posts', $timestamp . '_' . $gifCampo . '.' . $this->$gifCampo->getClientOriginalExtension(), 'public')
                : null;
        }

      $nombreEquipo = $this->equipo_imagen
    ? $timestamp . '_equipo.' . $this->equipo_imagen->getClientOriginalExtension()
    : null;

$nombreEntrenamiento = $this->entrenamiento_imagen
    ? $timestamp . '_entrenamiento.' . $this->entrenamiento_imagen->getClientOriginalExtension()
    : null;

$nombreAccesorio = $this->accesorio_imagen
    ? $timestamp . '_accesorio.' . $this->accesorio_imagen->getClientOriginalExtension()
    : null;

if ($this->equipo_imagen) {
    $this->equipo_imagen->storeAs('posts', $nombreEquipo, 'public');
}

if ($this->entrenamiento_imagen) {
    $this->entrenamiento_imagen->storeAs('posts', $nombreEntrenamiento, 'public');
}

if ($this->accesorio_imagen) {
    $this->accesorio_imagen->storeAs('posts', $nombreAccesorio, 'public');
}


        // Guardás solo el nombre en la base de datos
        $equipo_imagen = $this->equipo_imagen ? $nombreEquipo : null;
        $entrenamiento_imagen = $this->entrenamiento_imagen ? $nombreEntrenamiento : null;
        $accesorio_imagen = $this->accesorio_imagen ? $nombreAccesorio : null;

        $this->recalcularStats();
        $requisitosEquipo = $this->obtenerRequisitosParte('equipo');
        $requisitosEntrenamiento = $this->obtenerRequisitosParte('entrenamiento');
        $requisitosAccesorio = $this->obtenerRequisitosParte('accesorio');

      $post = Post::create([
    'titulo' => $this->titulo,
    'nivel' => $this->nivel,
    'tipo' => $this->tipo,
    'gif' => $nombreGif,
    'imagen' => $nombreImagen,
    'imagen1' => $imagenes['imagen1'],
    'imagen2' => $imagenes['imagen2'],
    'imagen3' => $imagenes['imagen3'],
    'stats' => $this->statsSinPoderes(),
    'stats_equipo' => $this->statsEquipo,
    'stats_entrenamiento' => $this->statsEntrenamiento,
    'stats_accesorio' => $this->statsAccesorio,
    'ajustes_manuales_equipo' => $this->ajustesManualesEquipo,
    'ajustes_manuales_entrenamiento' => $this->ajustesManualesEntrenamiento,
    'ajustes_manuales_accesorio' => $this->ajustesManualesAccesorio,
    'user_id' => auth()->id(),
    'gif_ataque' => $gifs['gifAtaque'],
    'gif_defensa' => $gifs['gifDefensa'],
    'gif_critico' => $gifs['gifCritico'],
    'gif_especial' => $gifs['gifEspecial'],
    'gif_derrota' => $gifs['gifDerrota'],
    'gif_victoria' => $gifs['gifVictoria'],
    // No envíes 'equipo', 'entrenamiento' ni 'accesorio'
    'equipo_nombre' => $this-> equipo_nombre,
    'entrenamiento_nombre' => $this-> entrenamiento_nombre,
    'accesorio_nombre' => $this-> accesorio_nombre,
    'equipo_imagen' => $nombreEquipo,
    'entrenamiento_imagen' => $nombreEntrenamiento,
    'accesorio_imagen' => $nombreAccesorio,
    'requisitos_equipo' => $requisitosEquipo,
    'requisitos_entrenamiento' => $requisitosEntrenamiento,
    'requisitos_accesorio' => $requisitosAccesorio,
]);

        $post->poderes()->attach($this->poderesSeleccionados);

        session()->flash('mensaje', 'El Post se publicó correctamente');
        return redirect()->route('posts.index');
    }

    public function barraColor()
    {
        $porcentaje = $this->puntosUsados() / max($this->puntosTotales(), 1);
        return $porcentaje < 0.6 ? 'bg-green-500' : ($porcentaje < 0.9 ? 'bg-yellow-400' : 'bg-red-600');
    }

    public function render()
    {
        return view('livewire.crear-post', [
            'poderesSeleccionadosDetalles' => $this->poderesSeleccionadosDetalles,
            'equipos' => $this->equipos,
            'entrenamientos' => $this->entrenamientos,
            'accesorios' => $this->accesorios,
            'statsDesglose' => $this->statsDesglose,
            'puntosTotales' => $this->puntosTotales(),
            'puntosUsados' => $this->puntosUsados(),
            'puntosRestantes' => $this->puntosRestantes(),
        ]);
    }
}
