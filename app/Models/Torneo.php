<?php

namespace App\Models;

use App\Support\AnulacionPoder;
use App\Support\PoderesStats;
use App\Support\RecompensasTorre;
use App\Support\SimuladorTorneo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

// Torneo de los viernes y sábados (hora local del juego, la misma que el Mercado y la Caza):
//  - A las HORA abre la inscripción por MINUTOS_INSCRIPCION: al anotarte te toca un set al azar (nivel 5 a 100).
//  - Todos pelean como nivel NIVEL: el set se lleva a ese nivel (sus stats repartidos igual) y se suman la joya y la
//    poción de stat que tengas equipadas; sus poderes cuentan. No se gana exp, oro ni nada en las peleas.
//  - Al cerrar la inscripción se juegan rondas cada MINUTOS_RONDA: se arman parejas al azar entre los que siguen
//    (si son impares, uno pasa sin pelear) y las peleas se resuelven solas (App\Support\SimuladorTorneo).
//    Cada derrota saca una vida; con 0 vidas quedás afuera. Gana el último que queda.
//  - Premio: PREMIO_ESMERALDAS y un set completo de tu nivel (redondeado para abajo de a 5: nivel 44 → set 40).
// No hay tareas programadas en el servidor: el torneo avanza cuando alguien entra al juego (ver actualizarHoy).
class Torneo extends Model
{
    protected $table = 'torneos';
    protected $guarded = [];
    protected $casts = ['fecha' => 'date', 'proxima_ronda_at' => 'datetime'];

    const DIAS = [Carbon::FRIDAY, Carbon::SATURDAY];
    const HORA = 16;
    const MINUTOS_INSCRIPCION = 30;
    const MINUTOS_RONDA = 5;
    const VIDAS = 2;
    const NIVEL = 50;
    const PREMIO_ESMERALDAS = 1000;
    const NIVEL_SET_MIN = 5;
    const NIVEL_SET_MAX = 100;

    public function participantes()
    {
        return $this->hasMany(TorneoParticipante::class);
    }

    public function peleas()
    {
        return $this->hasMany(TorneoPelea::class);
    }

    public function ganador()
    {
        return $this->belongsTo(Personaje::class, 'ganador_id');
    }

    public static function zona(): string
    {
        return Caza::ZONA_HORARIA;
    }

    public static function ahoraLocal(): Carbon
    {
        return now()->setTimezone(self::zona());
    }

    public static function esDiaDeTorneo(Carbon $dia): bool
    {
        return in_array($dia->dayOfWeek, self::DIAS, true);
    }

    // Comienzo (inscripción) del próximo torneo en hora local: el de hoy si todavía no terminó, si no el próximo día de torneo
    public static function proximoInicio(): Carbon
    {
        $ahora = self::ahoraLocal();
        for ($d = $ahora->copy()->startOfDay(), $i = 0; $i < 8; $d->addDay(), $i++) {
            if (! self::esDiaDeTorneo($d)) {
                continue;
            }
            $inicio = $d->copy()->setTime(self::HORA, 0);
            if (! $d->isSameDay($ahora)) {
                return $inicio;
            }
            $hoy = self::whereDate('fecha', $d->toDateString())->first();
            $terminado = $hoy && in_array($hoy->estado, ['terminado', 'cancelado'], true);
            if (! $terminado && ($ahora->lt($inicio->copy()->addMinutes(self::MINUTOS_INSCRIPCION)) || $hoy?->estado === 'en_curso')) {
                return $inicio;
            }
        }
        return $ahora->copy()->addWeek();
    }

    public function inicio(): Carbon
    {
        return Carbon::parse($this->fecha->toDateString() . ' ' . sprintf('%02d:00', self::HORA), self::zona());
    }

    public function finInscripcion(): Carbon
    {
        return $this->inicio()->addMinutes(self::MINUTOS_INSCRIPCION);
    }

    public function inscripcionAbierta(): bool
    {
        $ahora = self::ahoraLocal();
        return $this->estado === 'inscripcion' && $ahora->gte($this->inicio()) && $ahora->lt($this->finInscripcion());
    }

    // El torneo de hoy (si hoy es viernes o sábado y ya es la hora), avanzado hasta ahora
    public static function actualizarHoy(): ?self
    {
        $ahora = self::ahoraLocal();
        if (! self::esDiaDeTorneo($ahora) || $ahora->hour < self::HORA) {
            return null;
        }
        $torneo = self::firstOrCreate(['fecha' => $ahora->toDateString()], ['estado' => 'inscripcion']);
        if (in_array($torneo->estado, ['inscripcion', 'en_curso'], true)) {
            $torneo->avanzar();
        }
        return $torneo->fresh();
    }

