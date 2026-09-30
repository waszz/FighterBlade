<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Personaje;
use App\Models\SolicitudClan;
use Illuminate\Support\Facades\DB;
use App\Models\Clan; // Importar modelo Clan

class Ranking extends Component
{
    public $pestana = 'nivel'; // clave de PESTANAS
    public $mostrarModal = false;
    public $clanSeleccionado;
    public $personajeSeleccionadoModal = null;

    public function setPestana($pestana)
    {
        $this->pestana = $pestana;
    }

    public function abrirModalIngreso($clanId)
{
    $this->clanSeleccionado = Clan::find($clanId);
    $this->mostrarModal = true;
}

public function mostrarModalPersonaje($id)
{
    $this->personajeSeleccionadoModal = Personaje::with(['user', 'equipo', 'entrenamiento', 'accesorio'])
        ->find($id);

    // Stats con los que pelea: base + partes + anillo + poción de stat + poderes (lo mismo que el panel y la pelea)
    $this->personajeSeleccionadoModalStats = $this->personajeSeleccionadoModal->statsDeCombate();

    // Mostrar el modal
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

// Pestañas del ranking: clave => [título, tipo que entiende ranking-list]
const PESTANAS = [
    'nivel'      => ['Nivel', 'Nivel'],
    'pvp'        => ['PvP', 'PvP'],
    'pve'        => ['Exploración', 'PvE'],
    'misiones'   => ['Misiones', 'Misiones'],
    'torre'      => ['Torre', 'Torre'],
    'clanes'     => ['Clanes', 'Clanes'],
    'campeones'  => ['👑 Campeones', 'Campeones'],
];

public function render()
{
    $personajeActual = auth()->user()->personaje ?? null;
    $clanActual = $personajeActual ? $personajeActual->clan : null;

    if (! isset(self::PESTANAS[$this->pestana])) {
        $this->pestana = 'nivel';
    }

    // Solo se consulta la pestaña abierta (top 50; campeones, los últimos 20)
    $ranking = match ($this->pestana) {
        'pvp' => Personaje::select('personajes.*',
                DB::raw('COALESCE(pvp_ganadas,0) as ganadas'),
                DB::raw('(COALESCE(pvp_ganadas,0) - COALESCE(pvp_perdidas,0)) as puntaje'))
            ->sinAdmins()->orderByDesc('puntaje')->orderByDesc('ganadas')->take(50)->get(),

        // PvE: solo exploraciones (misiones y torre tienen su pestaña)
        'pve' => Personaje::select('personajes.*',
                DB::raw('COALESCE(pve_ganadas,0) as ganadas'),
                DB::raw('(COALESCE(pve_ganadas,0) - COALESCE(pve_perdidas,0)) as puntaje'))
            ->sinAdmins()->orderByDesc('puntaje')->orderByDesc('ganadas')->take(50)->get(),

        // Misiones completadas (solo los que completaron al menos una)
        'misiones' => Personaje::joinSub(
                DB::table('mision_personaje')->select('personaje_id', DB::raw('COUNT(*) as misiones'), DB::raw('MAX(completada_en) as ultima'))->groupBy('personaje_id'),
                'mp', 'mp.personaje_id', '=', 'personajes.id')
            ->select('personajes.*')
            ->sinAdmins()->orderByDesc('mp.misiones')->orderBy('mp.ultima')->take(50)->get(),

        // Piso más alto superado de la Torre
        'torre' => Personaje::sinAdmins()->where('torre_piso', '>', 0)
            ->orderByDesc('torre_piso')->orderByDesc('nivel')->take(50)->get(),

        'clanes' => Clan::orderBy('prestigio', 'desc')->take(50)->get(),

        // Últimos campeones: los últimos en llegar al nivel 100 (tabla campeones)
        'campeones' => Personaje::join('campeones', 'campeones.personaje_id', '=', 'personajes.id')
            ->select('personajes.*', 'campeones.alcanzado_en as campeon_desde')
            ->sinAdmins()->orderByDesc('campeones.alcanzado_en')->take(20)->get(),

        default => Personaje::sinAdmins()->orderBy('nivel', 'desc')->orderBy('experiencia', 'desc')->take(50)->get(),
    };

    return view('livewire.ranking', [
        'ranking'         => $ranking,
        'pestanas'        => self::PESTANAS,
        'tipo'            => self::PESTANAS[$this->pestana][1],
        'personajeActual' => $personajeActual,
        'clanActual'      => $clanActual,
    ]);
}

}
