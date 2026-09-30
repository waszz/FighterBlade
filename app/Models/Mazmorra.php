<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// Mazmorra: se elige una dificultad (normal, difícil, pesadilla) y se pelea contra 4 enemigos y después el jefe.
// Cada pelea cuesta ENERGIA_POR_PELEA rayitos (de ENERGIA_DIARIA por día: dos mazmorras completas). Si perdés, seguís
// en el mismo rival y lo podés volver a intentar (gastando otra vez). Una vez por día se compran +100 rayitos con esmeraldas.
//  - Enemigos: oro y a veces una poción (cualquiera)
//  - Jefe: oro y esmeraldas (500 en normal), y la mazmorra termina
// Las dificultades más altas tienen rivales más fuertes y multiplican el premio.
class Mazmorra extends Model
{
    const NIVEL_MINIMO = 10;
    const ENERGIA_DIARIA = 100;
    const ENERGIA_POR_PELEA = 10;
    const ENERGIA_COMPRA = 100;
    const PRECIO_COMPRA = 1000; // esmeraldas
    const ENEMIGOS = 4;         // antes del jefe

    // stats: sobre el refuerzo de Misiones/Torre (un jugador equipado de su nivel). premio: multiplica oro y esmeraldas
    const DIFICULTADES = [
        'normal'    => ['nombre' => 'Normal',    'icono' => '🟢', 'stats' => 1.0,  'premio' => 1],
        'dificil'   => ['nombre' => 'Difícil',   'icono' => '🟠', 'stats' => 1.25, 'premio' => 2],
        'pesadilla' => ['nombre' => 'Pesadilla', 'icono' => '🔴', 'stats' => 1.5,  'premio' => 3],
    ];
    const FACTOR_JEFE = 1.15;         // el jefe pega un poco más que los enemigos de su dificultad
    const ORO_ENEMIGO_POR_NIVEL = 20; // oro de un enemigo = nivel × esto × premio
    const ORO_JEFE_POR_NIVEL = 60;
    const ESMERALDAS_JEFE = 500;      // × premio
    const CHANCE_POCION = 30;         // % de que un enemigo tire una poción

