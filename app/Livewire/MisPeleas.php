<?php

namespace App\Livewire;

use App\Models\Pelea;
use Livewire\Component;

class MisPeleas extends Component
{
    public $peleas;
    public $peleaSeleccionada = null; // Pelea para mostrar detalles
    public $mostrarModal = false;
    public $datosCombate = [];
    public $poderesPersonaje;
    public $poderPrincipalPersonaje;
    public $poderesEnemigo;
    public $poderPrincipalEnemigo;
    public $nombrePersonaje;
    public $nombreEnemigo;
    public $ciudadActual;
    public $gifCiudadPersonaje;
    public $personajeId;
    public ?int $peleaVistaId = null; // pelea que se está repitiendo ("Ver")
    public string $pestana = 'pve';   // 'pve' (contra la máquina) o 'pvp' (contra jugadores)



    public function mount($personajeId, $peleaCompartidaId = null)
    {
        $this->personajeId = $personajeId;
        $this->cargarPeleas();

        // Pelea tocada en el chat: se muestra aunque sea de otro jugador, solo si de verdad se compartió
        if ($peleaCompartidaId && \App\Models\Mensaje::where('tipo', 'pelea')->where('adjunto->pelea_id', (int) $peleaCompartidaId)->exists()) {
            $this->peleaVistaId = (int) $peleaCompartidaId;
        }
    }

    public function cambiarPestana(string $pestana)
    {
        $this->pestana = $pestana === 'pvp' ? 'pvp' : 'pve';
        $this->peleaVistaId = null;
        $this->cargarPeleas();
    }

    // Últimas 20 peleas de la pestaña; PvP son las que se guardaron con el rival siendo otro jugador
    protected function cargarPeleas(): void
    {
        $this->peleas = Pelea::where('personaje_id', $this->personajeId)
            ->orderBy('realizada_en', 'desc')
            ->take(300)
            ->get()
            ->filter(fn ($pelea) => ! empty(($pelea->datos_combate ?? [])['enemigo_es_personaje']) === ($this->pestana === 'pvp'))
            ->take(20)
            ->values();
    }



public function verDetalle($id)
{
    $this->peleaSeleccionada = Pelea::with([
        'personaje.equipo.post',
        'personaje.entrenamiento.post',
        'personaje.accesorio.post',
        'personaje.post',
        'enemigo.poderes',
        'personaje.ciudadActual',
    ])->find($id);

    $this->datosCombate = [];

    if ($this->peleaSeleccionada) {
        if (is_string($this->peleaSeleccionada->datos_combate)) {
            $this->datosCombate = json_decode($this->peleaSeleccionada->datos_combate, true) ?? [];
        } elseif (is_array($this->peleaSeleccionada->datos_combate)) {
            $this->datosCombate = $this->peleaSeleccionada->datos_combate;
        }

        $personaje = $this->peleaSeleccionada->personaje;
        $enemigo = $this->peleaSeleccionada->enemigo;

        // Inicializar poderes vacío
        $poderes = collect();

        // Verificar que las 3 partes estén equipadas y tengan el mismo origen_post_id
        if (
            $personaje->equipo && $personaje->entrenamiento && $personaje->accesorio &&
            $personaje->equipo->origen_post_id === $personaje->entrenamiento->origen_post_id &&
            $personaje->equipo->origen_post_id === $personaje->accesorio->origen_post_id
        ) {
            $postEquipado = \App\Models\Post::find($personaje->equipo->origen_post_id);
            if ($postEquipado && $postEquipado->poderes) {
                $poderes = collect($postEquipado->poderes)
                    ->map(fn($p) => (object)['nombre' => strtoupper($p['nombre'] ?? '')]);
            }
        }

        // Si no tiene las 3 partes iguales o no hay poderes en el post equipado, usar poderes base del personaje
        if ($poderes->isEmpty() && $personaje->post?->poderes) {
            $poderes = collect($personaje->post->poderes)
                ->map(fn($p) => (object)['nombre' => strtoupper($p['nombre'] ?? '')]);
        }

        $this->poderesPersonaje = $poderes;
        $this->poderPrincipalPersonaje = $poderes->first()->nombre ?? null;

        // Poderes enemigo
        $this->poderesEnemigo = collect($enemigo->poderes ?? [])
            ->map(fn($p) => (object)['nombre' => strtoupper($p['nombre'] ?? '')]);
        $this->poderPrincipalEnemigo = $this->poderesEnemigo->first()->nombre ?? null;

        // Nombres para mostrar
        $this->nombrePersonaje = $personaje->nombre;
        $this->nombreEnemigo = $enemigo->titulo ?? 'Enemigo';

        
        // Obtener ciudad actual
        $personaje = $this->peleaSeleccionada->personaje;
        $this->gifCiudadPersonaje = $personaje->ciudadActual?->gif ?? null;

    }

// Para personaje equipado
$gifPrefixPersonaje = null;
if (
    $personaje->equipo && $personaje->entrenamiento && $personaje->accesorio &&
    $personaje->equipo->origen_post_id === $personaje->entrenamiento->origen_post_id &&
    $personaje->equipo->origen_post_id === $personaje->accesorio->origen_post_id
) {
    $postEquipado = \App\Models\Post::find($personaje->equipo->origen_post_id);
    if ($postEquipado && $postEquipado->gif) {
        $filename = pathinfo($postEquipado->gif, PATHINFO_FILENAME); // ej: 1752864613_gif
        $baseName = preg_replace('/_gif$/', '', $filename);          // quita "_gif"
        $gifPrefixPersonaje = 'posts/' . $baseName;
    }
}

// Para enemigo
$gifPrefixEnemigo = null;
if ($enemigo->gif) {
    $filenameEnemigo = pathinfo($enemigo->gif, PATHINFO_FILENAME);
    $baseNameEnemigo = preg_replace('/_gif$/', '', $filenameEnemigo);
    $gifPrefixEnemigo = 'posts/' . $baseNameEnemigo;
}


    $this->mostrarModal = true;
}



