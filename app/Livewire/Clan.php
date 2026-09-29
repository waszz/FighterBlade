<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Personaje;
use App\Models\SolicitudClan;
use Livewire\WithFileUploads;
use App\Models\ClanInventario;
use App\Models\Clan as ClanModel;
use Illuminate\Support\Facades\Auth;

class Clan extends Component
{
    use WithFileUploads;

    public $modalFundarClan = false;
    public $imagenClan;
    public $prestigio = 5000;
    public $tag;
    public $nombre;
    public $modalRetirarObjeto = false;
    public $objetoSeleccionado;
    public $reloadClan = 0;
    public $esFundador = false;
    public $oroNecesario = 1000;
    public $nivelNecesario = 20;
    public $mensajeRequisitos = '';
    public $miembrosDelClan = [];
    public $miembroSeleccionadoId = null;
    
    
    public $modalSolicitudes = false;
    public $solicitudesPendientes = [];
    public $modalConfirmarSalir = false;
    public $modalConfirmarEliminarClan = false;

    public $clan; // Clan del usuario
    public $inventario; // Colección con inventario del clan
    

        protected $rules = [
        'imagenClan' => 'required|mimes:jpg,jpeg,png,gif|max:2048',
        'tag' => 'required|string|max:3|unique:clans,tag',
        'nombre' => 'nullable|string|max:50',
    ];


public function mount()
{
    $personaje = Personaje::where('user_id', Auth::id())->first();

    if ($personaje && $personaje->clan_id) {
        $this->clan = ClanModel::find($personaje->clan_id);
        $this->inventario = ClanInventario::where('clan_id', $this->clan->id)
            ->with('objeto')
            ->limit(100)
            ->get();

        // Verificar si es fundador
        $this->esFundador = $this->clan->fundador_id === Auth::id();
    } else {
        $this->clan = null;
        $this->inventario = collect();
        $this->esFundador = false;
    }
}

   public function abrirModalFundarClan()
{
    $this->reset(['imagenClan', 'prestigio', 'tag', 'nombre']);
    $this->modalFundarClan = true;

    $this->mensajeRequisitos = "Para fundar un clan necesitás nivel {$this->nivelNecesario} y {$this->oroNecesario} oro.";
}

public function getPuedeFundarProperty()
{
    $personaje = Personaje::where('user_id', Auth::id())->first();

    if (!$personaje) return false;

    return $personaje->nivel >= $this->nivelNecesario && $personaje->oro >= $this->oroNecesario;
}

public function abrirModalSolicitudes()
{
    if (!$this->clan) {
        session()->flash('mensaje', 'No tenés clan fundado.');
        return;
    }

    $this->solicitudesPendientes = SolicitudClan::where('clan_id', $this->clan->id)
        ->where('estado', 'pendiente')
        ->with('usuario.personaje')
        ->get();

    $this->miembrosDelClan = Personaje::where('clan_id', $this->clan->id)
        ->with('user.personaje')
        ->get();

    $this->modalSolicitudes = true;
}


public function expulsarMiembro($personajeId)
{
    // Buscar al personaje
    $personaje = Personaje::find($personajeId);

    // Verificar si el personaje existe y está en el clan correcto
    if (!$personaje || $personaje->clan_id !== $this->clan->id) {
        session()->flash('mensaje', 'No se pudo expulsar al miembro.');
        return;
    }

    // Evitar que el fundador se expulse a sí mismo
    if ($personaje->user_id === Auth::id()) {
        session()->flash('mensaje', 'No podés expulsarte a vos mismo.');
        return;
    }

    // Eliminar solicitudes pendientes del usuario expulsado (si las hay)
    SolicitudClan::where('usuario_id', $personaje->user_id)
        ->where('clan_id', $this->clan->id)
        ->where('estado', 'pendiente')
        ->delete();

    // Eliminar solicitud aceptada (si existiera)
    SolicitudClan::where('usuario_id', $personaje->user_id)
        ->where('clan_id', $this->clan->id)
        ->where('estado', 'aceptado')
        ->delete();

    // Actualizar clan_id a null para expulsar al miembro
    $personaje->clan_id = null;
    $personaje->save();

    // Si el personaje fue expulsado, actualizar la lista de miembros
    $this->abrirModalSolicitudes();  // O cualquier otro método que refresque la lista de miembros

    session()->flash('mensaje', 'Miembro expulsado del clan.');
}



public function aceptarSolicitud($solicitudId)
{
    $solicitud = SolicitudClan::find($solicitudId);
    if (!$solicitud || $solicitud->clan_id !== $this->clan->id) return;

    // Verificar que el clan no tenga más de 5 miembros
    $miembrosActuales = Personaje::where('clan_id', $this->clan->id)->count();
    if ($miembrosActuales >= 5) {
        session()->flash('mensaje', 'El clan ya tiene el máximo de 5 miembros.');
        return;
    }

    $solicitud->estado = 'aceptado';
    $solicitud->save();

    // Asociar personaje del usuario al clan
    $usuario = $solicitud->usuario;
    if ($usuario && $usuario->personaje) {
        $usuario->personaje->clan_id = $this->clan->id;
        $usuario->personaje->save();
    }

    $this->abrirModalSolicitudes();

    session()->flash('mensaje', 'Solicitud aceptada.');
}


public function rechazarSolicitud($solicitudId)
{
    $solicitud = SolicitudClan::find($solicitudId);
    if (!$solicitud || $solicitud->clan_id !== $this->clan->id) return;

    $solicitud->estado = 'rechazado';
    $solicitud->save();

    // Refrescar lista de solicitudes
    $this->abrirModalSolicitudes();

    session()->flash('mensaje', 'Solicitud rechazada.');
}


