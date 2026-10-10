<?php

namespace App\Livewire;

use App\Models\Clan;
use App\Models\GuerraClanes;
use App\Models\GuerraViajero;
use App\Models\Personaje;
use Livewire\Component;

// Sección Guerra de clanes: cuándo es, la zona, viajar gratis y la tabla de puntos (ver App\Models\GuerraClanes)
class Guerra extends Component
{
    public $personajeId;

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    private function personaje(): ?Personaje
    {
        $personaje = Personaje::find($this->personajeId);
        return $personaje && $personaje->user_id === auth()->id() ? $personaje : null;
    }

    public function viajar()
    {
        if (! ($personaje = $this->personaje()) || ! ($guerra = GuerraClanes::actualizar()) || ! $guerra->enCurso()) {
            $this->dispatch('error', ['message' => 'La guerra de clanes no está en curso.']);
            return;
        }
        if (! $personaje->clan_id) {
            $this->dispatch('error', ['message' => 'Necesitás estar en un clan para ir a la guerra.']);
            return;
        }
        if ((int) $personaje->ciudad_id === (int) $guerra->ciudad_id) {
            return;
        }
        if ($personaje->enemigo_actual_id || $personaje->enemigo_actual_personaje_id || $personaje->mision_activa_id || $personaje->torre_piso_activo
            || ($personaje->fin_exploracion && $personaje->exploracion_duracion > 0 && now()->lt($personaje->fin_exploracion))
            || ($personaje->viajando_hasta && now()->lt($personaje->viajando_hasta)) || $personaje->estaEntrenando()) {
            $this->dispatch('error', ['message' => 'Terminá lo que estás haciendo (pelea, exploración, viaje o entrenamiento) antes de ir a la guerra.']);
            return;
        }
        GuerraViajero::firstOrCreate(['guerra_clanes_id' => $guerra->id, 'personaje_id' => $personaje->id], ['ciudad_origen_id' => $personaje->ciudad_id]);
        $personaje->forceFill(['ciudad_id' => $guerra->ciudad_id, 'viajando_hasta' => null, 'viajando_a_id' => null])->save();
        $this->dispatch('success', ['message' => '¡Llegaste a ' . $guerra->ciudad->nombre . '! Atacá a los de otros clanes desde los usuarios de la zona.']);
        $this->dispatch('statsActualizados');
    }

    public function render()
    {
        $personaje = Personaje::with('clan')->find($this->personajeId);
        $guerra = GuerraClanes::actualizar();
        $ultima = $guerra ?? GuerraClanes::where('estado', 'terminada')->latest('fecha')->first();
        $mostrar = $guerra ?? $ultima;
        $tabla = collect($mostrar?->puntos ?? [])->sortDesc();
        $clanes = Clan::whereIn('id', $tabla->keys())->get()->keyBy('id');

        return view('livewire.guerra', [
            'personaje'  => $personaje,
            'guerra'     => $guerra && $guerra->enCurso() ? $guerra : null,
            'mostrar'    => $mostrar,
            'tabla'      => $tabla,
            'clanes'     => $clanes,
            'enLaZona'   => $guerra && (int) $personaje->ciudad_id === (int) $guerra->ciudad_id,
            'proximo'    => GuerraClanes::proximoInicio(),
        ]);
    }
}
