<?php

namespace App\Livewire;

use App\Models\Caza as CazaModel;
use App\Models\ExploracionRapida;
use App\Models\Personaje;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Caza extends Component
{
    public $personajeId;

    // Presa y parte elegidas en el tablero
    public $presaId = null;
    public $parte = 'equipo';

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    public function elegirPresa($postId)
    {
        $this->presaId = (int) $postId;
    }

    public function rastrear()
    {
        if (! isset(CazaModel::PARTES[$this->parte])) {
            $this->dispatch('error', ['message' => 'Elegí qué parte querés cazar.']);
            return;
        }

        $resultado = DB::transaction(function () {
            $personaje = Personaje::with(['ciudadActual', 'estadosTemporales'])->lockForUpdate()->find($this->personajeId);
            if (! $personaje || $personaje->user_id !== auth()->id()) {
                return ['error' => 'Personaje no encontrado.'];
            }

            if ($motivo = $this->motivoBloqueo($personaje)) {
                return ['error' => $motivo];
            }

            $presa = CazaModel::tablero($personaje->ciudadActual)->first(fn ($p) => $p['post']->id === $this->presaId);
            if (! $presa) {
                return ['error' => 'Esa presa ya no está en el tablero. Elegí otra.'];
            }

            CazaModel::recargarCargas($personaje);
            if ($personaje->caza_cargas < 1) {
                return ['error' => 'No te quedan cargas de caza. Esperá a que se recarguen o comprá una.'];
            }

            // La carga se gasta al salir a cazar: si perdés, no se devuelve
            $personaje->caza_cargas--;
            if ($personaje->caza_cargas < CazaModel::CARGAS_MAX && ! $personaje->caza_cargas_desde) {
                $personaje->caza_cargas_desde = now();
            }
            $personaje->save();

            $minutos = ExploracionRapida::activaPara($personaje->id) ? CazaModel::RASTREO_MINUTOS_RAPIDA : CazaModel::RASTREO_MINUTOS;

            CazaModel::create([
                'personaje_id' => $personaje->id,
                'post_id'      => $presa['post']->id,
                'ciudad_id'    => $personaje->ciudadActual?->id,
                'rareza'       => $presa['rareza'],
                'parte'        => $this->parte,
                // Los admins no esperan (igual que en Explorar)
                'fin_rastreo'  => auth()->user()->isAdmin() ? now() : now()->addMinutes($minutos),
                'estado'       => 'rastreando',
            ]);

            return ['ok' => $presa['post']->titulo];
        });

        if (isset($resultado['error'])) {
            $this->dispatch('error', ['message' => $resultado['error']]);
            return;
        }

        $this->presaId = null;
        $this->dispatch('success', ['message' => "Saliste a rastrear a {$resultado['ok']}."]);
    }

    // Termina el rastreo: la presa pasa a ser el enemigo actual y se pelea en la Ciudad
    public function enfrentar()
    {
        $personaje = Personaje::find($this->personajeId);
        $caza = $personaje ? CazaModel::activaDe($personaje->id) : null;

        if (! $caza || $caza->fin_rastreo->isFuture()) {
            $this->dispatch('error', ['message' => 'Todavía estás rastreando a la presa.']);
            return;
        }
        if ($personaje->enemigo_actual_id && $personaje->enemigo_actual_id !== $caza->post_id) {
            $this->dispatch('error', ['message' => 'Terminá tu combate actual antes de enfrentar a la presa.']);
            return;
        }
        if ($personaje->fin_exploracion && now()->lt($personaje->fin_exploracion)) {
            $this->dispatch('error', ['message' => 'Te estás recuperando. Esperá para enfrentar a la presa.']);
            return;
        }

        $caza->update(['estado' => 'lista']);
        $personaje->enemigo_actual_id = $caza->post_id;
        $personaje->save();

        session([
            'enemigo'        => ['id' => $caza->post_id],
            'combate_activo' => true,
        ]);

        $this->dispatch('cambiarSeccion', nuevaSeccion: 'inicio');
    }

    public function cancelar()
    {
        $caza = CazaModel::activaDe($this->personajeId);
        if ($caza && $caza->estado === 'rastreando') {
            // Abandonar el rastreo no devuelve la carga
            $caza->update(['estado' => 'perdida']);
            $this->dispatch('success', ['message' => 'Abandonaste la caza.']);
        }
    }

    public function comprarCarga()
    {
        $resultado = DB::transaction(function () {
            $personaje = Personaje::lockForUpdate()->find($this->personajeId);
            if (! $personaje || $personaje->user_id !== auth()->id()) {
                return 'Personaje no encontrado.';
            }
            if ($personaje->diamante < CazaModel::COSTO_CARGA_DIAMANTES) {
                return 'No tenés suficientes esmeraldas.';
            }

            CazaModel::recargarCargas($personaje);
            $personaje->diamante -= CazaModel::COSTO_CARGA_DIAMANTES;
            $personaje->caza_cargas++;
            if ($personaje->caza_cargas >= CazaModel::CARGAS_MAX) {
                $personaje->caza_cargas_desde = null;
            }
            $personaje->save();

            return null;
        });

        if ($resultado) {
            $this->dispatch('error', ['message' => $resultado]);
            return;
        }
        $this->dispatch('success', ['message' => 'Compraste una carga de caza.']);
    }

    // Mismas restricciones que para explorar
    private function motivoBloqueo(Personaje $personaje): ?string
    {
        if ($personaje->estaEntrenando()) {
            return Personaje::MENSAJE_ENTRENANDO;
        }
        if (CazaModel::activaDe($personaje->id)) {
            return 'Ya tenés una caza en curso.';
        }

        $estados = $personaje->estadosTemporales->filter(fn ($e) => $e->estaActivo())->pluck('estado');
        foreach (['Aturdido', 'Congelado', 'Paralizado'] as $estado) {
            if ($estados->contains($estado)) {
                return "No podés cazar porque estás $estado.";
            }
        }

        if ($personaje->viajando_hasta && now()->lt($personaje->viajando_hasta)) {
            return 'Estás viajando. Cazá cuando llegues.';
        }
        if ($personaje->fin_exploracion && now()->lt($personaje->fin_exploracion)) {
            return $personaje->exploracion_duracion > 0
                ? 'Estás explorando. Terminá la exploración antes de cazar.'
                : 'Te estás recuperando. Esperá para salir a cazar.';
        }
        // En combate: enemigo guardado, o marca de combate con un enemigo cargado en la sesión
        if ($personaje->enemigo_actual_id || (session('combate_activo') === true && session()->has('enemigo'))) {
            return 'Terminá tu combate actual antes de cazar.';
        }

        return null;
    }

    public function render()
    {
        $personaje = Personaje::with('ciudadActual')->find($this->personajeId);

        CazaModel::recargarCargas($personaje);
        if ($personaje->isDirty(['caza_cargas', 'caza_cargas_desde'])) {
            $personaje->save();
        }

        [, $segundosRotacion] = CazaModel::rotacionActual();
        $caza = CazaModel::activaDe($personaje->id);
        $caza?->load('post');

        return view('livewire.caza', [
            'personaje'        => $personaje,
            'ciudad'           => $personaje->ciudadActual,
            'tablero'          => CazaModel::tablero($personaje->ciudadActual),
            'caza'             => $caza,
            'segundosRotacion' => $segundosRotacion,
            'recuperacion'     => $personaje->segundosRecuperacion(),
            'proximaCarga'     => CazaModel::segundosProximaCarga($personaje),
            'rastreoMinutos'   => ExploracionRapida::activaPara($personaje->id) ? CazaModel::RASTREO_MINUTOS_RAPIDA : CazaModel::RASTREO_MINUTOS,
            'historial'        => CazaModel::with('post')->where('personaje_id', $personaje->id)
                                    ->whereIn('estado', ['ganada', 'perdida'])->latest('id')->take(5)->get(),
        ]);
    }
}
