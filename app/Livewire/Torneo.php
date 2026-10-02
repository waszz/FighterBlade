<?php

namespace App\Livewire;

use App\Models\Personaje;
use App\Models\Torneo as TorneoModelo;
use App\Models\TorneoPelea;
use Livewire\Component;

// Sección Torneo: inscripción (te toca un set al azar), las rondas que se van jugando solas y el ganador.
// Ver App\Models\Torneo
class Torneo extends Component
{
    public $personajeId;
    // Pelea del torneo que se está viendo (id de TorneoPelea)
    public $peleaAbiertaId = null;
    // La última pelea propia que se abrió sola (cada pelea nueva tuya se muestra una vez sola)
    public $ultimaPropiaAbierta = null;

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    private function personaje(): ?Personaje
    {
        $personaje = Personaje::find($this->personajeId);
        return $personaje && $personaje->user_id === auth()->id() ? $personaje : null;
    }

    public function inscribirme()
    {
        if (! $personaje = $this->personaje()) {
            return;
        }
        $torneo = TorneoModelo::actualizarHoy();
        if (! $torneo) {
            $this->dispatch('error', ['message' => 'Hoy no hay torneo, o todavía no abrió la inscripción.']);
            return;
        }
        $resultado = $torneo->inscribir($personaje);
        if (is_string($resultado)) {
            $this->dispatch('error', ['message' => $resultado]);
            return;
        }
        $this->dispatch('success', ['message' => '¡Te anotaste! Te tocó ' . $resultado->post->titulo . ' (Nv ' . $resultado->post->nivel . ').']);
    }

    public function verPelea($id)
    {
        $this->peleaAbiertaId = $this->peleaAbiertaId == $id ? null : (int) $id;
    }

    public function render()
    {
        $torneo = TorneoModelo::actualizarHoy();
        // Si hoy no hay (o todavía no empezó), se muestra el último que se jugó
        $ultimo = $torneo ?? TorneoModelo::whereIn('estado', ['terminado', 'cancelado'])->latest('fecha')->first();
        $mostrar = $torneo ?? $ultimo;

        $participantes = $mostrar
            ? $mostrar->participantes()->with(['personaje', 'post'])->get()
                ->sortBy([['vidas', 'desc'], ['victorias', 'desc'], ['eliminado_en_ronda', 'desc']])->values()
            : collect();
        $yo = $participantes->firstWhere('personaje_id', $this->personajeId);
        // Cuando se juega una pelea tuya, se abre sola (una vez)
        if ($yo) {
            $miUltima = TorneoPelea::where('torneo_id', $mostrar->id)->whereNotNull('b_id')
                ->where(fn ($q) => $q->where('a_id', $yo->id)->orWhere('b_id', $yo->id))->max('id');
            if ($miUltima && $miUltima !== $this->ultimaPropiaAbierta) {
                $this->ultimaPropiaAbierta = $miUltima;
                $this->peleaAbiertaId = $miUltima;
            }
        }
        $abierta = $this->peleaAbiertaId ? TorneoPelea::with(['a.personaje', 'b.personaje'])->find($this->peleaAbiertaId) : null;

        $rondas = $mostrar
            ? TorneoPelea::where('torneo_id', $mostrar->id)->with(['a.personaje', 'a.post', 'b.personaje', 'b.post'])
                ->orderByDesc('ronda')->orderBy('id')->get()->groupBy('ronda')
            : collect();

        return view('livewire.torneo', [
            'torneo'        => $torneo,
            'mostrar'       => $mostrar,
            'participantes' => $participantes,
            'yo'            => $yo,
            'rondas'        => $rondas,
            'abierta'       => $abierta,
            'proximo'       => TorneoModelo::proximoInicio(),
            'statsYo'       => $yo ? TorneoModelo::luchador($yo, $yo->post->poderes ?? collect())['stats'] : null,
        ]);
    }
}
