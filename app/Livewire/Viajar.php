<?php
namespace App\Livewire;

use App\Models\Ciudad;
use Livewire\Component;
use App\Models\Personaje;

class Viajar extends Component
{
    const COSTO_TELEPORT = 25; // esmeraldas para llegar al instante
    public $personaje;
    public $ciudadesDisponibles = [];
    public $costosViaje = [];
    public $viajando = false;
    public $ciudadDestino = null;

    public function mount($personaje)
    {
        $this->personaje = Personaje::find($personaje->id);
        $this->actualizarEstado();
    }

    private function actualizarEstado()
    {
        // Si ya terminó el viaje, actualizar ciudad actual
        if ($this->personaje->viajando_hasta && now()->greaterThanOrEqualTo($this->personaje->viajando_hasta)) {
            $this->personaje->ciudad_id = $this->personaje->viajando_a_id;
            $this->personaje->viajando_hasta = null;
            $this->personaje->viajando_a_id = null;
            $this->personaje->save();
            $this->personaje->refresh();
        }

        // Estado si está viajando
        $this->viajando = $this->personaje->viajando_hasta && now()->lessThan($this->personaje->viajando_hasta);

        // Ciudad destino si está viajando
        $this->ciudadDestino = $this->personaje->viajando_a_id ? Ciudad::find($this->personaje->viajando_a_id) : null;

        // Todas las ciudades menos la actual y la destino; las de nivel mayor al del personaje se muestran bloqueadas
        $excluirIds = [$this->personaje->ciudad_id];
        if ($this->personaje->viajando_a_id) {
            $excluirIds[] = $this->personaje->viajando_a_id;
        }

        $this->ciudadesDisponibles = Ciudad::whereNotIn('id', $excluirIds)
            ->orderBy('nivel')
            ->get();

        // Calcular costos (ejemplo simple)
        $this->costosViaje = [];
        foreach ($this->ciudadesDisponibles as $ciudad) {
            $this->costosViaje[$ciudad->id] = $this->personaje->nivel * 50;
        }
    }