    // "Ver": muestra la pelea como en la Ciudad (escenario + rondas + resultado).
    // Las peleas viejas no tienen rondas guardadas: se ve el escenario y el resultado.
    public function verPelea($id)
    {
        $pelea = Pelea::where('personaje_id', $this->personajeId)->find($id);
        if (! $pelea) {
            return;
        }
        $this->peleaVistaId = $pelea->id;
    }

    // Comparte una pelea propia (exploración, PvP, misión, torre o caza) en el chat general
    public function compartirPelea($id)
    {
        $pelea = Pelea::where('personaje_id', $this->personajeId)->find($id);
        $personaje = \App\Models\Personaje::where('user_id', auth()->id())->find($this->personajeId);
        if (! $pelea || ! $personaje) {
            return;
        }
        \App\Support\ChatCompartir::pelea($personaje, $pelea);
        $this->dispatch('chatCompartido');
        $this->dispatch('success', ['message' => 'Compartiste la pelea en el chat.']);
    }

    public function volverALista()
    {
        $this->peleaVistaId = null;
    }

    // Variables que necesita el partial livewire.partials.resultado-pelea
    protected function datosRepeticion(): ?array
    {
        $pelea = $this->peleaVistaId ? Pelea::with('personaje.post', 'personaje.ciudadActual')->find($this->peleaVistaId) : null;
        $datos = $pelea?->datos_combate ?? [];
        if (! $pelea) {
            return null;
        }

        $enemigo = ! empty($datos['enemigo_es_personaje'])
            ? \App\Models\Personaje::with('post.poderes', 'equipo', 'entrenamiento', 'accesorio')->find($pelea->enemigo_id)
            : \App\Models\Post::conRivales()->with('poderes')->find($pelea->enemigo_id);
        // Sin rondas guardadas (peleas viejas) o sin el rival, no se puede mostrar la pantalla de rondas
        $conRondas = ! empty($datos['rondas']) && $enemigo;

        $vista = array_fill_keys(Explorar::VISTA_PELEA, 0);
        foreach ($datos['vista'] ?? [] as $clave => $valor) {
            $vista[$clave] = $valor;
        }

        return $vista + [
            'pelea'            => $pelea,
            // Botón Compartir abajo de la pelea: solo en las propias (las de otros se ven desde el chat)
            'idPeleaCompartir' => (int) $pelea->personaje_id === (int) $this->personajeId ? $pelea->id : null,
            'conRondas'        => $conRondas,
            'resultadosRondas' => $conRondas ? $datos['rondas'] : [],
            'personaje'        => $pelea->personaje,
            'enemigo'          => $enemigo,
            'ciudadActual'     => \App\Models\Ciudad::find($datos['ciudad_id'] ?? null) ?? $pelea->personaje->ciudadActual,
            'escenarioMision'  => $datos['escenario_mision'] ?? null,
            // En la Ciudad son propiedades del componente: las partes equipadas y el set base del personaje
            'equipo'           => $pelea->personaje->equipo,
            'entrenamiento'    => $pelea->personaje->entrenamiento,
            'accesorio'        => $pelea->personaje->accesorio,
            'post'             => $pelea->personaje->post,
        ];
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->peleaSeleccionada = null;
    }

    public function render()
    {
        return view('livewire.mis-peleas', ['repeticion' => $this->datosRepeticion()]);
    }
}
