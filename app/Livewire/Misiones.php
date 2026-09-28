<?php

namespace App\Livewire;

use App\Models\Caza;
use App\Models\Mision;
use App\Models\Personaje;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Misiones extends Component
{
    public $personajeId;

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    // Pelear contra el rival de la siguiente misión de la escalera
    public function pelear($misionId)
    {
        $personaje = Personaje::with('estadosTemporales')->find($this->personajeId);
        if (! $personaje || $personaje->user_id !== auth()->id()) {
            return;
        }

        $mision = Mision::find($misionId);
        $siguiente = Mision::siguientePara($personaje->id);
        if (! $mision || ! $siguiente || $mision->id !== $siguiente->id) {
            $this->dispatch('error', ['message' => 'Primero tenés que ganar las misiones anteriores.']);
            return;
        }

        if ($motivo = $this->motivoBloqueo($personaje)) {
            $this->dispatch('error', ['message' => $motivo]);
            return;
        }

        $personaje->mision_activa_id  = $mision->id;
        $personaje->enemigo_actual_id = $mision->post_id;
        $personaje->save();

        session([
            'enemigo'        => ['id' => $mision->post_id],
            'combate_activo' => true,
        ]);

        $this->dispatch('cambiarSeccion', nuevaSeccion: 'inicio');
    }

    // Mismas restricciones que para explorar o cazar
    private function motivoBloqueo(Personaje $personaje): ?string
    {
        if ($personaje->estaEntrenando()) {
            return Personaje::MENSAJE_ENTRENANDO;
        }
        $estados = $personaje->estadosTemporales->filter(fn ($e) => $e->estaActivo())->pluck('estado');
        foreach (['Aturdido', 'Congelado', 'Paralizado'] as $estado) {
            if ($estados->contains($estado)) {
                return "No podés pelear porque estás $estado.";
            }
        }
        if ($personaje->viajando_hasta && now()->lt($personaje->viajando_hasta)) {
            return 'Estás viajando. Peleá cuando llegues.';
        }
        if ($personaje->fin_exploracion && now()->lt($personaje->fin_exploracion)) {
            return $personaje->exploracion_duracion > 0
                ? 'Estás explorando. Terminá la exploración antes de pelear.'
                : 'Te estás recuperando. Esperá para pelear.';
        }
        // En combate: enemigo guardado, o marca de combate con un enemigo cargado en la sesión
        if ($personaje->enemigo_actual_id || (session('combate_activo') === true && session()->has('enemigo'))) {
            return 'Terminá tu combate actual antes de pelear.';
        }
        if (Caza::activaDe($personaje->id)) {
            return 'Tenés una caza en curso. Terminala antes de pelear.';
        }

        return null;
    }

    public function render()
    {
        $completadas = DB::table('mision_personaje')->where('personaje_id', $this->personajeId)->pluck('mision_id')->all();
        $siguiente = Mision::siguientePara($this->personajeId);

        return view('livewire.misiones', [
            'recuperacion' => Personaje::find($this->personajeId)?->segundosRecuperacion() ?? 0,
            'misiones'    => Mision::with('rival.poderes')->orderBy('orden')->get(),
            'completadas' => array_flip($completadas),
            'siguiente'   => $siguiente,
        ]);
    }
}
