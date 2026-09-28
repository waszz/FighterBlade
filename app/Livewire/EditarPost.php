<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Post;
use App\Models\Poder;
use Illuminate\Support\Facades\Storage;

class EditarPost extends Component
{
    use WithFileUploads;

    public $postId;
    public $titulo, $nivel, $tipo;

    public $gifPersonajeGuardado, $imagenGuardado, $gifAtaqueGuardado, $gifDefensaGuardado, $gifCriticoGuardado, $gifEspecialGuardado, $gifDerrotaGuardado, $gifVictoriaGuardado;
    public $gifPersonaje, $imagen, $gifAtaque, $gifDefensa, $gifCritico, $gifEspecial, $gifDerrota, $gifVictoria;

    public $poderesDisponibles;
    public $poderesSeleccionados = [];
    
    public $equipo_nombre, $entrenamiento_nombre, $accesorio_nombre;
    public $equipo_imagen, $entrenamiento_imagen, $accesorio_imagen;
    public $equipo_imagen_preview, $entrenamiento_imagen_preview, $accesorio_imagen_preview;

    public $ajustesManualesEquipo = [];
    public $ajustesManualesEntrenamiento = [];
    public $ajustesManualesAccesorio = [];
    public $requisitosEquipo = [];
    public $requisitosEntrenamiento = [];
    public $requisitosAccesorio = [];
    public $statsFinales = [];

    public $statsBase = [
        'fuerza' => 5,
        'resistencia' => 5,
        'ataque' => 5,
        'defensa' => 5,
        'velocidad' => 5,
        'energia' => 5,
    ];

    public $statsDesglose = [];

    public $puntosTotales;
    public $puntosUsados;
    public $puntosRestantes;

    protected $rules = [
        'titulo' => 'required|string|min:1|max:60',
        'nivel' => 'required|integer|min:5|max:100',
        'tipo' => 'required|string|in:fisico,elemental,hibrido',
        'poderesSeleccionados' => 'required|array|min:1|max:3',
        'poderesSeleccionados.*' => 'exists:poderes,id',
        'gifPersonaje' => 'nullable|image|max:2048',
        'imagen' => 'nullable|mimes:jpg,jpeg,png|max:12288',
        'gifAtaque' => 'nullable|image|max:2048',
        'gifDefensa' => 'nullable|image|max:2048',
        'gifCritico' => 'nullable|image|max:2048',
        'gifEspecial' => 'nullable|image|max:2048',
        'gifDerrota' => 'nullable|image|max:2048',
        'gifVictoria' => 'nullable|image|max:2048',
        'equipo_imagen' => 'nullable|mimes:jpg,jpeg,png|max:2048',
        'entrenamiento_imagen' => 'nullable|mimes:jpg,jpeg,png|max:2048',
        'accesorio_imagen' => 'nullable|mimes:jpg,jpeg,png|max:2048',
        'requisitosEquipo' => 'nullable|array',
        'requisitosEquipo.*' => 'nullable|integer|min:0|max:100',

        'requisitosEntrenamiento' => 'nullable|array',
        'requisitosEntrenamiento.*' => 'nullable|integer|min:0|max:100',

        'requisitosAccesorio' => 'nullable|array',
        'requisitosAccesorio.*' => 'nullable|integer|min:0|max:100',

    ];

