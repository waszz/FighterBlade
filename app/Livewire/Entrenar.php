<?php

namespace App\Livewire;

use App\Models\Ciudad;
use App\Models\Personaje;
use App\Models\Post;
use Livewire\Component;

// Entrenamiento con el maestro: dura 8 horas y al terminar da siempre la mitad de la exp que pide el nivel actual.
// No bloquea nada: se puede seguir explorando y peleando mientras tanto.
class Entrenar extends Component
{
    const HORAS = 8;
    const PORCENTAJE_EXP = 0.5;
    const NIVEL_MAXIMO = 100;
    // Maestro y lugar (si no están, se usa otro especial y la zona actual)
    const MAESTRO = 'Chin Gentsai';
    const LUGAR = '%DOJO%';

    public $personajeId;

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    protected function yo(): ?Personaje
    {
        return Personaje::where('user_id', auth()->id())->find($this->personajeId);
    }

    // La mitad de la exp que pide el nivel actual (del nivel N al N+1 hacen falta 10000 × (N² − (N−1)²))
    public static function expPremio(int $nivel): int
    {
        $nivel = max(1, $nivel);
        return (int) round((10000 * ($nivel ** 2 - ($nivel - 1) ** 2)) * self::PORCENTAJE_EXP);
    }

    public function entrenar()
    {
        $pj = $this->yo();
        if (! $pj) {
            return;
        }
        if ($pj->nivel >= self::NIVEL_MAXIMO) {
            return $this->dispatch('error', ['message' => 'Ya llegaste al nivel máximo: no hay más para entrenar.']);
        }
        if ($pj->entreno_fin) {
            return;
        }
        // Mientras entrena no puede hacer nada de esto, así que tiene que empezar sin nada pendiente
        $motivo = match (true) {
            $pj->fin_exploracion && now()->lt($pj->fin_exploracion) && $pj->exploracion_duracion > 0 => 'Estás explorando: terminá la exploración antes de entrenar.',
            $pj->viajando_hasta && now()->lt($pj->viajando_hasta) => 'Estás viajando: entrená cuando llegues.',
            $pj->enemigo_actual_id || $pj->enemigo_actual_personaje_id || $pj->mision_activa_id || $pj->torre_piso_activo => 'Terminá tu pelea actual antes de entrenar.',
            (bool) \App\Models\Caza::activaDe($pj->id) => 'Tenés una caza en curso: terminala antes de entrenar.',
            default => null,
        };
        if ($motivo) {
            return $this->dispatch('error', ['message' => $motivo]);
        }
        $pj->entreno_fin = now()->addHours(self::HORAS);
        $pj->save();
        $this->dispatch('success', ['message' => '¡Empezó el entrenamiento! Volvé en ' . self::HORAS . ' horas.']);
    }

    public function cancelar()
    {
        $pj = $this->yo();
        if ($pj && $pj->entreno_fin && now()->lt($pj->entreno_fin)) {
            $pj->entreno_fin = null;
            $pj->save();
        }
    }

    // Al terminar: la mitad de la exp del nivel en el que está ahora (con el mismo tope y puntos que las peleas)
    public function reclamar()
    {
        $pj = $this->yo();
        if (! $pj || ! $pj->entreno_fin || now()->lt($pj->entreno_fin)) {
            return;
        }

        $exp = self::expPremio((int) $pj->nivel);
        $nivelAntes = (int) $pj->nivel;
        $pj->entreno_fin = null;

        if ($pj->nivel < self::NIVEL_MAXIMO) {
            $pj->experiencia += $exp;
            while ($pj->nivel < self::NIVEL_MAXIMO && $pj->experiencia >= 10000 * pow($pj->nivel, 2)) {
                $pj->nivel++;
            }
            $pj->puntos_stats += ($pj->nivel - $nivelAntes) * 5;
            if ($pj->nivel >= self::NIVEL_MAXIMO) {
                $pj->nivel = self::NIVEL_MAXIMO;
                $pj->experiencia = 10000 * pow(self::NIVEL_MAXIMO, 2);
            }
        }
        $pj->save();

        $subio = $pj->nivel > $nivelAntes ? " ¡Subiste al nivel {$pj->nivel}!" : '';
        $this->dispatch('success', ['message' => 'Terminaste el entrenamiento: +' . number_format($exp, 0, ',', '.') . ' EXP.' . $subio]);
        // El panel lateral muestra la exp y el nivel nuevos
        $this->dispatch('statsActualizados');
    }

    public function render()
    {
        $pj = $this->yo();
        $maestro = Post::conRivales()->where('titulo', self::MAESTRO)->first()
            ?? Post::conRivales()->where('es_enemigo', 3)->whereNotNull('gif')->orderBy('id')->first();
        $lugar = Ciudad::where('nombre', 'like', self::LUGAR)->first() ?? $pj?->ciudadActual;

        $segundos = $pj?->entreno_fin ? max(0, $pj->entreno_fin->timestamp - now()->timestamp) : null;
        $estado = match (true) {
            ! $pj => 'nada',
            $pj->nivel >= self::NIVEL_MAXIMO && ! $pj->entreno_fin => 'maximo',
            $segundos === null => 'libre',
            $segundos > 0 => 'entrenando',
            default => 'terminado',
        };

        return view('livewire.entrenar', [
            'personaje' => $pj,
            'maestro'   => $maestro,
            'lugar'     => $lugar,
            'segundos'  => $segundos ?? 0,
            'estado'    => $estado,
            'expPremio' => $pj ? self::expPremio((int) $pj->nivel) : 0,
        ]);
    }
}
