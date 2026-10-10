<?php

namespace App\Livewire;

use App\Models\Caza;
use App\Models\JefeSemanal;
use App\Models\Personaje;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

// Sección Jefe de la semana: ver al jefe en su ciudad, viajar gratis a esa zona y pelearlo (3 intentos por semana).
// La pelea es la de la Ciudad (App\Livewire\Explorar), que lo reconoce con jefeActivo()
class Jefe extends Component
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

    // Viaje gratis e instantáneo a la ciudad del jefe
    public function viajar()
    {
        if (! ($personaje = $this->personaje()) || ! ($jefe = JefeSemanal::actual())) {
            return;
        }
        if ((int) $personaje->ciudad_id === (int) $jefe->ciudad_id) {
            return;
        }
        if ($motivo = $this->motivoBloqueo($personaje, false)) {
            $this->dispatch('error', ['message' => $motivo]);
            return;
        }
        if ($jefe->ciudad->nivel > $personaje->nivel) {
            $this->dispatch('error', ['message' => 'Necesitás nivel ' . $jefe->ciudad->nivel . ' para viajar a ' . $jefe->ciudad->nombre . '.']);
            return;
        }
        $personaje->forceFill(['ciudad_id' => $jefe->ciudad_id, 'viajando_hasta' => null, 'viajando_a_id' => null])->save();
        $this->dispatch('success', ['message' => '¡Llegaste a ' . $jefe->ciudad->nombre . '! El jefe te espera.']);
        $this->dispatch('statsActualizados');
    }

    // Pelear: gasta un intento y abre la pelea en la Ciudad
    public function pelear()
    {
        if (! ($personaje = $this->personaje()) || ! ($jefe = JefeSemanal::actual())) {
            return;
        }
        if ((int) $personaje->ciudad_id !== (int) $jefe->ciudad_id) {
            $this->dispatch('error', ['message' => 'Tenés que estar en ' . $jefe->ciudad->nombre . ' para pelear contra el jefe.']);
            return;
        }
        if ($motivo = $this->motivoBloqueo($personaje, true)) {
            $this->dispatch('error', ['message' => $motivo]);
            return;
        }

        $error = DB::transaction(function () use ($personaje, $jefe) {
            $intento = $jefe->intentoDe($personaje->id);
            $intento = \App\Models\JefeIntento::whereKey($intento->id)->lockForUpdate()->first();
            if ($intento->derrotado) {
                return 'Ya venciste al jefe de esta semana. El lunes aparece otro.';
            }
            if ($intento->restantes() < 1) {
                return 'No te quedan intentos contra este jefe. El lunes aparece otro.';
            }
            $intento->intentos_usados++;
            $intento->en_pelea = true;
            $intento->save();

            $personaje->enemigo_actual_id = $jefe->post_id;
            $personaje->save();
            return null;
        });
        if ($error) {
            $this->dispatch('error', ['message' => $error]);
            return;
        }

        session(['enemigo' => ['id' => $jefe->post_id], 'combate_activo' => true]);
        $this->dispatch('cambiarSeccion', nuevaSeccion: 'inicio');
    }

    // Mismas restricciones que Torre, Misiones y Mazmorra
    private function motivoBloqueo(Personaje $personaje, bool $paraPelear): ?string
    {
        if ($personaje->estaEntrenando()) {
            return Personaje::MENSAJE_ENTRENANDO;
        }
        if ($estado = $personaje->estadoQueBloquea($paraPelear ? 'pelear' : 'viajar')) {
            return "No podés hacerlo porque estás $estado.";
        }
        if ($personaje->viajando_hasta && now()->lt($personaje->viajando_hasta)) {
            return 'Estás viajando. Esperá a llegar.';
        }
        if ($personaje->fin_exploracion && now()->lt($personaje->fin_exploracion)) {
            return $personaje->exploracion_duracion > 0
                ? 'Estás explorando. Terminá la exploración primero.'
                : 'Te estás recuperando. Esperá un momento.';
        }
        if ($personaje->enemigo_actual_id || $personaje->enemigo_actual_personaje_id || $personaje->mision_activa_id || $personaje->torre_piso_activo
            || (session('combate_activo') === true && session()->has('enemigo'))) {
            return 'Terminá tu combate actual primero.';
        }
        if (Caza::activaDe($personaje->id)) {
            return 'Tenés una caza en curso. Terminala primero.';
        }
        return null;
    }

    public function render()
    {
        $personaje = Personaje::with('ciudadActual')->find($this->personajeId);
        $jefe = JefeSemanal::actual();
        $jefe?->load(['ciudad', 'post.poderes']);
        $intento = $jefe ? $jefe->intentoDe($personaje->id) : null;

        return view('livewire.jefe', [
            'personaje'   => $personaje,
            'jefe'        => $jefe,
            'intento'     => $intento,
            'enSuCiudad'  => $jefe && (int) $personaje->ciudad_id === (int) $jefe->ciudad_id,
            'statsJefe'   => $jefe ? \App\Support\PoderesStats::aplicar($jefe->statsPara((int) $personaje->nivel), $jefe->post->poderes) : [],
            'proximo'     => JefeSemanal::proximoCambio(),
        ]);
    }
}