    // Juega lo que ya tendría que haberse jugado (cierre de inscripción y rondas vencidas). Con candado: si dos
    // personas entran a la vez, una sola avanza el torneo
    public function avanzar(): void
    {
        $candado = Cache::lock('torneo-' . $this->id, 120);
        if (! $candado->get()) {
            return;
        }
        try {
            $this->refresh();
            $ahora = self::ahoraLocal();

            if ($this->estado === 'inscripcion' && $ahora->gte($this->finInscripcion())) {
                if ($this->participantes()->count() < 2) {
                    $this->update(['estado' => 'cancelado']);
                    foreach ($this->participantes as $p) {
                        NotificacionJuego::avisar($p->personaje_id, '🏆', 'El torneo de hoy se canceló: no se anotaron suficientes jugadores.');
                    }
                    return;
                }
                $this->update(['estado' => 'en_curso', 'proxima_ronda_at' => $this->finInscripcion()->utc()]);
            }

            // Rondas vencidas (como mucho las que entran en el tiempo; por si nadie entró durante un rato)
            $vueltas = 0;
            while ($this->estado === 'en_curso' && $this->proxima_ronda_at && $this->proxima_ronda_at->lte(now()) && $vueltas++ < 50) {
                $this->jugarRonda();
            }
        } finally {
            $candado->release();
        }
    }

    public function vivos()
    {
        return $this->participantes()->where('vidas', '>', 0)->with(['personaje', 'post.poderes'])->get();
    }

    private function jugarRonda(): void
    {
        $vivos = $this->vivos()->shuffle()->values();
        if ($vivos->count() <= 1) {
            $this->terminar($vivos->first());
            return;
        }

        $ronda = $this->ronda + 1;
        // Si son impares, pasa sin pelear el que menos veces pasó (así no le toca siempre al mismo)
        $libre = null;
        if ($vivos->count() % 2 === 1) {
            $pasadas = TorneoPelea::where('torneo_id', $this->id)->whereNull('b_id')->pluck('a_id')->countBy();
            $libre = $vivos->sortBy(fn ($p) => [$pasadas[$p->id] ?? 0, mt_rand()])->first();
            $vivos = $vivos->reject(fn ($p) => $p->id === $libre->id)->values();
            TorneoPelea::create(['torneo_id' => $this->id, 'ronda' => $ronda, 'a_id' => $libre->id, 'b_id' => null, 'ganador_id' => $libre->id]);
        }

        foreach ($vivos->chunk(2) as $pareja) {
            [$a, $b] = $pareja->values()->all();
            $resultado = $this->pelear($a, $b);
            $ganador = $resultado['ganador'] === 'a' ? $a : $b;
            $perdedor = $ganador->is($a) ? $b : $a;

            $ganador->increment('victorias');
            $perdedor->derrotas++;
            $perdedor->vidas = max(0, $perdedor->vidas - 1);
            if ($perdedor->vidas === 0) {
                $perdedor->eliminado_en_ronda = $ronda;
            }
            $perdedor->save();

            TorneoPelea::create([
                'torneo_id' => $this->id, 'ronda' => $ronda, 'a_id' => $a->id, 'b_id' => $b->id,
                'ganador_id' => $ganador->id, 'detalle' => $resultado,
            ]);
        }

        $this->ronda = $ronda;
        $this->proxima_ronda_at = $this->proxima_ronda_at->copy()->addMinutes(self::MINUTOS_RONDA);
        $this->save();

        $quedan = $this->participantes()->where('vidas', '>', 0)->get();
        if ($quedan->count() <= 1) {
            $this->terminar($quedan->first());
        }
    }

