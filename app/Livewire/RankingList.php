<?php

namespace App\Livewire;

use App\Models\Clan;
use App\Models\Personaje;
use Livewire\Component;
use App\Models\SolicitudClan;

class RankingList extends Component
{
    public $ranking;
    public $tipo;
   public $personajeActual;
   public $clanActual;
   public $mostrarModal = false;
public $clanSeleccionado;
public $personajeSeleccionadoModal;
public $personajeSeleccionadoModalStats = [];
public $gifPersonajeEquipado = null;
// Instancia sin lista que solo abre el modal de un jugador (desde los Conectados del chat)
public bool $soloModal = false;
   
   public function mount($personaje = null)
{
    $this->clanActual = Clan::where('fundador_id', auth()->id())->first();
    // El personaje del usuario: resalta su fila y permite "Atacar" desde el modal
    $this->personajeActual = $personaje ?? Personaje::where('user_id', auth()->id())->first();
}


#[\Livewire\Attributes\On('verPerfilJugador')]
public function verPerfilJugador($personajeId)
{
    if ($this->soloModal) {
        $this->mostrarModalPersonaje($personajeId);
    }
}

public function mostrarModalPersonaje($id)
{
$this->personajeSeleccionadoModal = Personaje::with([
    'ciudadActual', // 👈 trae la ciudad
    'user',
    'equipo',
    'entrenamiento',
    'accesorio',
    'post'
])->find($id);


    // Stats con los que pelea: base + partes + anillo + poción de stat + poderes (lo mismo que el panel y la pelea)
    $this->personajeSeleccionadoModalStats = $this->personajeSeleccionadoModal->statsDeCombate();

    // Carga GIF del personaje equipado si los 3 objetos tienen el mismo origen_post_id
    $equipo = $this->personajeSeleccionadoModal->equipo;
    $entrenamiento = $this->personajeSeleccionadoModal->entrenamiento;
    $accesorio = $this->personajeSeleccionadoModal->accesorio;

    $origenPostId = null;

    if ($equipo && $entrenamiento && $accesorio &&
        $equipo->origen_post_id === $entrenamiento->origen_post_id &&
        $equipo->origen_post_id === $accesorio->origen_post_id
    ) {
        $origenPostId = $equipo->origen_post_id;
    } elseif ($this->personajeSeleccionadoModal->origen_post_id) {
        // Si no, tomar el origen_post_id del personaje (si tiene)
        $origenPostId = $this->personajeSeleccionadoModal->origen_post_id;
    } elseif ($this->personajeSeleccionadoModal->post) {
        // Finalmente fallback al post base
        $origenPostId = $this->personajeSeleccionadoModal->post->id;
    }

    if ($origenPostId) {
        $postEquipado = \App\Models\Post::find($origenPostId);
        $this->gifPersonajeEquipado = $postEquipado?->gif ?? null;
    } else {
        $this->gifPersonajeEquipado = null;
    }

    $this->mostrarModal = true;
}



public function abrirModalIngreso($clanId)
{
    $usuarioId = auth()->id();

    // 1. Verificar si el usuario es fundador del clan seleccionado
    $clan = Clan::find($clanId);
    if (!$clan) {
        session()->flash('mensaje', 'Clan no encontrado.');
        return;
    }

    if ($clan->fundador_id == $usuarioId) {
        session()->flash('mensaje', '❌ No puedes enviar solicitud a tu propio clan (eres fundador).');
        return;
    }

    // 2. Verificar si el usuario ya tiene clan
    $tieneClan = Personaje::where('user_id', $usuarioId)
        ->whereNotNull('clan_id')
        ->exists();

    if ($tieneClan) {
        session()->flash('mensaje', '❌ Ya perteneces a un clan, no puedes enviar otra solicitud.');
        return;
    }

    // 3. Evitar enviar múltiples solicitudes pendientes al mismo clan
    $yaSolicitado = SolicitudClan::where('usuario_id', $usuarioId)
        ->where('clan_id', $clanId)
        ->where('estado', 'pendiente')
        ->exists();

    if ($yaSolicitado) {
        session()->flash('mensaje', '⚠️ Ya enviaste una solicitud a este clan.');
        return;
    }

    $this->clanSeleccionado = $clan;
    $this->mostrarModal = true;
}

public function enviarSolicitudIngreso()
{
    if (!$this->clanSeleccionado) return;

    SolicitudClan::firstOrCreate([
        'usuario_id' => auth()->id(),
        'clan_id' => $this->clanSeleccionado->id,
    ], [
        'estado' => 'pendiente'
    ]);

    $this->mostrarModal = false;
    session()->flash('mensaje', '✅ Solicitud enviada correctamente.');
}


  public function render()
{
    return view('livewire.ranking-list', [
        'clanActual' => $this->clanActual,
        'ranking' => $this->ranking,
        'tipo' => $this->tipo,
        'personajeActual' => $this->personajeActual,
    ]);
}

}
