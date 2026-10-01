<?php

namespace App\Livewire;

use App\Models\Caza;
use App\Models\Mazmorra as MazmorraModelo;
use App\Models\Personaje;
use App\Models\Post;
use Livewire\Component;

// Mazmorra: elegir dificultad, pelear contra 4 enemigos y el jefe gastando energía (ver App\Models\Mazmorra)
class Mazmorra extends Component
{
    public $personajeId;
    // Paso (0..4) cuyo rival se ve en el modal
    public $rivalModalId = null;

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    private function personaje(): ?Personaje
    {
        $personaje = Personaje::with('estadosTemporales')->find($this->personajeId);
        return $personaje && $personaje->user_id === auth()->id() ? $personaje : null;
    }

    public function elegir($dificultad)
    {
        if (! $personaje = $this->personaje()) {
            return;
        }
        if ($personaje->nivel < MazmorraModelo::NIVEL_MINIMO) {
            $this->dispatch('error', ['message' => 'Necesitás nivel ' . MazmorraModelo::NIVEL_MINIMO . ' para entrar a la Mazmorra.']);
            return;
        }
        if (! isset(MazmorraModelo::DIFICULTADES[$dificultad])) {
            return;
        }
        $mazmorra = MazmorraModelo::de($personaje->id);
        if ($mazmorra->enCurso()) {
            $this->dispatch('error', ['message' => 'Ya estás en una mazmorra. Terminala o abandonala para elegir otra.']);
            return;
        }
        $mazmorra->empezar($personaje, $dificultad);
    }

    // Pelear con el rival que toca: gasta la energía y abre la pelea
    public function pelear()
    {
        if (! $personaje = $this->personaje()) {
            return;
        }
        $mazmorra = MazmorraModelo::de($personaje->id);
        $rival = $mazmorra->enCurso() ? $mazmorra->rivalActual() : null;
        if (! $rival) {
            return;
        }
        if ($mazmorra->energia < MazmorraModelo::ENERGIA_POR_PELEA) {
            $this->dispatch('error', ['message' => '⚡ No te alcanza la energía. Se recarga mañana, o comprá +' . MazmorraModelo::ENERGIA_COMPRA . '.']);
            return;
        }
        if ($motivo = $this->motivoBloqueo($personaje)) {
            $this->dispatch('error', ['message' => $motivo]);
            return;
        }

        $mazmorra->energia -= MazmorraModelo::ENERGIA_POR_PELEA;
        $mazmorra->en_pelea = true;
        $mazmorra->save();

        $personaje->enemigo_actual_id = $rival['post_id'];
        $personaje->save();

        session([
            'enemigo'        => ['id' => $rival['post_id']],
            'combate_activo' => true,
        ]);

        $this->dispatch('cambiarSeccion', nuevaSeccion: 'inicio');
    }

    // +100 de energía por esmeraldas, una vez por día
    public function comprarEnergia()
    {
        if (! $personaje = $this->personaje()) {
            return;
        }
        $mazmorra = MazmorraModelo::de($personaje->id);
        if (! $mazmorra->puedeComprar()) {
            $this->dispatch('error', ['message' => 'Ya compraste energía hoy. Mañana podés volver a comprar.']);
            return;
        }
        // Descuento atómico: solo si le alcanzan las esmeraldas
        $pagado = Personaje::whereKey($personaje->id)->where('diamante', '>=', MazmorraModelo::PRECIO_COMPRA)
            ->decrement('diamante', MazmorraModelo::PRECIO_COMPRA);
        if (! $pagado) {
            $this->dispatch('error', ['message' => 'Necesitás ' . number_format(MazmorraModelo::PRECIO_COMPRA, 0, ',', '.') . ' esmeraldas.']);
            return;
        }
        $mazmorra->energia += MazmorraModelo::ENERGIA_COMPRA;
        $mazmorra->compra_dia = MazmorraModelo::hoy();
        $mazmorra->save();

        $this->dispatch('success', ['message' => '⚡ +' . MazmorraModelo::ENERGIA_COMPRA . ' de energía.']);
        $this->dispatch('statsActualizados');
    }

    // Deja la mazmorra en curso (para elegir otra dificultad); la energía gastada no vuelve
    public function abandonar()
    {
        if (! $personaje = $this->personaje()) {
            return;
        }
        $mazmorra = MazmorraModelo::de($personaje->id);
        if ($mazmorra->en_pelea) {
            $this->dispatch('error', ['message' => 'Terminá tu pelea antes de abandonar.']);
            return;
        }
        $mazmorra->fill(['dificultad' => null, 'rivales' => null, 'paso' => 0])->save();
        $this->rivalModalId = null;
    }

    // Modal del rival: los ya vencidos y el que toca (los que siguen quedan ocultos)
    public function verRival($paso)
    {
        $mazmorra = MazmorraModelo::de($this->personajeId);
        if ($mazmorra->enCurso() && (int) $paso >= 0 && (int) $paso <= $mazmorra->paso) {
            $this->rivalModalId = (int) $paso;
        }
    }

    // Mismas restricciones que Torre y Misiones
    private function motivoBloqueo(Personaje $personaje): ?string
    {
        if ($personaje->estaEntrenando()) {
            return Personaje::MENSAJE_ENTRENANDO;
        }
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
        $mazmorra = MazmorraModelo::de($this->personajeId);

        $rivales = collect();
        if ($mazmorra->enCurso()) {
            $posts = Post::conRivales()->with('poderes')->whereIn('id', collect($mazmorra->rivales)->pluck('post_id'))->get()->keyBy('id');
            $rivales = collect($mazmorra->rivales)->map(fn ($r, $i) => [
                'paso'      => $i,
                'post'      => $posts[$r['post_id']] ?? null,
                'escenario' => $r['escenario'] ?? null,
            ]);
        }

        $modal = $this->rivalModalId !== null ? $rivales->firstWhere('paso', $this->rivalModalId) : null;

        return view('livewire.mazmorra', [
            'personaje'       => $personaje,
            'mazmorra'        => $mazmorra,
            'rivales'         => $rivales,
            'recuperacion'    => $personaje?->segundosRecuperacion() ?? 0,
            'rivalModal'      => $modal['post'] ?? null,
            'escenarioModal'  => $modal['escenario'] ?? null,
            'statsRivalModal' => ($modal['post'] ?? null)
                ? Explorar::statsRivalMisionTorre($modal['post'], $mazmorra->factorStats($modal['paso']))
                : [],
        ]);
    }
}
