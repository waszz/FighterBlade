<?php

namespace App\Livewire;

use App\Models\Caza;
use App\Models\Personaje;
use App\Models\TorrePiso;
use Livewire\Component;

// Torre: se sube de a un piso peleando contra su rival (set normal o especial) en su zona
class Torre extends Component
{
    public $personajeId;
    // Piso cuyo rival se ve en el modal (tocando su card)
    public $rivalModalId = null;

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    // Modal del rival: solo de los pisos superados o del actual (los de más arriba siguen siendo "???")
    public function verRival($piso)
    {
        $personaje = Personaje::find($this->personajeId);
        if ($personaje && (int) $piso >= 1 && (int) $piso <= (int) ($personaje->torre_piso ?? 0) + 1) {
            $this->rivalModalId = (int) $piso;
        }
    }

    // Pelear contra el rival del piso que le toca
    public function subir()
    {
        $personaje = Personaje::with('estadosTemporales')->find($this->personajeId);
        if (! $personaje || $personaje->user_id !== auth()->id()) {
            return;
        }
        if ($personaje->nivel < TorrePiso::NIVEL_MINIMO) {
            $this->dispatch('error', ['message' => 'Necesitás nivel ' . TorrePiso::NIVEL_MINIMO . ' para entrar a la Torre.']);
            return;
        }

        $piso = TorrePiso::siguientePara($personaje);
        if (! $piso) {
            $this->dispatch('success', ['message' => '¡Ya llegaste a la cima de la Torre!']);
            return;
        }

        if ($motivo = $this->motivoBloqueo($personaje)) {
            $this->dispatch('error', ['message' => $motivo]);
            return;
        }

        $personaje->torre_piso_activo = $piso->piso;
        $personaje->enemigo_actual_id = $piso->post_id;
        $personaje->save();

        session([
            'enemigo'        => ['id' => $piso->post_id],
            'combate_activo' => true,
        ]);

        $this->dispatch('cambiarSeccion', nuevaSeccion: 'inicio');
    }

    // Mismas restricciones que Misiones y Caza
    private function motivoBloqueo(Personaje $personaje): ?string
    {
        if ($personaje->estaEntrenando()) {
            return Personaje::MENSAJE_ENTRENANDO;
        }
        // Aturdido o Paralizado no dejan pelear (Congelado sí: solo impide viajar). Ver Personaje::ESTADOS_QUE_BLOQUEAN
        if ($estado = $personaje->estadoQueBloquea('pelear')) {
            return "No podés pelear porque estás $estado.";
        }
        if ($personaje->viajando_hasta && now()->lt($personaje->viajando_hasta)) {
            return 'Estás viajando. Peleá cuando llegues.';
        }
        if ($personaje->fin_exploracion && now()->lt($personaje->fin_exploracion)) {
            return $personaje->exploracion_duracion > 0
                ? 'Estás explorando. Terminá la exploración antes de pelear.'
                : 'Te estás recuperando. Esperá para pelear.';
        }
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
        $personaje = Personaje::find($this->personajeId);
        $superado = (int) ($personaje->torre_piso ?? 0);
        $total = TorrePiso::count();
        $actual = min($superado + 1, $total);

        // Pisos que se ven: unos cuantos superados abajo, el actual y los próximos arriba (bloqueados)
        $desde = max(1, $actual - 3);
        $hasta = min($total, $actual + 6);
        $pisos = TorrePiso::with('rival.poderes')->whereBetween('piso', [$desde, $hasta])->orderByDesc('piso')->get();

        $pisoModal = $this->rivalModalId ? TorrePiso::with('rival.poderes')->where('piso', $this->rivalModalId)->first() : null;

        return view('livewire.torre', [
            'rivalModal'      => $pisoModal?->rival,
            'escenarioModal'  => $pisoModal?->escenario,
            'statsRivalModal' => $pisoModal?->rival ? Explorar::statsRivalMisionTorre($pisoModal->rival) : [],
            'personaje'    => $personaje,
            'pisos'        => $pisos,
            'superado'     => $superado,
            'actual'       => $actual,
            'total'        => $total,
            'terminada'    => $superado >= $total && $total > 0,
            'recuperacion' => $personaje?->segundosRecuperacion() ?? 0,
            'nivelMinimo'  => TorrePiso::NIVEL_MINIMO,
        ]);
    }
}