    public function mount($postId)
    {
        $this->postId = $postId;
        $post = Post::conRivales()->with('poderes')->findOrFail($postId);

        $this->titulo = $post->titulo;
        $this->nivel = $post->nivel;
        $this->tipo = $post->tipo;

        $this->gifPersonajeGuardado = $post->gif;
        $this->imagenGuardado = $post->imagen;
        $this->gifAtaqueGuardado = $post->gif_ataque;
        $this->gifDefensaGuardado = $post->gif_defensa;
        $this->gifCriticoGuardado = $post->gif_critico;
        $this->gifEspecialGuardado = $post->gif_especial;
        $this->gifDerrotaGuardado = $post->gif_derrota;
        $this->gifVictoriaGuardado = $post->gif_victoria;


        $this->poderesSeleccionados = $post->poderes->pluck('id')->toArray();
        $this->poderesDisponibles = Poder::all();

        $this->equipo_nombre = $post->equipo_nombre;
        $this->entrenamiento_nombre = $post->entrenamiento_nombre;
        $this->accesorio_nombre = $post->accesorio_nombre;

        $this->equipo_imagen_preview = $post->equipo_imagen ? Storage::url('posts/' . $post->equipo_imagen) : null;
        $this->entrenamiento_imagen_preview = $post->entrenamiento_imagen ? Storage::url('posts/' . $post->entrenamiento_imagen) : null;
        $this->accesorio_imagen_preview = $post->accesorio_imagen ? Storage::url('posts/' . $post->accesorio_imagen) : null;

        $this->requisitosEquipo = is_array($post->requisitos_equipo)
        ? $post->requisitos_equipo
        : json_decode($post->requisitos_equipo, true) ?? array_fill_keys(array_keys($this->statsBase), 0);

        $this->requisitosEntrenamiento = is_array($post->requisitos_entrenamiento)
            ? $post->requisitos_entrenamiento
            : json_decode($post->requisitos_entrenamiento, true) ?? array_fill_keys(array_keys($this->statsBase), 0);

        $this->requisitosAccesorio = is_array($post->requisitos_accesorio)
            ? $post->requisitos_accesorio
            : json_decode($post->requisitos_accesorio, true) ?? array_fill_keys(array_keys($this->statsBase), 0);




        // Decodificar ajustes manuales (si son JSON)
        $this->ajustesManualesEquipo = is_array($post->ajustes_manuales_equipo)
    ? $post->ajustes_manuales_equipo
    : json_decode($post->ajustes_manuales_equipo, true) ?? array_fill_keys(array_keys($this->statsBase), 0);

        $this->ajustesManualesEntrenamiento = is_array($post->ajustes_manuales_entrenamiento)
            ? $post->ajustes_manuales_entrenamiento
            : json_decode($post->ajustes_manuales_entrenamiento, true) ?? array_fill_keys(array_keys($this->statsBase), 0);

        $this->ajustesManualesAccesorio = is_array($post->ajustes_manuales_accesorio)
        ? $post->ajustes_manuales_accesorio
        : json_decode($post->ajustes_manuales_accesorio, true) ?? array_fill_keys(array_keys($this->statsBase), 0);


        $this->puntosTotales = $this->nivel * 5;

        $this->calcularPuntosYStats();
    }

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

