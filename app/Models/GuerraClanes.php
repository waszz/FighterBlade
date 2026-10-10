<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

// Guerra de clanes: los domingos de HORA_INICIO a HORA_INICIO + MINUTOS (hora local del juego), en una zona al azar.
//  - Los jugadores con clan viajan gratis a la zona (y al terminar vuelven solos a donde estaban).
//  - Ahí, ganarle a un jugador de otro clan suma PUNTOS_VICTORIA al clan del ganador y resta PUNTOS_DERROTA al del
//    perdedor. Esas peleas no dan exp.
//  - Al terminar, el clan con más puntos gana: suma esos puntos a su prestigio y sus miembros reciben un buff de
//    BUFF_PORCENTAJE% de exp, oro y drop por BUFF_HORAS.
// No hay tareas programadas: la guerra empieza y termina cuando alguien entra al juego (actualizar).
class GuerraClanes extends Model
{
    protected $table = 'guerras_clanes';
    protected $guarded = [];
    protected $casts = ['fecha' => 'date', 'puntos' => 'array'];

    const DIA = Carbon::SUNDAY;
    const HORA_INICIO = '20:00';
    const MINUTOS = 60;
    const PUNTOS_VICTORIA = 10;
    const PUNTOS_DERROTA = 5;
    const BUFF_PORCENTAJE = 10;
    const BUFF_HORAS = 24;
    const NIVEL_MAX_CIUDAD = 30;

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function ganador()
    {
        return $this->belongsTo(Clan::class, 'ganador_clan_id');
    }

    public static function ahoraLocal(): Carbon
    {
        return now()->setTimezone(Caza::ZONA_HORARIA);
    }

    public static function inicioDelDia(Carbon $dia): Carbon
    {
        [$h, $m] = array_map('intval', explode(':', self::HORA_INICIO));
        return $dia->copy()->setTimezone(Caza::ZONA_HORARIA)->setTime($h, $m);
    }

    public function inicio(): Carbon
    {
        return self::inicioDelDia(Carbon::parse($this->fecha->toDateString(), Caza::ZONA_HORARIA));
    }

    public function fin(): Carbon
    {
        return $this->inicio()->addMinutes(self::MINUTOS);
    }

    // Próximo comienzo (si hay una en curso, el de esa)
    public static function proximoInicio(): Carbon
    {
        $ahora = self::ahoraLocal();
        $hoy = self::inicioDelDia($ahora);
        if ($ahora->dayOfWeek === self::DIA && $ahora->lt($hoy->copy()->addMinutes(self::MINUTOS))) {
            return $hoy;
        }
        return self::inicioDelDia($ahora->copy()->next(self::DIA));
    }

    // La guerra de hoy, avanzada hasta ahora: la crea al empezar (y avisa) y la cierra al terminar (y da los premios)
    public static function actualizar(): ?self
    {
        $ahora = self::ahoraLocal();
        if ($ahora->dayOfWeek !== self::DIA || $ahora->lt(self::inicioDelDia($ahora))) {
            return null;
        }
        $guerra = self::whereDate('fecha', $ahora->toDateString())->first();
        if (! $guerra) {
            if ($ahora->gte(self::inicioDelDia($ahora)->addMinutes(self::MINUTOS))) {
                return null; // ya pasó la hora y nadie entró: no hubo guerra
            }
            $candado = Cache::lock('guerra-clanes-crear', 30);
            if (! $candado->get()) {
                return null;
            }
            try {
                $guerra = self::whereDate('fecha', $ahora->toDateString())->first() ?? self::crear($ahora);
            } finally {
                $candado->release();
            }
        }
        if ($guerra && $guerra->estado === 'en_curso' && $ahora->gte($guerra->fin())) {
            $guerra->terminar();
        }
        return $guerra?->fresh();
    }

    private static function crear(Carbon $ahora): ?self
    {
        $ciudad = Ciudad::whereNotNull('gif')->where('gif', '!=', '')->where('nivel', '<=', self::NIVEL_MAX_CIUDAD)->inRandomOrder()->first();
        if (! $ciudad) {
            return null;
        }
        $guerra = self::create(['fecha' => $ahora->toDateString(), 'ciudad_id' => $ciudad->id, 'estado' => 'en_curso', 'puntos' => []]);
        $mensaje = '¡Empezó la guerra de clanes! Viajá gratis a ' . $ciudad->nombre . ' (sección Guerra) y ganale a los de otros clanes. Termina a las '
            . $guerra->fin()->format('H:i') . '.';
        foreach (Personaje::whereNotNull('clan_id')->pluck('id') as $id) {
            NotificacionJuego::avisar($id, '⚔️', $mensaje);
        }
        return $guerra;
    }

