<?php

namespace App\Livewire;

use App\Models\Personaje;
use App\Models\Post;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ElegirPersonaje extends Component
{
    // Esmeraldas con las que arranca cada personaje nuevo
    const ESMERALDAS_INICIALES = 100;

    public $personajesBase;       // personajes oficiales base (Post)
    public $personajesUsuario;    // personajes del usuario actual (Personaje)
    public $personajeSeleccionado = null;

    public $mostrarFormularioNombre = false;
    public $nuevoNombre = '';
    public $personajeBaseSeleccionado = null;

    public $mostrarModalPost = false;
    public $gifPost = null;
    public $statsPost = null;
    public $nombrePost = null;
    public $poderesPersonaje;

public function mount()
{
    $userId = Auth::id();
    $user = Auth::user();

    // Traer personajes base (los 5 iniciales, iguales para todos)
    $this->personajesBase = Post::personajesBase()->with('poderes')->limit(5)->get()->keyBy('id');

    // Personajes ya creados por el usuario
    $this->personajesUsuario = Personaje::where('user_id', $userId)->get();

    // Si el usuario NO es admin y ya tiene al menos 1 personaje, ocultar personajes base
    if (!$user->isAdmin() && $this->personajesUsuario->count() > 0) {
        $this->personajesBase = collect();
    }
}


    // Modal para ver un personaje inicial antes de elegirlo
    public $inicialModalId = null;

    public function verInicial($postId)
    {
        $this->inicialModalId = $this->personajesBase->has($postId) ? (int) $postId : null;
    }

    public function cerrarInicial()
    {
        $this->inicialModalId = null;
    }

    public function seleccionarPersonajeBase($personajeBaseId)
    {
        $this->inicialModalId = null;
        $this->personajeBaseSeleccionado = $personajeBaseId;
        $this->nuevoNombre = '';
        $this->mostrarFormularioNombre = true;
    }

    public function cancelarCrearPersonaje()
    {
        $this->mostrarFormularioNombre = false;
        $this->nuevoNombre = '';
        $this->personajeBaseSeleccionado = null;
    }

    public function confirmarCrearPersonaje()
    {
            $userId = Auth::id();
            $user = Auth::user();

            // Solo bloqueamos creación si NO es admin y ya tiene un personaje
        if (!$user->isAdmin() && Personaje::where('user_id', $userId)->exists()) {
            $this->dispatch('error', ['message' => 'Ya tienes un personaje creado.']);
            return;
        }


        // Validar nombre único
        $this->validate(
        [
            'nuevoNombre' => 'required|string|max:255|unique:personajes,nombre',
        ],
        [
            'nuevoNombre.unique' => 'Ese nombre ya está en uso. Elige otro.',
        ]
    );

        $personajeBase = Post::with('poderes')->findOrFail($this->personajeBaseSeleccionado);

        // Stats base fijos
        $statsBase = [
            'fuerza' => 5,
            'ataque' => 5,
            'velocidad' => 5,
            'resistencia' => 5,
            'defensa' => 5,
            'energia' => 5,
        ];

        // // Aplicar modificadores de poderes
        // $poderes = $personajeBase->poderes ?? collect();

        // foreach ($poderes as $poder) {
        //     $modificadores = $poder->modificadores ?? [];

        //     if (is_string($modificadores)) {
        //         $modificadores = json_decode($modificadores, true) ?? [];
        //     }

        //     foreach ($modificadores as $mod) {
        //         if (!is_array($mod)) continue;

        //         $tipo = $mod['tipo'] ?? 'incremento_stat';
        //         $stat = strtolower($mod['stat'] ?? '');

        //         if (!isset($statsBase[$stat])) continue;

        //         if ($tipo === 'incremento_stat') {
        //             $valor = $mod['valor'] ?? 0;
        //             $statsBase[$stat] += $valor;

        //         } elseif ($tipo === 'multiplicador_stat') {
        //             $factor = $mod['factor'] ?? 1;
        //             $statsBase[$stat] *= $factor;
        //         }
        //     }
        // }


        // Redondear y evitar valores negativos
        foreach ($statsBase as $stat => $valor) {
            $statsBase[$stat] = max(1, round($valor));
        }

        // Ciudad inicial: la de menor nivel disponible (por defecto, la ciudad de partida)
        $ciudadInicial = \App\Models\Ciudad::orderBy('nivel')->first();

        // Primer personaje de la cuenta: se lleva el cofre de bienvenida
        $esPrimerPersonaje = ! Personaje::where('user_id', $userId)->exists();

        // Crear el nuevo personaje
        $nuevoPersonaje = Personaje::create([
            'user_id' => $userId,
            'post_id' => $personajeBase->id,
            'nombre' => $this->nuevoNombre,
            'tipo' => in_array($personajeBase->tipo, ['fisico', 'elemental', 'hibrido']) 
                ? $personajeBase->tipo 
                : 'fisico',
            'nivel' => 1,
            'imagen' => $personajeBase->imagen,
            'gif' => $personajeBase->gif,
            'ciudad_id' => $ciudadInicial?->id,
            'stats' => json_encode($statsBase),
            'diamante' => self::ESMERALDAS_INICIALES,
        ]);

        if ($esPrimerPersonaje) {
            \App\Support\RecompensasTorre::darCofreBienvenida($nuevoPersonaje);
        }

        // Recargar lista del usuario
        $this->personajesUsuario = Personaje::where('user_id', $userId)->get();

        // Reset
        $this->mostrarFormularioNombre = false;
        $this->nuevoNombre = '';
        $this->personajeBaseSeleccionado = null;

        return redirect()->route('juego.mostrar', ['personajeId' => $nuevoPersonaje->id]);
    }

    public function entrarConPersonaje($personajeId)
    {
        $this->personajeSeleccionado = $this->personajesUsuario->firstWhere('id', $personajeId);
    }

    public function confirmarSeleccion($personajeId)
    {
        session(['personaje_id' => $personajeId]);
        return redirect()->route('juego.mostrar', ['personajeId' => $personajeId]);
    }

    public function cancelarSeleccion()
    {
        $this->personajeSeleccionado = null;
    }

public function verPostOriginal($id, $tipo = 'personaje')
{
    if ($tipo === 'personaje') {
        $personaje = Personaje::with(['equipo', 'entrenamiento', 'accesorio'])->find($id);

        if ($personaje) {
            $origenEquipo = $personaje->equipo->origen_post_id ?? null;
            $origenEntrenamiento = $personaje->entrenamiento->origen_post_id ?? null;
            $origenAccesorio = $personaje->accesorio->origen_post_id ?? null;

            // Verifico si equipo, entrenamiento y accesorio tienen el mismo origen_post_id
            if ($origenEquipo && $origenEquipo === $origenEntrenamiento && $origenEquipo === $origenAccesorio) {
                $postOrigen = Post::with('poderes')->find($origenEquipo);

                if ($postOrigen) {
                    $this->gifPost = $postOrigen->gif ?? null;
                    $this->statsPost = is_string($postOrigen->stats)
                        ? json_decode($postOrigen->stats, true)
                        : ($postOrigen->stats ?? []);
                    $this->nombrePost = $postOrigen->nombre ?? 'Personaje Equipado';
                    $this->poderesPersonaje = $postOrigen->poderes ?? collect();

                    $this->mostrarModalPost = true;
                    return;
                }
            }

            // Si no coinciden o no existe post común, uso el post base del personaje
            $postBase = Post::with('poderes')->find($personaje->post_id);

            $this->gifPost = $personaje->gif ?? ($postBase->gif ?? null);

            if ($postBase && $postBase->stats) {
                $this->statsPost = is_string($postBase->stats)
                    ? json_decode($postBase->stats, true)
                    : $postBase->stats;
            } elseif ($personaje->stats) {
                $this->statsPost = is_string($personaje->stats)
                    ? json_decode($personaje->stats, true)
                    : $personaje->stats;
            } else {
                $this->statsPost = [];
            }

            $this->nombrePost = $personaje->nombre ?? ($postBase->nombre ?? 'Personaje');
            $this->poderesPersonaje = $postBase->poderes ?? collect();

            $this->mostrarModalPost = true;
            return;
        }
    }

    // Cuando es tipo post o no se encuentra personaje
    $post = Post::with('poderes')->find($id);

    if ($post) {
        $this->gifPost = $post->gif ?? null;
        $this->statsPost = is_string($post->stats)
            ? json_decode($post->stats, true)
            : ($post->stats ?? []);
        $this->nombrePost = $post->nombre ?? 'Personaje Base';
        $this->poderesPersonaje = $post->poderes ?? collect();
        $this->mostrarModalPost = true;
    }
}







    public function cerrarModalPost()
    {
        $this->mostrarModalPost = false;
        $this->gifPost = null;
        $this->statsPost = null;
    }

    public function render()
    {
        return view('livewire.elegir-personaje')->layout('layouts.app');
    }
}