 public function crearClan()
{
    $this->validate();

    $personaje = Personaje::where('user_id', Auth::id())->first();

    if (!$personaje) {
        session()->flash('mensaje', 'No tenés un personaje creado.');
        return;
    }

    if ($personaje->nivel < $this->nivelNecesario) {
        session()->flash('mensaje', "Necesitás nivel {$this->nivelNecesario} para fundar un clan.");
        return;
    }

    if ($personaje->oro < $this->oroNecesario) {
        session()->flash('mensaje', "Necesitás al menos {$this->oroNecesario} oro para fundar un clan.");
        return;
    }

    // Restar el oro
    $personaje->oro -= $this->oroNecesario;
    $personaje->save();

    $rutaImagen = $this->imagenClan->store('clans', 'public');

    $this->clan = ClanModel::create([
        'nombre' => $this->nombre,
        'tag' => strtoupper($this->tag),
        'imagen' => $rutaImagen,
        'prestigio' => 5000,
        'fundador_id' => Auth::id(),
    ]);

    $personaje->clan_id = $this->clan->id;
    $personaje->save();

    $this->inventario = collect(); // Inventario vacío al crear clan
    $this->modalFundarClan = false;

    session()->flash('mensaje', 'Clan fundado con éxito.');
}

public function salirDelClan()
{
    $personaje = Personaje::where('user_id', Auth::id())->first();

    if (!$personaje || !$personaje->clan_id) {
        session()->flash('mensaje', 'No estás en ningún clan.');
        return;
    }

    if ($this->clan && $this->clan->fundador_id == Auth::id()) {
        session()->flash('mensaje', 'No podés salir siendo fundador del clan.');
        return;
    }

    $clanId = $personaje->clan_id;

    // Eliminar solicitud aceptada
    SolicitudClan::where('usuario_id', Auth::id())
        ->where('clan_id', $clanId)
        ->where('estado', 'aceptado')
        ->delete();

    // Salir del clan
    $personaje->clan_id = null;
    $personaje->save();

    // Verificar si quedan miembros
    $miembrosRestantes = Personaje::where('clan_id', $clanId)->count();

    if ($miembrosRestantes === 0) {
        ClanModel::find($clanId)?->delete();
    }

    session()->flash('mensaje', 'Saliste del clan correctamente.');

    $this->clan = null;
    $this->inventario = collect();
}



public function abrirModalConfirmarSalir()
{
    $this->modalConfirmarSalir = true;
}

public function confirmarSalirDelClan()
{
    $personaje = Personaje::where('user_id', Auth::id())->first();

    SolicitudClan::where('usuario_id', Auth::id())
    ->where('clan_id', $personaje->clan_id)
    ->where('estado', 'aceptado')
    ->delete();

    if (!$personaje || !$personaje->clan_id) {
        session()->flash('mensaje', 'No estás en ningún clan.');
        $this->modalConfirmarSalir = false;
        return;
    }

    if ($this->clan && $this->clan->fundador_id == Auth::id()) {
        session()->flash('mensaje', 'No podés salir siendo fundador del clan.');
        $this->modalConfirmarSalir = false;
        return;
    }

    $personaje->clan_id = null;
    $personaje->save();

    session()->flash('mensaje', 'Saliste del clan correctamente.');

    $this->clan = null;
    $this->inventario = collect();
    $this->modalConfirmarSalir = false;
}


public function abrirModalRetirarObjeto($clanInventarioId)
{
    $this->objetoSeleccionado = ClanInventario::with('objeto')->find($clanInventarioId);

    $this->miembrosDelClan = Personaje::where('clan_id', $this->clan->id)
        ->where('user_id', '!=', Auth::id()) // No incluir al fundador mismo
        ->get();

    if ($this->objetoSeleccionado) {
        $this->modalRetirarObjeto = true;
    }
}




public function abrirModalConfirmarEliminarClan()
{
    $this->modalConfirmarEliminarClan = true;
}

public function eliminarClan()
{
    if (!$this->clan || $this->clan->fundador_id !== Auth::id()) {
        session()->flash('mensaje', 'No tenés permisos para eliminar el clan.');
        $this->modalConfirmarEliminarClan = false;
        return;
    }

    // Opcional: eliminar objetos, solicitudes, etc. asociados al clan antes
    // Por simplicidad, solo eliminamos el clan aquí

    $this->clan->delete();

    // Remover clan de personajes fundadores y demás (puede hacerse con eventos o aquí manualmente)
    // Por ejemplo:
    $personaje = Personaje::where('user_id', Auth::id())->first();
    if ($personaje) {
        $personaje->clan_id = null;
        $personaje->save();
    }

    $this->clan = null;
    $this->inventario = collect();

    session()->flash('mensaje', 'Clan eliminado con éxito.');
    $this->modalConfirmarEliminarClan = false;
}

public function cerrarModalRetirarObjeto()
{
    $this->modalRetirarObjeto = false;
    $this->objetoSeleccionado = null;
}
public function retirarObjetoDelClan()
{
    if (!$this->objetoSeleccionado) {
        return;
    }

    $personaje = Personaje::where('user_id', Auth::id())->first();

    if (!$personaje) {
        session()->flash('mensaje', 'No tienes personaje para retirar el objeto.');
        return;
    }

    if (! $personaje->tieneLugar()) {
        session()->flash('mensaje', '🎒 ' . Personaje::MENSAJE_INVENTARIO_LLENO);
        return;
    }

    $objeto = $this->objetoSeleccionado->objeto;
    $objeto->personaje_id = $personaje->id;
    $objeto->save();

    $this->objetoSeleccionado->delete();

    $this->inventario = ClanInventario::where('clan_id', $this->clan->id)
        ->with('objeto')
        ->limit(100)
        ->get();

    $this->cerrarModalRetirarObjeto();

    session()->flash('mensaje', 'Objeto retirado y devuelto al inventario del personaje.');
    $this->reloadClan++;
}
public function eliminarObjetoDelClan()
{
    if (!$this->objetoSeleccionado) {
        return;
    }

    // Primero eliminas el objeto (puede ser soft delete si usás)
    $objeto = $this->objetoSeleccionado->objeto;
    
    // Eliminamos el objeto
    $objeto->delete();

    // Luego eliminas el registro del inventario del clan
    $this->objetoSeleccionado->delete();

    // Refrescar inventario clan
    $this->inventario = ClanInventario::where('clan_id', $this->clan->id)
        ->with('objeto')
        ->limit(100)
        ->get();

    $this->cerrarModalRetirarObjeto();

    session()->flash('mensaje', 'Objeto eliminado del clan.');
     $this->reloadClan++;
}



public function enviarObjetoAUsuario()
{
    // dd([
    //     'objetoSeleccionado' => $this->objetoSeleccionado,
    //     'miembroSeleccionadoId' => $this->miembroSeleccionadoId,
    // ]);
    if (!$this->objetoSeleccionado || !$this->miembroSeleccionadoId) {
        session()->flash('mensaje', 'Debe seleccionar un miembro válido.');
        return;
    }

    $personajeDestino = Personaje::find($this->miembroSeleccionadoId);

    // Verificar que el personaje exista y esté en el mismo clan
    if (!$personajeDestino || $personajeDestino->clan_id !== $this->clan->id) {
        session()->flash('mensaje', 'El personaje seleccionado no es válido.');
        return;
    }

    $objeto = $this->objetoSeleccionado->objeto;

    if (!$objeto) {
        session()->flash('mensaje', 'Objeto no encontrado.');
        return;
    }

    if (! $personajeDestino->tieneLugar()) {
        session()->flash('mensaje', "🎒 {$personajeDestino->nombre} tiene el inventario lleno.");
        return;
    }

    // Asignar el objeto al personaje destino
    $objeto->personaje_id = $personajeDestino->id;
    $objeto->save();

    // Eliminar el registro de inventario del clan
    $this->objetoSeleccionado->delete();

    // Actualizar inventario del clan
    $this->inventario = ClanInventario::where('clan_id', $this->clan->id)
        ->with('objeto')
        ->limit(100)
        ->get();

    // Limpiar y cerrar modal
    $this->cerrarModalRetirarObjeto();
    $this->miembroSeleccionadoId = null;

    session()->flash('mensaje', 'Objeto enviado correctamente a ' . $personajeDestino->nombre . '.');
    $this->reloadClan++;
}


public function render()
{
    $personaje = Personaje::where('user_id', Auth::id())->first();

    $puedeFundar = false;
    if ($personaje) {
        $puedeFundar = $personaje->nivel >= $this->nivelNecesario && $personaje->oro >= $this->oroNecesario;
    }

    // Sin clan (todavía no fundó ni se unió a uno) no hay miembros que buscar
    $miembros = $this->clan
        ? Personaje::where('clan_id', $this->clan->id)->orderByDesc('nivel')->get()
        : collect();
    $fundador = $this->clan ? $miembros->firstWhere('user_id', $this->clan->fundador_id) : null;
    $miembrosRestantes = $this->clan ? $miembros->filter(fn($m) => $m->user_id !== $this->clan->fundador_id) : collect();

    return view('livewire.clan', [
        'clan' => $this->clan,
        'inventario' => $this->inventario,
        'esFundador' => $this->esFundador,
        'nivelNecesario' => $this->nivelNecesario,
        'oroNecesario' => $this->oroNecesario,
        'puedeFundar' => $puedeFundar,
        'miembrosCantidad' => $miembros->count(),
        'fundador' => $fundador,
        'miembros' => $miembrosRestantes,
    ]);
}


}