    // Los datos con los que pelea un participante: su set llevado a nivel NIVEL, más su joya y su poción de stat
    public static function luchador(TorneoParticipante $p, $poderes): array
    {
        $set = $p->post;
        $base = Personaje::decodificarStats($set->stats);
        $claves = ['fuerza', 'ataque', 'velocidad', 'resistencia', 'defensa', 'energia'];
        $total = array_sum(array_map(fn ($c) => (float) ($base[$c] ?? 0), $claves)) ?: 1;
        // Los puntos de un nivel NIVEL (30 + 5 por nivel), repartidos como el set, y con el set equipado (como un rival de la Torre)
        $factor = (30 + 5 * self::NIVEL) / $total * \App\Livewire\Explorar::refuerzoRivalMisionTorre(self::NIVEL, 1.0);
        $stats = [];
        foreach ($claves as $c) {
            $stats[$c] = (int) round(($base[$c] ?? 0) * $factor);
        }

        $pj = $p->personaje;
        if ($pj?->joya_id && ($joya = Objeto::find($pj->joya_id))) {
            foreach (Personaje::decodificarStats($joya->stats) as $c => $v) {
                if (isset($stats[$c]) && is_numeric($v)) {
                    $stats[$c] += (int) $v;
                }
            }
        }
        if ($pocion = $pj?->pocionDeStat()) {
            $stats[$pocion['afecta']] = (int) round($stats[$pocion['afecta']] * $pocion['multiplicador']);
        }

        return [
            'nombre'  => $pj?->nombre ?? 'Jugador',
            'tipo'    => $set->tipo ?: 'fisico',
            'stats'   => PoderesStats::aplicar($stats, $poderes),
            'poderes' => collect($poderes),
        ];
    }

    private function pelear(TorneoParticipante $a, TorneoParticipante $b): array
    {
        $poderesA = collect($a->post->poderes ?? []);
        $poderesB = collect($b->post->poderes ?? []);
        // Anulación de poder, como en las peleas del juego
        [$pa, $pb] = [AnulacionPoder::filtrar($poderesA, $poderesB), AnulacionPoder::filtrar($poderesB, $poderesA)];

        $resultado = SimuladorTorneo::pelear(self::luchador($a, $pa), self::luchador($b, $pb), self::NIVEL);
        $resultado['sets'] = ['a' => $a->post->titulo, 'b' => $b->post->titulo];
        return $resultado;
    }

    private function terminar(?TorneoParticipante $ganador): void
    {
        $this->estado = 'terminado';
        $this->proxima_ronda_at = null;

        if ($ganador && ($pj = $ganador->personaje)) {
            $nivelSet = max(self::NIVEL_SET_MIN, min(self::NIVEL_SET_MAX, intdiv((int) $pj->nivel, 5) * 5));
            $set = self::setsNormales()->where('nivel', '<=', $nivelSet)->orderByDesc('nivel')->first();
            $set = $set ? self::setsNormales()->where('nivel', $set->nivel)->inRandomOrder()->first() : null;

            $pj->diamante = (int) $pj->diamante + self::PREMIO_ESMERALDAS;
            $pj->save();
            if ($set) {
                RecompensasTorre::darSetCompleto($pj, $set);
            }

            $this->ganador_id = $pj->id;
            $this->premio_set = $set ? "{$set->titulo} (Nv {$set->nivel})" : null;

            NotificacionJuego::avisar($pj->id, '🏆', '¡Ganaste el torneo! Premio: ' . number_format(self::PREMIO_ESMERALDAS, 0, ',', '.')
                . ' esmeraldas' . ($set ? " y el set completo de {$set->titulo} (Nv {$set->nivel})" : '') . '.');
            foreach ($this->participantes()->where('personaje_id', '!=', $pj->id)->pluck('personaje_id') as $otro) {
                NotificacionJuego::avisar($otro, '🏆', "{$pj->nombre} ganó el torneo de hoy.");
            }
        }
        $this->save();
    }

    // Sets que pueden tocar en el torneo o darse de premio: los normales publicados (sin iniciales ni enemigos)
    public static function setsNormales()
    {
        return Post::where('publicado', true)->where('inicial', false)
            ->where(fn ($q) => $q->whereNull('es_enemigo')->orWhere('es_enemigo', 0));
    }

    // Anotarse: le toca un set al azar de nivel NIVEL_SET_MIN a NIVEL_SET_MAX
    public function inscribir(Personaje $personaje): TorneoParticipante|string
    {
        if (! $this->inscripcionAbierta()) {
            return 'La inscripción no está abierta.';
        }
        if ($ya = $this->participantes()->where('personaje_id', $personaje->id)->first()) {
            return $ya;
        }
        $set = self::setsNormales()->whereBetween('nivel', [self::NIVEL_SET_MIN, self::NIVEL_SET_MAX])->inRandomOrder()->first();
        if (! $set) {
            return 'No hay sets para el torneo.';
        }
        return $this->participantes()->create([
            'personaje_id' => $personaje->id, 'post_id' => $set->id, 'vidas' => self::VIDAS,
        ]);
    }
}
