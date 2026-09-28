<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Caza extends Model
{
    protected $table = 'cazas';

    protected $fillable = ['personaje_id', 'post_id', 'ciudad_id', 'rareza', 'parte', 'fin_rastreo', 'estado'];

    protected $casts = [
        'fin_rastreo' => 'datetime',
    ];

    // Multiplicador de stats de la presa y bonus de recompensa por rareza
    const RAREZAS = [
        'comun'      => ['nombre' => 'Común',      'stats' => 1.2, 'oro' => 1.5, 'diamantes' => 0,  'color' => 'gray'],
        'rara'       => ['nombre' => 'Rara',       'stats' => 1.4, 'oro' => 2,   'diamantes' => 0,  'color' => 'blue'],
        'legendaria' => ['nombre' => 'Legendaria', 'stats' => 1.7, 'oro' => 3,   'diamantes' => 25, 'color' => 'amber'],
    ];

    // Chance (en %) de que la presa rara de la rotación sea legendaria
    const CHANCE_LEGENDARIA = 5;

    const PARTES = ['equipo' => 'Equipo', 'entrenamiento' => 'Entrenamiento', 'accesorio' => 'Accesorio'];

    // El tablero de cada ciudad cambia cada tantas horas (igual para todos)
    const ROTACION_HORAS = 8;

    // Los cambios de tablero se alinean a esta hora local (00:00, 08:00 y 16:00)
    const ZONA_HORARIA = 'America/Montevideo';

    // Rastreo antes de poder enfrentar a la presa (con Exploración rápida se reduce)
    const RASTREO_MINUTOS = 30;
    const RASTREO_MINUTOS_RAPIDA = 15;

    // Cargas: máximo, recarga y precio de una carga extra
    const CARGAS_MAX = 3;
    const CARGA_HORAS = 8;
    const COSTO_CARGA_DIAMANTES = 30;

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function personaje()
    {
        return $this->belongsTo(Personaje::class);
    }

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function rarezaInfo(): array
    {
        return self::RAREZAS[$this->rareza] ?? self::RAREZAS['comun'];
    }

    // Caza en curso (rastreando o lista para pelear)
    public static function activaDe(int $personajeId): ?self
    {
        return self::where('personaje_id', $personajeId)
            ->whereIn('estado', ['rastreando', 'lista'])
            ->latest('id')
            ->first();
    }

    // Número de la rotación actual y segundos que faltan para la próxima (en hora local de ZONA_HORARIA)
    public static function rotacionActual(): array
    {
        $duracion = self::ROTACION_HORAS * 3600;
        $local = now()->setTimezone(self::ZONA_HORARIA);
        $ahora = $local->timestamp + $local->getOffset();

        return [intdiv($ahora, $duracion), $duracion - ($ahora % $duracion)];
    }

    // Hora local (HH:MM) del próximo cambio de tablero
    public static function horaProximaRotacion(): string
    {
        [, $segundos] = self::rotacionActual();

        return now()->addSeconds($segundos)->setTimezone(self::ZONA_HORARIA)->format('H:i');
    }

    // 3 presas de la ciudad para la rotación actual: [['post' => Post, 'rareza' => '...'], ...]
    public static function tablero(?Ciudad $ciudad): Collection
    {
        [$rotacion] = self::rotacionActual();
        $nivel = max(5, (int) ($ciudad->nivel ?? 5));

        $sets = Post::where('nivel', $nivel)
            ->where('publicado', true)
            ->where('es_enemigo', '!=', 2)
            ->whereNotNull('equipo_nombre')
            ->with('poderes')
            ->orderBy('id')
            ->get();

        if ($sets->isEmpty()) {
            return collect();
        }

        // Semilla por ciudad y rotación: todos ven el mismo tablero hasta que rota
        $random = new \Random\Randomizer(new \Random\Engine\Mt19937(crc32(($ciudad->id ?? 0) . '-' . $rotacion)));
        $elegidos = array_slice($random->shuffleArray($sets->all()), 0, 3);
        $legendaria = $random->getInt(1, 100) <= self::CHANCE_LEGENDARIA;

        return collect($elegidos)->values()->map(fn (Post $post, int $i) => [
            'post'   => $post,
            'rareza' => $i < 2 ? 'comun' : ($legendaria ? 'legendaria' : 'rara'),
        ]);
    }

    // Suma las cargas recuperadas por el tiempo transcurrido (no guarda)
    public static function recargarCargas(Personaje $personaje): void
    {
        if ($personaje->caza_cargas >= self::CARGAS_MAX) {
            $personaje->caza_cargas_desde = null;
            return;
        }

        if (! $personaje->caza_cargas_desde) {
            $personaje->caza_cargas_desde = now();
            return;
        }

        $segundosCarga = self::CARGA_HORAS * 3600;
        $recuperadas = intdiv(max(0, now()->timestamp - $personaje->caza_cargas_desde->timestamp), $segundosCarga);
        if ($recuperadas <= 0) {
            return;
        }

        $personaje->caza_cargas = min(self::CARGAS_MAX, $personaje->caza_cargas + $recuperadas);
        $personaje->caza_cargas_desde = $personaje->caza_cargas >= self::CARGAS_MAX
            ? null
            : $personaje->caza_cargas_desde->copy()->addSeconds($recuperadas * $segundosCarga);
    }

    // Segundos para la próxima carga (null si está lleno)
    public static function segundosProximaCarga(Personaje $personaje): ?int
    {
        if (! $personaje->caza_cargas_desde || $personaje->caza_cargas >= self::CARGAS_MAX) {
            return null;
        }

        return max(0, $personaje->caza_cargas_desde->timestamp + self::CARGA_HORAS * 3600 - now()->timestamp);
    }
}