   private function noPuedeViajar(): bool
{
    // Explorando
    $explorando = $this->personaje->fin_exploracion && now()->lt($this->personaje->fin_exploracion);

    // Combate activo
    $combateActivo = session('combate_activo', false);

    //  Ver si tiene estado "Congelado" activo
    $congelado = $this->personaje->estadosTemporales
        ->where('estado', 'Congelado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();


       //  Estado Aturdido activo
    $aturdido = $this->personaje->estadosTemporales
        ->where('estado', 'Aturdido')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

         //Estado envenenado
    $envenenado = $this->personaje->estadosTemporales
        ->where('estado', 'Envenenado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

         //Estado paralizado
    $paralizado = $this->personaje->estadosTemporales
        ->where('estado', 'Paralizado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

    return $explorando || $combateActivo || $congelado || $aturdido || $envenenado || $paralizado;
}

    public function viajarA($ciudadId)
    {
        $this->actualizarEstado();

            // Verificación específica para congelado
    $congelado = $this->personaje->estadosTemporales
        ->where('estado', 'Congelado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

    if ($congelado) {
        session()->flash('error', 'No puedes viajar mientras estás Congelado..');
        return;
    }

    $aturdido = $this->personaje->estadosTemporales
    ->where('estado', 'Aturdido')
    ->filter(fn($estado) => $estado->estaActivo())
    ->isNotEmpty();

    if($aturdido) {
        session()->flash('error', 'No puedes viajar mientras estás Aturdido...');
        return;
    }

     $envenenado = $this->personaje->estadosTemporales
        ->where('estado', 'Envenenado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

         if($envenenado) {
        session()->flash('error', 'No puedes viajar mientras estás Envenenado...');
        return;
    }

    
     $paralizado = $this->personaje->estadosTemporales
        ->where('estado', 'Paralizado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

         if($paralizado) {
        session()->flash('error', 'No puedes viajar mientras estás Paralizado...');
        return;
    }

        if ($this->noPuedeViajar()) {
            session()->flash('error', 'No puedes viajar mientras estás explorando o en combate.');
            return;
        }

        if ($this->viajando && !auth()->user()->is_admin) {
            session()->flash('error', 'Ya estás viajando. Debes esperar a que termine el viaje.');
            return;
        }

        $ciudad = Ciudad::find($ciudadId);
        if (!$ciudad) {
            session()->flash('error', 'Ciudad no encontrada.');
            return;
        }

        if ($ciudad->nivel > $this->personaje->nivel) {
            session()->flash('error', 'No puedes viajar a esta ciudad. Nivel insuficiente.');
            return;
        }

        $costo = $this->personaje->nivel * 50;
        if ($this->personaje->oro < $costo) {
            session()->flash('error', 'No tienes suficiente oro para viajar.');
            return;
        }

        
    // Buscar % de reducción de tiempo de viaje por poderes
    $reduccion = 0;
    if ($this->personaje->post && $this->personaje->post->poderes) {
        foreach ($this->personaje->post->poderes as $poder) {
            if (isset($poder['modificadores']) && is_array($poder['modificadores'])) {
                foreach ($poder['modificadores'] as $modificador) {
                    if ($modificador['tipo'] === 'reduccion_tiempo_viaje' && isset($modificador['porcentaje'])) {
                        $reduccion += $modificador['porcentaje'];
                    }
                }
            }
        }
    }

    // Limitar reducción máxima a 100%
    $reduccion = min($reduccion, 100);

    // Calcular tiempo restante considerando la reducción (1 hora = 3600 segundos)
    $segundosViaje = 3600 * (1 - $reduccion / 100);

    // Si la reducción es 100% o más, viaje instantáneo
    if ($segundosViaje <= 0) {
        $this->personaje->viajando_hasta = now();
    } else {
        $this->personaje->viajando_hasta = now()->addSeconds($segundosViaje);
    }


        // Descontar oro y establecer viaje
        $this->personaje->oro -= $costo;
        $this->personaje->viajando_a_id = $ciudad->id;
        $this->personaje->save();

        session()->flash('message', '¡Has comenzado tu viaje a ' . $ciudad->nombre . '!');
        $this->dispatch('viajeExitoso');

        $this->actualizarEstado();
    }

    public function teleportarA($ciudadId)
    {
        $this->actualizarEstado();

            // Verificación específica para congelado
    $congelado = $this->personaje->estadosTemporales
        ->where('estado', 'Congelado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

    if ($congelado) {
        session()->flash('error', 'No puedes teletransportarte mientras estás congelado..');
        return;
    }

    $aturdido = $this->personaje->estadosTemporales
    ->where('estado', 'Aturdido')
    ->filter(fn($estado) => $estado->estaActivo())
    ->isNotEmpty();

    if($aturdido) {
        session()->flash('error', 'No puedes teletransportarte mientras estás Aturdido...');
        return;
    }

     $envenenado = $this->personaje->estadosTemporales
        ->where('estado', 'Envenenado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

         if($envenenado) {
        session()->flash('error', 'No puedes teletransportarte mientras estás Envenenado...');
        return;
    }

       $paralizado = $this->personaje->estadosTemporales
        ->where('estado', 'Paralizado')
        ->filter(fn($estado) => $estado->estaActivo())
        ->isNotEmpty();

         if($paralizado) {
        session()->flash('error', 'No puedes teletransportarte mientras estás Paralizado...');
        return;
    }

        if ($this->noPuedeViajar()) {
            session()->flash('error', 'No puedes teletransportarte mientras estás explorando o en combate.');
            return;
        }

        // Si viaje venció pero no se limpió, limpiarlo acá también antes de validar
        if ($this->personaje->viajando_hasta && now()->greaterThanOrEqualTo($this->personaje->viajando_hasta)) {
            $this->personaje->ciudad_id = $this->personaje->viajando_a_id;
            $this->personaje->viajando_hasta = null;
            $this->personaje->viajando_a_id = null;
            $this->personaje->save();
            $this->personaje->refresh();
            $this->actualizarEstado();
        }

        $ciudad = Ciudad::find($ciudadId);
        if (!$ciudad) {
            session()->flash('error', 'Ciudad no encontrada.');
            return;
        }

        if ($ciudad->nivel > $this->personaje->nivel) {
            session()->flash('error', 'No puedes viajar a esta ciudad. Nivel insuficiente.');
            return;
        }

        if ($this->personaje->diamante < self::COSTO_TELEPORT) {
            session()->flash('error', 'No tienes suficientes esmeraldas para teleportarte.');
            return;
        }

        // Descontar diamantes y actualizar ciudad instantáneamente
        $this->personaje->diamante -= self::COSTO_TELEPORT;
        $this->personaje->viajando_a_id = null;
        $this->personaje->viajando_hasta = null;
        $this->personaje->ciudad_id = $ciudad->id;
        $this->personaje->save();
        $this->personaje->refresh();

        session()->flash('message', '¡Te has teletransportado instantáneamente a ' . $ciudad->nombre . '!');
        $this->dispatch('viajeExitoso');

        $this->actualizarEstado();

        // Recargar el juego: vuelve a la Ciudad mostrando la ciudad nueva (fondo, panel y ranking)
        $this->dispatch('recargar-pagina');
    }

    public function cancelarViaje()
    {
        $this->personaje->viajando_hasta = null;
        $this->personaje->viajando_a_id = null;
        $this->personaje->save();

        session()->flash('message', 'Viaje cancelado correctamente.');

        $this->actualizarEstado();
    }

    public function render()
    {
        return view('livewire.viajar', [
            'personaje' => $this->personaje,
            'ciudadesDisponibles' => $this->ciudadesDisponibles,
            'costosViaje' => $this->costosViaje,
            'viajando' => $this->viajando,
            'ciudadDestino' => $this->ciudadDestino,
        ]);
    }
}
