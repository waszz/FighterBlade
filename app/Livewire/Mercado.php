<?php
namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Post;
use App\Models\Objeto;
use Livewire\Component;
use App\Models\Personaje;
use App\Models\MercadoPocion;
use Illuminate\Support\Facades\Cache;

class Mercado extends Component
{
    public $personaje;
    public $objetosEnVenta = [];
    public $postsDisponibles = [];
    public $reloadMercado = 0;
    public $esAdmin = false;
    public $partesRandom = [];
    public $personajesRandom = [];
    public $pocionesMercado;
    public $costo = 0;



public function mount(Personaje $personaje)
{
    $this->personaje = $personaje->load('user');
    $this->esAdmin = $this->personaje->user && $this->personaje->user->role === 'admin';

    $this->pocionesMercado = MercadoPocion::all();

    $this->objetosEnVenta = Objeto::with(['personaje', 'post'])
    ->whereNotNull('precio_venta')
    ->get();


    $primerJugador = Personaje::sinAdmins()->orderByDesc('nivel')->first();
    $nivelReferencia = $primerJugador ? $primerJugador->nivel : 1;
    $nivelMaximo = max(1, $nivelReferencia - 5);

    $claveCacheMercado = $this->claveCacheMercado();

    $postsIds = Cache::remember($claveCacheMercado, self::proximaRotacion(), function () use ($nivelMaximo) {
        return Post::where('nivel', '<=', $nivelMaximo)
            ->inRandomOrder()
            ->take(4)
            ->pluck('id')
            ->toArray();
    });

  $this->postsDisponibles = Post::whereIn('id', $postsIds)
    ->orderByDesc('nivel')
    ->get();

    // Partes aleatorias desde cache o se carga
   $claveCachePartes = $this->claveCachePartesRandom();
$cachedPartes = Cache::get($claveCachePartes);

if ($cachedPartes) {
    $this->partesRandom = collect($cachedPartes)
        ->sortByDesc('nivel')
        ->values()
        ->take(8);
} else {
    $this->cargarPartesRandom();
}
}

public function forzarActualizacion()
{
    if (optional($this->personaje->user)->role !== 'admin') {
        $this->dispatch('error', ['message' => '❌ No tienes permisos para actualizar el mercado']);
        return;
    }

    $primerJugador = Personaje::sinAdmins()->orderByDesc('nivel')->first();
    $nivelReferencia = $primerJugador ? $primerJugador->nivel : 1;
    $nivelMaximo = max(1, $nivelReferencia - 5);

    $postsIds = Post::where('nivel', '<=', $nivelMaximo)
        ->inRandomOrder()
        ->take(4)
        ->pluck('id')
        ->toArray();

    $claveCacheMercado = $this->claveCacheMercado();

    Cache::forget($claveCacheMercado);
    Cache::put($claveCacheMercado, $postsIds, self::proximaRotacion());
    $this->postsDisponibles = Post::whereIn('id', $postsIds)
    ->orderByDesc('nivel')
    ->get();

    $claveCachePartes = $this->claveCachePartesRandom();
    Cache::forget($claveCachePartes);

    $this->cargarPartesRandom(true);

    $this->partesRandom = $this->partesRandom->toArray();

    $this->dispatch('success', ['message' => '✅ Mercado y partes aleatorias actualizados manualmente']);

    $this->reloadMercado++;
}



// El mercado se renueva los lunes y los sábados a las 00:00 (hora del servidor)
public static function ultimaRotacion(): Carbon
{
    $hoy = now()->startOfDay();
    if ($hoy->isMonday() || $hoy->isSaturday()) {
        return $hoy;
    }
    return max($hoy->copy()->previous(Carbon::SATURDAY), $hoy->copy()->previous(Carbon::MONDAY));
}

public static function proximaRotacion(): Carbon
{
    $hoy = now()->startOfDay();
    return min($hoy->copy()->next(Carbon::SATURDAY), $hoy->copy()->next(Carbon::MONDAY));
}

private function claveCacheMercado(): string
{
    return 'mercado_posts_' . self::ultimaRotacion()->toDateString();
}

private function claveCachePartesRandom(): string
{
    return 'partes_random_' . self::ultimaRotacion()->toDateString();
}

private function duracionCachePartesRandom()
{
    return self::proximaRotacion();
}
public function cargarPartesRandom($forzar = false)
{
    $cacheKey = $this->claveCachePartesRandom();

    if ($forzar) {
        Cache::forget($cacheKey);
    }

    $partes = Cache::get($cacheKey);

    if (!$partes || $forzar) {
        // Nivel objetivo: máximo 5 niveles menos, o mínimo 5 si el personaje tiene nivel bajo
        $nivelPersonaje = $this->personaje->nivel ?? 1;
        $nivelObjetivo = max(5, $nivelPersonaje - 5);

        // Traer posts aleatorios con nivel igual al nivel objetivo
        $posts = Post::where('nivel', $nivelObjetivo)
            ->inRandomOrder()
            ->take(10)
            ->get();

        // Si no hay posts en ese nivel, traer posts con nivel >= 5
        if ($posts->isEmpty()) {
            $posts = Post::where('nivel', '>=', 5)
                ->inRandomOrder()
                ->take(10)
                ->get();
        }

        $partes = [];

        foreach ($posts as $post) {
            $partesDisponibles = [];

            $nivel = $post->nivel ?? 1;
            $costoBase = 200;

            if ($post->equipo_nombre) {
                $partesDisponibles[] = [
                    'tipo' => 'equipo',
                    'nombre' => $post->equipo_nombre,
                    'imagen' => $post->equipo_imagen,
                    'ajustes' => $post->ajustes_manuales_equipo,
                    'nivel' => $nivel,
                    'costo' => $costoBase + $nivel * 100,
                    'estilo' => $post->tipo,
                    'origen_post_id' => $post->id,
                    'requisitos_equipo' => $post->requisitos_equipo,
                ];
            }

            if ($post->entrenamiento_nombre) {
                $partesDisponibles[] = [
                    'tipo' => 'entrenamiento',
                    'nombre' => $post->entrenamiento_nombre,
                    'imagen' => $post->entrenamiento_imagen,
                    'ajustes' => $post->ajustes_manuales_entrenamiento,
                    'nivel' => $nivel,
                    'costo' => $costoBase + $nivel * 100,
                    'estilo' => $post->tipo,
                    'origen_post_id' => $post->id,
                    'requisitos_entrenamiento' => $post->requisitos_entrenamiento,
                ];
            }

            if ($post->accesorio_nombre) {
                $partesDisponibles[] = [
                    'tipo' => 'accesorio',
                    'nombre' => $post->accesorio_nombre,
                    'imagen' => $post->accesorio_imagen,
                    'ajustes' => $post->ajustes_manuales_accesorio,
                    'nivel' => $nivel,
                    'costo' => $costoBase + $nivel * 100,
                    'estilo' => $post->tipo,
                    'origen_post_id' => $post->id,
                    'requisitos_accesorio' => $post->requisitos_accesorio,
                ];
            }

            if (!empty($partesDisponibles)) {
                $partes[] = $partesDisponibles[array_rand($partesDisponibles)];
            }

            if (count($partes) >= 5) {
                break;
            }
        }

        // Ordenar partes por nivel descendente antes de guardar cache
        $partes = collect($partes)->sortByDesc('nivel')->values()->toArray();

        Cache::put($cacheKey, $partes, $this->duracionCachePartesRandom());
    }

    $this->partesRandom = collect($partes)
        ->sortByDesc('nivel')
        ->values()
        ->take(5);
}


public function comprarParteAleatoria($index)
{
    $parte = $this->partesRandom[$index] ?? null;

    if (!$parte || !$this->personaje) return;

    $nivel = $parte['nivel'] ?? 1;

    // Costo fijo: multiplicar nivel por 672 (ajustalo si querés otro valor)
    $costo = 50 * $nivel;

    if ($this->personaje->oro < $costo) {
        $this->dispatch('error', ['message' => 'No tienes suficiente oro']);
        return;
    }

    $this->personaje->oro -= $costo;
    $this->personaje->save();

    // Set de la parte: el guardado en la oferta o, en las ofertas viejas (sin el id), buscado por nombre
    $post = (! empty($parte['origen_post_id']) ? Post::conRivales()->find($parte['origen_post_id']) : null)
        ?? Post::where('nivel', $nivel)
                ->where(function($query) use ($parte) {
                    $query->where('equipo_nombre', $parte['nombre'])
                          ->orWhere('entrenamiento_nombre', $parte['nombre'])
                          ->orWhere('accesorio_nombre', $parte['nombre']);
                })
                ->first();

    // Preparar requisitos según tipo
    $requisitosEquipo = [];
    $requisitosEntrenamiento = [];
    $requisitosAccesorio = [];

    if ($post) {
        $requisitosEquipo = is_array($post->requisitos_equipo) ? $post->requisitos_equipo : (json_decode($post->requisitos_equipo ?? '{}', true) ?: []);
        $requisitosEntrenamiento = is_array($post->requisitos_entrenamiento) ? $post->requisitos_entrenamiento : (json_decode($post->requisitos_entrenamiento ?? '{}', true) ?: []);
        $requisitosAccesorio = is_array($post->requisitos_accesorio) ? $post->requisitos_accesorio : (json_decode($post->requisitos_accesorio ?? '{}', true) ?: []);
    }

    $stats = is_string($parte['ajustes']) ? json_decode($parte['ajustes'], true) : ($parte['ajustes'] ?? []);

    Objeto::create([
        'personaje_id' => $this->personaje->id,
        'nombre' => $parte['nombre'],
        'tipo' => $parte['tipo'],
        'imagen' => $parte['imagen'],
        'nivel' => $post ? $post->nivel : 1,
        'stats' => $stats,
        'origen_post_id' => $post ? $post->id : null,
        'requisitos_equipo' => $parte['tipo'] === 'equipo' ? $requisitosEquipo : [],
        'requisitos_entrenamiento' => $parte['tipo'] === 'entrenamiento' ? $requisitosEntrenamiento : [],
        'requisitos_accesorio' => $parte['tipo'] === 'accesorio' ? $requisitosAccesorio : [],
        'precio_venta' => null,
    ]);

    session()->flash('mensaje', "¡Compra Realizada!");
    $this->reloadMercado++;
}
public function cargarPersonajesRandom()
{
    $nivelPersonaje = $this->personaje->nivel ?? 1;

    // Nivel objetivo: máximo 5 niveles menos, o mínimo 5 si el personaje tiene nivel bajo
    $nivelObjetivo = max(5, $nivelPersonaje - 5);

    // Buscar personajes con nivel igual al nivel objetivo, ordenados de mayor a menor
    $posts = Post::where('nivel', $nivelObjetivo)
        ->orderByDesc('nivel')
        ->take(5)
        ->get();

    // Si no hay personajes con ese nivel, buscar personajes con nivel >= 5, ordenados también
    if ($posts->isEmpty()) {
        $posts = Post::where('nivel', '>=', 5)
            ->orderByDesc('nivel')
            ->take(5)
            ->get();
    }

    // Opcional: ordenar por nivel descendente por seguridad
  $this->postsDisponibles = $posts->sortByDesc('nivel')->values();
}


public function comprarPersonaje($postId)
{
  


    $post = Post::find($postId);

    if (!$post) {
        $this->dispatch('error', ['message' => 'Personaje no encontrado']);
        return;
    }

   $nivelPost = $post->nivel ?? 1;

if ($nivelPost === 5) {
    $costo = 150;
} else {
    $costo = 150 + ($nivelPost - 5) * 20; // cada nivel extra suma 20 diamantes
}

    if ($this->personaje->diamante < $costo) {
        $this->dispatch('error', ['message' => 'No tienes suficientes esmeraldas']);
        return;
    }

    $this->personaje->diamante -= $costo;
    $this->personaje->save();

    $objetos = [
        [
            'tipo' => 'equipo',
            'nombre' => $post->equipo_nombre,
            'imagen' => $post->equipo_imagen,
            'stats' => $post->stats_equipo,
            'ajustes' => $post->ajustes_manuales_equipo,
            'requisitos' => $post->requisitos_equipo,
        ],
        [
            'tipo' => 'entrenamiento',
            'nombre' => $post->entrenamiento_nombre,
            'imagen' => $post->entrenamiento_imagen,
            'stats' => $post->stats_entrenamiento,
            'ajustes' => $post->ajustes_manuales_entrenamiento,
            'requisitos' => $post->requisitos_entrenamiento,
        ],
        [
            'tipo' => 'accesorio',
            'nombre' => $post->accesorio_nombre,
            'imagen' => $post->accesorio_imagen,
            'stats' => $post->stats_accesorio,
            'ajustes' => $post->ajustes_manuales_accesorio,
            'requisitos' => $post->requisitos_accesorio,
        ],
    ];

    foreach ($objetos as $obj) {
        if (!$obj['nombre']) continue;

        $requisitosDecodificados = is_array($obj['requisitos']) ? $obj['requisitos'] : (json_decode($obj['requisitos'] ?? '{}', true) ?: []);
        $requisitosEquipo = $obj['tipo'] === 'equipo' ? $requisitosDecodificados : [];
        $requisitosEntrenamiento = $obj['tipo'] === 'entrenamiento' ? $requisitosDecodificados : [];
        $requisitosAccesorio = $obj['tipo'] === 'accesorio' ? $requisitosDecodificados : [];

        $stats = [];
        if (!empty($obj['ajustes'])) {
            $stats = is_array($obj['ajustes']) ? $obj['ajustes'] : json_decode($obj['ajustes'], true);
            if (!is_array($stats)) {
                $stats = [];
            }
        }

        Objeto::create([
            'personaje_id' => $this->personaje->id,
            'nombre' => $obj['nombre'],
            'tipo' => $obj['tipo'],
            'imagen' => $obj['imagen'] ?? null,
            'nivel' => $post->nivel ?? 1,
            'stats' => $stats,
            'origen_post_id' => $post->id,
            'requisitos_equipo' => $requisitosEquipo,
            'requisitos_entrenamiento' => $requisitosEntrenamiento,
            'requisitos_accesorio' => $requisitosAccesorio,
            'precio_venta' => null,
        ]);
    }

    session()->flash('mensaje', "¡Compra Realizada!");
    $this->mount($this->personaje);
    $this->reloadMercado++;
}

public function comprarObjeto($objetoId)
{
    $objeto = Objeto::find($objetoId);

    if (!$objeto || !$objeto->precio_venta || $objeto->personaje_id == $this->personaje->id) {
        $this->dispatch('error', ['message' => 'Compra inválida']);
        return;
    }

    if ($this->personaje->oro < $objeto->precio_venta) {
        $this->dispatch('error', ['message' => 'No tienes suficiente oro']);
        return;
    }

    // Descontar oro
    $this->personaje->oro -= $objeto->precio_venta;
    $this->personaje->save();

    // Dar oro al vendedor
    $vendedor = $objeto->personaje;
    if ($vendedor) {
        $vendedor->oro += $objeto->precio_venta;
        $vendedor->save();
    }

  
    // Transferir el objeto
    $objeto->personaje_id = $this->personaje->id;
    $objeto->precio_venta = null;
    $objeto->save();

    session()->flash('mensaje', "¡Compra Realizada!");
    $this->mount($this->personaje);
    $this->reloadMercado++;
}


    public $mostrarStats = [
    'equipo' => false,
    'entrenamiento' => false,
    'accesorio' => false,
];

public function toggleStats($tipo)
{
    $this->mostrarStats[$tipo] = !$this->mostrarStats[$tipo];
}

public function render()
{
    return view('livewire.mercado', [
        'objetosEnVenta' => $this->objetosEnVenta,
        'postsDisponibles' => $this->postsDisponibles,
        'personaje' => $this->personaje,
        'esAdmin' => $this->esAdmin,
        'partesRandom' => $this->partesRandom, // 👈 agregá esta línea
    ]);
}
}
