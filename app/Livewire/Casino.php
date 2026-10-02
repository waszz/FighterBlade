<?php

namespace App\Livewire;

use App\Models\Buff;
use App\Models\CasinoTirada;
use App\Models\ExploracionRapida;
use App\Models\MercadoPocion;
use App\Models\Objeto;
use App\Models\Personaje;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class Casino extends Component
{
    public $personajeId;

    // Símbolos de la tragaperras: peso = probabilidad relativa, pago = multiplicador con 3 iguales
    const SIMBOLOS = [
        // 3 pociones = pociones de drop (Búsqueda) al inventario (no paga oro/diamantes)
        // pesoTriple: aparece mucho en los rodillos, pero el triple sale como si pesara 5 (antes 10: salía demasiado)
        'pocion'   => ['icono' => '🧪', 'imagen' => 'images/pocion-busqueda.png', 'peso' => 30, 'pesoTriple' => 5, 'pago' => 0],
        'oro'      => ['icono' => '🪙', 'imagen' => 'images/oro.png', 'peso' => 18, 'pago' => 30],
        'diamante' => ['icono' => '💚', 'imagen' => 'images/diamante.png', 'peso' => 9,  'pago' => 80],
        // 3 regalos = set aleatorio (no paga oro/diamantes). pesoTriple 7: el triple sale un poco menos que con su peso (8)
        'set'      => ['icono' => '🎁', 'imagen' => 'images/casino-set.png', 'peso' => 8, 'pesoTriple' => 7, 'pago' => 0],
        // 3 rayos = buff aleatorio de exp, oro o drop (no paga oro/diamantes)
        'buff'     => ['icono' => '⏫', 'imagen' => 'images/buff-combo.png', 'peso' => 10, 'pago' => 0],
        // 3 cohetes = exploración rápida (no paga oro/diamantes)
        'rapida'   => ['icono' => '⏱️', 'imagen' => 'images/casino-rapida.png', 'peso' => 8,  'pago' => 0],
        // 3 corazones = vidas extra de casino (pueden superar el máximo)
        'vida'     => ['icono' => '❤️', 'imagen' => 'images/casino-vida.png', 'peso' => 8,  'pago' => 0],
        // 3 dianas = cargas de caza extra (pueden superar el máximo)
        'caza'     => ['icono' => '🎯', 'imagen' => 'images/iconos/caza.svg', 'peso' => 8,  'pago' => 0],
        // 3 pociones verdes = pociones de esmeraldas al inventario (cada una da 100 esmeraldas al usarla)
        'pota_esmeralda' => ['icono' => '🧪', 'imagen' => 'images/pocion-diamantes.png', 'peso' => 8, 'pago' => 0],
    ];

    // Símbolos cuyo triple da un premio especial (con diamantes tienen x2 chances)
    const ESPECIALES = ['set', 'buff', 'rapida', 'pocion', 'vida', 'caza', 'pota_esmeralda'];

    // Pociones de esmeraldas que da el triple de pociones verdes
    const POCIONES_ESMERALDA_PREMIO = 3;

    const VIDAS_PREMIO = 3;

    // Tiradas que se muestran en el historial
    const HISTORIAL_MAX = 50;

    // Cargas de caza que da el triple de dianas
    const CAZA_CARGAS_PREMIO = 2;

    // Precio en diamantes de 1 vida normal
    const COSTO_VIDA_NORMAL = 5;

    // Vidas que se compran con diamantes: chance = % de sacar un premio especial al girar con ella
    const VIDAS_TIPOS = [
        'comun' => ['nombre' => 'Común', 'costo' => 25,  'chance' => 10],
        'super' => ['nombre' => 'Super', 'costo' => 50,  'chance' => 30],
        'ultra' => ['nombre' => 'Ultra', 'costo' => 100, 'chance' => 50],
    ];

    const POCIONES_PREMIO = 5;
    const POCION_PREMIO   = 'Poción de Búsqueda';

    const RAPIDA_HORAS = 24;

    const NIVEL_SET_MIN = 5;
    const NIVEL_SET_MAX = 100;

    const BUFF_TIPOS       = ['xp' => 'EXP', 'oro' => 'Oro', 'drop' => 'Drop'];
    // Porcentaje que da cada buff (drop 100% = parte de set asegurada en peleas de 5+ min)
    const BUFF_PORCENTAJES = ['xp' => 100, 'oro' => 100, 'drop' => 100];
    const BUFF_HORAS      = 3;
    // Si ya tiene todos los buffs al 100%, se paga la apuesta x5
    const PAGO_BUFF_LLENO = 5;

    // Con 2 iguales se paga el doble de la apuesta
    const PAGO_PAR = 2;

    // Vidas: cada giro gasta 1, se recupera 1 cada VIDA_HORAS hasta el máximo
    const VIDAS_MAX  = 3;
    const VIDA_HORAS = 8;

    const APUESTAS = [
        'oro'      => [100, 500, 1000, 5000],
        'diamante' => [25, 50, 75, 100],
    ];

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    #[Renderless]
    public function comprarVida($tipo)
    {
        if ($tipo !== 'normal' && ! isset(self::VIDAS_TIPOS[$tipo])) {
            $this->dispatch('error', ['message' => 'Tipo de vida inválido.']);
            return null;
        }

        $resultado = DB::transaction(function () use ($tipo) {
            $personaje = Personaje::where('id', $this->personajeId)
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->first();

            if (! $personaje) {
                return ['error' => 'Personaje no encontrado.'];
            }

            $costo = $tipo === 'normal' ? self::COSTO_VIDA_NORMAL : self::VIDAS_TIPOS[$tipo]['costo'];
            if ($personaje->diamante < $costo) {
                return ['error' => 'No tenés suficientes esmeraldas.'];
            }

            $this->recargarVidas($personaje);
            $personaje->diamante -= $costo;
            if ($tipo === 'normal') {
                $personaje->casino_vidas++;
                if ($personaje->casino_vidas >= self::VIDAS_MAX) {
                    $personaje->casino_vidas_desde = null;
                }
            } else {
                $personaje->{'casino_vidas_' . $tipo}++;
            }
            $personaje->save();

            return [
                'oro'      => $personaje->oro,
                'diamante' => $personaje->diamante,
                'vidas'    => $this->estadoVidas($personaje),
            ];
        });

        if (isset($resultado['error'])) {
            $this->dispatch('error', ['message' => $resultado['error']]);
            return null;
        }

        return $resultado;
    }

    #[Renderless]
    public function girar($moneda, $apuesta, $vida = 'normal')
    {
        $apuesta = (int) $apuesta;

        if ($vida !== 'normal' && ! isset(self::VIDAS_TIPOS[$vida])) {
            $this->dispatch('error', ['message' => 'Tipo de vida inválido.']);
            return null;
        }

        if (! isset(self::APUESTAS[$moneda]) || ! in_array($apuesta, self::APUESTAS[$moneda], true)) {
            $this->dispatch('error', ['message' => 'Apuesta inválida.']);
            return null;
        }

        // Tiene que haber lugar para el premio más grande (las pociones o un set de 3 partes)
        $lugaresPremio = max(self::POCIONES_PREMIO, self::POCIONES_ESMERALDA_PREMIO, 3);
        if (! Personaje::find($this->personajeId)?->tieneLugar($lugaresPremio)) {
            $this->dispatch('error', ['message' => "🎒 Necesitás {$lugaresPremio} lugares libres en el inventario para girar (por si ganás objetos)."]);
            return null;
        }

        $resultado = DB::transaction(function () use ($moneda, $apuesta, $vida) {
            $personaje = Personaje::where('id', $this->personajeId)
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->first();

            if (! $personaje) {
                return ['error' => 'Personaje no encontrado.'];
            }

            $this->recargarVidas($personaje);

            $columnaVida = $vida === 'normal' ? 'casino_vidas' : 'casino_vidas_' . $vida;
            if ($personaje->$columnaVida < 1) {
                return ['error' => $vida === 'normal'
                    ? 'No te quedan vidas. Esperá a que se recarguen.'
                    : 'No tenés vidas ' . self::VIDAS_TIPOS[$vida]['nombre'] . '.'];
            }

            if ($personaje->$moneda < $apuesta) {
                return ['error' => $moneda === 'oro' ? 'No tenés suficiente oro.' : 'No tenés suficientes esmeraldas.'];
            }

            $personaje->$columnaVida--;
            if ($vida === 'normal' && $personaje->casino_vidas < self::VIDAS_MAX && ! $personaje->casino_vidas_desde) {
                $personaje->casino_vidas_desde = now();
            }

            if ($vida !== 'normal') {
                // Vida comprada: chance exacta de premio especial
                // (con diamantes, segunda oportunidad con la misma chance)
                $chance = self::VIDAS_TIPOS[$vida]['chance'];
                $acierta = random_int(1, 100) <= $chance
                    || ($moneda === 'diamante' && random_int(1, 100) <= $chance);
                $rodillos = $acierta
                    ? $this->tiradaEspecial()
                    : $this->tiradaSinEspecial();
            } else {
                $rodillos = $this->tirada();

                // Con diamantes: segunda oportunidad oculta de sacar un premio especial (x2 chances)
                if ($moneda === 'diamante' && ! $this->esEspecial($rodillos)) {
                    $segunda = $this->tirada();
                    if ($this->esEspecial($segunda)) {
                        $rodillos = $segunda;
                    }
                }
            }

            $premio = $apuesta * $this->multiplicador($rodillos);

            $set    = null;
            $buff   = null;
            $rapida = null;
            $pociones = null;
            $vidasGanadas = null;
            $cargasCaza = null;
            $potasEsmeralda = null;
            if ($rodillos === ['set', 'set', 'set']) {
                $set = $this->darSetAleatorio($personaje);
            } elseif ($rodillos === ['buff', 'buff', 'buff']) {
                $buff = $this->darBuffAleatorio($personaje);
                if (! $buff) {
                    $premio = $apuesta * self::PAGO_BUFF_LLENO;
                }
            } elseif ($rodillos === ['rapida', 'rapida', 'rapida']) {
                $rapida = $this->darExploracionRapida($personaje);
            } elseif ($rodillos === ['pocion', 'pocion', 'pocion']) {
                $pociones = $this->darPociones($personaje);
            } elseif ($rodillos === ['pota_esmeralda', 'pota_esmeralda', 'pota_esmeralda']) {
                $potasEsmeralda = $this->darPocionesEsmeralda($personaje);
            } elseif ($rodillos === ['vida', 'vida', 'vida']) {
                $personaje->casino_vidas += self::VIDAS_PREMIO;
                if ($personaje->casino_vidas >= self::VIDAS_MAX) {
                    $personaje->casino_vidas_desde = null;
                }
                $vidasGanadas = self::VIDAS_PREMIO;
            } elseif ($rodillos === ['caza', 'caza', 'caza']) {
                \App\Models\Caza::recargarCargas($personaje);
                $personaje->caza_cargas += self::CAZA_CARGAS_PREMIO;
                if ($personaje->caza_cargas >= \App\Models\Caza::CARGAS_MAX) {
                    $personaje->caza_cargas_desde = null;
                }
                $cargasCaza = self::CAZA_CARGAS_PREMIO;
            }

            $personaje->$moneda += $premio - $apuesta;
            $personaje->save();

            // Historial: premio especial con el mismo texto que se muestra en la vista
            $especial = match (true) {
                (bool) $set          => ['clave' => 'set',    'texto' => 'Set ' . $set['titulo']],
                (bool) $buff         => ['clave' => 'buff',   'texto' => $buff['tipo'] . ' +' . $buff['porcentaje'] . '%'],
                (bool) $rapida       => ['clave' => 'rapida', 'texto' => 'Explor. rápida ' . $rapida['horas'] . 'h'],
                (bool) $pociones     => ['clave' => 'pocion', 'texto' => $pociones['cantidad'] . ' pociones drop'],
                (bool) $vidasGanadas => ['clave' => 'vida',   'texto' => '+' . $vidasGanadas . ' vidas'],
                (bool) $cargasCaza   => ['clave' => 'caza',   'texto' => '+' . $cargasCaza . ' cargas caza'],
                (bool) $potasEsmeralda => ['clave' => 'pota_esmeralda', 'texto' => $potasEsmeralda . ' potas esmeralda'],
                default              => null,
            };
            $tirada = CasinoTirada::create([
                'personaje_id' => $personaje->id,
                'rodillos'     => $rodillos,
                'moneda'       => $moneda,
                'apuesta'      => $apuesta,
                'premio'       => $premio,
                'especial'     => $especial,
            ]);

            return [
                'rodillos' => $rodillos,
                'premio'   => $premio,
                'set'      => $set,
                'buff'     => $buff,
                'rapida'   => $rapida,
                'pociones' => $pociones,
                'vidasGanadas' => $vidasGanadas,
                'cargasCaza' => $cargasCaza,
                'potasEsmeralda' => $potasEsmeralda,
                'tirada'   => $tirada->paraVista(),
                'oro'      => $personaje->oro,
                'diamante' => $personaje->diamante,
                'vidas'    => $this->estadoVidas($personaje),
            ];
        });

        if (isset($resultado['error'])) {
            $this->dispatch('error', ['message' => $resultado['error']]);
            return null;
        }

        return $resultado;
    }

    // Entrega las 3 partes (equipo, entrenamiento, accesorio) de un set al azar, igual que la compra en el Mercado
    private function darSetAleatorio(Personaje $personaje): ?array
    {
        $post = Post::whereBetween('nivel', [self::NIVEL_SET_MIN, self::NIVEL_SET_MAX])
            ->where('publicado', true)
            ->whereNotNull('equipo_nombre')
            ->inRandomOrder()
            ->first();

        if (! $post) {
            return null;
        }

        $partes = [
            'equipo'        => [$post->equipo_nombre, $post->equipo_imagen, $post->ajustes_manuales_equipo, $post->requisitos_equipo],
            'entrenamiento' => [$post->entrenamiento_nombre, $post->entrenamiento_imagen, $post->ajustes_manuales_entrenamiento, $post->requisitos_entrenamiento],
            'accesorio'     => [$post->accesorio_nombre, $post->accesorio_imagen, $post->ajustes_manuales_accesorio, $post->requisitos_accesorio],
        ];

        foreach ($partes as $tipo => [$nombre, $imagen, $ajustes, $requisitos]) {
            if (! $nombre) {
                continue;
            }

            $stats      = is_array($ajustes) ? $ajustes : (json_decode($ajustes ?? '[]', true) ?: []);
            $requisitos = is_array($requisitos) ? $requisitos : (json_decode($requisitos ?? '[]', true) ?: []);

            Objeto::create([
                'personaje_id'             => $personaje->id,
                'nombre'                   => $nombre,
                'tipo'                     => $tipo,
                'imagen'                   => $imagen,
                'nivel'                    => $post->nivel ?? 1,
                'stats'                    => $stats,
                'origen_post_id'           => $post->id,
                'requisitos_equipo'        => $tipo === 'equipo' ? $requisitos : [],
                'requisitos_entrenamiento' => $tipo === 'entrenamiento' ? $requisitos : [],
                'requisitos_accesorio'     => $tipo === 'accesorio' ? $requisitos : [],
                'precio_venta'             => null,
            ]);
        }

        return [
            'titulo' => $post->titulo,
            'nivel'  => $post->nivel,
            'gif'    => $post->gif ? asset('storage/' . $post->gif) : null,
            'imagen' => $post->imagen ? asset('storage/' . $post->imagen) : null,
        ];
    }

    // Buff personal de un tipo al azar que todavía no esté al 100% (mismo tope que en Extras)
    private function darBuffAleatorio(Personaje $personaje): ?array
    {
        $tipos = array_keys(self::BUFF_TIPOS);
        shuffle($tipos);

        foreach ($tipos as $tipo) {
            $actual = Buff::where('tipo', $tipo)
                ->where('fin', '>=', now())
                ->where(fn ($q) => $q->where('personaje_id', $personaje->id)->orWhereNull('personaje_id'))
                ->sum('porcentaje');

            $porcentaje = min(self::BUFF_PORCENTAJES[$tipo], 100 - $actual);
            if ($porcentaje <= 0) {
                continue;
            }

            Buff::create([
                'personaje_id' => $personaje->id,
                'tipo'         => $tipo,
                'frase'        => 'Premio del casino',
                'global'       => false,
                'costo'        => 0,
                'inicio'       => now(),
                'fin'          => now()->addHours(self::BUFF_HORAS),
                'porcentaje'   => $porcentaje,
            ]);

            return [
                'tipo'       => self::BUFF_TIPOS[$tipo],
                'porcentaje' => $porcentaje,
                'horas'      => self::BUFF_HORAS,
            ];
        }

        return null;
    }

    // Exploración rápida por RAPIDA_HORAS; si ya tiene una activa, se le suma el tiempo
    // Entrega POCIONES_PREMIO pociones de drop al inventario, igual que la compra en el Mercado de Pociones
    private function darPociones(Personaje $personaje): ?array
    {
        $pocion = MercadoPocion::where('nombre', self::POCION_PREMIO)->first();
        if (! $pocion) {
            return null;
        }

        for ($i = 0; $i < self::POCIONES_PREMIO; $i++) {
            Objeto::create([
                'personaje_id'   => $personaje->id,
                'tipo'           => $pocion->tipo,
                'nombre'         => $pocion->nombre,
                'imagen'         => $pocion->imagen,
                'nivel'          => $pocion->nivel,
                'requisitos'     => $pocion->requisitos,
                'stats'          => $pocion->stats,
                'origen_post_id' => MercadoPociones::ORIGEN_MERCADO_POCIONES,
                'pocion'         => $pocion->tipo === 'pocion',
                'descripcion'    => $pocion->descripcion,
            ]);
        }

        return ['cantidad' => self::POCIONES_PREMIO, 'nombre' => $pocion->nombre];
    }

    // Pociones de esmeraldas (las mismas que salen de los cofres) al inventario
    private function darPocionesEsmeralda(Personaje $personaje): int
    {
        $p = \App\Support\RecompensasTorre::POCIONES_COFRE['diamante'];
        for ($i = 0; $i < self::POCIONES_ESMERALDA_PREMIO; $i++) {
            Objeto::create([
                'personaje_id'   => $personaje->id,
                'tipo'           => 'pocion',
                'nombre'         => $p['nombre'],
                'nivel'          => 1,
                'stats'          => ['usos_restantes' => 1, 'usos_totales' => 1, 'multiplicador' => 1, 'afecta' => $p['afecta']],
                'imagen'         => $p['imagen'],
                'pocion'         => true,
                'descripcion'    => $p['descripcion'],
                'usos_restantes' => 1,
                'usos_totales'   => 1,
                'requisitos_equipo' => [], 'requisitos_entrenamiento' => [], 'requisitos_accesorio' => [],
            ]);
        }

        return self::POCIONES_ESMERALDA_PREMIO;
    }

    private function darExploracionRapida(Personaje $personaje): array
    {
        $actual = ExploracionRapida::activaPara($personaje->id);

        if ($actual) {
            $actual->fin = $actual->fin->copy()->addHours(self::RAPIDA_HORAS);
            $actual->save();
            $fin = $actual->fin;
        } else {
            $fin = now()->addHours(self::RAPIDA_HORAS);
            ExploracionRapida::create([
                'personaje_id' => $personaje->id,
                'inicio'       => now(),
                'fin'          => $fin,
            ]);
        }

        return [
            'horas'    => self::RAPIDA_HORAS,
            'extendida' => (bool) $actual,
            'fin'      => $fin->format('d/m H:i'),
        ];
    }

    private function tirada(): array
    {
        while (true) {
            $rodillos = [$this->simboloAleatorio(), $this->simboloAleatorio(), $this->simboloAleatorio()];

            // Símbolos con pesoTriple: el triple sale como si tuviera ese peso (si no, se vuelve a tirar)
            $simbolo = self::SIMBOLOS[$rodillos[0]];
            if (count(array_unique($rodillos)) === 1 && isset($simbolo['pesoTriple'])) {
                $aceptar = ($simbolo['pesoTriple'] / $simbolo['peso']) ** 3;
                if (random_int(1, 1000000) > $aceptar * 1000000) {
                    continue;
                }
            }

            return $rodillos;
        }
    }

    // 3 iguales de un especial elegido según su peso
    private function tiradaEspecial(): array
    {
        $pesos = [];
        foreach (self::ESPECIALES as $clave) {
            $pesos[$clave] = $this->pesoTriple($clave);
        }

        $tirada = random_int(1, array_sum($pesos));
        foreach ($pesos as $clave => $peso) {
            $tirada -= $peso;
            if ($tirada <= 0) {
                return [$clave, $clave, $clave];
            }
        }

        return array_fill(0, 3, self::ESPECIALES[0]);
    }

    private function tiradaSinEspecial(): array
    {
        do {
            $rodillos = $this->tirada();
        } while ($this->esEspecial($rodillos));

        return $rodillos;
    }

    private function pesoTriple(string $clave): int
    {
        return self::SIMBOLOS[$clave]['pesoTriple'] ?? self::SIMBOLOS[$clave]['peso'];
    }

    private function esEspecial(array $rodillos): bool
    {
        return count(array_unique($rodillos)) === 1 && in_array($rodillos[0], self::ESPECIALES, true);
    }

    private function simboloAleatorio(): string
    {
        $total = array_sum(array_column(self::SIMBOLOS, 'peso'));
        $tirada = random_int(1, $total);

        foreach (self::SIMBOLOS as $clave => $simbolo) {
            $tirada -= $simbolo['peso'];
            if ($tirada <= 0) {
                return $clave;
            }
        }

        return array_key_first(self::SIMBOLOS);
    }

    // Suma las vidas recuperadas por el tiempo transcurrido (no guarda)
    private function recargarVidas(Personaje $personaje): void
    {
        if ($personaje->casino_vidas >= self::VIDAS_MAX) {
            // Con vidas llenas (o extra) no corre la recarga
            $personaje->casino_vidas_desde = null;
            return;
        }

        if (! $personaje->casino_vidas_desde) {
            $personaje->casino_vidas_desde = now();
            return;
        }

        $segundosVida = self::VIDA_HORAS * 3600;
        $recuperadas = intdiv(max(0, now()->timestamp - $personaje->casino_vidas_desde->timestamp), $segundosVida);

        if ($recuperadas < 1) {
            return;
        }

        $personaje->casino_vidas = min(self::VIDAS_MAX, $personaje->casino_vidas + $recuperadas);
        $personaje->casino_vidas_desde = $personaje->casino_vidas >= self::VIDAS_MAX
            ? null
            : $personaje->casino_vidas_desde->copy()->addSeconds($recuperadas * $segundosVida);
    }

    // Vidas actuales y segundos que faltan para la próxima
    private function estadoVidas(Personaje $personaje): array
    {
        $siguiente = $personaje->casino_vidas_desde
            ? max(0, $personaje->casino_vidas_desde->timestamp + self::VIDA_HORAS * 3600 - now()->timestamp)
            : null;

        $compradas = [];
        foreach (array_keys(self::VIDAS_TIPOS) as $tipo) {
            $compradas[$tipo] = (int) $personaje->{'casino_vidas_' . $tipo};
        }

        return ['actuales' => $personaje->casino_vidas, 'siguiente' => $siguiente, 'compradas' => $compradas];
    }

    private function multiplicador(array $rodillos): int
    {
        $conteo = array_count_values($rodillos);
        $maximo = max($conteo);

        if ($maximo === 3) {
            return self::SIMBOLOS[$rodillos[0]]['pago'];
        }

        return $maximo === 2 ? self::PAGO_PAR : 0;
    }

    // Probabilidad exacta (en %) de cada premio por tirada, según los pesos, la moneda y el tipo de vida
    private function probabilidades(string $moneda, string $vida = 'normal'): array
    {
        $total = array_sum(array_column(self::SIMBOLOS, 'peso'));
        $p = array_map(fn ($s) => $s['peso'] / $total, self::SIMBOLOS);

        $triple = fn ($clave) => ($this->pesoTriple($clave) / $total) ** 3;
        // Triples rechazados por pesoTriple se vuelven a tirar: el resto se reparte proporcional
        $norm = 1 - array_sum(array_map(fn ($clave) => $p[$clave] ** 3 - $triple($clave), array_keys($p)));

        $triplesDinero = array_sum(array_map(
            fn ($clave) => $triple($clave),
            array_diff(array_keys($p), self::ESPECIALES)
        )) / $norm;
        $par   = array_sum(array_map(fn ($x) => 3 * $x ** 2 * (1 - $x), $p)) / $norm;
        $esp   = [];
        foreach (self::ESPECIALES as $clave) {
            $esp[$clave] = $triple($clave) / $norm;
        }

        // Diamantes: si la 1ª tirada no es especial, una 2ª puede reemplazarla por un especial
        if ($vida !== 'normal') {
            // Vida comprada: especial con chance fija; si no, tirada normal sin especiales
            $chance     = self::VIDAS_TIPOS[$vida]['chance'] / 100;
            if ($moneda === 'diamante') {
                // Segunda oportunidad con la misma chance
                $chance = 1 - (1 - $chance) ** 2;
            }
            $baseEsp    = array_sum($esp);
            $escala     = (1 - $chance) / (1 - $baseEsp);
            $triplesDinero *= $escala;
            $par           *= $escala;
            // tiradaEspecial elige el especial según su peso
            $pesosEsp = array_sum(array_map(fn ($clave) => $this->pesoTriple($clave), self::ESPECIALES));
            foreach (self::ESPECIALES as $clave) {
                $esp[$clave] = $this->pesoTriple($clave) / $pesosEsp * $chance;
            }
        } elseif ($moneda === 'diamante') {
            $noEspecial     = 1 - array_sum($esp);
            $triplesDinero *= $noEspecial;
            $par           *= $noEspecial;
            $esp = array_map(fn ($x) => $x + $noEspecial * $x, $esp);
        }

        return [
            ['nombre' => 'Algún premio', 'icono' => '🎰',  'valor' => ($triplesDinero + array_sum($esp) + $par) * 100],
            ['nombre' => 'Par (x2)', 'icono' => '🔁', 'valor' => $par * 100],
            ['nombre' => '3 iguales', 'icono' => '🪙',  'valor' => $triplesDinero * 100],
            ['nombre' => 'Buff', 'clave' => 'buff',             'valor' => $esp['buff'] * 100],
            ['nombre' => 'Explor. rápida', 'clave' => 'rapida', 'valor' => $esp['rapida'] * 100],
            ['nombre' => 'Set', 'clave' => 'set',               'valor' => $esp['set'] * 100],
            ['nombre' => 'Pociones drop', 'clave' => 'pocion',  'valor' => $esp['pocion'] * 100],
            ['nombre' => 'Vidas extra', 'clave' => 'vida',      'valor' => $esp['vida'] * 100],
            ['nombre' => 'Cargas caza', 'clave' => 'caza',      'valor' => $esp['caza'] * 100],
            ['nombre' => 'Potas esmeralda', 'clave' => 'pota_esmeralda', 'valor' => $esp['pota_esmeralda'] * 100],
        ];
    }

    public function render()
    {
        $personaje = Personaje::find($this->personajeId);

        if ($personaje) {
            $this->recargarVidas($personaje);
            if ($personaje->isDirty(['casino_vidas', 'casino_vidas_desde'])) {
                $personaje->save();
            }
        }

        return view('livewire.casino', [
            'vidas'    => $personaje ? $this->estadoVidas($personaje) : ['actuales' => 0, 'siguiente' => null, 'compradas' => array_fill_keys(array_keys(self::VIDAS_TIPOS), 0)],
            'vidasMax' => self::VIDAS_MAX,
            'vidasTipos' => self::VIDAS_TIPOS,
            'costoVidaNormal' => self::COSTO_VIDA_NORMAL,
            'vidaHoras' => self::VIDA_HORAS,
            'oro'      => $personaje->oro ?? 0,
            'diamante' => $personaje->diamante ?? 0,
            'simbolos' => self::SIMBOLOS,
            'apuestas' => self::APUESTAS,
            'pagoPar'  => self::PAGO_PAR,
            'nivelSetMin' => self::NIVEL_SET_MIN,
            'nivelSetMax' => self::NIVEL_SET_MAX,
            'buffPorcentajes' => self::BUFF_PORCENTAJES,
            'buffHoras'   => self::BUFF_HORAS,
            'rapidaHoras' => self::RAPIDA_HORAS,
            'pocionesPremio' => self::POCIONES_PREMIO,
            'vidasPremio'    => self::VIDAS_PREMIO,
            'cazaCargasPremio' => self::CAZA_CARGAS_PREMIO,
            // Historial guardado: últimas HISTORIAL_MAX tiradas
            'historial' => $personaje
                ? CasinoTirada::where('personaje_id', $personaje->id)->latest('id')->limit(self::HISTORIAL_MAX)->get()
                    ->map->paraVista()->values()
                : [],
            'historialMax' => self::HISTORIAL_MAX,
            // Caras de personajes que se muestran en el rodillo cuando cae el símbolo del set
            'carasSets' => Post::whereBetween('nivel', [self::NIVEL_SET_MIN, self::NIVEL_SET_MAX])
                ->where('publicado', true)
                ->whereNotNull('imagen')
                ->inRandomOrder()
                ->limit(40)
                ->pluck('imagen')
                ->map(fn ($imagen) => asset('storage/' . $imagen))
                ->values(),
            'especiales' => self::ESPECIALES,
            'probabilidades' => collect(['normal' => null] + self::VIDAS_TIPOS)
                ->map(fn ($_, $vida) => [
                    'oro'      => $this->probabilidades('oro', $vida),
                    'diamante' => $this->probabilidades('diamante', $vida),
                ])->all(),
        ]);
    }
}