  public function calcularPuntosYStats()
{
    $this->puntosTotales = $this->puntosTotales();
    $this->puntosUsados = $this->puntosUsados();
    $this->puntosRestantes = $this->puntosRestantes();

    // Calcular base + ajustes
    $base = [];
    foreach ($this->statsBase as $stat => $valorBase) {
        $equipo = $this->ajustesManualesEquipo[$stat] ?? 0;
        $entrenamiento = $this->ajustesManualesEntrenamiento[$stat] ?? 0;
        $accesorio = $this->ajustesManualesAccesorio[$stat] ?? 0;

        $total = $valorBase + $equipo + $entrenamiento + $accesorio;

        $this->statsDesglose[$stat] = [
            'base' => $valorBase,
            'ajuste_equipo' => $equipo,
            'ajuste_entrenamiento' => $entrenamiento,
            'ajuste_accesorio' => $accesorio,
            'total' => $total,
        ];

        $base[$stat] = $total;
    }

    // Guardamos temporalmente para usar en recalcularStats
    $this->statsParciales = $base;

    // Ahora llamamos al método que recalcula stats con poderes
    $this->recalcularStats();
}

public function recalcularStats()
{
    $base = $this->statsParciales ?? [];

    // Aplicar multiplicadores de poderes
    foreach ($this->poderesDisponibles->whereIn('id', $this->poderesSeleccionados) as $poder) {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];
        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? null) === 'multiplicador_stat') {
                $stat = strtolower($mod['stat'] ?? '');
                $factor = $mod['factor'] ?? 1;
                if (isset($base[$stat])) {
                    $base[$stat] *= $factor;
                }
            }
        }
    }

    // Aplicar poder ENERGIZADO si está seleccionado
    foreach ($this->poderesDisponibles->whereIn('id', $this->poderesSeleccionados) as $poder) {
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
foreach ($this->poderesDisponibles->whereIn('id', $this->poderesSeleccionados) as $poder) {
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


    

    // Aplicar poder GOLPES VELOCES si está seleccionado
foreach ($this->poderesDisponibles->whereIn('id', $this->poderesSeleccionados) as $poder) {
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

// Aplicar poder MOLE si está seleccionado
foreach ($this->poderesDisponibles->whereIn('id', $this->poderesSeleccionados) as $poder) {
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


// Aplicar poder OFENSIVO EXPERTO si está seleccionado
foreach ($this->poderesDisponibles->whereIn('id', $this->poderesSeleccionados) as $poder) {
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


    // Asegurar mínimos y redondear
    foreach ($base as $stat => $valor) {
        $base[$stat] = max(5, round($valor));
        // Actualizar también el desglose total para que coincida
        if (isset($this->statsDesglose[$stat])) {
            $this->statsDesglose[$stat]['total'] = $base[$stat];
        }
    }

    // Guardar resultado final
    $this->statsFinales = $base;
}

   public function incrementarStat($stat, $tipo, $cantidad = 1)
{
    $prop = 'ajustesManuales' . ucfirst($tipo);

    if (!isset($this->$prop[$stat])) return;

    $maxIncremento = $this->puntosRestantes();

    $incrementoReal = min($cantidad, $maxIncremento);

    if ($incrementoReal > 0) {
        $this->$prop[$stat] += $incrementoReal;
        $this->calcularPuntosYStats();
    }
}

public function decrementarStat($stat, $tipo, $cantidad = 1)
{
    $prop = 'ajustesManuales' . ucfirst($tipo);

    if (!isset($this->$prop[$stat])) return;

    $maxDecremento = $this->$prop[$stat];

    $decrementoReal = min($cantidad, $maxDecremento);

    if ($decrementoReal > 0) {
        $this->$prop[$stat] -= $decrementoReal;
        $this->calcularPuntosYStats();
    }
}

    public function updatedNivel()
    {
        $this->puntosTotales = $this->puntosTotales();
        $this->calcularPuntosYStats();
    }

    public function editarPost()
    {
        $this->validate();

        // conRivales: también se editan los personajes especiales (rivales de misión, enemigo de bienvenida)
        $post = Post::conRivales()->findOrFail($this->postId);

        $post->titulo = $this->titulo;
        $post->nivel = $this->nivel;
        $post->tipo = $this->tipo;

        foreach ([
            'gifPersonaje', 'imagen', 'gifAtaque', 'gifDefensa', 'gifCritico',
            'gifEspecial', 'gifDerrota', 'gifVictoria',
            'equipo_imagen', 'entrenamiento_imagen', 'accesorio_imagen'
        ] as $campo) {
            $post = $this->guardarArchivoSiHay($post, $campo);
        }

        $post->equipo_nombre = $this->equipo_nombre;
        $post->entrenamiento_nombre = $this->entrenamiento_nombre;
        $post->accesorio_nombre = $this->accesorio_nombre;

        $post->ajustes_manuales_equipo = $this->ajustesManualesEquipo;
        $post->ajustes_manuales_entrenamiento = $this->ajustesManualesEntrenamiento;
        $post->ajustes_manuales_accesorio = $this->ajustesManualesAccesorio;
        $post->requisitos_equipo = array_filter($this->requisitosEquipo, fn($v) => $v > 0);
        $post->requisitos_entrenamiento = array_filter($this->requisitosEntrenamiento, fn($v) => $v > 0);
        $post->requisitos_accesorio = array_filter($this->requisitosAccesorio, fn($v) => $v > 0);

        $this->calcularPuntosYStats(); // solo si no estás llamándolo antes

        $post->stats = array_map(fn($d) => $d['total'], $this->statsDesglose);


        $post->save();

        $post->poderes()->sync($this->poderesSeleccionados);

        session()->flash('mensaje', 'Personaje / Post actualizado correctamente.');
        // Los especiales vuelven a su propia lista
        if (in_array((int) $post->es_enemigo, [Post::ENEMIGO_ESPECIAL, Post::RIVAL_MISION], true)) {
            return redirect()->route('personajes.especiales');
        }
         return redirect()->route('posts.index');
        // Refrescar datos
        $this->mount($this->postId);
    }
private function guardarArchivoSiHay(Post $post, string $campo)
{
    if (!is_null($this->$campo)) {
        $archivo = $this->$campo;

        if (method_exists($archivo, 'getClientOriginalExtension')) {
            $extension = $archivo->getClientOriginalExtension();

            $sufijos = [
                'gifPersonaje' => 'gif',
                'gifAtaque' => 'gifAtaque',
                'gifDefensa' => 'gifDefensa',
                'gifCritico' => 'gifCritico',
                'gifEspecial' => 'gifEspecial',
                'gifDerrota' => 'gifDerrota',
                'gifVictoria' => 'gifVictoria',
                'imagen' => 'img',
                'equipo_imagen' => 'img',
                'entrenamiento_imagen' => 'img',
                'accesorio_imagen' => 'img',
            ];

            $sufijo = $sufijos[$campo] ?? $campo;
            $timestamp = time();
            $nombreArchivo = $timestamp . '_' . $sufijo . '.' . $extension;

            $archivo->storeAs('public/posts', $nombreArchivo);

            $columna = [
                'gifPersonaje' => 'gif',
                'gifAtaque' => 'gif_ataque',
                'gifDefensa' => 'gif_defensa',
                'gifCritico' => 'gif_critico',
                'gifEspecial' => 'gif_especial',
                'gifDerrota' => 'gif_derrota',
                'gifVictoria' => 'gif_victoria',
                'imagen' => 'imagen',
                'equipo_imagen' => 'equipo_imagen',
                'entrenamiento_imagen' => 'entrenamiento_imagen',
                'accesorio_imagen' => 'accesorio_imagen',
            ][$campo] ?? $campo;

            // ✅ Guardar con "posts/" solo si es gif o imagen principal
            $guardarConPath = in_array($campo, [
                'gifPersonaje', 'gifAtaque', 'gifDefensa', 'gifCritico',
                'gifEspecial', 'gifDerrota', 'gifVictoria', 'imagen'
            ]);

            $post->$columna = $guardarConPath
                ? 'posts/' . $nombreArchivo
                : $nombreArchivo;

            // Para la vista siempre usamos path completo
            $this->{$campo . 'Guardado'} = 'posts/' . $nombreArchivo;

            $this->$campo = null;
        }
    }

    return $post;
}






    public function barraColor()
    {
        $ratio = $this->puntosUsados / max($this->puntosTotales, 1);
        if ($ratio < 0.5) return 'bg-green-500';
        if ($ratio < 0.8) return 'bg-yellow-400';
        return 'bg-red-500';
    }

    public function render()
    {
        return view('livewire.editar-post', [
            'poderesDisponibles' => $this->poderesDisponibles,
            'poderesSeleccionados' => $this->poderesSeleccionados,
            'statsDesglose' => $this->statsDesglose,
            'puntosTotales' => $this->puntosTotales,
            'puntosUsados' => $this->puntosUsados,
            'puntosRestantes' => $this->puntosRestantes,
        ]);
    }
}
