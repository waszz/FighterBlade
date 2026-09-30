<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

     protected $fillable = [
        'titulo',
        'user_id',
        'nivel',
        'gif',
        'gif_defensa',
        'gif_ataque',
        'gif_critico',
        'gif_especial',
        'gif_derrota',
        'gif_victoria',
        'stats',
        'imagen',
        'tipo',
        'ciudad_id',
        'es_enemigo',
        'poder',
        'accesorio',
        'imagen1',
        'imagen2',
        'imagen3',
        'tipo',
        'stats_equipo',
        'stats_entrenamiento',
        'stats_accesorio',
        'equipo_nombre',
        'entrenamiento_nombre',
        'accesorio_nombre',
        'equipo_imagen',
        'entrenamiento_imagen',
        'accesorio_imagen',
        'ajustes_manuales_equipo',
        'ajustes_manuales_entrenamiento',
        'ajustes_manuales_accesorio',
        'requisitos_equipo',
        'requisitos_entrenamiento',
        'requisitos_accesorio',



    ];

protected $casts = [
    'stats' => 'array',
    'stats_equipo' => 'array',
    'stats_entrenamiento' => 'array',
    'stats_accesorio' => 'array',
    'ajustes_manuales_equipo'       => 'array',
    'ajustes_manuales_entrenamiento'=> 'array',
    'ajustes_manuales_accesorio'    => 'array',
    'requisitos_equipo' => 'array',
    'requisitos_entrenamiento' => 'array',
    'requisitos_accesorio' => 'array',
    'gif_medidas' => 'array',
    'gifs_girados' => 'array',
    'gif_ajustes' => 'array',
];

    // Relación: este post (enemigo) pertenece a una ciudad
    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id');
    }

    // Relación con personajes que tengan este post como enemigo
    // Ojo: esto depende del esquema. Si personajes tienen campo 'es_enemigo' boolean, esta relación no tiene sentido.
    // Si personajes tienen relación con posts enemigos, tendrías que ajustarlo.
    // Por ejemplo, si 'es_enemigo' es un campo en posts, no en personajes.
    // Aquí te dejo un ejemplo si personajes tienen muchos enemigos:
    /*
    public function personajesQueLoTienenComoEnemigo()
    {
        return $this->belongsToMany(Personaje::class, 'personaje_post_enemigo', 'post_id', 'personaje_id');
    }
    */

    // Relación muchos a muchos con poderes
public function poderes()
{
    return $this->belongsToMany(Poder::class, 'poder_post', 'post_id', 'poder_id');
}
    // En tu modelo Post:
