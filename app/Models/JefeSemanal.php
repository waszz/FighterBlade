<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

// Jefe de la semana: cada lunes (hora local del juego) aparece en una ciudad al azar con un set al azar.
//  - Cada jugador pelea contra su propio jefe, a su mismo nivel y bastante más fuerte que un rival de la Torre
//    (statsPara). Tiene INTENTOS intentos por semana; si le gana, queda derrotado hasta el jefe siguiente.
//  - Para pelear hay que estar en su ciudad: el viaje a esa zona es gratis e instantáneo (ver App\Livewire\Jefe).
//  - Premio: PREMIO_ESMERALDAS y un cofre o un anillo con rareza.
//  - En la ciudad del jefe el PvP no da exp (para que no se aprovechen de los que viajan gratis).
class JefeSemanal extends Model
{
    protected $table = 'jefes_semanales';
    protected $guarded = [];
    protected $casts = ['semana' => 'date'];

    const INTENTOS = 3;
    // Sobre un rival de Misión/Torre de tu nivel (set y medio equipado), el jefe tiene este extra en todos sus stats
    const FACTOR_STATS = 1.4;
    const PREMIO_ESMERALDAS = 500;
    // Rareza del anillo del premio (como el jefe de la Mazmorra en Pesadilla)
    const RAREZA_ANILLO = ['normal' => 20, 'rara' => 45, 'legendaria' => 35];
    // Ciudades donde puede aparecer: hasta este nivel, así casi todos pueden viajar
    const NIVEL_MAX_CIUDAD = 30;

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public static function lunesActual(): Carbon
    {
        return now()->setTimezone(Caza::ZONA_HORARIA)->startOfWeek(Carbon::MONDAY);
    }

    // El jefe de esta semana (se elige la primera vez que alguien lo necesita)
    public static function actual(): ?self
    {
        $semana = self::lunesActual()->toDateString();
        if ($jefe = self::whereDate('semana', $semana)->first()) {
            return $jefe;
        }
        $ciudad = Ciudad::whereNotNull('gif')->where('gif', '!=', '')->where('nivel', '<=', self::NIVEL_MAX_CIUDAD)->inRandomOrder()->first()
            ?? Ciudad::whereNotNull('gif')->inRandomOrder()->first();
        $post = Post::where('publicado', true)->where('inicial', false)
            ->where(fn ($q) => $q->whereNull('es_enemigo')->orWhere('es_enemigo', 0))
            ->whereNotNull('gif')->where('nivel', '>=', 50)->inRandomOrder()->first();
        if (! $ciudad || ! $post) {
            return null;
        }
        return self::firstOrCreate(['semana' => $semana], ['ciudad_id' => $ciudad->id, 'post_id' => $post->id]);
    }

    // Cuándo aparece el próximo jefe
    public static function proximoCambio(): Carbon
    {
        return self::lunesActual()->addWeek();
    }

    public function intentoDe(int $personajeId): JefeIntento
    {
        return JefeIntento::firstOrCreate(['jefe_semanal_id' => $this->id, 'personaje_id' => $personajeId]);
    }

    // Los stats del jefe contra un jugador de ese nivel: los puntos de ese nivel (30 + 5 por nivel) repartidos como el set,
    // con set y medio equipado (como Misiones y Torre) y FACTOR_STATS más
    public function statsPara(int $nivel): array
    {
        $base = Personaje::decodificarStats($this->post->stats);
        $claves = ['fuerza', 'ataque', 'velocidad', 'resistencia', 'defensa', 'energia'];
        $total = array_sum(array_map(fn ($c) => (float) ($base[$c] ?? 0), $claves)) ?: 1;
        $factor = (30 + 5 * $nivel) / $total
            * \App\Livewire\Explorar::refuerzoRivalMisionTorre($nivel)
            * self::FACTOR_STATS;
        $stats = [];
        foreach ($claves as $c) {
            $stats[$c] = (int) round(($base[$c] ?? 0) * $factor);
        }
        return $stats;
    }

    // Premio extra al vencerlo: un cofre o un anillo (mitad y mitad), del nivel del jugador
    public static function premio(int $nivel): array
    {
        $nivel = max(10, min(100, $nivel));
        if (random_int(0, 1) === 0) {
            return \App\Support\RecompensasTorre::cofre($nivel);
        }
        $tiro = random_int(1, array_sum(self::RAREZA_ANILLO));
        foreach (self::RAREZA_ANILLO as $rareza => $peso) {
            $tiro -= $peso;
            if ($tiro <= 0) {
                break;
            }
        }
        return \App\Support\RecompensasTorre::joya($nivel, $rareza);
    }

    // ¿Esa ciudad es la del jefe de esta semana? (ahí el PvP no da exp)
    public static function esCiudadDelJefe(?int $ciudadId): bool
    {
        return $ciudadId && (int) self::actual()?->ciudad_id === (int) $ciudadId;
    }
}
