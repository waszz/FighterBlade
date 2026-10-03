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
//  - A la hora de HORAS (viernes 21:30, sábado 16:00) abre la inscripción por MINUTOS_INSCRIPCION: al anotarte te toca un set al azar (nivel 5 a 100).
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
    // Hora de la inscripción de cada día (hora local)
    const HORAS = [Carbon::FRIDAY => '21:30', Carbon::SATURDAY => '16:00'];
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

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

    // La zona del torneo (la misma para todos): se elige al azar la primera vez que se necesita
    public function zonaDelTorneo(): ?Ciudad
    {
        if (! $this->ciudad_id || ! $this->ciudad) {
            $ciudad = Ciudad::whereNotNull('gif')->where('gif', '!=', '')->inRandomOrder()->first();
            if ($ciudad) {
                $this->update(['ciudad_id' => $ciudad->id]);
                $this->setRelation('ciudad', $ciudad);
            }
        }
        return $this->ciudad;
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

    // "HH:MM" de la inscripción de ese día
    public static function horaDe(Carbon $dia): string
    {
        return self::HORAS[$dia->dayOfWeek] ?? '16:00';
    }

    // El momento en que abre la inscripción ese día (hora local)
    public static function inicioDelDia(Carbon $dia): Carbon
    {
        [$h, $m] = array_map('intval', explode(':', self::horaDe($dia)));
        return $dia->copy()->setTimezone(self::zona())->setTime($h, $m);
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
            $inicio = self::inicioDelDia($d);
            if (! $d->isSameDay($ahora)) {
                return $inicio;
            }
            $hoy = self::whereDate('fecha', $d->toDateString())->first();
            // Uno cancelado antes de su hora (ej. se cambió la hora del torneo) todavía se juega
            $terminado = $hoy && ($hoy->estado === 'terminado'
                || ($hoy->estado === 'cancelado' && $ahora->gte($inicio->copy()->addMinutes(self::MINUTOS_INSCRIPCION))));
            if (! $terminado && ($ahora->lt($inicio->copy()->addMinutes(self::MINUTOS_INSCRIPCION)) || $hoy?->estado === 'en_curso')) {
                return $inicio;
            }
        }
        return $ahora->copy()->addWeek();
    }

    public function inicio(): Carbon
    {
        $dia = Carbon::parse($this->fecha->toDateString(), self::zona());
        return self::inicioDelDia($dia);
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
        if (! self::esDiaDeTorneo($ahora) || $ahora->lt(self::inicioDelDia($ahora))) {
            return null;
        }
        $torneo = self::firstOrCreate(['fecha' => $ahora->toDateString()], ['estado' => 'inscripcion']);
        $abrio = $torneo->wasRecentlyCreated;
        // Se canceló antes de la hora actual del torneo (ej. se cambió la hora): vuelve a abrir la inscripción
        if ($torneo->estado === 'cancelado' && $ahora->lt($torneo->finInscripcion())) {
            $torneo->update(['estado' => 'inscripcion', 'ronda' => 0, 'proxima_ronda_at' => null]);
            $abrio = true;
        }
        // Abrió la inscripción (y todavía se puede anotar): aviso a todos los personajes
        if ($abrio && $torneo->inscripcionAbierta()) {
            $torneo->avisarATodos('¡Abrió la inscripción del torneo! Tenés hasta las ' . $torneo->finInscripcion()->format('H:i')
                . ' para anotarte (sección Torneo). Premio: ' . number_format(self::PREMIO_ESMERALDAS, 0, ',', '.') . ' esmeraldas y un set de tu nivel.');
        }
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
                foreach ($this->participantes()->pluck('personaje_id') as $id) {
                    NotificacionJuego::avisar($id, '⚔️', '¡Arrancó el torneo! Las peleas son solas: una ronda cada ' . self::MINUTOS_RONDA
                        . ' minutos. Mirá tus peleas en la sección Torneo.');
                }
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

    // Un aviso (campanita) para todos los personajes, de una sola vez
    public function avisarATodos(string $mensaje): void
    {
        $ahora = now();
        foreach (Personaje::pluck('id')->chunk(500) as $ids) {
            NotificacionJuego::insert($ids->map(fn ($id) => [
                'personaje_id' => $id, 'icono' => '🏆', 'mensaje' => mb_substr($mensaje, 0, 500),
                'leida' => false, 'created_at' => $ahora, 'updated_at' => $ahora,
            ])->all());
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

        foreach ($this->armarParejas($vivos) as [$a, $b]) {
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

            $peleaId = $resultado['pelea_id'] ?? null;
            unset($resultado['rondas'], $resultado['pelea_id']); // las rondas quedan en la pelea guardada
            TorneoPelea::create([
                'torneo_id' => $this->id, 'ronda' => $ronda, 'a_id' => $a->id, 'b_id' => $b->id,
                'ganador_id' => $ganador->id, 'pelea_id' => $peleaId, 'detalle' => $resultado,
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

    // Parejas de la ronda evitando repetir rivales: se prueban varios sorteos y se queda el que menos repite
    // (si ya peleaste con todos, puede volver a tocarte alguno, pero primero los que menos enfrentaste)
    private function armarParejas($vivos): array
    {
        $veces = [];
        foreach (TorneoPelea::where('torneo_id', $this->id)->whereNotNull('b_id')->get(['a_id', 'b_id']) as $p) {
            $clave = min($p->a_id, $p->b_id) . '-' . max($p->a_id, $p->b_id);
            $veces[$clave] = ($veces[$clave] ?? 0) + 1;
        }
        $repite = fn ($x, $y) => $veces[min($x->id, $y->id) . '-' . max($x->id, $y->id)] ?? 0;

        $mejor = null;
        $mejorCosto = PHP_INT_MAX;
        for ($intento = 0; $intento < 30 && $mejorCosto > 0; $intento++) {
            $libres = $vivos->shuffle()->values()->all();
            $parejas = [];
            $costo = 0;
            while (count($libres) >= 2) {
                $a = array_shift($libres);
                // El rival que menos veces enfrentó (empates: el primero del sorteo)
                $idx = 0;
                foreach ($libres as $i => $c) {
                    if ($repite($a, $c) < $repite($a, $libres[$idx])) {
                        $idx = $i;
                    }
                }
                $b = $libres[$idx];
                array_splice($libres, $idx, 1);
                $costo += $repite($a, $b);
                $parejas[] = [$a, $b];
            }
            if ($costo < $mejorCosto) {
                [$mejor, $mejorCosto] = [$parejas, $costo];
            }
        }
        return $mejor ?? [];
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
            'gifs'    => [
                'base' => $set->gif, 'ataque' => $set->gif_ataque ?: $set->gif, 'critico' => $set->gif_critico ?: $set->gif_ataque ?: $set->gif,
                'especial' => $set->gif_especial ?: $set->gif_ataque ?: $set->gif, 'defensa' => $set->gif_defensa ?: $set->gif,
            ],
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
        $resultado['pelea_id'] = $this->guardarPelea($a, $b, $pa, $pb, $resultado);
        return $resultado;
    }

    // La pelea queda como una pelea PvP normal (sin exp ni oro), así se ve con la misma pantalla de rondas que el resto
    // (Mis Peleas → PvP de los dos, y desde la página del Torneo). El escenario es una ciudad al azar
    private function guardarPelea(TorneoParticipante $a, TorneoParticipante $b, $pa, $pb, array $r): ?int
    {
        if (! $a->personaje || ! $b->personaje) {
            return null;
        }
        $ciudad = $this->zonaDelTorneo(); // la misma zona para todas las peleas del torneo
        $vista = array_fill_keys(\App\Livewire\Explorar::VISTA_PELEA, false);
        $vista = array_merge($vista, [
            'recompensas' => [], 'totalDanioPersonaje' => $r['danio']['a'], 'totalDanioEnemigo' => $r['danio']['b'],
            'danioExtraTotalPersonaje' => $r['extra']['a'] ?? 0, 'danioExtraTotalEnemigo' => $r['extra']['b'] ?? 0,
            'danioAtaqueDesesperadoPersonaje' => 0, 'danioAtaqueDesesperadoEnemigo' => 0,
            'absorcionTotalPersonaje' => $r['absorcion']['a'] ?? 0, 'absorcionTotalEnemigo' => $r['absorcion']['b'] ?? 0,
        ]);
        $pelea = Pelea::create([
            'personaje_id'  => $a->personaje_id,
            'enemigo_id'    => $b->personaje_id,
            'resultado'     => $r['ganador'] === 'a' ? 'victoria' : 'derrota',
            'exp_ganada'    => 0,
            'oro_ganado'    => 0,
            'realizada_en'  => now(),
            'ciudad_actual' => $ciudad?->nombre,
            'datos_combate' => [
                'origen' => 'torneo', 'torneo_id' => $this->id, 'enemigo_es_personaje' => true,
                'rondas' => $r['rondas'], 'vista' => $vista,
                'nombre_personaje' => $a->personaje->nombre, 'nombre_enemigo' => $b->personaje->nombre,
                'danio_personaje' => $r['danio']['a'], 'danio_enemigo' => $r['danio']['b'],
                'gif_personaje' => $a->post->gif, 'gif_enemigo' => $b->post->gif,
                'post_personaje_id' => $a->post_id, 'post_enemigo_id' => $b->post_id,
                'tipo_personaje' => $a->post->tipo ?: 'fisico', 'tipo_enemigo' => $b->post->tipo ?: 'fisico',
                'poderes_personaje' => collect($pa)->pluck('nombre')->all(), 'poderes_enemigo' => collect($pb)->pluck('nombre')->all(),
                'ciudad_id' => $ciudad?->id, 'escenario_mision' => null,
                'exp_ganada' => 0, 'oro_ganado' => 0, 'diamante' => 0, 'drop' => null, 'minutos' => null,
            ],
        ]);
        return $pelea->id;
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