public function getNombreAttribute()
{
    return $this->titulo;
}

    // Gifs del set que se miden para mostrarlos a la misma escala
    const CAMPOS_GIF = ['gif', 'gif_ataque', 'gif_critico', 'gif_especial', 'gif_defensa', 'gif_derrota', 'gif_victoria'];

    // Alto en px del personaje en pantalla (la mediana de los sets es 113) y cuánto se permite escalar
    const GIF_ALTO_OBJETIVO = 130;
    const GIF_ZOOM_MIN = 0.45;
    const GIF_ZOOM_MAX = 1.5;

    protected static array $estilosGif = [];

    // Sets ocultos (no aparecen en Personajes, Mercado, Casino, Caza ni como enemigos normales al explorar):
    // el enemigo especial de bienvenida y los rivales de Misiones
    const ENEMIGO_ESPECIAL = 2;
    const RIVAL_MISION = 3;
    // Variantes de la zona inicial (Black / normal / Gold de cada set de nivel 5): ocultas en las listas
    const VARIANTE_ZONA = 4;
    const SCOPE_SIN_RIVALES = 'sinRivalesMision';

    // Consulta que incluye también a los rivales de misión (combate, misiones y admin)
    public static function conRivales()
    {
        return static::withoutGlobalScope(self::SCOPE_SIN_RIVALES);
    }

    // Livewire vuelve a buscar los modelos en cada request: tiene que encontrar también a los rivales
    public function newQueryForRestoration($ids)
    {
        return parent::newQueryForRestoration($ids)->withoutGlobalScope(self::SCOPE_SIN_RIVALES);
    }

    // Las rutas del admin (Ver/Editar personaje) también encuentran a los rivales
    public function resolveRouteBinding($value, $field = null)
    {
        return static::conRivales()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    protected static function booted()
    {
        static::addGlobalScope(self::SCOPE_SIN_RIVALES, function ($query) {
            // Buscar un set puntual por su id (Post::find, la relación de una parte con su set, etc.) sí encuentra
            // a los especiales: así funcionan las partes de un rival que un admin se agrega para probar.
            // Los especiales solo se ocultan en las listas.
            $porId = collect($query->getQuery()->wheres)->contains(fn ($w) => in_array($w['column'] ?? null, ['id', 'posts.id'], true)
                && in_array($w['type'] ?? null, ['Basic', 'In'], true) && ($w['operator'] ?? '=') === '=');
            if ($porId) {
                return;
            }
            $query->where(fn ($q) => $q->whereNull('posts.es_enemigo')
                ->orWhereNotIn('posts.es_enemigo', [self::ENEMIGO_ESPECIAL, self::RIVAL_MISION, self::VARIANTE_ZONA]));
        });

        // Al cambiar algún gif: si tiene fondo magenta se hace transparente (antes había que correr
        // "php artisan gifs:transparentar") y se vuelven a medir
        static::saving(function (Post $post) {
            foreach (self::CAMPOS_GIF as $campo) {
                if ($post->isDirty($campo) && $post->$campo) {
                    \App\Support\GifTransparente::arreglarArchivo($post->$campo);
                }
            }
            if (! $post->gif_medidas || $post->isDirty(self::CAMPOS_GIF)) {
                $post->gif_medidas = $post->medirGifs();
            }
        });
    }

    // [alto del personaje, px vacíos debajo] del primer cuadro de cada gif
    public function medirGifs(): array
    {
        $medidas = [];
        foreach (self::CAMPOS_GIF as $campo) {
            if ($this->$campo && $m = self::medirGif(storage_path('app/public/' . $this->$campo))) {
                $medidas[$campo] = $m;
            }
        }
        return $medidas;
    }

    public static function medirGif(string $ruta): ?array
    {
        if (! is_file($ruta) || strtolower(pathinfo($ruta, PATHINFO_EXTENSION)) !== 'gif') {
            return null;
        }
        $im = @imagecreatefromgif($ruta);
        if (! $im) {
            return null;
        }

        $ancho = imagesx($im);
        $alto = imagesy($im);

        // GD abre el primer cuadro con su propio tamaño: si no ocupa todo el gif (cuadro parcial),
        // se mide la animación completa con el tamaño real del lienzo
        [$anchoLienzo, $altoLienzo] = getimagesize($ruta) ?: [$ancho, $alto];
        if ($ancho !== $anchoLienzo || $alto !== $altoLienzo) {
            return self::medirCuadrosGif(file_get_contents($ruta), $altoLienzo);
        }

        $transparente = imagecolortransparent($im);
        $filaConPixel = function (int $y) use ($im, $ancho, $transparente) {
            for ($x = 0; $x < $ancho; $x++) {
                if (imagecolorat($im, $x, $y) !== $transparente) {
                    return true;
                }
            }
            return false;
        };

        $arriba = 0;
        while ($arriba < $alto && ! $filaConPixel($arriba)) {
            $arriba++;
        }
        $abajo = $alto - 1;
        while ($abajo > $arriba && ! $filaConPixel($abajo)) {
            $abajo--;
        }

        // Primer cuadro vacío (ej. el personaje "aparece" en la animación): se mide la unión de todos los cuadros
        if ($arriba >= $alto) {
            return self::medirCuadrosGif(file_get_contents($ruta), $alto);
        }

        return [$abajo - $arriba + 1, $alto - 1 - $abajo];
    }

    // [alto del contenido, px vacíos debajo] de la animación completa. GD solo lee el primer cuadro,
    // así que se separa cada cuadro en un gif propio y se van "pintando" sobre una grilla como lo hace el
    // navegador (los cuadros pueden ser parciales y algunos se borran al terminar: disposal 2).
    // Los pies son la línea más baja que MÁS SE REPITE entre los cuadros (donde el personaje está parado la
    // mayor parte del tiempo), así no cuentan efectos de un par de cuadros ni la "aparición" del personaje.
    protected static function medirCuadrosGif(string $datos, int $altoLienzo): ?array
    {
        if (strlen($datos) < 13 || ! str_starts_with($datos, 'GIF')) {
            return null;
        }

        $lsd = substr($datos, 6, 7);
        $empaquetado = ord($lsd[4]);
        $pos = 13;
        $tablaGlobal = '';
        if ($empaquetado & 0x80) {
            $tamano = 3 * (2 ** (($empaquetado & 0x07) + 1));
            $tablaGlobal = substr($datos, 13, $tamano);
            $pos += $tamano;
        }
        $largo = strlen($datos);
        $saltarSubbloques = function (int $p) use ($datos, $largo): int {
            while ($p < $largo && ($n = ord($datos[$p])) !== 0) {
                $p += $n + 1;
            }
            return $p + 1;
        };

        $grilla = [];            // [y][x] => true donde hay píxel visible
        $cuadros = [];           // [fila más alta, fila más baja] de cada cuadro ya pintado
        $borrarPendiente = null; // área del cuadro anterior que se borra antes de pintar el siguiente
        $controlGrafico = '';
        while ($pos < $largo) {
            $bloque = ord($datos[$pos]);
            if ($bloque === 0x3B) {
                break;
            }
            if ($bloque === 0x21) { // extensión (la de control gráfico trae el color transparente)
                $fin = $saltarSubbloques($pos + 2);
                if (ord($datos[$pos + 1]) === 0xF9) {
                    $controlGrafico = substr($datos, $pos, $fin - $pos);
                }
                $pos = $fin;
                continue;
            }
            if ($bloque !== 0x2C) {
                break;
            }

            $cuadro = unpack('vizq/varr/vw/vh', substr($datos, $pos + 1, 8));
            $empaquetadoCuadro = ord($datos[$pos + 9]);
            $p = $pos + 10;
            $tablaLocal = '';
            if ($empaquetadoCuadro & 0x80) {
                $tamano = 3 * (2 ** (($empaquetadoCuadro & 0x07) + 1));
                $tablaLocal = substr($datos, $p, $tamano);
                $p += $tamano;
            }
            $fin = $saltarSubbloques($p + 1);

            $gif = 'GIF89a' . pack('vv', $cuadro['w'], $cuadro['h']) . substr($lsd, 4) . $tablaGlobal . $controlGrafico
                . "\x2C" . pack('vvvv', 0, 0, $cuadro['w'], $cuadro['h']) . chr($empaquetadoCuadro) . $tablaLocal
                . substr($datos, $p, $fin - $p) . "\x3B";
            $disposal = $controlGrafico !== '' ? (ord($controlGrafico[3]) >> 2) & 0x07 : 0;
            $controlGrafico = '';
            $pos = $fin;

            // Borrar el área del cuadro anterior si pedía "volver al fondo"
            if ($borrarPendiente) {
                [$bx, $by, $bw, $bh] = $borrarPendiente;
                for ($y = $by; $y < $by + $bh; $y++) {
                    for ($x = $bx; $x < $bx + $bw; $x++) {
                        unset($grilla[$y][$x]);
                    }
                }
                $borrarPendiente = null;
            }

            $im = @imagecreatefromstring($gif);
            if ($im) {
                $transparente = imagecolortransparent($im);
                for ($y = 0; $y < $cuadro['h']; $y++) {
                    for ($x = 0; $x < $cuadro['w']; $x++) {
                        if (imagecolorat($im, $x, $y) !== $transparente) {
                            $grilla[$cuadro['arr'] + $y][$cuadro['izq'] + $x] = true;
                        }
                    }
                }
            }
            if ($disposal === 2) {
                $borrarPendiente = [$cuadro['izq'], $cuadro['arr'], $cuadro['w'], $cuadro['h']];
            }

            // Cómo se ve este cuadro: fila más alta y más baja con píxeles
            $filas = array_keys(array_filter($grilla));
            if ($filas) {
                $cuadros[] = [min($filas), max($filas)];
            }
        }

        if (empty($cuadros)) {
            return null;
        }

        // Línea de los pies = la fila más baja que más se repite; el alto, el mayor entre esos cuadros
        $porAbajo = [];
        foreach ($cuadros as [$arriba, $abajo]) {
            $porAbajo[$abajo][] = $abajo - $arriba + 1;
        }
        uasort($porAbajo, fn ($a, $b) => count($b) <=> count($a));
        $abajo = array_key_first($porAbajo);

        return [max($porAbajo[$abajo]), max(0, $altoLienzo - 1 - $abajo)];
    }

    // Ajuste manual de una animación: escala (1 = sin cambio) y px a subir (negativo = bajar)
    public function ajusteGif(string $campo): array
    {
        $ajuste = $this->gif_ajustes[$campo] ?? [];

        return ['escala' => (float) ($ajuste['escala'] ?? 1), 'subir' => (int) ($ajuste['subir'] ?? 0)];
    }

    // ¿Esta animación viene mirando a la izquierda?
    public function gifGirado(string $campo): bool
    {
        return in_array($campo, $this->gifs_girados ?? [], true);
    }

    // Estilo inline para un gif de cualquier set (por su ruta): misma escala para todos, pies en la misma línea
    // y, si esa animación viene mirando a la izquierda (está en gifs_girados), un giro que la endereza.
    // El giro usa la propiedad `rotate` para sumarse (no pisar) al volteo con scale-x que ya hace cada vista.
    // $factor achica/agranda para lugares con menos espacio (ej. el sidebar usa 0.8).
    public static function estiloGif(?string $ruta, float $factor = 1): string
    {
        if (! $ruta) {
            return '';
        }

        if (! self::$estilosGif) {
            self::cargarEstilosGif();
        }

        // El tamaño se multiplica por --escala-gif (1 en PC; en el celular es menor, ver app.css): así todos los gifs
        // se achican juntos en pantallas chicas sin tocar cada vista.
        // Sin medidas (ej. sets que solo tienen una foto): se limita el alto para que no salga gigante
        $sinMedidas = sprintf('max-height:calc(%dpx * var(--escala-gif, 1))', self::GIF_ALTO_OBJETIVO * $factor);

        $estilo = self::$estilosGif[$ruta] ?? null;
        if (! $estilo) {
            return $sinMedidas;
        }

        [$zoom, $pie, $girado, $subir] = $estilo;
        $filtro = $estilo[4] ?? null; // tinte de las variantes Black / Gold
        $css = $zoom !== null ? sprintf('zoom:calc(%.3f * var(--escala-gif, 1));margin-bottom:%dpx', $zoom * $factor, $subir - $pie) : $sinMedidas;

        return ltrim($css . ($girado ? ';rotate:y 180deg' : '') . ($filtro ? ';filter:' . $filtro : ''), ';');
    }

    // [zoom, px vacíos debajo, girado, px a subir] de cada gif de todos los sets, por ruta
    protected static function cargarEstilosGif(): void
    {
        foreach (self::conRivales()->get(array_merge(['id', 'gif_medidas', 'gifs_girados', 'gif_escala', 'gif_ajustes', 'filtro_gif'], self::CAMPOS_GIF)) as $post) {
            $medidas = $post->gif_medidas ?? [];
            // La escala sale del gif principal, así todas las animaciones del set quedan del mismo tamaño,
            // y se multiplica por el ajuste manual del set (gif_escala, 1 = sin ajuste)
            $base = $medidas['gif'][0] ?? null;
            $zoom = $base ? max(self::GIF_ZOOM_MIN, min(self::GIF_ZOOM_MAX, self::GIF_ALTO_OBJETIVO / $base)) : 1;
            $zoom *= (float) ($post->gif_escala ?: 1);

            foreach (self::CAMPOS_GIF as $campo) {
                if (! $post->$campo) {
                    continue;
                }
                // Si esta animación no se pudo medir usa la escala del set; encima va su ajuste manual (tamaño y altura)
                $ajuste = $post->ajusteGif($campo);
                self::$estilosGif[$post->$campo] = [
                    isset($medidas[$campo]) || $base ? $zoom * $ajuste['escala'] : null,
                    $medidas[$campo][1] ?? 0,
                    $post->gifGirado($campo),
                    $ajuste['subir'],
                    $post->filtro_gif,
                ];
            }
        }
        // Marca de "ya cargado" aunque no haya sets
        self::$estilosGif['__cargado'] = null;
    }

    // Stats que aprovecha cada poder (para elegir los requisitos que lo complementan)
    const AFINIDAD_PODERES = [
        'ABSORVER SALUD'      => ['ataque'],
        'ROBAR VIDA'          => ['ataque'],
        'ATAQUE DESESPERADO'  => ['resistencia', 'defensa'],
        'ATAQUE TRAICIONERO'  => ['velocidad'],
        'CAMUFLAJE'           => ['velocidad'],
        'COMBO VELOZ'         => ['velocidad'],
        'CONGELAR'            => ['energia'],
        'CONTROL CLIMATICO'   => ['energia', 'velocidad'],
        'DAMAGE ABSORV'       => ['resistencia'],
        'DIRECT DAMAGE'       => ['energia'],
        'ENEMISTAD'           => ['fuerza', 'ataque', 'velocidad'],
        'ESPINAS'             => ['defensa'],
        'FRENESÍ'             => ['ataque', 'fuerza', 'velocidad'],
        'FURIA CIEGA'         => ['ataque', 'fuerza', 'energia'],
        'HEMORRAGIA'          => ['ataque'],
        'INSTINTO MEJORADO'   => ['velocidad', 'ataque', 'energia'],
        'INTIMIDAR'           => ['fuerza'],
        'MÁXIMA POTENCIA'     => ['velocidad'],
        'PIEL DURA'           => ['defensa'],
        'PIEL IMPENETRABLE'   => ['defensa'],
        'QUEMAR'              => ['ataque'],
        'REDUCCIÓN ELEMENTAL' => ['resistencia'],
        'REGENERAR'           => ['resistencia'],
        'REGENERAR SUPERIOR'  => ['resistencia'],
        'SANGRADO'            => ['ataque'],
        'SUPER CARGA'         => ['energia'],
        'SUPER NOVA'          => ['energia'],
        'SUPER SENTIDOS'      => ['ataque', 'fuerza', 'velocidad'],
        'TELETRANSPORTARSE'   => ['velocidad'],
        'TRANCE'              => ['ataque', 'energia'],
    ];

    // Partes del set: cada una pide un stat igual al nivel del set (ej: nivel 70 → ATA 70)
    const PARTES_REQUISITO = ['equipo', 'entrenamiento', 'accesorio'];

    // 1 stat por parte (3 distintos): los que más usa el set según sus stats y sus poderes
    public function calcularRequisitos(): array
    {
        $stats = is_array($this->stats) ? $this->stats : (json_decode($this->stats ?? '[]', true) ?: []);
        $max = max(1, max($stats ?: [1]));

        // Peso por stats del set (0-100) + 30 por poder repartido entre los stats que aprovecha
        $puntaje = [];
        foreach (['fuerza', 'ataque', 'energia', 'velocidad', 'defensa', 'resistencia'] as $stat) {
            $puntaje[$stat] = ($stats[$stat] ?? 0) / $max * 100;
        }
        foreach ($this->poderes as $poder) {
            $afines = self::AFINIDAD_PODERES[mb_strtoupper($poder->nombre)] ?? [];
            foreach ($afines as $stat) {
                $puntaje[$stat] += 30 / count($afines);
            }
        }
        arsort($puntaje);
        $elegidos = array_slice(array_keys($puntaje), 0, 3);

        $requisitos = [];
        foreach (self::PARTES_REQUISITO as $i => $parte) {
            $requisitos[$parte] = [$elegidos[$i] => (int) $this->nivel];
        }

        return $requisitos;
    }

    // Todos los sets: los stats que dan sus partes dependen del tipo de daño. Cada nivel rota entre las opciones de
    // su tipo (5, 20, 35, 50... → la 1ª; 10, 25, 40, 55... → la 2ª; 15, 30, 45, 60... → la 3ª). Los híbridos tienen una sola
    const STATS_POR_TIPO = [
        'elemental' => [['velocidad', 'ataque', 'energia'], ['resistencia', 'energia', 'ataque'], ['defensa', 'energia', 'ataque']],
        'fisico'    => [['fuerza', 'ataque', 'velocidad'], ['defensa', 'fuerza', 'ataque'], ['resistencia', 'ataque', 'fuerza']],
        'hibrido'   => [['fuerza', 'velocidad', 'ataque', 'energia']],
    ];

    // Los stats que tiene que dar este set según su tipo y nivel, o null si no tiene tipo
    public function statsPorTipo(): ?array
    {
        $opciones = self::STATS_POR_TIPO[$this->tipo] ?? null;
        if (! $opciones) {
            return null;
        }
        return $opciones[max(0, intdiv((int) $this->nivel, 5) - 1) % count($opciones)];
    }

    // Aplica statsPorTipo: el total de puntos que dan las 3 partes no cambia y se reparte en partes iguales entre
    // esos stats (y nada en los demás). Cada parte da uno de ellos y lo pide de requisito; en los híbridos el 4º stat
    // (energía) se reparte entre las 3 partes. También actualiza las partes que ya tienen los jugadores.
    public function aplicarStatsPorTipo(): bool
    {
        $elegidos = $this->statsPorTipo();
        if (! $elegidos) {
            return false;
        }

        $total = 0;
        foreach (self::PARTES_REQUISITO as $parte) {
            $total += array_sum(array_map(fn ($v) => max(0, (int) $v), $this->{'ajustes_manuales_' . $parte} ?? []));
        }

        // Puntos de cada stat: partes iguales (el resto, de a 1 a los primeros)
        $porStat = [];
        foreach ($elegidos as $i => $stat) {
            $porStat[$stat] = intdiv($total, count($elegidos)) + ($i < $total % count($elegidos) ? 1 : 0);
        }

        $vacio = array_fill_keys(['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'], 0);
        foreach (self::PARTES_REQUISITO as $i => $parte) {
            $da = $vacio;
            $da[$elegidos[$i]] = $porStat[$elegidos[$i]];
            // Híbridos: el 4º stat, repartido entre las 3 partes
            if (isset($elegidos[3])) {
                $da[$elegidos[3]] = intdiv($porStat[$elegidos[3]], 3) + ($i < $porStat[$elegidos[3]] % 3 ? 1 : 0);
            }
            $requisito = [$elegidos[$i] => (int) $this->nivel];

            $this->{'ajustes_manuales_' . $parte} = $da;
            $this->{'requisitos_' . $parte} = $requisito;
        }
        $this->saveQuietly();
        $this->sincronizarPartesEnInventarios();

        return true;
    }

    // Las partes que ya tienen los jugadores (en el inventario, equipadas o a la venta) son copias de cuando cayeron:
    // se les pone lo que el set tiene ahora (stats, requisito, nivel y, si el set los tiene cargados, nombre e imagen)
    public function sincronizarPartesEnInventarios(): int
    {
        $actualizadas = 0;
        foreach (self::PARTES_REQUISITO as $parte) {
            $datos = [
                'stats'                => json_encode($this->{'ajustes_manuales_' . $parte} ?? []),
                'requisitos_' . $parte => json_encode($this->{'requisitos_' . $parte} ?? []),
                'nivel'                => (int) $this->nivel,
            ];
            if ($this->{$parte . '_nombre'}) {
                $datos['nombre'] = $this->{$parte . '_nombre'};
            }
            if ($this->{$parte . '_imagen'}) {
                $datos['imagen'] = $this->{$parte . '_imagen'};
            }
            $actualizadas += Objeto::where('origen_post_id', $this->id)->where('tipo', $parte)->update($datos);
        }
        return $actualizadas;
    }

    // Stats de pelea (los que usa como enemigo: exploración, Torre, misiones). Siguen la misma regla de su tipo de daño:
    // cada stat conserva una base de 5 (como un jugador sin nada equipado) y el resto del total se reparte en partes
    // iguales entre los stats de su tipo. El total no cambia.
    //  - Sets normales y variantes de la zona inicial: la misma combinación que dan sus partes (statsPorTipo)
    //  - Rivales de misión: hay uno por nivel y la combinación rota en cada nivel (5 → la 1ª, 6 → la 2ª, 7 → la 3ª...)
    //  - El enemigo de bienvenida (Wolverine) no cambia
    const BASE_STAT_RIVAL = 5;

    public function statsPeleaPorTipo(): ?array
    {
        return match (true) {
            (int) $this->es_enemigo === self::RIVAL_MISION => $this->statsPorTipoRival(),
            (int) $this->es_enemigo === self::ENEMIGO_ESPECIAL => null,
            default => $this->statsPorTipo(),
        };
    }

    public function statsPorTipoRival(): ?array
    {
        $opciones = self::STATS_POR_TIPO[$this->tipo] ?? null;
        if (! $opciones || (int) $this->es_enemigo !== self::RIVAL_MISION) {
            return null;
        }
        $indice = ((int) $this->nivel - 5) % count($opciones);
        return $opciones[$indice < 0 ? $indice + count($opciones) : $indice];
    }

    // Solo rivales de misión (lo usa la migración 2026_09_30_000003)
    public function aplicarStatsPorTipoRival(): bool
    {
        return (int) $this->es_enemigo === self::RIVAL_MISION && $this->aplicarStatsPelea();
    }

    public function aplicarStatsPelea(): bool
    {
        $elegidos = $this->statsPeleaPorTipo();
        if (! $elegidos) {
            return false;
        }
        $orden = ['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'];
        $actuales = is_array($this->stats) ? $this->stats : (json_decode($this->stats ?? '[]', true) ?: []);
        $total = array_sum(array_map(fn ($v) => max(0, (int) $v), $actuales));

        $base = min(self::BASE_STAT_RIVAL, intdiv($total, count($orden)));
        $resto = $total - $base * count($orden);
        $nuevos = array_fill_keys($orden, $base);
        foreach ($elegidos as $i => $stat) {
            $nuevos[$stat] += intdiv($resto, count($elegidos)) + ($i < $resto % count($elegidos) ? 1 : 0);
        }

        $this->stats = $nuevos;
        $this->saveQuietly();
        return true;
    }

    // Stats para mostrar del set: lo que da el set completo equipado, la suma de sus 3 partes (solo los stats que da).
    // Si no tiene tipo o las partes no tienen nada cargado, los stats del set
    public function statsSetCompleto(): array
    {
        $propios = is_array($this->stats) ? $this->stats : (json_decode($this->stats ?? '[]', true) ?: []);
        if (! $this->statsPorTipo()) {
            return $propios;
        }
        $total = array_fill_keys(['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'], 0);
        foreach (self::PARTES_REQUISITO as $parte) {
            foreach ($this->{'ajustes_manuales_' . $parte} ?? [] as $stat => $valor) {
                if (isset($total[$stat])) {
                    $total[$stat] += max(0, (int) $valor);
                }
            }
        }
        return array_filter($total) ?: $propios;
    }

    // Cuántos stats distintos puede dar cada parte según el nivel del set
    public function maxStatsPorParte(): int
    {
        return match (true) {
            $this->nivel <= 45 => 3,
            $this->nivel <= 75 => 4,
            default            => 5,
        };
    }

    // ¿Alguna parte da bonus en más stats de los permitidos?
    public function partesExcedenMaxStats(): bool
    {
        foreach (self::PARTES_REQUISITO as $parte) {
            if (count(array_filter($this->{'ajustes_manuales_' . $parte} ?? [], fn ($v) => $v > 0)) > $this->maxStatsPorParte()) {
                return true;
            }
        }
        return false;
    }

    // Reparte los bonus del set entre sus 3 partes: cuántos stats da cada parte (1 al máximo del nivel) y cuáles
    // salen al azar, con semilla fija por set (siempre sale igual). El total de cada stat del set no cambia;
    // cada parte da el stat que pide de requisito y se emparejan los puntos donde se puede.
    public function repartirAjustes(): array
    {
        $orden = ['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'];
        $partes = self::PARTES_REQUISITO;
        $maximo = $this->maxStatsPorParte();
        $random = new \Random\Randomizer(new \Random\Engine\Mt19937(crc32('reparto-' . $this->id)));

        $pendientes = array_fill_keys($orden, 0);
        foreach ($partes as $parte) {
            foreach (($this->{'ajustes_manuales_' . $parte} ?? []) as $stat => $valor) {
                if (isset($pendientes[$stat])) {
                    $pendientes[$stat] += max(0, (int) $valor);
                }
            }
        }
        $pendientes = array_filter($pendientes);

        $reparto = array_fill_keys($partes, []);

        // 1) Cada parte se queda con el stat que pide de requisito
        foreach ($partes as $parte) {
            $stat = array_key_first($this->{'requisitos_' . $parte} ?? []);
            if ($stat && isset($pendientes[$stat])) {
                $reparto[$parte][$stat] = $pendientes[$stat];
                unset($pendientes[$stat]);
            }
        }

        // 2) Cuántos stats da cada parte (al azar, de 2 al máximo), con lugar suficiente para todos los stats del set.
        //    Con al menos 2 stats cada parte puede recibir puntos de las otras y quedan parejas.
        $minimo = min(2, $maximo);
        $necesarios = count($pendientes) + array_sum(array_map('count', $reparto));
        $tope = array_fill_keys($partes, $maximo);
        for ($intento = 0; $intento < 50; $intento++) {
            $prueba = [];
            foreach ($partes as $parte) {
                $prueba[$parte] = $random->getInt(max($minimo, count($reparto[$parte])), $maximo);
            }
            if (array_sum($prueba) >= $necesarios) {
                $tope = $prueba;
                break;
            }
        }

        // 3) El resto de los stats, enteros y en orden al azar, a la parte con menos puntos que tenga lugar
        foreach ($random->shuffleArray(array_keys($pendientes)) as $stat) {
            $conLugar = array_values(array_filter($partes, fn ($p) => count($reparto[$p]) < $tope[$p]));
            usort($conLugar, fn ($a, $b) => array_sum($reparto[$a]) <=> array_sum($reparto[$b]));
            $reparto[$conLugar[0]][$stat] = $pendientes[$stat];
        }

        // 3b) Las partes que todavía tienen lugar comparten un stat al azar de otra parte (le toman un pedazo)
        foreach ($random->shuffleArray($partes) as $parte) {
            while (count($reparto[$parte]) < $tope[$parte]) {
                $opciones = [];
                foreach ($partes as $otra) {
                    foreach ($reparto[$otra] as $stat => $valor) {
                        if ($otra !== $parte && ! isset($reparto[$parte][$stat]) && $valor >= 10) {
                            $opciones[] = [$otra, $stat];
                        }
                    }
                }
                if (! $opciones) {
                    break;
                }
                [$otra, $stat] = $opciones[$random->getInt(0, count($opciones) - 1)];
                $pedazo = max(5, intdiv($reparto[$otra][$stat] * $random->getInt(25, 45), 100));
                $reparto[$otra][$stat] -= $pedazo;
                $reparto[$parte][$stat] = $pedazo;
            }
        }

        // 4) Emparejar puntos: de la parte con más a la de menos, probando todos los pares
        //    (sin superar su cantidad de stats ni sacarle a una parte el stat que pide de requisito)
        $statRequisito = [];
        foreach ($partes as $parte) {
            $statRequisito[$parte] = array_key_first($this->{'requisitos_' . $parte} ?? []);
        }
        $mover = function (string $desde, string $hacia) use (&$reparto, $tope, $statRequisito): bool {
            $mitad = intdiv(array_sum($reparto[$desde]) - array_sum($reparto[$hacia]), 2);
            if ($mitad <= 1) {
                return false;
            }
            arsort($reparto[$desde]);
            foreach ($reparto[$desde] as $stat => $valor) {
                $statNuevo = ! isset($reparto[$hacia][$stat]);
                if ($statNuevo && count($reparto[$hacia]) >= $tope[$hacia]) {
                    continue;
                }
                // Se puede pasar el stat entero salvo que sea el requisito de la parte; si no, que queden al menos 3
                $pasar = ($valor <= $mitad && $stat !== $statRequisito[$desde]) ? $valor : min($mitad, $valor - 3);
                // No abrir un stat nuevo en la parte por unos pocos puntos (evita bonus tipo +1 / +2)
                if ($pasar > 0 && (! $statNuevo || $pasar >= 5)) {
                    $reparto[$desde][$stat] -= $pasar;
                    $reparto[$hacia][$stat] = ($reparto[$hacia][$stat] ?? 0) + $pasar;
                    if ($reparto[$desde][$stat] === 0) {
                        unset($reparto[$desde][$stat]);
                    }
                    return true;
                }
            }
            return false;
        };
        for ($i = 0; $i < 40; $i++) {
            $porTotal = $partes;
            usort($porTotal, fn ($a, $b) => array_sum($reparto[$a]) <=> array_sum($reparto[$b]));
            if (array_sum($reparto[end($porTotal)]) - array_sum($reparto[$porTotal[0]]) <= 2) {
                break;
            }
            // Primero mayor → menor; si no se puede, los pares intermedios
            if (! $mover($porTotal[2], $porTotal[0]) && ! $mover($porTotal[2], $porTotal[1]) && ! $mover($porTotal[1], $porTotal[0])) {
                break;
            }
        }

        // 5) Bonus de 1-2 puntos: se juntan con el mismo stat de otra parte
        foreach ($partes as $parte) {
            foreach ($reparto[$parte] as $stat => $valor) {
                if ($valor >= 3 || $stat === $statRequisito[$parte]) {
                    continue;
                }
                foreach ($partes as $otra) {
                    if ($otra !== $parte && isset($reparto[$otra][$stat])) {
                        $reparto[$otra][$stat] += $valor;
                        unset($reparto[$parte][$stat]);
                        break;
                    }
                }
            }
        }

        $resultado = [];
        foreach ($partes as $parte) {
            foreach ($orden as $stat) {
                $resultado[$parte][$stat] = $reparto[$parte][$stat] ?? 0;
            }
        }
        return $resultado;
    }

    // Personajes base: los posts jugables de nivel inicial que se ofrecen al crear un personaje.
    // Son siempre los mismos para todos: los marcados como iniciales (Batman, Spider-Man, Wolverine, Superman, Goku)
    public function scopePersonajesBase($query)
    {
        return $query->where('inicial', true)
                     ->where('publicado', true)
                     ->orderBy('id');
    }
}