    protected $table = 'mazmorras';
    protected $fillable = ['personaje_id', 'energia', 'energia_dia', 'compra_dia', 'dificultad', 'rivales', 'paso', 'en_pelea', 'jefes_derrotados'];
    protected $casts = [
        'rivales'     => 'array',
        'en_pelea'    => 'boolean',
        'energia_dia' => 'date',
        'compra_dia'  => 'date',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class);
    }

    // La fila del personaje (se crea la primera vez) con la energía del día ya recargada
    public static function de(int $personajeId): self
    {
        $mazmorra = self::firstOrCreate(['personaje_id' => $personajeId], ['energia' => self::ENERGIA_DIARIA, 'energia_dia' => today()]);
        $mazmorra->recargarEnergia();
        return $mazmorra;
    }

    // Cada día vuelve a ENERGIA_DIARIA (si le quedaba más de lo comprado, lo conserva)
    public function recargarEnergia(): void
    {
        if (! $this->energia_dia || $this->energia_dia->lt(today())) {
            $this->energia = max((int) $this->energia, self::ENERGIA_DIARIA);
            $this->energia_dia = today();
            $this->save();
        }
    }

    public function puedeComprar(): bool
    {
        return ! $this->compra_dia || $this->compra_dia->lt(today());
    }

    public function enCurso(): bool
    {
        return $this->dificultad !== null && ! empty($this->rivales);
    }

    public function datosDificultad(): array
    {
        return self::DIFICULTADES[$this->dificultad] ?? self::DIFICULTADES['normal'];
    }

    public function esJefe(?int $paso = null): bool
    {
        return ($paso ?? $this->paso) >= self::ENEMIGOS;
    }

    // Rival del paso actual: ['post_id' => .., 'escenario' => ..]
    public function rivalActual(): ?array
    {
        return $this->rivales[$this->paso] ?? null;
    }

    // Stats × esto sobre el refuerzo de Misiones/Torre
    public function factorStats(?int $paso = null): float
    {
        return $this->datosDificultad()['stats'] * ($this->esJefe($paso) ? self::FACTOR_JEFE : 1);
    }

    public function oroPorVictoria(int $nivelRival): int
    {
        $porNivel = $this->esJefe() ? self::ORO_JEFE_POR_NIVEL : self::ORO_ENEMIGO_POR_NIVEL;
        return $nivelRival * $porNivel * $this->datosDificultad()['premio'];
    }

    public function esmeraldasPorVictoria(): int
    {
        return $this->esJefe() ? self::ESMERALDAS_JEFE * $this->datosDificultad()['premio'] : 0;
    }

    // Arma una mazmorra nueva para el personaje: 4 enemigos de su nivel (hasta +4) y un jefe de +5 a +9, cada uno en un escenario
    public function empezar(Personaje $personaje, string $dificultad): void
    {
        $nivel = (int) $personaje->nivel;
        $enemigos = self::setsEntre($nivel, min(100, $nivel + 4), self::ENEMIGOS);
        $jefe = self::setsEntre(min(100, $nivel + 5), min(100, $nivel + 9), 1, true);

        $escenarios = collect(Mision::pluck('escenario'))->merge(TorrePiso::pluck('escenario'))->filter()->unique()->shuffle()->values();
        $rivales = [];
        foreach ($enemigos->concat($jefe)->values() as $i => $post) {
            $rivales[] = ['post_id' => $post->id, 'escenario' => $escenarios[$i % max(1, $escenarios->count())] ?? null];
        }

        $this->fill(['dificultad' => $dificultad, 'rivales' => $rivales, 'paso' => 0, 'en_pelea' => false])->save();
    }

    // Sets normales (los de exploración; el scope de Post deja afuera rivales de misión, especiales y variantes) entre esos niveles (si no alcanzan, se completan con los más cercanos)
    private static function setsEntre(int $desde, int $hasta, int $cantidad, bool $elMasFuerte = false)
    {
        $sets = Post::query()->whereBetween('nivel', [$desde, $hasta])->inRandomOrder()->get();
        if ($elMasFuerte) {
            $sets = $sets->sortByDesc('nivel')->values();
        }
        if ($sets->count() < $cantidad) {
            $medio = intdiv($desde + $hasta, 2);
            $cercanos = Post::query()->whereNotIn('id', $sets->pluck('id'))
                ->orderByRaw('ABS(nivel - ?)', [$medio])->limit($cantidad - $sets->count())->get();
            $sets = $sets->concat($cercanos);
        }
        return $sets->take($cantidad)->values();
    }

    // Todas las pociones que puede tirar un enemigo (normales, super, recuperación, búsqueda, oro y esmeraldas),
    // en el formato de drop de la pelea
    public static function pocionAlAzar(): array
    {
        $pociones = collect([
            ['Poción de Defensa', 'defensa', 1.5, 'pocion-defensa.png', 'Multiplica la defensa por 1.5'],
            ['Poción de Ataque', 'ataque', 1.5, 'pocion-ataque.png', 'Multiplica el ataque por 1.5'],
            ['Poción de Energía', 'energia', 1.5, 'pocion-energia.png', 'Multiplica la Energia por 1.5'],
            ['Poción de Velocidad', 'velocidad', 1.5, 'pocion-velocidad.png', 'Multiplica la velocidad por 1.5'],
            ['Poción de Fuerza', 'fuerza', 1.5, 'pocion-fuerza.png', 'Multiplica la fuerza por 1.5'],
            ['Poción de Resistencia', 'resistencia', 1.5, 'pocion-resistencia.png', 'Multiplica la Resistencia por 1.5'],
            ['Poción de Recuperacion', 'recuperacion', 15, 'pocion-recuperacion.png', 'Recuperación de 15 segundos'],
            ['Poción de Búsqueda', 'drop_partes', 1, 'pocion-busqueda.png', '100% de probabilidad de drop'],
            ['Poción de Oro', 'oro', 2, 'pocion-oro.png', 'Duplica el oro de la próxima victoria'],
            ['Poción de Esmeraldas', 'diamante', 1, 'pocion-diamantes.png', 'Otorga 100 esmeraldas'],
        ])->map(fn ($p) => ['nombre' => $p[0], 'afecta' => $p[1], 'multiplicador' => $p[2], 'imagen' => $p[3], 'descripcion' => $p[4]]);

        // Las super pociones (×2) de la tienda
        foreach (DB::table('mercado_pociones')->where('nombre', 'like', 'Poción de Super%')->get() as $super) {
            $stats = json_decode($super->stats ?? '[]', true) ?: [];
            $pociones->push(['nombre' => $super->nombre, 'afecta' => $stats['afecta'] ?? null, 'multiplicador' => $stats['multiplicador'] ?? 2,
                'imagen' => $super->imagen, 'descripcion' => $super->descripcion]);
        }

        $p = $pociones->filter(fn ($p) => $p['afecta'])->random();
        return [
            'tipo'        => 'pocion',
            'nombre'      => $p['nombre'],
            'stats'       => ['usos_restantes' => 1, 'usos_totales' => 1, 'multiplicador' => $p['multiplicador'], 'afecta' => $p['afecta']],
            'imagen'      => $p['imagen'],
            'descripcion' => $p['descripcion'],
            'nivel'       => 1,
            'requisitos'  => [],
            'origen_post_id' => null,
        ];
    }
}
