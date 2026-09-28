<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Personaje;
use App\Models\SolicitudClan;
use Illuminate\Support\Facades\DB;
use App\Models\Clan; // Importar modelo Clan

class Ranking extends Component
{
    public $pestana = 'nivel'; // nivel, pvp, pve, clanes
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

    // Obtener stats base (JSON decodificado)
    $statsBase = Personaje::decodificarStats($this->personajeSeleccionadoModal->stats);

    // Inicializar array stats totales con base
    $statsTotales = $statsBase;

    // Sumar stats de equipo, entrenamiento y accesorio
    foreach (['equipo', 'entrenamiento', 'accesorio', 'joya'] as $relacion) {
        $obj = $this->personajeSeleccionadoModal->$relacion;
        if ($obj) {
            $statsObj = is_array($obj->stats) ? $obj->stats : json_decode($obj->stats ?? '{}', true);
            foreach ($statsObj ?? [] as $stat => $valor) {
                if (!isset($statsTotales[$stat])) {
                    $statsTotales[$stat] = 0;
                }
                $statsTotales[$stat] += intval($valor);
            }
        }
    }

    // Guardar los stats totales en una propiedad para la vista
    $this->personajeSeleccionadoModalStats = $statsTotales;

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

public function render()
{
    $personajeActual = auth()->user()->personaje ?? null;
    $clanActual = $personajeActual ? $personajeActual->clan : null;

   // Ranking por nivel simple (top 50), ahora ordena por nivel y porcentaje
$rankingNivel = Personaje::sinAdmins()->orderBy('nivel', 'desc')
    ->orderBy('experiencia', 'desc')
    ->take(50)
    ->get();

    // Ranking PvP
    $rankingPvp = Personaje::select('personajes.*',
            DB::raw('COALESCE(pvp_ganadas,0) as ganadas'),
            DB::raw('COALESCE(pvp_perdidas,0) as perdidas'),
            DB::raw('(COALESCE(pvp_ganadas,0) - COALESCE(pvp_perdidas,0)) as puntaje')
        )
        ->sinAdmins()
        ->orderByDesc('puntaje')
        ->orderByDesc('ganadas')
        ->take(50)
        ->get();

    // Ranking PvE
    $rankingPve = Personaje::select('personajes.*',
            DB::raw('COALESCE(pve_ganadas,0) as ganadas'),
            DB::raw('COALESCE(pve_perdidas,0) as perdidas'),
            DB::raw('(COALESCE(pve_ganadas,0) - COALESCE(pve_perdidas,0)) as puntaje')
        )
        ->sinAdmins()
        ->orderByDesc('puntaje')
        ->orderByDesc('ganadas')
        ->take(50)
        ->get();

    // Ranking Clanes
    $rankingClanes = Clan::orderBy('prestigio', 'desc')->take(50)->get();

    // Últimos campeones: los últimos en llegar al nivel 100 (tabla campeones)
    $rankingCampeones = Personaje::join('campeones', 'campeones.personaje_id', '=', 'personajes.id')
        ->select('personajes.*', 'campeones.alcanzado_en as campeon_desde')
        ->sinAdmins()
        ->orderByDesc('campeones.alcanzado_en')
        ->take(20)
        ->get();

    return view('livewire.ranking', compact(
        'rankingNivel', 
        'rankingPvp', 
        'rankingPve', 
        'rankingClanes',
        'rankingCampeones',
        'personajeActual',
        'clanActual'
    ));
}


}