    public function enCurso(): bool
    {
        return $this->estado === 'en_curso' && self::ahoraLocal()->lt($this->fin());
    }

    // La guerra que está en curso ahora (o null)
    public static function enCursoAhora(): ?self
    {
        $guerra = self::where('estado', 'en_curso')->latest('fecha')->first();
        return $guerra && $guerra->enCurso() ? $guerra : null;
    }

    // ¿Esta pelea PvP cuenta para la guerra? Los dos en la zona de la guerra, con clanes distintos
    public static function cuentaPelea(Personaje $a, Personaje $b): ?self
    {
        $guerra = self::enCursoAhora();
        if (! $guerra || ! $a->clan_id || ! $b->clan_id || (int) $a->clan_id === (int) $b->clan_id) {
            return null;
        }
        return (int) $a->ciudad_id === (int) $guerra->ciudad_id && (int) $b->ciudad_id === (int) $guerra->ciudad_id ? $guerra : null;
    }

    // ¿Está en la zona de la guerra mientras dura? (ahí el PvP no da exp)
    public static function estaEnLaGuerra(Personaje $p): bool
    {
        $guerra = self::enCursoAhora();
        return $guerra && (int) $p->ciudad_id === (int) $guerra->ciudad_id;
    }

    public function sumar(int $clanId, int $puntos): void
    {
        $this->refresh();
        $tabla = $this->puntos ?? [];
        $tabla[$clanId] = ($tabla[$clanId] ?? 0) + $puntos;
        $this->puntos = $tabla;
        $this->save();
    }

    public function terminar(): void
    {
        $candado = Cache::lock('guerra-clanes-' . $this->id, 30);
        if (! $candado->get()) {
            return;
        }
        try {
            $this->refresh();
            if ($this->estado !== 'en_curso') {
                return;
            }
            $tabla = collect($this->puntos ?? [])->filter(fn ($p) => $p > 0)->sortDesc();
            $ganadorId = $tabla->keys()->first();
            // Empate arriba: no gana nadie
            if ($ganadorId && $tabla->count() > 1 && $tabla->values()[0] === $tabla->values()[1]) {
                $ganadorId = null;
            }
            $this->estado = 'terminada';
            $this->ganador_clan_id = $ganadorId;
            $this->save();

            $ganador = $ganadorId ? Clan::find($ganadorId) : null;
            if ($ganador) {
                $ganador->increment('prestigio', (int) $tabla[$ganadorId]);
                foreach (Personaje::where('clan_id', $ganador->id)->pluck('id') as $id) {
                    foreach (['xp', 'oro', 'drop'] as $tipo) {
                        Buff::create([
                            'personaje_id' => $id, 'tipo' => $tipo, 'frase' => 'Ganadores de la guerra de clanes', 'global' => false,
                            'costo' => 0, 'inicio' => now(), 'fin' => now()->addHours(self::BUFF_HORAS), 'porcentaje' => self::BUFF_PORCENTAJE,
                        ]);
                    }
                    NotificacionJuego::avisar($id, '🏆', "¡Tu clan {$ganador->nombre} ganó la guerra de clanes! +{$tabla[$ganadorId]} de prestigio y +"
                        . self::BUFF_PORCENTAJE . '% de exp, oro y drop por ' . self::BUFF_HORAS . ' horas.');
                }
            }
            $texto = $ganador ? "Terminó la guerra de clanes: ganó {$ganador->nombre}." : 'Terminó la guerra de clanes sin ganador.';

            // Todos los que viajaron vuelven a donde estaban
            foreach (GuerraViajero::where('guerra_clanes_id', $this->id)->get() as $v) {
                $pj = Personaje::find($v->personaje_id);
                if ($pj && $v->ciudad_origen_id && (int) $pj->ciudad_id === (int) $this->ciudad_id) {
                    $pj->forceFill(['ciudad_id' => $v->ciudad_origen_id])->save();
                }
                if ($pj && (! $ganador || (int) $pj->clan_id !== (int) $ganador->id)) {
                    NotificacionJuego::avisar($pj->id, '⚔️', $texto . ' Volviste a tu zona.');
                }
            }
        } finally {
            $candado->release();
        }
    }
}
