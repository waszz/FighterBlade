<?php
namespace App\Livewire;

use App\Models\Buff;
use App\Models\Caza;
use App\Models\Ciudad;
use App\Models\ExploracionRapida;
use App\Models\Objeto;
use App\Models\Pelea;
use App\Models\Desafio;
use App\Models\NotificacionJuego;
use App\Models\Personaje;
use App\Models\Post;
use App\Models\User;
use Livewire\Component;

class Explorar extends Component
{
    // Cuánto suma cada nivel al multiplicador de daño (daño × (1 + nivel × esto)). Antes 0.05: en nivel 70 era ×4.5
    const DANIO_POR_NIVEL = 0.02;

    // Enemigo especial de bienvenida (Wolverine): aparece hasta este nivel y cada victoria da esta fracción
    // de la exp del nivel (1 = un nivel entero por pelea). Con 2: dos peleas, del nivel 1 al 3
    const ENEMIGO_ESPECIAL = Post::ENEMIGO_ESPECIAL;
    const NIVEL_MAX_ENEMIGO_ESPECIAL = 2;
    const EXP_ENEMIGO_ESPECIAL = 1;

    // PvP: exp por victoria según la diferencia de nivel con el rival
    const PVP_DIFERENCIA_MAX = 5;
    const PVP_EXP_CERCA = 0.04;
    const PVP_EXP_LEJOS = 0.01;

    // Exp por victoria según el nivel del personaje: [nivel desde, nivel hasta, fracción de la exp del nivel]
    // Las misiones dan esta cantidad de veces la exp de una pelea común
    const MISION_MULTIPLICADOR_EXP = 2;

    // Misiones y Torre: el rival pelea como un jugador de su nivel con equipo. Sus stats (30 + 5 por nivel, como un
    // jugador sin nada equipado) se refuerzan con lo que sumaría un set de su nivel (5 por nivel) × esta fracción.
    // 1 = set completo (rival de tu nivel = pelea pareja), 0.5 = medio set, 0 = sin refuerzo.
    // 1.5: más difíciles (un 20-25% más de stats que con 1). También lo usa la Mazmorra, antes de su dificultad
    const EQUIPO_RIVAL_MISION_TORRE = 1.5;

    // Contraataque (defensa) y rebote (resistencia), cuando al defensor le entra un golpe. Solo los tiene quien tiene más
    // de 30 en ese stat (como el especial con velocidad o el crítico con fuerza):
    //  - Contraataque: chance = defensa × CONTRA_POR_DEFENSA %, hasta CONTRA_TOPE %. Bloquea el golpe y pega la mitad de su daño
    //  - Rebote: si no contraataca, chance = resistencia × REBOTE_POR_RESISTENCIA %, hasta REBOTE_TOPE %. Resiste el golpe
    //    entero (no recibe daño) y le rebota al atacante REBOTE_PORCENTAJE de ese golpe (cuenta como daño del que resiste)
    const MINIMO_CONTRA_REBOTE = 30;

    // Híbridos: pegan físico y elemental a la vez (el ataque les cuenta en los dos), así que cada parte vale este
    // tanto. Con 0,65 un híbrido pega más o menos lo mismo que un físico o un elemental del mismo nivel (antes, 1,5 veces)
    const FACTOR_DANIO_HIBRIDO = 0.65;
    const CONTRA_POR_DEFENSA = 0.5;
    const CONTRA_TOPE = 50;
    const REBOTE_POR_RESISTENCIA = 0.5;
    const REBOTE_TOPE = 50;
    const REBOTE_PORCENTAJE = 1.0; // rebota el golpe entero

    // Exploración: el enemigo también se refuerza, pero como si tuviera medio set (misiones y torre, set completo).
    // No aplica al enemigo de bienvenida (Wolverine) ni a la caza (tiene su propio multiplicador por rareza)
    const EQUIPO_RIVAL_EXPLORACION = 0.5;

    // Multiplicador de stats del rival de misión o torre según su nivel: (30 + 5N + 5N·fracción) / (30 + 5N)
    public static function refuerzoRivalMisionTorre(int $nivel, float $fraccion = self::EQUIPO_RIVAL_MISION_TORRE): float
    {
        $base = 30 + 5 * max(1, $nivel);
        return ($base + 5 * max(1, $nivel) * $fraccion) / $base;
    }

    // Stats con los que pelea un rival de Misión o Torre: los del set reforzados como un jugador equipado de su nivel,
    // más sus poderes que suben stats (la misma cuenta que hace la pelea). Los usan los modales de Misiones y Torre
    // ($extra: la Mazmorra lo hace más fuerte según la dificultad)
    public static function statsRivalMisionTorre(Post $rival, float $extra = 1.0): array
    {
        $stats  = Personaje::decodificarStats($rival->stats);
        $factor = self::refuerzoRivalMisionTorre((int) ($rival->nivel ?? 1)) * $extra;
        foreach ($stats as $stat => $valor) {
            if (is_numeric($valor)) {
                $stats[$stat] = (int) round($valor * $factor);
            }
        }
        return \App\Support\PoderesStats::aplicar($stats, $rival->poderes ?? collect());
    }

    const EXP_POR_NIVEL = [
        [1, 25, 0.03],
        [26, 49, 0.02],
        [50, 50, 0.01],
        [51, 74, 0.02],
        [75, 75, 0.01],
        [76, 100, 0.02],
    ];

    // Exp según la zona (exploración y caza): en una zona de tu nivel o más alta, el 100%;
    // por cada nivel que le pasás a la zona, 10% menos (mínimo 10%). Así conviene ir cambiando de zona
    const EXP_ZONA_BAJA_POR_NIVEL = 0.10;
    const EXP_ZONA_MINIMO = 0.10;

    public static function factorExpZona(int $nivelPersonaje, int $nivelZona): float
    {
        $diferencia = $nivelPersonaje - $nivelZona;
        if ($diferencia <= 0) {
            return 1.0;
        }
        return max(self::EXP_ZONA_MINIMO, 1 - $diferencia * self::EXP_ZONA_BAJA_POR_NIVEL);
    }

    // Espera después de una pelea. Si empatás, siempre 5 s.
    // PvP: hasta nivel 20, 10 s; desde el 21, 1 minuto (ganes o pierdas).
    // Exploración, misiones, torre y caza: 15 s si ganás y 1 minuto si perdés, a cualquier nivel.
    // (Se puede saltear pagando oro: recuperarConOro)
    // $resultado: 'Victoria' | 'Derrota' | 'Empate'
    public static function segundosRecuperacion(int $nivel, string $resultado, bool $esPvp): int
    {
        if ($resultado === 'Empate') {
            return 5;
        }
        if (! $esPvp) {
            return $resultado === 'Victoria' ? 15 : 60;
        }
        if ($nivel <= 20) {
            return 10;
        }
        return 60;
    }

    // PvP: fracción de la exp del nivel que cobra el ganador, según la diferencia de nivel con el perdedor
    public static function fraccionExpPvp(int $nivelGanador, int $nivelPerdedor): float
    {
        return abs($nivelGanador - $nivelPerdedor) <= self::PVP_DIFERENCIA_MAX ? self::PVP_EXP_CERCA : self::PVP_EXP_LEJOS;
    }

    public static function porcentajeExpPorNivel(int $nivel): float
    {
        foreach (self::EXP_POR_NIVEL as [$desde, $hasta, $fraccion]) {
            if ($nivel >= $desde && $nivel <= $hasta) {
                return $fraccion;
            }
        }
        return 0.02;
    }

    // Fondo de la pelea cuando es una misión (se mantiene hasta cerrar el resultado)
    public ?string $escenarioMision = null;

    // Última pelea guardada: se puede compartir en el chat desde abajo del resultado
    public ?int $ultimaPeleaId = null;

    protected $listeners = [
        'timerTerminado'    => 'onTimerTerminado',
        'generarEnemigo',
        'reloadExploracion' => '$refresh',
        'refreshComponent'  => '$refresh',
        'refrescarBuffs'    => '$refresh',
        'recuperacionTerminada' => 'onRecuperacionTerminada',
        'recuperacionPorPvp'    => 'onRecuperacionPorPvp',
    ];
    public Post $post;
    public $personaje;
    public $personajeUsuario;
    public $ciudadActual;
    public $statsCompletos = [];
    public $equipo;
    public $entrenamiento;
    public $accesorio;
    public $oro = 0;

    public $enemigo           = null;
    public $resultadoFinal    = null; // valor simple: 'Victoria', 'Derrota', 'Empate' o null
    public $recompensas       = [];
    public $mostrarOpciones   = true;
    public $combateActivo     = false;
    public $tiempoExploracion = null;
    public $rankingCiudad     = [];
    public $mostrarRanking    = true;
    public $esPvp             = false;
    // Duelo aceptado (pelea amistosa): sin premio, sin pociones gastadas, sin recuperación y sin contar para el PvP
    public bool $esDuelo      = false;
    public ?int $dueloId      = null;
    // PvP: exp que cobró el atacado en esta pelea (para su aviso)
    protected int $expDadaAlRival = 0;

    public $rondaActual         = 1;
    public $resultadosRondas    = [];
    public $stats               = [];
    public $statsPersonaje      = [];
    public $totalDanioPersonaje = 0;
    public $totalDanioEnemigo   = 0;

    public $expGanada = 0;
    public $oroGanado = 0;
    public $enemigosExploracion;
    public $poderes                         = [];
    public $poderesPersonaje                = [];
    public $poderesEnemigo                  = [];
    public $danioPoderesPersonaje           = 0;
    public $danioPoderesEnemigo             = 0;
    public $danioExtraTotalPersonaje        = 0;
    public $danioExtraTotalEnemigo          = 0;
    public bool $huboDanioDirectoPersonaje  = false;
    public bool $huboDanioDirectoEnemigo    = false;
    public $absorcionTotalPersonaje         = 0;
    public $absorcionTotalEnemigo           = 0;
    public $danioEjercidoTotalPersonaje     = 0;
    public $danioEjercidoTotalEnemigo       = 0;
    public $danioAtaqueDesesperadoPersonaje = 0;
    public $danioAtaqueDesesperadoEnemigo   = 0;
    public $esEmpate;
    public $finExploracion;
    public bool $mostrarBotonesBatalla = true;
    public $mensajeExploracion         = null;
    public $mostrarEnemigoCiudad       = true;
    public $mensajeRecuperacion;
    public int $buffExperiencia      = 0;
    public ?int $buffExpFinTimestamp = null;
    public $buffsExperienciaActivos  = [];
    public $buffsOroActivos          = [];
    public $buffsDropActivos         = [];
    public $fueCongeladoEnEstaRonda = false;
    public $fueAturdidoEnEstaRonda = false;
    public $fueEnvenenadoEnEstaRonda = false;
    public $fueDesangradoEnEstaRonda = false;
    public $fueParalizadoEnEstaRonda = false;
    public $fueQuemadoEnEstaRonda = false;
    public bool $poderActivoFrenesiPersonaje = false;
    public bool $poderActivoFrenesiEnemigo = false;
    public bool $poderActivoFuriaCiegaPersonaje = false;
    public bool $poderActivoFuriaCiegaEnemigo = false;
    public bool $poderActivoTrancePersonaje = false;
    public bool $poderActivoTranceEnemigo = false;
    public  $poderActivoSuperNovaPersonaje = false;
    public  $poderActivoSuperNovaEnemigo = false;
    public  $poderActivoSuperCargaPersonaje = false;
    public  $poderActivoSuperCargaEnemigo = false;
    public $mostrarModal = false;
    public $personajeSeleccionadoModal;
    public $personajeSeleccionadoModalStats = [];

    public $modalEnemigoVisible = false;
    public $enemigoModalStats = [];
    public $gifPersonajeEquipado = null;
    public $mostrarCombate = false;
    public $combateResultados = [];
    public $enemigoCombate = null;


    

    public function mount($personajeId, $ciudadId, $enemigoId = null)
    {

        // 1️⃣ Cargar personaje y ciudad
        $this->personaje    = Personaje::with('post.poderes')->find($personajeId);
        $this->ciudadActual = Ciudad::find($ciudadId);
        $this->cargarBuffs();

        if (! $this->personaje || ! $this->personaje->post) {
            abort(404);
        }

        // 2️⃣ Cargar objetos equipados
        $this->cargarObjetos();

        // 3️⃣ Calcular stats combinados
        $this->statsCompletos = $this->obtenerStatsCompletos();

        // 4️⃣ Cargar ranking
        // Todos los usuarios de la zona (sin límite)
        $this->rankingCiudad = Personaje::with(['user', 'post', 'equipo', 'entrenamiento', 'accesorio', 'clan'])
            ->where('ciudad_id', $this->ciudadActual->id)
            ->sinAdmins()
            ->orderByDesc('nivel')
            ->orderByDesc('experiencia')
            ->get();

        $this->post = $this->personaje->post;

        // 5️⃣ Combatiente PVP
        if ($this->personaje->enemigo_actual_personaje_id) {
            $this->enemigo         = Personaje::with(['post.poderes', 'poderes', 'equipo', 'entrenamiento', 'accesorio'])
                ->find($this->personaje->enemigo_actual_personaje_id);
            $this->mostrarOpciones = false;
            $this->combateActivo   = (bool) $this->enemigo;
            $this->esPvp           = (bool) $this->enemigo;
        }
        // 6️⃣ Combatiente PVE
        elseif ($this->personaje->enemigo_actual_post_id) {
            $this->enemigo         = Post::conRivales()->with('poderes')->find($this->personaje->enemigo_actual_post_id);
            $this->mostrarOpciones = false;
            $this->combateActivo   = true;
            $this->esPvp           = false;
        }
        // 7️⃣ Combatiente desde sesión
        elseif (session()->has('enemigo') && session('combate_activo') === true) {
            $enemigoSesion = session('enemigo');
            $postEnemigo   = Post::conRivales()->with('poderes')->find($enemigoSesion['id']);

            if ($postEnemigo) {
                $this->enemigo         = $postEnemigo;
                $this->mostrarOpciones = false;
                $this->combateActivo   = true;
                $this->esPvp           = false;
            } else {
                $this->limpiarCombate(); // Limpieza en caso de fallo
            }
        }
        // 7️⃣b Enemigo guardado en la base pero no en la sesión (se cerró la sesión o cambió de dispositivo):
        // se recupera; si no, quedaba "asignado" sin mostrarse y bloqueaba el enemigo especial y el PvP
        elseif ($this->personaje->enemigo_actual_id
            && ($postEnemigo = Post::conRivales()->with('poderes')->find($this->personaje->enemigo_actual_id))) {
            $this->enemigo         = $postEnemigo;
            $this->mostrarOpciones = false;
            $this->combateActivo   = true;
            $this->esPvp           = false;
            session([
                'enemigo'        => ['id' => $postEnemigo->id, 'nombre' => $postEnemigo->titulo, 'nivel' => $postEnemigo->nivel, 'gif' => $postEnemigo->gif],
                'combate_activo' => true,
            ]);
        }
        // 8️⃣ No hay enemigo
        else {
            // Marca vieja de un enemigo que ya no existe: se limpia para no trabar al personaje
            if ($this->personaje->enemigo_actual_id) {
                $this->personaje->enemigo_actual_id = null;
                $this->personaje->save();
            }
            $this->enemigo         = null;
            $this->mostrarOpciones = false;
            $this->combateActivo   = false;
            $this->esPvp           = false;
        }

        // 9️⃣ Exploración
        $finExploracion = $this->personaje->fin_exploracion;

        // (si ya hay un enemigo cargado —PvP, misión, caza— no se toca)
        if ($finExploracion && ! $this->enemigo) {
            $finExploracionCarbon = \Carbon\Carbon::parse($finExploracion);
            $tiempoRestante       = $finExploracionCarbon->timestamp - now()->timestamp;

            // Atajo de admin: la exploración termina al instante (solo si estaba explorando, no en la espera de recuperación)
            if (auth()->user()->isAdmin() && $this->personaje->exploracion_duracion > 0) {
                $this->generarYGuardarEnemigo();
                return;
            }

            if ($tiempoRestante > 0) {
                $this->mostrarOpciones   = false;
                $this->tiempoExploracion = $tiempoRestante;
                $this->finExploracion    = $finExploracionCarbon->timestamp;
                $this->dispatch('iniciar-timer', ['finTimestamp' => $finExploracionCarbon->timestamp]);
                return;
            }

            if ($tiempoRestante <= 0) {
                $duracionExploracion = $this->personaje->exploracion_duracion;
                if ($duracionExploracion > 0) {
                    $this->generarYGuardarEnemigo();
                    return;
                }
                // Terminó la recuperación de la pelea anterior: se limpia y sigue (así aparece el enemigo especial)
                $this->personaje->fin_exploracion = null;
                $this->personaje->save();
            }
        }

        // 🔟 Enemigo especial para personajes nuevos
        if ($this->personaje->nivel <= self::NIVEL_MAX_ENEMIGO_ESPECIAL && ! $this->personaje->enemigo_actual_id && ! $this->enemigo) {
            // Enemigo especial de bienvenida: el set oculto marcado con es_enemigo = 2 (Wolverine)
            $enemigoEspecial = Post::conRivales()->where('es_enemigo', self::ENEMIGO_ESPECIAL)->first();

            if ($enemigoEspecial) {
                $this->personaje->enemigo_actual_id = $enemigoEspecial->id;
                $this->personaje->save();

                $this->enemigo       = $enemigoEspecial;
                $this->combateActivo = true;

                session([
                    'enemigo'        => [
                        'id'     => $enemigoEspecial->id,
                        'nombre' => $enemigoEspecial->titulo,
                        'nivel'  => $enemigoEspecial->nivel,
                        'gif'    => $enemigoEspecial->gif,
                    ],
                    'combate_activo' => true,
                ]);
            }
        }
       

 $this->estadosTemporalesActivos = collect($this->personaje->estadosTemporales)
        ->filter(fn($estado) => $estado->estaActivo());

        $this->escenarioMision = $this->misionActiva()?->escenario ?? $this->torreActiva()?->escenario
            ?? ($this->mazmorraActiva()?->rivalActual()['escenario'] ?? null);

        // PvP recién iniciado con "Atacar": la pelea arranca sola, sin volver a apretar Atacar
        if ($this->esPvp && $this->enemigo && session()->pull('pvp_auto_atacar') == $this->enemigo->id) {
            // Duelo aceptado: el que aceptó pelea contra el que lo desafió
            $dueloSesion = session()->pull('duelo_id');
            $duelo = $dueloSesion ? Desafio::where('tipo', 'duelo')->where('estado', 'aceptado')
                ->where('para_id', $this->personaje->id)->where('de_id', $this->enemigo->id)
                ->find($dueloSesion) : null;

            if ($dueloSesion && ! $duelo) {
                // El duelo ya no vale (venció o se canceló): no se arranca un PvP de verdad en su lugar
                $this->personaje->enemigo_actual_personaje_id = null;
                $this->personaje->save();
                $this->enemigo = null;
                $this->combateActivo = false;
                $this->esPvp = false;
                $this->mostrarOpciones = true;
                $this->dispatch('error', ['message' => 'El duelo ya no está disponible.']);
            } else {
                $this->esDuelo = (bool) $duelo;
                $this->dueloId = $duelo?->id;
                $this->atacar();
            }
        }
    }

    public function mostrarModalPersonaje($id)
{
    $this->personajeSeleccionadoModal = Personaje::with([
        'ciudadActual',
        'user',
        'equipo',
        'entrenamiento',
        'accesorio',
        'post'
    ])->find($id);

    // Stats con los que pelea: base + partes + anillo + poción de stat + poderes (lo mismo que el panel y la pelea)
    $this->personajeSeleccionadoModalStats = $this->personajeSeleccionadoModal->statsDeCombate();

    // Determinar gif del personaje
    $equipo = $this->personajeSeleccionadoModal->equipo;
    $ent = $this->personajeSeleccionadoModal->entrenamiento;
    $acc = $this->personajeSeleccionadoModal->accesorio;

    $origenPostId = null;
    if ($equipo && $ent && $acc &&
        $equipo->origen_post_id === $ent->origen_post_id &&
        $equipo->origen_post_id === $acc->origen_post_id) {
        $origenPostId = $equipo->origen_post_id;
    } elseif ($this->personajeSeleccionadoModal->origen_post_id) {
        $origenPostId = $this->personajeSeleccionadoModal->origen_post_id;
    } elseif ($this->personajeSeleccionadoModal->post) {
        $origenPostId = $this->personajeSeleccionadoModal->post->id;
    }

    $postEquipado = $origenPostId ? \App\Models\Post::find($origenPostId) : null;
    $this->gifPersonajeEquipado = $postEquipado?->gif ?? null;

    $this->mostrarModal = true;
}

public function mostrarModalEnemigo()
{
    if (! $this->enemigo) {
        return;
    }

    $stats = is_array($this->enemigo->stats)
        ? $this->enemigo->stats
        : (json_decode($this->enemigo->stats ?? '{}', true) ?: []);

    $this->enemigoModalStats = $stats;
    $this->modalEnemigoVisible = true;
}

public function colorBarraPorStat($valor)
{
    if ($valor <= 40) return 'bg-orange-400';
    if ($valor <= 100) return 'bg-green-400';
    if ($valor <= 150) return 'bg-blue-400';
    if ($valor < 200) return 'bg-indigo-400';
    return 'bg-purple-400';
}


    public function getTiempoExploracionProperty()
    {
        if (! $this->personaje->fin_exploracion) {
            return null;
        }

        return max(0, now()->diffInSeconds($this->personaje->fin_exploracion));
    }

    public function generarYGuardarEnemigo()
    {
        // Terminó de explorar pero lo atacaron en PvP y se sigue recuperando: la exploración se estira
        // hasta que termine la recuperación y recién ahí aparece el enemigo
        $finRecuperacion = $this->personaje->fin_recuperacion;
        if ($this->personaje->exploracion_duracion > 0 && $finRecuperacion && now()->lt($finRecuperacion)) {
            $this->personaje->fin_exploracion = $finRecuperacion;
            $this->personaje->save();
            $this->tiempoExploracion = (int) ceil(now()->diffInSeconds($finRecuperacion));
            $this->finExploracion    = $finRecuperacion->timestamp;
            $this->mostrarOpciones   = false;
            $this->dispatch('statsActualizados');
            return;
        }

        $enemigo = $this->generarEnemigo(true);

        if ($enemigo) {
            $this->personaje->enemigo_actual_id = $enemigo->id;
            $this->personaje->save();

            session([
                'enemigo'        => ['id' => $enemigo->id],
                'combate_activo' => true,
            ]);

            $this->enemigo           = $enemigo;
            $this->mostrarOpciones   = false;
            $this->combateActivo     = true;
            $this->tiempoExploracion = null;
            $this->finExploracion    = null;
            // Enemigo nuevo: mostrar Atacar / Huir
            $this->mostrarBotonesBatalla = true;
        }
    }

    // Compartir en el chat la pelea recién terminada (explorar, misión, torre, caza o PvP)
    public function compartirPelea($id)
    {
        $pelea = Pelea::where('personaje_id', $this->personaje->id)->find($id);
        if (! $pelea || $this->personaje->user_id !== auth()->id()) {
            return;
        }
        \App\Support\ChatCompartir::pelea($this->personaje, $pelea);
        $this->dispatch('chatCompartido');
        $this->dispatch('success', ['message' => 'Compartiste la pelea en el chat.']);
    }

    public function limpiarCombate()
    {
        $this->esDuelo         = false;
        $this->dueloId         = null;
        $this->ultimaPeleaId   = null;
        $this->escenarioMision = null;
        $this->enemigo         = null;
        $this->combateActivo   = false;
        $this->resultadoFinal  = null;
        $this->recompensas     = [];
        $this->mostrarOpciones = true;

        $this->personaje->enemigo_actual_id = null; $this->personaje->enemigo_actual_personaje_id = null;
        $this->personaje->save();

        session()->forget('enemigo');
        session()->forget('combate_activo');
    }

    // "Me gusta" en un anuncio del panel (uno por cuenta; volver a tocarlo lo saca)
    public function meGustaAnuncio(int $anuncioId)
    {
        $anuncio = \App\Models\Anuncio::where('activo', true)->find($anuncioId);
        if ($anuncio && auth()->id()) {
            $anuncio->likes()->toggle(auth()->id());
        }
    }

    // Fin de la recuperación (la cuenta regresiva del panel lateral): si le toca el enemigo especial de bienvenida,
    // se recarga la página para que aparezca (el mount lo asigna)
    public function onRecuperacionTerminada()
    {
        $personaje = $this->personaje->fresh();
        if ($personaje && $personaje->nivel <= self::NIVEL_MAX_ENEMIGO_ESPECIAL && ! $personaje->enemigo_actual_id) {
            $this->dispatch('recargar-pagina');
            return;
        }

        // Terminó la espera: vuelve a aparecer el botón Explorar sin recargar la página
        if ($personaje && ! $personaje->exploracion_duracion && (! $personaje->fin_exploracion || now()->gte($personaje->fin_exploracion))) {
            $this->personaje->fin_exploracion = null;
            $this->tiempoExploracion = null;
        }
    }

    // Me atacaron en PvP (lo avisa Desafios): se esconde Explorar mientras dura la recuperación, sin recargar
    public function onRecuperacionPorPvp()
    {
        $personaje = $this->personaje->fresh();
        if (! $personaje || $this->enemigo || $personaje->exploracion_duracion > 0) {
            return;
        }
        $restante = $personaje->segundosRecuperacion();
        if ($restante > 0) {
            $this->personaje->fin_exploracion = $personaje->fin_exploracion;
            $this->tiempoExploracion = $restante;
            $this->mostrarOpciones   = false;
        }
    }

    public function onTimerTerminado()
    {
        // Terminó de explorar pero se sigue recuperando de un PvP: la exploración se estira (no se limpia nada)
        if ($this->personaje->exploracion_duracion > 0 && $this->personaje->fin_recuperacion && now()->lt($this->personaje->fin_recuperacion)) {
            $this->generarYGuardarEnemigo();
            return;
        }

        $this->personaje->fin_exploracion = null;

        // ✅ Guardamos y limpiamos estado
        $this->personaje->save();

        $this->tiempoExploracion = null;
        // No abrir las opciones de explorar encima del resultado de una pelea
        $this->mostrarOpciones   = ! $this->enemigo;

        // ✅ Solo generamos enemigo si fue una exploración real
        if ($this->personaje->exploracion_duracion > 0) {
            $this->generarYGuardarEnemigo();
        }

        // 🧹 Siempre limpiamos duración
        $this->personaje->exploracion_duracion = 0;
        $this->personaje->save();

    }

    public function cargarBuffs()
    {
        $tipos = ['xp', 'oro', 'drop'];

        foreach ($tipos as $tipo) {
            $buffsActivos = Buff::where('tipo', $tipo)
                ->where('inicio', '<=', now())
                ->where('fin', '>=', now())
                ->where(function ($q) {
                    $q->where('personaje_id', $this->personaje->id)
                        ->orWhereNull('personaje_id'); // Buff global
                })
                ->orderByDesc('fin')
                ->get()
                ->map(function ($buff) {
                    return [
                        'id'           => $buff->id,
                        'porcentaje'   => $buff->porcentaje,
                        'finTimestamp' => $buff->fin->timestamp,
                        'global'       => $buff->personaje_id === null,
                        'frase'        => $buff->frase,
                    ];
                })
                ->toArray();

            // Guardar en la propiedad correspondiente
            match ($tipo) {
                'xp' => $this->buffsExperienciaActivos = $buffsActivos,
                'oro' => $this->buffsOroActivos        = $buffsActivos,
                'drop' => $this->buffsDropActivos      = $buffsActivos,
            };

            // Lanzar eventos para cada buff activo
            foreach ($buffsActivos as $buff) {
                $this->dispatch("iniciar-timer-buff-{$tipo}-{$buff['id']}", [
                    'finTimestamp' => $buff['finTimestamp'],
                ]);
            }
        }
    }

    public function explorar($minutos)
    {
        if ($this->personaje->fresh()?->estaEntrenando()) {
            $this->mensajeExploracion = Personaje::MENSAJE_ENTRENANDO;
            $this->mostrarOpciones    = false;
            return;
        }

        // Aturdido o Paralizado no dejan explorar (Congelado sí: solo impide viajar). Ver Personaje::ESTADOS_QUE_BLOQUEAN
        if ($estado = $this->personaje->estadoQueBloquea('pelear')) {
            $this->mensajeExploracion = "No puedes explorar porque estás $estado.";
            return;
        }

        // Bloquear si tiene una misión en curso
        if ($this->personaje->mision_activa_id) {
            $this->mensajeExploracion = '📜 Tenés una misión en curso. Peleá con tu rival antes de explorar.';
            return;
        }

        // Bloquear si está subiendo la Torre
        if ($this->personaje->torre_piso_activo) {
            $this->mensajeExploracion = '🗼 Estás en la Torre. Peleá con el rival del piso antes de explorar.';
            return;
        }

        // Bloquear si se está recuperando de un PvP que le llegó explorando
        if ($this->personaje->fin_recuperacion && now()->lt($this->personaje->fin_recuperacion)) {
            $restante                 = $this->personaje->fin_recuperacion->diffForHumans(now(), ['parts' => 1]);
            $this->mensajeExploracion = "⏳ Debes esperar $restante antes de volver a explorar.";
            return;
        }

        // Bloquear si está en una caza
        if ($cazaEnCurso = Caza::activaDe($this->personaje->id)) {
            $this->mensajeExploracion = $cazaEnCurso->estado === 'lista'
                ? '🎯 Tu presa te está esperando. Peleá con ella antes de explorar.'
                : '🎯 Estás rastreando una presa. Terminá la caza antes de explorar.';
            return;
        }

        // Bloquear si está viajando
        if ($this->personaje->viajando_hasta && now()->lt($this->personaje->viajando_hasta)) {
            $restante                 = $this->personaje->viajando_hasta->diffForHumans(now(), ['parts' => 1]);
            $restante                 = str_replace(' después', '', $restante);
            $this->mensajeExploracion = "Estás viajando... Llegás en $restante y recién ahí podrás explorar.";
            return;
        }

        // Bloquear si aún está en cooldown por exploración anterior
        if ($this->personaje->fin_exploracion && now()->lt($this->personaje->fin_exploracion)) {
            $restante                 = $this->personaje->fin_exploracion->diffForHumans(now(), ['parts' => 1]);
            $this->mensajeExploracion = "⏳ Debes esperar $restante antes de volver a explorar.";
            return;
        }

        // ❌ Bloquear si ya está explorando
        if ($this->tiempoExploracion && $this->tiempoExploracion > 0) {
            $this->mensajeExploracion = '¡Ya estás explorando!';
            return;
        }

        $this->mensajeExploracion = null;
        $this->mostrarOpciones    = false;

        // Guardamos el valor original antes de la reducción
        $minutosOriginal = $minutos;

        // ⚡ Verificar si hay exploración rápida activa
        $exploracionRapida = ExploracionRapida::activaPara($this->personaje->id);

        // ⏱️ Ajustar minutos si tiene exploración rápida
        if ($exploracionRapida) {
            if ($minutos == 5) {
                $minutos = 2;
            } elseif ($minutos == 10) {
                $minutos = 5;
            } elseif ($minutos == 15) {
                $minutos = 10;
            }
        }

        // 🌱 Zona inicial (El Comienzo): la exploración siempre dura 2 minutos (1 con exploración rápida)
        if ($this->esZonaInicial()) {
            $minutosOriginal = 5;
            $minutos = $exploracionRapida ? self::MINUTOS_ZONA_INICIAL_RAPIDA : self::MINUTOS_ZONA_INICIAL;
        }

        $segundos                = $minutos * 60;
        $this->tiempoExploracion = $segundos;

        $finExploracion = now()->addSeconds($segundos);

        // 📝 Guardar estado en el personaje
        $this->personaje->fin_exploracion      = $finExploracion;
        $this->personaje->exploracion_duracion = $minutos;
                                                                 // Guardar también el valor original para usarlo después si querés (opcional)
        $this->personaje->minutos_originales = $minutosOriginal; // crea esta columna si no existe
        $this->personaje->save();

        $this->cargarBuffs();

        // Emitir evento Livewire con buffs
        $this->dispatch('iniciar-timers-buff-exp', [
            'buffs' => $this->buffsExperienciaActivos,
        ]);

        if (auth()->user()->isAdmin()) {
            // Admins generan enemigo instantáneamente y sin cooldown
            $this->generarYGuardarEnemigo();

            $this->tiempoExploracion               = null;
            $this->personaje->fin_exploracion      = null;
            $this->personaje->exploracion_duracion = 0;
            $this->personaje->save();
        } else {
            // 🕒 Emitir evento JS para temporizador
            $this->dispatch('iniciar-timer', ['finTimestamp' => $finExploracion->timestamp]);
            // Mostrar el "Explorando" en el panel lateral
            $this->dispatch('statsActualizados');
        }
    }

    public function toggleExplorar()
    {
        if ($this->personaje->fresh()?->estaEntrenando()) {
            $this->mensajeExploracion = Personaje::MENSAJE_ENTRENANDO;
            $this->mostrarOpciones    = false;
            return;
        }

        // Si está en cooldown
        if ($this->personaje->fin_exploracion && now()->lt($this->personaje->fin_exploracion)) {
            $restante                 = \Carbon\Carbon::parse($this->personaje->fin_exploracion)->diffForHumans(now(), ['parts' => 1]);
            $this->mensajeExploracion = "⏳ Debes esperar $restante antes de volver a explorar.";
            $this->mostrarOpciones    = false;
            return;
        }

        // Si está explorando
        if ($this->tiempoExploracion && $this->tiempoExploracion > 0) {
            $this->mensajeExploracion = "¡Ya estás explorando!";
            $this->mostrarOpciones    = false;
            return;
        }

        // Si no, mostrar opciones
        $this->mensajeExploracion = null;
        $this->mostrarOpciones    = true;
    }

    public function cancelarExploracion()
    {
        $this->personaje->fin_exploracion   = null;
        $this->personaje->enemigo_actual_id = null; $this->personaje->enemigo_actual_personaje_id = null;
        $this->personaje->save();

        $this->tiempoExploracion = null;
        $this->mostrarOpciones   = true;
        $this->enemigo           = null;

        session()->forget('enemigo');
        session()->forget('combate_activo');

        // Quitar el "Explorando" del panel lateral
        $this->dispatch('statsActualizados');
    }

    public function generarEnemigo($inmediato = false)
    {
        $this->personaje->fin_exploracion = null;
        $this->personaje->save();

        // 👹 Enemigo especial de bienvenida hasta NIVEL_MAX_ENEMIGO_ESPECIAL
        if ($this->personaje->nivel <= self::NIVEL_MAX_ENEMIGO_ESPECIAL) {
            $enemigoEspecial = Post::conRivales()->where('es_enemigo', self::ENEMIGO_ESPECIAL)
                ->with('poderes')
                ->first();

            if ($enemigoEspecial) {
                session([
                    'enemigo'        => [
                        'id'     => $enemigoEspecial->id,
                        'nombre' => $enemigoEspecial->titulo,
                        'nivel'  => $enemigoEspecial->nivel,
                        'gif'    => $enemigoEspecial->gif,
                    ],
                    'combate_activo' => true,
                ]);

                $this->combateActivo       = true;
                $this->rondaActual         = 1;
                $this->totalDanioPersonaje = 0;
                $this->totalDanioEnemigo   = 0;
                $this->resultadosRondas    = [];
                $this->resultadoFinal      = null;
                $this->expGanada           = 0;
                $this->oroGanado           = 0;
                $this->recompensas         = [];

                return $enemigoEspecial;
            }
        }

        // 🌱 Zona inicial: aparecen las variantes Black / normal / Gold de los sets de nivel 5
        if ($this->esZonaInicial()) {
            $variante = Post::conRivales()->where('es_enemigo', Post::VARIANTE_ZONA)->with('poderes')->inRandomOrder()->first();
            if ($variante) {
                session([
                    'enemigo'        => ['id' => $variante->id, 'nombre' => $variante->titulo, 'nivel' => $variante->nivel, 'gif' => $variante->gif],
                    'combate_activo' => true,
                ]);
                $this->combateActivo       = true;
                $this->rondaActual         = 1;
                $this->totalDanioPersonaje = 0;
                $this->totalDanioEnemigo   = 0;
                $this->resultadosRondas    = [];
                $this->resultadoFinal      = null;
                $this->expGanada           = 0;
                $this->oroGanado           = 0;
                $this->recompensas         = [];

                return $variante;
            }
        }

        // 🎯 Enemigos normales (PvE) por nivel de ciudad
        $nivelBase = $this->ciudadActual->nivel ?? $this->personaje->nivel;
        $nivelMin  = $nivelBase + 5;
        $nivelMax  = $nivelBase + 9;

        $post = Post::where('es_enemigo', 1)
            ->whereBetween('nivel', [$nivelMin, $nivelMax])
            ->with('poderes')
            ->inRandomOrder()
            ->first();

        if (! $post) {
            $post = Post::where('es_enemigo', 1)
                ->with('poderes')
                ->inRandomOrder()
                ->first();
        }

        // Si no hay posts marcados como enemigos, usamos los personajes (sets) creados como enemigos
        if (! $post) {
            $post = Post::whereBetween('nivel', [$nivelMin, $nivelMax])
                ->with('poderes')
                ->inRandomOrder()
                ->first();
        }

        if (! $post) {
            $post = Post::with('poderes')
                ->inRandomOrder()
                ->first();
        }

        if (! $post) {
            session()->forget('enemigo');
            session()->forget('combate_activo');
            return null;
        }

        session([
            'enemigo'        => [
                'id'     => $post->id,
                'nombre' => $post->titulo,
                'nivel'  => $post->nivel,
                'gif'    => $post->gif,
            ],
            'combate_activo' => true,
        ]);

        $this->combateActivo       = true;
        $this->rondaActual         = 1;
        $this->totalDanioPersonaje = 0;
        $this->totalDanioEnemigo   = 0;
        $this->resultadosRondas    = [];
        $this->resultadoFinal      = null;
        $this->expGanada           = 0;
        $this->oroGanado           = 0;
        $this->recompensas         = [];

        return $post;
    }

    public function recuperarConOro()
    {
        $costoOro = $this->personaje->nivel * 20;

        if ($this->personaje->oro < $costoOro) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'No tienes suficiente oro para recuperarte.']);
            return;
        }

        $this->personaje->oro -= $costoOro;
        $this->personaje->exploracion_duracion = 0;
        $this->personaje->fin_exploracion      = null; // <--- importante para que el contador desaparezca
        $this->personaje->fin_recuperacion     = null;
        $this->personaje->save();

        $this->dispatch('recuperacionCompleta');
        $this->dispatch('alert', ['type' => 'success', 'message' => "Recuperado instantáneamente pagando $costoOro de oro."]);

        $this->dispatch('$refresh'); // refrescar Livewire para actualizar el DOM
        $this->dispatch('recargarPagina');
    }

    public function cargarRankingCiudad()
    {
        if (! $this->ciudadActual) {
            return;
        }

        $this->rankingCiudad = Personaje::with(['user', 'post', 'equipo', 'entrenamiento', 'accesorio', 'clan'])
            ->where('ciudad_id', $this->ciudadActual->id)
            ->sinAdmins()
            ->orderByDesc('nivel')
            ->orderByDesc('experiencia')
            ->get();
    }

    // Propiedades que usa la pantalla de rondas (partial resultado-pelea) y que hay que guardar para repetirla
    const VISTA_PELEA = [
        'poderActivoFrenesiPersonaje', 'poderActivoFrenesiEnemigo', 'poderActivoFuriaCiegaPersonaje', 'poderActivoFuriaCiegaEnemigo',
        'poderActivoTrancePersonaje', 'poderActivoTranceEnemigo', 'poderActivoSuperNovaPersonaje', 'poderActivoSuperNovaEnemigo',
        'poderActivoSuperCargaPersonaje', 'poderActivoSuperCargaEnemigo',
        'danioExtraTotalPersonaje', 'danioExtraTotalEnemigo', 'danioAtaqueDesesperadoPersonaje', 'danioAtaqueDesesperadoEnemigo',
        'totalDanioPersonaje', 'totalDanioEnemigo', 'absorcionTotalPersonaje', 'absorcionTotalEnemigo',
        'fueCongeladoEnEstaRonda', 'fueAturdidoEnEstaRonda', 'fueEnvenenadoEnEstaRonda', 'fueDesangradoEnEstaRonda',
        'fueParalizadoEnEstaRonda', 'fueQuemadoEnEstaRonda', 'recompensas',
    ];

    protected function estadoVistaPelea(): array
    {
        $estado = [];
        foreach (self::VISTA_PELEA as $propiedad) {
            $estado[$propiedad] = $this->$propiedad;
        }
        return $estado;
    }

    public function determinarGanador()
    {
        $minutosOriginales = (int) ($this->personaje->minutos_originales ?? $this->personaje->exploracion_duracion ?? 5);

// No mapees ni hagas match, porque el minuto original ya es el correcto para drop y recompensas
        $statsOriginal = null;

        if ($this->esDuelo) {
            // Duelo amistoso: solo el resultado
            $this->resultadoFinal = match (true) {
                $this->totalDanioPersonaje > $this->totalDanioEnemigo => 'Victoria',
                $this->totalDanioPersonaje < $this->totalDanioEnemigo => 'Derrota',
                default => 'Empate',
            };
            $this->recompensas = [];
        } elseif ($this->totalDanioPersonaje > $this->totalDanioEnemigo) {
            // Verificar si hay un objeto consumible
            if ($this->personaje->objeto_consumible_id) {
                $objeto = \App\Models\Objeto::find($this->personaje->objeto_consumible_id);
                if ($objeto) {
                    $stats         = is_array($objeto->stats) ? $objeto->stats : json_decode($objeto->stats, true);
                    $statsOriginal = $stats;

                    $usosRestantes = $stats['usos_restantes'] ?? 0;
                    // Recuperación se gasta al perder y la de drop solo si la parte cae (más abajo)
                    if (! in_array($stats['afecta'] ?? '', ['recuperacion', 'drop_partes'], true)) {
                        if ($usosRestantes > 0) {
                            $stats['usos_restantes'] = $usosRestantes - 1;
                            $objeto->stats           = $stats;

                            if ($usosRestantes <= 1) {
                                $objeto->delete();
                                $this->personaje->objeto_consumible_id = null;
                            } else {
                                $objeto->save();
                            }

                            if (($stats['afecta'] ?? '') === 'oro') {
                                $cantidadOro              = 0;
                                $this->personaje->oro     = ($this->personaje->oro ?? 0) + $cantidadOro;
                                $this->recompensas['oro'] = ($this->recompensas['oro'] ?? 0) + $cantidadOro;
                            }

                            if (($stats['afecta'] ?? '') === 'diamante') {
                                $cantidadDiamantes             = 0;
                                $this->personaje->diamante     = ($this->personaje->diamante ?? 0) + $cantidadDiamantes;
                                $this->recompensas['diamante'] = ($this->recompensas['diamante'] ?? 0) + $cantidadDiamantes;
                            }

                            $this->personaje->save();
                        }
                    }
                }
            }

            // Restauramos stats si hubo alguna poción que haya modificado los stats

            // Procesamos las recompensas por la victoria
            $recompensasBase = $this->asignarRecompensas($minutosOriginales, $statsOriginal);

            unset($recompensasBase['diamante']);
            unset($recompensasBase['oro']);

            if (! isset($this->recompensas)) {
                $this->recompensas = [];
            }

            foreach ($recompensasBase as $key => $valor) {
                $this->recompensas[$key] = ($this->recompensas[$key] ?? 0) + $valor;
            }

            // Marcamos el resultado como victoria
            $this->resultadoFinal = 'Victoria';

            // Actualizamos las estadísticas de PvP o PvE (PvE: solo exploraciones; misiones y torre tienen su propio ranking)
            if ($this->esPvp) {
                $this->personaje->pvp_ganadas = ($this->personaje->pvp_ganadas ?? 0) + 1;
                $this->personaje->pvp_puntos  = ($this->personaje->pvp_puntos ?? 0) + 10;
            } elseif ($this->esExploracion()) {
                $this->personaje->pve_ganadas = ($this->personaje->pve_ganadas ?? 0) + 1;
                $this->personaje->pve_puntos  = ($this->personaje->pve_puntos ?? 0) + 10;
            }

            // 🔥 Consumir poción de drop_partes solo si efectivamente cayó la parte del enemigo
            $cayoParte = in_array($recompensasBase['drop']['tipo'] ?? null, ['equipo', 'entrenamiento', 'accesorio'], true);
            // (en la zona inicial la parte cae siempre: no se gasta la poción)
            $esVarianteZona = ($this->enemigo->es_enemigo ?? null) == Post::VARIANTE_ZONA;
            if ($cayoParte && ! $esVarianteZona && ! $this->cazaActiva() && $this->personaje->objeto_consumible_id) {
                $objeto = \App\Models\Objeto::find($this->personaje->objeto_consumible_id);
                if ($objeto) {
                    $stats = is_array($objeto->stats) ? $objeto->stats : json_decode($objeto->stats, true);
                    if (($stats['afecta'] ?? '') === 'drop_partes') {
                        // Gasta un uso; con el último se termina
                        $usosRestantes = (int) ($stats['usos_restantes'] ?? 1);
                        if ($usosRestantes <= 1) {
                            $this->personaje->objeto_consumible_id = null;
                            $this->personaje->save();
                            $objeto->delete();
                        } else {
                            $stats['usos_restantes'] = $usosRestantes - 1;
                            $objeto->stats           = $stats;
                            $objeto->save();
                        }
                    }
                }
            }

            // Restauramos los stats de la poción, si aplica

        } elseif ($this->totalDanioPersonaje < $this->totalDanioEnemigo) {
            // En caso de derrota
            $this->resultadoFinal = 'Derrota';
            $this->recompensas    = [];

            if ($this->esPvp) {
                $this->personaje->pvp_perdidas = ($this->personaje->pvp_perdidas ?? 0) + 1;
                $this->darExpAlRivalPvp();
                // El atacado ganó: si peleó con una poción de stat equipada, gasta un uso (como cuando gana atacando)
                Personaje::find($this->enemigo->id ?? null)?->gastarUsoPocionDeStat();
            } elseif ($this->esExploracion()) {
                $this->personaje->pve_perdidas = ($this->personaje->pve_perdidas ?? 0) + 1;
            }

            // Restauramos los stats de la poción (si hubo algún cambio por poción)

            $usarPocionRecuperacion = false;

            // Si hay una poción de recuperación equipada, solo la consumimos si el personaje pierde
            if ($this->personaje->objeto_consumible_id) {
                $objeto = Objeto::find($this->personaje->objeto_consumible_id);

                if ($objeto) {
                    $stats = is_array($objeto->stats) ? $objeto->stats : json_decode($objeto->stats, true);

                    if (($stats['afecta'] ?? '') === 'recuperacion') {
                        $usosRestantes = $stats['usos_restantes'] ?? 0;

                        if ($usosRestantes > 0) {
                            $usarPocionRecuperacion = true;

                            $stats['usos_restantes'] = $usosRestantes - 1;
                            $objeto->stats           = $stats;

                            if ($usosRestantes <= 1) {
                                $objeto->delete();
                                $this->personaje->objeto_consumible_id = null;
                            } else {
                                $objeto->save();
                            }
                        }
                    }
                }
            }
             $tieneSiempreEnPie = collect($this->personaje->postDeCombate()?->poderes ?? [])->contains(function ($poder) {
        return strtoupper($poder['nombre'] ?? '') === 'SIEMPRE EN PIE';
    });
if ($tieneSiempreEnPie) {
        // Aplica reducción 100% al tiempo de recuperación
        $this->personaje->fin_exploracion = now();
    } else {
            // Recuperación por derrota según el nivel; la poción de recuperación la deja en 15 s como máximo
            $segundos = self::segundosRecuperacion($this->personaje->nivel, 'Derrota', $this->esPvp);
            if ($usarPocionRecuperacion) {
                $segundos = min($segundos, 15);
            }
            $this->personaje->fin_exploracion = now()->addSeconds($segundos);
        }

        } else {
            $this->resultadoFinal = 'Empate';
            $this->recompensas    = [];
        }
// Preparar el prefijo para gifs personaje equipado
        $gifPrefix = null;
        if (
            $this->personaje->equipo && $this->personaje->entrenamiento && $this->personaje->accesorio &&
            $this->personaje->equipo->origen_post_id === $this->personaje->entrenamiento->origen_post_id &&
            $this->personaje->equipo->origen_post_id === $this->personaje->accesorio->origen_post_id
        ) {
            $postEquipado = \App\Models\Post::find($this->personaje->equipo->origen_post_id);
            if ($postEquipado && $postEquipado->gif) {
                $filename  = pathinfo($postEquipado->gif, PATHINFO_FILENAME);   // ej: "1752867224_gif"
                $gifPrefix = 'posts/' . preg_replace('/_gif$/', '', $filename); // quita solo el "_gif"
            }
        }

// Preparar el prefijo para gifs enemigo
        $gifPrefixEnemigo = null;
        if ($this->gifEnemigo()) {
            $filenameEnemigo  = pathinfo($this->gifEnemigo(), PATHINFO_FILENAME); // ej: "1752867224_gif"
            $gifPrefixEnemigo = 'posts/' . preg_replace('/_gif$/', '', $filenameEnemigo);
        }

        $gifMostrar    = null;
        $equipo        = $this->personaje->equipo ?? null;
        $entrenamiento = $this->personaje->entrenamiento ?? null;
        $accesorio     = $this->personaje->accesorio ?? null;

        if ($equipo && $entrenamiento && $accesorio &&
            $equipo->origen_post_id === $entrenamiento->origen_post_id &&
            $equipo->origen_post_id === $accesorio->origen_post_id) {
            $gifMostrar = \App\Models\Post::find($equipo->origen_post_id)?->gif;
        }

        if (! $gifMostrar) {
            $gifMostrar = $this->personaje->post?->gif ?? $this->personaje->gif ?? null;
        }

        $poderesPersonaje = collect();

        if ($equipo && $entrenamiento && $accesorio &&
            $equipo->origen_post_id === $entrenamiento->origen_post_id &&
            $equipo->origen_post_id === $accesorio->origen_post_id) {

            $postEquipado = \App\Models\Post::find($equipo->origen_post_id);

            if ($postEquipado && $postEquipado->poderes) {
                $poderesPersonaje = collect($postEquipado->poderes)
                    ->map(fn($p) => strtoupper($p['nombre'] ?? ''));
            }
        }

        if ($poderesPersonaje->isEmpty()) {
            $poderesPersonaje = collect($this->personaje->post->poderes ?? [])
                ->map(fn($p) => strtoupper($p['nombre'] ?? ''));
        }

        $datosCombateCompleto = [
            'danio_personaje'   => $this->totalDanioPersonaje,
            'danio_enemigo'     => $this->totalDanioEnemigo,
            'exp_ganada'        => $expGanada ?? 0,
            'oro_ganado'        => $oroGanado ?? 0,
            'drop'              => $this->recompensas['drop'] ?? null,
            'diamante'          => $this->recompensas['diamante'] ?? 0,
            'gif_personaje'     => $gifMostrar,
            'gif_enemigo'       => $this->gifEnemigo(),
            'nombre_personaje'  => $this->personaje->nombre,
            // Set o, en PvP, el nombre del personaje rival
            'nombre_enemigo'    => $this->enemigo->titulo ?? $this->enemigo->nombre ?? 'Enemigo',
            'poderes_personaje' => $poderesPersonaje->toArray(),
            'poderes_enemigo'   => collect($this->enemigo->poderes ?? [])->map(fn($p) => strtoupper($p['nombre'] ?? ''))->toArray(),
            // Para volver a ver la pelea desde "Mis peleas" (partial livewire.partials.resultado-pelea)
            'enemigo_es_personaje' => (bool) $this->esPvp,
            // El set con el que peleó cada uno: al volver a verla se muestra ese, no el que tengan equipado después
            'post_personaje_id' => $this->personaje->postDeCombate()?->id,
            'post_enemigo_id'   => $this->gifsEnemigo()?->id,
            // Tipo de daño con el que peleó cada uno (al volver a verla no se toma el del set que tenga equipado después)
            'tipo_personaje'    => $this->personaje->postDeCombate()?->tipo ?? 'fisico',
            'tipo_enemigo'      => $this->gifsEnemigo()?->tipo ?? $this->enemigo->tipo ?? 'fisico',
            'ciudad_id'         => $this->ciudadActual->id ?? null,
            // Para "Mis Drops": de dónde salió la pelea y cuántos minutos se exploró
            'origen'            => match (true) {
                $this->esDuelo => 'duelo',
                (bool) $this->esPvp => 'pvp',
                (bool) $this->misionActiva() => 'mision',
                (bool) $this->torreActiva() => 'torre',
                (bool) $this->mazmorraActiva() => 'mazmorra',
                (bool) $this->cazaActiva() => 'caza',
                default => 'explorar',
            },
            'minutos'           => (int) ($this->personaje->exploracion_duracion ?? 0) ?: null,
            'escenario_mision'  => $this->escenarioMision,
            'rondas'            => $this->resultadosRondas,
            'vista'             => $this->estadoVistaPelea(),
        ];

// Guardar pelea con datos (el id queda para el botón Compartir de abajo de la pelea)
        $this->ultimaPeleaId = Pelea::create([
            'personaje_id'  => $this->personaje->id,
            'enemigo_id'    => $this->enemigo->id ?? null,
            // victoria | derrota | empate (el mismo daño de los dos lados)
            'resultado'     => strtolower($this->resultadoFinal ?? 'derrota'),
            'exp_ganada'    => $this->recompensas['exp'] ?? 0,
            'oro_ganado'    => $this->recompensas['oro'] ?? 0,
            'drop'          => $this->recompensas['drop'] ?? null,  // 🎁 Drop
            'diamante'      => $this->recompensas['diamante'] ?? 0, // 💎 Diamantes
            'realizada_en'  => now(),
            'datos_combate' => $datosCombateCompleto,
            'ciudad_actual' => $this->personaje->ciudad_actual ?? 'Desconocida',
            'gif_personaje' => $gifMostrar,
            'gif_enemigo'   => $this->gifEnemigo(),
        ])->id;

        // Duelo: queda terminado y el que desafió puede ver la pelea
        if ($this->esDuelo && $this->dueloId) {
            Desafio::whereKey($this->dueloId)->update(['estado' => 'completado', 'pelea_id' => $this->ultimaPeleaId]);
        }

        // ⏳ Recuperación al ganar o empatar (la de derrota se calcula arriba, con la poción de recuperación)
        if ($this->resultadoFinal !== 'Derrota' && ! $this->esDuelo) {
            $siempreEnPie = collect($this->personaje->postDeCombate()?->poderes ?? [])
                ->contains(fn ($poder) => strtoupper($poder['nombre'] ?? '') === 'SIEMPRE EN PIE');
            $this->personaje->fin_exploracion = $siempreEnPie
                ? now()
                : now()->addSeconds(self::segundosRecuperacion($this->personaje->nivel, (string) $this->resultadoFinal, $this->esPvp));
        }
        // PvP: el atacado también queda en recuperación, según cómo le fue a él
        if ($this->esPvp && ! $this->esDuelo) {
            $this->darRecuperacionAlRivalPvp();
            $this->avisarAlRivalPvp();
        }
        // Mostrar el contador de recuperación sin recargar la página
        $restante = $this->personaje->fin_exploracion ? now()->diffInSeconds($this->personaje->fin_exploracion, false) : 0;
        $this->tiempoExploracion = $restante > 0 ? (int) ceil($restante) : null;

        // 🎯 Caza: ganada solo con victoria; con derrota o empate la presa se escapa (la carga ya se gastó)
        $this->cazaActiva()?->update(['estado' => $this->resultadoFinal === 'Victoria' ? 'ganada' : 'perdida']);

        // 📜 Misión: con victoria queda completada; si no, se puede volver a intentar
        if ($mision = $this->misionActiva()) {
            if ($this->resultadoFinal === 'Victoria') {
                \Illuminate\Support\Facades\DB::table('mision_personaje')->insertOrIgnore([
                    'mision_id' => $mision->id, 'personaje_id' => $this->personaje->id, 'completada_en' => now(),
                ]);
            }
            $this->personaje->mision_activa_id = null;
        }

        // 🗼 Torre: con victoria se sube de piso; si no, se puede volver a intentar el mismo
        if ($pisoTorre = $this->torreActiva()) {
            if ($this->resultadoFinal === 'Victoria') {
                $this->personaje->torre_piso = max((int) $this->personaje->torre_piso, $pisoTorre->piso);
            }
            $this->personaje->torre_piso_activo = null;
        }

        // 🕳️ Mazmorra: con victoria pasa al rival siguiente; ganándole al jefe la mazmorra termina.
        // Si no, sigue en el mismo rival (se vuelve a intentar gastando energía otra vez)
        if ($mazmorra = $this->mazmorraActiva()) {
            if ($this->resultadoFinal === 'Victoria') {
                if ($mazmorra->esJefe()) {
                    $mazmorra->fill(['dificultad' => null, 'rivales' => null, 'paso' => 0]);
                    $mazmorra->jefes_derrotados++;
                } else {
                    $mazmorra->paso++;
                }
            }
            $mazmorra->en_pelea = false;
            $mazmorra->save();
        }

        // Finalizamos el combate y actualizamos (un duelo aceptado mientras explora no corta la exploración)
        if (! $this->esDuelo) {
            $this->personaje->exploracion_duracion = 0;
            $this->personaje->minutos_originales   = null;
        }
        $this->personaje->enemigo_actual_id    = null; $this->personaje->enemigo_actual_personaje_id = null;
        $this->personaje->save();

        session(['combate_activo' => false]);
        $this->combateActivo = false;

        // Disparar el evento de actualización de inventario
        $this->dispatch('actualizarInventario');
        // El panel lateral muestra la recuperación (y el oro/exp nuevos)
        $this->dispatch('statsActualizados');
    }

    public function tieneDanioDirecto($poderes): bool
    {
        // Convertir a array si es Collection
        $poderesArray = $poderes instanceof \Illuminate\Support\Collection  ? $poderes->toArray() : $poderes;

        foreach ($poderesArray as $poder) {
            $modificadores = is_array($poder['modificadores']) ? $poder['modificadores'] : json_decode($poder['modificadores'] ?? '[]', true);
            foreach ($modificadores as $mod) {
                if (($mod['tipo'] ?? '') === 'daño_directo') {
                    return true;
                }
            }
        }
        return false;
    }

    public function cargarObjetos()
    {
        $personajeId = $this->personaje->id ?? null;
        if (! $personajeId) {
            return;
        }

        $this->equipo        = \App\Models\Objeto::find($this->personaje->equipo_id);
        $this->entrenamiento = \App\Models\Objeto::find($this->personaje->entrenamiento_id);
        $this->accesorio     = \App\Models\Objeto::find($this->personaje->accesorio_id);

        // Cargar la pocion equipada
        $this->pocionEquipada = null;
        if ($this->personaje->objeto_consumible_id) {
            $this->pocionEquipada = \App\Models\Objeto::find($this->personaje->objeto_consumible_id);
            if ($this->pocionEquipada && is_string($this->pocionEquipada->stats)) {
                $this->pocionEquipada->stats = json_decode($this->pocionEquipada->stats, true);
            }
        }
    }

    public function obtenerStatsCompletos()
    {
        $statsBase = is_string($this->personaje->stats)
        ? json_decode($this->personaje->stats, true) ?: []
        : ($this->personaje->stats ?? []);

        $statsEquipo = is_string($this->equipo->stats ?? '')
        ? json_decode($this->equipo->stats ?? '[]', true)
        : ($this->equipo->stats ?? []);

        $statsEntrenamiento = is_string($this->entrenamiento->stats ?? '')
        ? json_decode($this->entrenamiento->stats ?? '[]', true)
        : ($this->entrenamiento->stats ?? []);

        $statsAccesorio = is_string($this->accesorio->stats ?? '')
        ? json_decode($this->accesorio->stats ?? '[]', true)
        : ($this->accesorio->stats ?? []);

        // Joya de la Torre (2 stats)
        $joya = $this->personaje->joya_id ? \App\Models\Objeto::find($this->personaje->joya_id) : null;
        $statsJoya = Personaje::decodificarStats($joya?->stats ?? []);

        $statsCombinados = [];

        foreach (['fuerza', 'ataque', 'velocidad', 'resistencia', 'defensa', 'energia'] as $stat) {
            $statsCombinados[$stat] =
                ($statsBase[$stat] ?? 0) +
                ($statsEquipo[$stat] ?? 0) +
                ($statsEntrenamiento[$stat] ?? 0) +
                ($statsAccesorio[$stat] ?? 0) +
                (int) ($statsJoya[$stat] ?? 0);
        }

        // Aplicar multiplicador de la poción si está equipada y tiene stats válidos
        if ($this->pocionEquipada && isset($this->pocionEquipada->stats['afecta']) && isset($this->pocionEquipada->stats['multiplicador'])) {
            $afectaStat    = $this->pocionEquipada->stats['afecta'];
            $multiplicador = $this->pocionEquipada->stats['multiplicador'];

            if (isset($statsCombinados[$afectaStat])) {
                $statsCombinados[$afectaStat] = intval($statsCombinados[$afectaStat] * $multiplicador);
            }
        }

        // Poderes que suben stats (SUPER DEFENSA, ENERGIZADO...): los mismos puntos amarillos que muestra el panel
        $statsCombinados = \App\Support\PoderesStats::aplicar($statsCombinados, $this->personaje->postDeCombate()?->poderes ?? collect());

        // dd para debug (podés comentar o borrar después)
        // dd([
        //  'equipo' => $this->equipo,
        //  'statsEquipo' => $statsEquipo,
        //  'entrenamiento' => $this->entrenamiento,
        //  'statsEntrenamiento' => $statsEntrenamiento,
        //  'accesorio' => $this->accesorio,
        //  'statsAccesorio' => $statsAccesorio,
        //  'pocionEquipada' => $this->pocionEquipada,
        //  'statsCombinados' => $statsCombinados,
        // ]);

 foreach ($this->personaje->estadosTemporales->filter(fn($e) => $e->estaActivo()) as $estado) {
    if (is_array($estado->stats_afectados) && $estado->porcentaje) {
        foreach ($estado->stats_afectados as $stat) {
            if (isset($statsCombinados[$stat])) {
                $factor = (100 - $estado->porcentaje) / 100;
                $statsCombinados[$stat] = round($statsCombinados[$stat] * $factor);
            }
        }
    }
}

        return $statsCombinados;
    }

// Ejemplo de uso: actualizar tipo del personaje al cargar los objetos
    public function actualizarTipoPersonaje()
    {
        $this->personaje->tipo = $this->determinarTipoPersonaje($this->equipo, $this->entrenamiento, $this->accesorio);
    }

// Poderes que reducen el daño recibido de un tipo (PIEL DURA 25% físico, PIEL IMPENETRABLE 50% físico,
// REDUCCIÓN ELEMENTAL 50% elemental). Antes calculaba el daño reducido pero devolvía el original: no reducían nada
private function aplicarReduccionDanioPorTipo($danio, $poderes, $tipoDanio)
{
    foreach ($poderes ?? [] as $poder) {
        $modsRaw = $poder['modificadores'] ?? [];
        $mods = is_array($modsRaw) ? $modsRaw : (json_decode($modsRaw ?: '[]', true) ?: []);

        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'reduccion_danio' &&
                strtolower($mod['tipo_danio'] ?? '') === strtolower($tipoDanio)
            ) {
                $porcentaje = min(100, max(0, (float) ($mod['porcentaje'] ?? 0)));
                $danio = round($danio * (1 - $porcentaje / 100));
            }
        }
    }

    return $danio;
}



protected function obtenerPoderesAnulados($combatiente)
{
    $poderesAnulados = [];

    $poderes = $combatiente->post ? $combatiente->post->poderes : collect();

    foreach ($poderes as $poder) {
        if ($poder->nombre === 'ANULACIÓN DE PODER') {
            $modsRaw = $poder['modificadores'] ?? '[]';
            $mods = is_array($modsRaw) ? $modsRaw : (json_decode($modsRaw, true) ?? []);

            foreach ($mods as $mod) {
                if (($mod['tipo'] ?? '') === 'anulacion_poder') {
                    $poderesAnulados = $mod['poderes'] ?? [];
                }
            }
        }
    }

    return $poderesAnulados;
}


    public function atacar()
    {

        $this->mostrarBotonesBatalla = false;

        $this->danioPoderesPersonaje     = 0;
        $this->danioPoderesEnemigo       = 0;
        $this->huboDanioDirectoPersonaje = false;
        $this->huboDanioDirectoEnemigo   = false;
        $this->danioExtraTotalPersonaje  = 0;
        $this->danioExtraTotalEnemigo    = 0;

        $this->totalDanioPersonaje             = 0;
        $this->totalDanioEnemigo               = 0;
        $this->danioAtaqueDesesperadoPersonaje = 0;
        $this->danioAtaqueDesesperadoEnemigo   = 0;


        if (! $this->combateActivo) {
            return;
        }

        $this->actualizarTipoPersonaje();
        $this->cargarObjetos();

        $res = [];

        $statsPersonaje = $this->obtenerStatsCompletos();
        // dd($statsPersonaje);

        // PvP: el rival es un jugador → sus stats base más los de sus partes equipadas
        $statsEnemigo = $this->enemigo instanceof Personaje
            ? $this->enemigo->statsDeCombate()
            : Personaje::decodificarStats($this->enemigo->stats);
        // dd($statsEnemigo);

        // 📜🗼 Misión o Torre: el rival pelea como un jugador equipado de su nivel.
        // 🧭 Exploración: también (ver EQUIPO_RIVAL_EXPLORACION)
        $mazmorraPelea = $this->mazmorraActiva();
        $esRivalMisionTorre = $this->misionActiva() || $this->torreActiva() || $mazmorraPelea; // la mazmorra, como la Torre
        $esEnemigoExploracion = $this->esExploracion() && ($this->enemigo->es_enemigo ?? null) != self::ENEMIGO_ESPECIAL;
        if (! ($this->enemigo instanceof Personaje) && ($esRivalMisionTorre || $esEnemigoExploracion)) {
            $factorRival = self::refuerzoRivalMisionTorre(
                (int) ($this->enemigo->nivel ?? 1),
                $esRivalMisionTorre ? self::EQUIPO_RIVAL_MISION_TORRE : self::EQUIPO_RIVAL_EXPLORACION
            );
            // 🕳️ Mazmorra: más fuerte según la dificultad (y el jefe un poco más)
            if ($mazmorraPelea) {
                $factorRival *= $mazmorraPelea->factorStats();
            }
            foreach ($statsEnemigo as $stat => $valor) {
                if (is_numeric($valor)) {
                    $statsEnemigo[$stat] = (int) round($valor * $factorRival);
                }
            }
        }

        // Enemigos del juego: sus poderes que suben stats también cuentan (los jugadores ya los traen en statsDeCombate)
        if (! ($this->enemigo instanceof Personaje)) {
            $statsEnemigo = \App\Support\PoderesStats::aplicar($statsEnemigo, $this->enemigo->poderes ?? collect());
        }

        // 🎯 Presa de caza: stats reforzados según la rareza
        if ($caza = $this->cazaActiva()) {
            $factorCaza = $caza->rarezaInfo()['stats'];
            foreach ($statsEnemigo as $stat => $valor) {
                if (is_numeric($valor)) {
                    $statsEnemigo[$stat] = (int) round($valor * $factorCaza);
                }
            }
        }

        $nivelPersonaje = $this->personaje->nivel;
        $nivelEnemigo   = $this->enemigo->nivel ?? 1;

        $tipoPersonaje    = 'fisico';
        $poderesPersonaje = [];

        $equipo        = $this->personaje->equipo;
        $entrenamiento = $this->personaje->entrenamiento;
        $accesorio     = $this->personaje->accesorio;

        if (
            $equipo && $entrenamiento && $accesorio &&
            $equipo->origen_post_id &&
            $equipo->origen_post_id === $entrenamiento->origen_post_id &&
            $equipo->origen_post_id === $accesorio->origen_post_id
        ) {
            $postOrigen       = \App\Models\Post::find($equipo->origen_post_id);
            $tipoPersonaje    = $postOrigen?->tipo ?? 'fisico';
            $poderesPersonaje = $postOrigen?->poderes ?? [];
        } elseif ($this->personaje->post) {
            $tipoPersonaje    = $this->personaje->post->tipo ?? 'fisico';
            $poderesPersonaje = $this->personaje->post->poderes ?? [];
        }

        // PvP: el rival pelea con el tipo de daño y los poderes de su set completo equipado (o de su set base),
        // igual que el jugador; no con los del personaje con el que arrancó
        if ($this->enemigo instanceof Personaje && ($postRival = $this->gifsEnemigo()) instanceof Post) {
            $this->enemigo->setRelation('poderes', $postRival->poderes);
        }
        // El jugador también: sus poderes son los del set con el que pelea (la relación "poderes" es la del set base)
        $this->personaje->setRelation('poderes', collect($poderesPersonaje));
        $tipoEnemigo = $this->gifsEnemigo()?->tipo ?? $this->enemigo->tipo ?? 'fisico';

        $calcularDanioFisico = function ($stats, $nivel) {
            $multiplicadorNivel = 1 + ($nivel * self::DANIO_POR_NIVEL);
            $ataque             = $stats['ataque'] ?? 0;
            $fuerza             = $stats['fuerza'] ?? 0;
            return rand(
                round(($fuerza * 0.5 + $ataque) * $multiplicadorNivel * 0.8),
                round(($fuerza * 0.5 + $ataque) * $multiplicadorNivel * 1.2)
            );
        };

        $calcularDanioElemental = function ($stats, $nivel) {
            $multiplicadorNivel = 1 + ($nivel * self::DANIO_POR_NIVEL);
            $ataque             = $stats['ataque'] ?? 0;
            $energia            = $stats['energia'] ?? 0;
            return rand(
                round(($energia * 0.5 + $ataque) * $multiplicadorNivel * 0.8),
                round(($energia * 0.5 + $ataque) * $multiplicadorNivel * 1.2)
            );
        };

        $determinarTipoAtaque = function ($stats) {
            if (($stats['velocidad'] ?? 0) > 30 && rand(0, 100) < 40) {
                return 'especial';
            } elseif (($stats['fuerza'] ?? 0) > 30 && rand(0, 100) < 40) {
                return 'critico';
            }
            // La defensa ya no hace perder el turno (antes, con más de 30, el 30% de los turnos se quedaba en guardia
            // sin atacar): trabaja cuando le pegan, con el bloqueo y el contraataque
            return 'normal';
        };

        $calcularDanioSegunTipo = function ($tipo, $stats, $nivel, $tipoAtaque, $atacante) use (
            $calcularDanioFisico, $calcularDanioElemental, &$poderesPersonaje
        ) {
            $danioFisico    = 0;
            $danioElemental = 0;

            $poderes = $atacante === 'personaje' ? $poderesPersonaje : $this->enemigo->poderes;

            $danioExtraPoderes = 0;
            $huboDanioDirecto  = false;

            foreach ($poderes as $poder) {
                $modificadoresRaw = $poder['modificadores'] ?? '[]';
                $modificadores    = is_array($modificadoresRaw) ? $modificadoresRaw : (json_decode($modificadoresRaw, true) ?? []);

                foreach ($modificadores as $mod) {
                    if (($mod['tipo'] ?? '') === 'daño_directo') {
                        $statBase   = $mod['stat_base'] ?? null;
                        $porcentaje = ($mod['porcentaje'] ?? 0) / 100;

                        if ($statBase && isset($stats[$statBase])) {
                            $multiplicadorNivel = 1 + ($nivel * self::DANIO_POR_NIVEL);
                            $danioBase          = rand(
                                round($stats[$statBase] * $multiplicadorNivel * 0.8),
                                round($stats[$statBase] * $multiplicadorNivel * 1.2)
                            );
                            $danioExtra = round($danioBase * $porcentaje);

                            if ($tipo === 'hibrido') {
                                $danioFisico += round($danioExtra / 2);
                                $danioElemental += round($danioExtra / 2);
                            } elseif ($tipo === 'fisico') {
                                $danioFisico += $danioExtra;
                            } else {
                                $danioElemental += $danioExtra;
                            }

                            $danioExtraPoderes += $danioExtra;
                            $huboDanioDirecto = true;
                        }
                    }
                }
            }

            if ($atacante === 'personaje') {
                $this->danioPoderesPersonaje += $danioExtraPoderes;
                if ($huboDanioDirecto) {
                    $this->huboDanioDirectoPersonaje = true;
                }
            } else {
                $this->danioPoderesEnemigo += $danioExtraPoderes;
                if ($huboDanioDirecto) {
                    $this->huboDanioDirectoEnemigo = true;
                }
            }

            if ($tipo === 'hibrido') {
                $danioFisico += $calcularDanioFisico($stats, $nivel) * self::FACTOR_DANIO_HIBRIDO;
                $danioElemental += $calcularDanioElemental($stats, $nivel) * self::FACTOR_DANIO_HIBRIDO;

                if ($tipoAtaque === 'critico') {
                    $danioFisico *= 0.5;
                    $danioElemental *= 0.5;
                } elseif ($tipoAtaque === 'especial') {
                    $danioFisico *= 0.2;
                    $danioElemental *= 0.2;
                }
            } elseif ($tipo === 'fisico') {
                $danioFisico += $calcularDanioFisico($stats, $nivel);
                if ($tipoAtaque === 'critico') {
                    $danioFisico *= 0.5;
                }

                if ($tipoAtaque === 'especial') {
                    $danioFisico *= 0.2;
                }

            } else {
                $danioElemental += $calcularDanioElemental($stats, $nivel);
                if ($tipoAtaque === 'critico') {
                    $danioElemental *= 0.5;
                }

                if ($tipoAtaque === 'especial') {
                    $danioElemental *= 0.2;
                }

            }

            if ($atacante !== 'personaje') {
                // Los enemigos del juego pegan el 30% en exploración y el 40% en misiones, torre y caza;
                // en PvP el rival es otro jugador y pega completo
                $reductorDanioEnemigo = match (true) {
                    (bool) $this->esPvp => 1,
                    $this->esExploracion() => 0.3,
                    default => 0.4,
                };
                return [
                    'fisico'    => round($danioFisico * $reductorDanioEnemigo),
                    'elemental' => round($danioElemental * $reductorDanioEnemigo),
                ];
            } else {
                return [
                    'fisico'    => round($danioFisico),
                    'elemental' => round($danioElemental),
                ];
            }

            return [
                'fisico'    => round($danioFisico),
                'elemental' => round($danioElemental),
            ];
        };

        $calcularContraataque = function ($defensorTipo, $defensorStats, $defensorNivel, $defensorNombre, $defensorPoderes) use ($calcularDanioFisico, $calcularDanioElemental) {
            $danioFisico       = 0;
            $danioElemental    = 0;
            $danioExtraContra  = 0;
            $poseeDanioDirecto = false; // Variable para saber si tiene el poder daño directo

            // Daño base 50% para contraataque
            if ($defensorTipo === 'hibrido') {
                $danioFisico    = $calcularDanioFisico($defensorStats, $defensorNivel) * 0.5 * self::FACTOR_DANIO_HIBRIDO;
                $danioElemental = $calcularDanioElemental($defensorStats, $defensorNivel) * 0.5 * self::FACTOR_DANIO_HIBRIDO;
            } elseif ($defensorTipo === 'elemental') {
                $danioElemental = $calcularDanioElemental($defensorStats, $defensorNivel) * 0.5;
            } else {
                $danioFisico = $calcularDanioFisico($defensorStats, $defensorNivel) * 0.5;
            }

            // Agregar daño extra por poderes en contraataque
            foreach ($defensorPoderes as $poder) {
                $modificadoresRaw = $poder['modificadores'] ?? '[]';
                $modificadores    = is_array($modificadoresRaw) ? $modificadoresRaw : (json_decode($modificadoresRaw, true) ?? []);

                foreach ($modificadores as $mod) {
                    if (($mod['tipo'] ?? '') === 'daño_directo') {
                        $poseeDanioDirecto = true;

                        $statBase   = $mod['stat_base'] ?? null;
                        $porcentaje = ($mod['porcentaje'] ?? 0) / 100;

                        if ($statBase && isset($defensorStats[$statBase])) {
                            $multiplicadorNivel = 1 + ($defensorNivel * self::DANIO_POR_NIVEL);
                            $danioBase          = rand(
                                round($defensorStats[$statBase] * $multiplicadorNivel * 0.8),
                                round($defensorStats[$statBase] * $multiplicadorNivel * 1.2)
                            );
                            $danioExtra = round($danioBase * $porcentaje);

                            if ($defensorTipo === 'hibrido') {
                                $danioFisico += round($danioExtra / 2);
                                $danioElemental += round($danioExtra / 2);
                            } elseif ($defensorTipo === 'fisico') {
                                $danioFisico += $danioExtra;
                            } else {
                                $danioElemental += $danioExtra;
                            }

                            $danioExtraContra += $danioExtra;
                        }
                    }
                }
            }

// Si no tiene el poder daño directo, no suma daño extra
            if (! $poseeDanioDirecto) {
                $danioExtraContra = 0;
            }

            // Acumulo daño extra de contraataque para mostrar (usalo si quieres)
            // Pero en esta función no se acumula en la propiedad, solo se retorna.
            return [
                'fisico'    => round($danioFisico),
                'elemental' => round($danioElemental),
                'extra'     => round($danioExtraContra),
            ];
        };

        $this->totalDanioPersonaje = 0;
        $this->totalDanioEnemigo   = 0;

        // Variables para acumular daño extra global
        $danioExtraTotalPersonaje = 0;
        $danioExtraTotalEnemigo   = 0;

        // Guardar los stats base originales
        $statsBasePersonaje = $statsPersonaje;
        $statsBaseEnemigo   = $statsEnemigo;
        // Aplicar Super Carga a personaje y enemigo



// 🔽 Aplicar "CONTROL CLIMATICO" si alguno lo tiene
        $aplicarControlClimatico = function (&$statsObjetivo, $poderesDelAtacante) {
            foreach ($poderesDelAtacante as $poder) {
                if (strtoupper($poder['nombre'] ?? '') === 'CONTROL CLIMATICO') {
                    $mods = is_array($poder['modificadores']) ? $poder['modificadores'] : json_decode($poder['modificadores'], true);
                    foreach ($mods as $mod) {
                        if (
                            ($mod['tipo'] ?? '') === 'modificador_stat' &&
                            ($mod['stat'] ?? '') === 'velocidad' &&
                            isset($mod['valor'])
                        ) {
                            // Aplicar porcentaje a la velocidad (ej: -50% -> multiplicar por 0.5)
                            $statsObjetivo['velocidad'] = max(1, round($statsObjetivo['velocidad'] * (1 + $mod['valor'] / 100)));
                        }
                    }
                }
            }
        };

        $aplicarControlClimatico($statsBaseEnemigo, $poderesPersonaje);
        $aplicarControlClimatico($statsBasePersonaje, $this->enemigo->poderes ?? []);

    function obtenerBuffMaximaPotencia($poderes) {
    $porcentaje = 0;
    foreach ($poderes as $poder) {
        if (strtoupper($poder['nombre'] ?? '') === 'MÁXIMA POTENCIA') {
            $mods = is_array($poder['modificadores']) ? $poder['modificadores'] : json_decode($poder['modificadores'], true);
            foreach ($mods as $mod) {
                if (($mod['tipo'] ?? '') === 'buff_especiales') {
                    $porcentaje += $mod['porcentaje'] ?? 0;
                }
            }
        }
    }
    return $porcentaje;
}

// Antes de aplicar los estados, inicializá las banderas
$poderActivoTrancePersonaje = false;
$poderActivoTranceEnemigo   = false;

// Función general para aplicar estados con modificadores y marcar activación
$aplicarEstadoEspecial = function (&$stats, $poderes, $nombreEstadoBuscado, &$poderActivo) {
    $poderEstado = collect($poderes)->first(function($p) use ($nombreEstadoBuscado) {
        return strtoupper($p['nombre'] ?? '') === strtoupper($nombreEstadoBuscado);
    });

    if ($poderEstado) {
        $mods = is_array($poderEstado['modificadores'])
            ? $poderEstado['modificadores']
            : json_decode($poderEstado['modificadores'], true);

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'estado_con_modificacion_stat') {
                $chance = $mod['chance'] ?? 0;
                $modificaciones = $mod['modificaciones'] ?? [];

                if (rand(1, 100) <= $chance) {
                    foreach ($modificaciones as $modStat) {
                        $stat = strtolower($modStat['stat'] ?? '');
                        $factor = $modStat['factor'] ?? 1;

                        if (isset($stats[$stat])) {
                            $stats[$stat] = round($stats[$stat] * $factor);
                        }
                    }
                    // Marcar que el estado se activó
                    $poderActivo = true;
                }
            }
        }
    }
};

$aplicarReduccionPorCongelado = function ($stats, $personaje) {
    if (!$personaje->estadosTemporales) {
        return $stats;
    }

    $estadoCongelado = $personaje->estadosTemporales
        ->where('estado', 'Congelado')
        ->filter(fn($e) => $e->estaActivo())
        ->first();

    if ($estadoCongelado) {
        $stats['energia']   = round($stats['energia'] * 0.9);
        $stats['velocidad'] = round($stats['velocidad'] * 0.9);
    }

    return $stats;
};

$aplicarReduccionPorAturdido = function ($stats, $personaje) {
    if (!$personaje->estadosTemporales) {
        return $stats;
    }

    $estadoAturdido = $personaje->estadosTemporales
        ->where('estado', 'Aturdido')
        ->filter(fn($e) => $e->estaActivo())
        ->first();

    if ($estadoAturdido) {
        $stats['fuerza']  = round($stats['fuerza'] * 0.9);
        $stats['defensa'] = round($stats['defensa'] * 0.9);
    }

    return $stats;
};


$aplicarEstadoEspecial($statsBasePersonaje, $poderesPersonaje, 'SUPER CARGA', $poderActivoSuperCargaPersonaje);
$aplicarEstadoEspecial($statsBasePersonaje, $poderesPersonaje, 'SUPER NOVA', $poderActivoSuperNovaPersonaje);
$aplicarEstadoEspecial($statsBasePersonaje, $poderesPersonaje, 'TRANCE', $poderActivoTrancePersonaje);

$aplicarEstadoEspecial($statsBaseEnemigo, $this->enemigo->poderes ?? [], 'SUPER CARGA', $poderActivoSuperCargaEnemigo);
$aplicarEstadoEspecial($statsBaseEnemigo, $this->enemigo->poderes ?? [], 'SUPER NOVA', $poderActivoSuperNovaEnemigo);
$aplicarEstadoEspecial($statsBaseEnemigo, $this->enemigo->poderes ?? [], 'TRANCE', $poderActivoTranceEnemigo);


$listaPoderesAnulados = [
    'ABSORVER SALUD',
    'ATAQUE DESESPERADO',
    'ATAQUE TRAICIONERO',
    'ATURDIR',
    'CAMUFLAJE',
    'COMBO VELOZ',
    'CONGELAR',
    'CONTROL CLIMATICO',
    'DAMAGE ABSORV',
    'DIRECT DAMAGE',
    'ENEMISTAD',
    'ENERGIZADO',
    'ENVENENAR',
    'ESPINAS',
    'FRENESÍ',
    'FURIA CIEGA',
    'GOLPES VELOCES',
    'HEMORRAGIA',
    'INSTINTO MEJORADO',
    'INTIMIDAR',
    'MÁXIMA POTENCIA',
    'MOLE',
    'OFENSIVO EXPERTO',
    'PARALIZAR',
    'PIEL DURA',
    'PIEL IMPENETRABLE',
    'QUEMAR',
    'REGENERAR SUPERIOR',
    'REGENERAR',
    'SIEMPRE EN PIE',
    'SUERTUDO',
    'ROBAR VIDA',
    'SANGRADO',
    'SUPER ATAQUE',
    'SUPER CARGA',
    'SUPER DEFENSA',
    'SUPER ENERGÍA',
    'SUPER FUERZA',
    'SUPER NOVA',
    'SUPER RESISTENCIA',
    'SUPER SENTIDOS',
    'SUPER VELOCIDAD',
    'TÉCNICAS CERTERAS',
    'TELETRANSPORTARSE',
    'TRANCE',
    'REDUCCIÓN ELEMENTAL',
];


$poderesPersonajeArray = is_array($poderesPersonaje) ? $poderesPersonaje : $poderesPersonaje->toArray();
$poderesEnemigoArray = is_array($this->enemigo->poderes) ? $this->enemigo->poderes : $this->enemigo->poderes->toArray();
// Si personaje tiene Anulación de poder, anular poderes del enemigo
if (in_array('ANULACIÓN DE PODER', array_column($poderesPersonajeArray, 'nombre'))) {
    if ($poderesEnemigoArray instanceof \Illuminate\Database\Eloquent\Collection) {
        $poderesEnemigoArray = $poderesEnemigoArray->toArray();
    }
    $poderesEnemigoArray = array_filter($poderesEnemigoArray, fn($p) => !in_array(strtoupper($p['nombre']), $listaPoderesAnulados));
    // Actualizo la propiedad del enemigo con la lista filtrada para que afecte el combate
    $this->enemigo->poderes = collect($poderesEnemigoArray);
}

// Si enemigo tiene Anulación de poder, anular poderes del personaje
if (in_array('ANULACIÓN DE PODER', array_column($poderesEnemigoArray, 'nombre'))) {
    if ($poderesPersonajeArray instanceof \Illuminate\Database\Eloquent\Collection) {
        $poderesPersonajeArray = $poderesPersonajeArray->toArray();
    }
    $poderesPersonajeArray = array_filter($poderesPersonajeArray, fn($p) => !in_array(strtoupper($p['nombre']), $listaPoderesAnulados));
    // Actualizo la propiedad del personaje con la lista filtrada
    $this->personaje->poderes = collect($poderesPersonajeArray);
}


 // Frenesí y Furia Ciega tiran su chance una sola vez, al empezar la pelea (como Trance): si sale, valen en todas las
 // rondas. Las banderas quedan para toda la pelea (antes se reiniciaban en cada ronda y solo contaba la última)
 $poderActivoFrenesiPersonaje = false;
 $poderActivoFrenesiEnemigo = false;
 $poderActivoFuriaCiegaPersonaje = false;
 $poderActivoFuriaCiegaEnemigo = false;
 $tiradasEstadoPelea = []; // [estado][lado] => si salió la chance (se tira una vez por pelea)

 for ($r = 1; $r <= 5; $r++) {

    $this->danioPoderesPersonaje = 0;
    $this->danioPoderesEnemigo   = 0;
    $this->rondaActual           = $r;

    $modificarStatsConBuffs = function ($stats, $poderes, $rondaActual, $esPersonaje) use (&$poderActivoFrenesiPersonaje, &$poderActivoFrenesiEnemigo,
    &$poderActivoFuriaCiegaPersonaje, &$poderActivoFuriaCiegaEnemigo, &$tiradasEstadoPelea) {
     
        $poderesAAnular = [];
        foreach ($poderes as $poder) {
            $nombrePoder = strtoupper($poder['nombre'] ?? '');
            
            $modificadores = is_array($poder['modificadores'])
                ? $poder['modificadores']
                : json_decode($poder['modificadores'] ?? '[]', true);

            foreach ($modificadores as $mod) {
                
                // COMBO VELOZ
                if (($mod['tipo'] ?? '') === 'aumento_stat') {
                    $duracion = $mod['duracion'] ?? 0;
                    if ($duracion >= $rondaActual) {
                        if ($nombrePoder === 'COMBO VELOZ') {
                            $stat            = $mod['stat'] ?? null;
                            $valorPorcentaje = $mod['valor_porcentaje'] ?? 0;
                            if ($stat && isset($stats[$stat])) {
                                $stats[$stat] = round($stats[$stat] * (1 + $valorPorcentaje / 100));
                            }
                        } elseif ($nombrePoder === 'ENEMISTAD') {
                            $statsAfectados  = $mod['stats_afectados'] ?? [];
                            $valorPorcentaje = $mod['porcentaje'] ?? 0;
                            foreach ($statsAfectados as $stat) {
                                if (isset($stats[$stat])) {
                                    $stats[$stat] = round($stats[$stat] * (1 + $valorPorcentaje / 100));
                                }
                            }
                        }
                    }
                }



// FRENESÍ
if ($nombrePoder === 'FRENESÍ') {
    if (($mod['tipo'] ?? '') === 'estado_en_ronda' && ($mod['estado'] ?? '') === 'Frenesí') {
        $chance = $mod['chance'] ?? 0;
        $ladoTirada = $esPersonaje ? 'personaje' : 'enemigo';
        $tiradasEstadoPelea['frenesi'][$ladoTirada] ??= rand(1, 100) <= $chance;
        if ($tiradasEstadoPelea['frenesi'][$ladoTirada]) {

            foreach ($mod['modificaciones_stat'] ?? [] as $modStat) {
                $porcentaje = $modStat['porcentaje'] ?? 0;
                foreach ($modStat['stats'] ?? [] as $stat) {
                    if (isset($stats[$stat])) {
                        $stats[$stat] = round($stats[$stat] * (1 + $porcentaje / 100));
                    }
                }
            }

            // Acá justo después de modificar los stats:
            if ($esPersonaje) {
                $poderActivoFrenesiPersonaje = true;
            } else {
                $poderActivoFrenesiEnemigo = true;
            }

        }
    }
}


if ($nombrePoder === 'FURIA CIEGA') {
    if (($mod['tipo'] ?? '') === 'estado_en_ronda') {
        $chance = $mod['chance'] ?? 0;
        $ladoTirada = $esPersonaje ? 'personaje' : 'enemigo';
        $tiradasEstadoPelea['furia'][$ladoTirada] ??= rand(1, 100) <= $chance;
        if ($tiradasEstadoPelea['furia'][$ladoTirada]) {
            foreach ($mod['modificaciones_stat'] ?? [] as $modStat) {
                $porcentaje = $modStat['porcentaje'] ?? 0;
                foreach ($modStat['stats'] ?? [] as $stat) {
                    if (isset($stats[$stat])) {
                        $stats[$stat] = round($stats[$stat] * (1 + $porcentaje / 100));
                    }
                }
            }

            // Aquí marcamos que Furia Ciega se activó
            if ($esPersonaje) {
                $poderActivoFuriaCiegaPersonaje = true;
            } else {
                $poderActivoFuriaCiegaEnemigo = true;
            }
        }
    }
}

                // INSTINTO MEJORADO
                if ($nombrePoder === 'INSTINTO MEJORADO') {
                    if (($mod['tipo'] ?? '') === 'buff_temporal') {
                        $duracion = $mod['duracion'] ?? 0;
                        if ($rondaActual <= $duracion) {
                            $porcentaje = $mod['porcentaje'] ?? 0;
                            foreach ($mod['stats'] ?? [] as $stat) {
                                if (isset($stats[$stat])) {
                                    $stats[$stat] = round($stats[$stat] * (1 + $porcentaje / 100));
                                }
                            }
                        }
                    }
                    if (($mod['tipo'] ?? '') === 'anulacion_poder') {
                        $poderesAAnular = array_merge($poderesAAnular, $mod['poderes'] ?? []);
                    }
                }

                // SUPER SENTIDOS
                if ($nombrePoder === 'SUPER SENTIDOS') {
                    if (($mod['tipo'] ?? '') === 'buff_temporal') {
                        $duracion = $mod['duracion'] ?? 0;
                        if ($rondaActual <= $duracion) {
                            $porcentaje = $mod['porcentaje'] ?? 0;
                            foreach ($mod['stats'] ?? [] as $stat) {
                                if (isset($stats[$stat])) {
                                    $stats[$stat] = round($stats[$stat] * (1 + $porcentaje / 100));
                                }
                            }
                        }
                    }

                    if (($mod['tipo'] ?? '') === 'anulacion_poder') {
                        $poderesAAnular = array_merge($poderesAAnular, $mod['poderes'] ?? []);
                    }
                }
            }
        }
    

        // Podés guardar $poderesAAnular para usar luego en la lógica de ataque para anular efectos
        return $stats;
    };

    // Nueva función para aplicar debuffs que afectan a este personaje, por poderes del enemigo
    $aplicarDebuffsDelOponente = function ($stats, $poderesOponente) {
        foreach ($poderesOponente as $poder) {
            $nombrePoder = strtoupper($poder['nombre'] ?? '');

            $modificadores = is_array($poder['modificadores'])
                ? $poder['modificadores']
                : json_decode($poder['modificadores'] ?? '[]', true);

            foreach ($modificadores as $mod) {
                if (($mod['tipo'] ?? '') === 'debuff' && $nombrePoder === 'INTIMIDAR') {
                    $porcentaje = $mod['porcentaje'] ?? 0;
                    foreach ($mod['stats'] ?? [] as $stat) {
                        if (isset($stats[$stat])) {
                            $stats[$stat] = round($stats[$stat] * (1 + $porcentaje / 100));
                        }
                    }
                }
                // Podés agregar más debuffs que afecten al enemigo acá
            }
        }
        return $stats;
    };


            // Aplicar buffs de stat según ronda actual desde los stats base
            $statsPersonaje = $modificarStatsConBuffs($statsBasePersonaje, $poderesPersonaje, $r, true);
            $statsEnemigo   = $modificarStatsConBuffs($statsBaseEnemigo, $this->enemigo->poderes ?? [], $r, false);

            $this->poderActivoFrenesiPersonaje = $poderActivoFrenesiPersonaje;
            $this->poderActivoFrenesiEnemigo = $poderActivoFrenesiEnemigo;
    
            $this->poderActivoFuriaCiegaPersonaje = $poderActivoFuriaCiegaPersonaje;
            $this->poderActivoFuriaCiegaEnemigo = $poderActivoFuriaCiegaEnemigo;

            $this->poderActivoTrancePersonaje = $poderActivoTrancePersonaje;
            $this->poderActivoTranceEnemigo = $poderActivoTranceEnemigo;

            $this->poderActivoSuperNovaPersonaje = $poderActivoSuperNovaPersonaje;
            $this->poderActivoSuperNovaEnemigo = $poderActivoSuperNovaEnemigo;

            $this->poderActivoSuperCargaPersonaje = $poderActivoSuperCargaPersonaje;
            $this->poderActivoSuperCargaEnemigo = $poderActivoSuperCargaEnemigo;



               // Aplicar debuffs que el enemigo tiene sobre este personaje
    $statsPersonaje = $aplicarDebuffsDelOponente($statsPersonaje, $this->enemigo->poderes ?? []);

    // Aplicar debuffs que el personaje tiene sobre el enemigo
    $statsEnemigo = $aplicarDebuffsDelOponente($statsEnemigo, $poderesPersonaje);

            //  dump("Ronda $r - Stats Personaje", $statsPersonaje);
            //  dump("Ronda $r - Stats Enemigo", $statsEnemigo);


            $prioridadPersonaje = ($statsPersonaje['velocidad'] ?? 0) + $nivelPersonaje + rand(0, 2);
            $prioridadEnemigo   = ($statsEnemigo['velocidad'] ?? 0) + $nivelEnemigo + rand(0, 2);

            // Pega uno solo por ronda. Contra los enemigos del juego pega el más rápido.
            // En PvP (y duelos) el turno se sortea con peso según velocidad + nivel: el más rápido pega más seguido,
            // pero no todas las rondas (si no, con un punto más de velocidad ganaba siempre el mismo)
            if ($this->esPvp) {
                $pesoPersonaje = max(1, ($statsPersonaje['velocidad'] ?? 0) + $nivelPersonaje);
                $pesoEnemigo   = max(1, ($statsEnemigo['velocidad'] ?? 0) + $nivelEnemigo);
                $primeroEnAtacar = mt_rand(1, $pesoPersonaje + $pesoEnemigo) <= $pesoPersonaje ? 'personaje' : 'enemigo';
            } else {
                $primeroEnAtacar = $prioridadPersonaje >= $prioridadEnemigo ? 'personaje' : 'enemigo';
            }
            $ordenAtaques = [$primeroEnAtacar];

            foreach ($ordenAtaques as $atacante) {
            if ($atacante === 'personaje') {
                $statsAtacante       = $statsPersonaje;
                $nivelAtacante       = $nivelPersonaje;
                $tipoAtacante        = $tipoPersonaje;
                $nombreAtacante      = $this->personaje->nombre;
                $gifCriticoAtacante  = $this->personaje->post->gif_critico ?? 'critico.gif';
                $gifEspecialAtacante = $this->personaje->post->gif_especial ?? 'especial.gif';
                $gifDefensaAtacante  = $this->personaje->post->gif_defensa ?? 'defensa.gif';
                $gifAtaqueAtacante   = $this->personaje->post->gif_ataque ?? 'normal.gif';
                $gifContraAtacante   = $this->personaje->post->gif_ataque ?? 'normal.gif';

                $statsDefensor       = $statsEnemigo;
                $nivelDefensor       = $nivelEnemigo;
                $tipoDefensor        = $tipoEnemigo;
                $nombreDefensor      = $this->enemigo->nombre;
                $gifCriticoDefensor  = $this->gifsEnemigo()->gif_critico ?? 'critico.gif';
                $gifEspecialDefensor = $this->gifsEnemigo()->gif_especial ?? 'especial.gif';
                $gifDefensaDefensor  = $this->gifsEnemigo()->gif_defensa ?? 'defensa.gif';
                $gifAtaqueDefensor   = $this->gifsEnemigo()->gif_ataque ?? 'normal.gif';
                $gifContraDefensor   = $this->gifsEnemigo()->gif_ataque ?? 'normal.gif';

                $poderesDefensor = $this->enemigo->poderes;
            } else {
                $statsAtacante       = $statsEnemigo;
                $nivelAtacante       = $nivelEnemigo;
                $tipoAtacante        = $tipoEnemigo;
                $nombreAtacante      = $this->enemigo->nombre;
                $gifCriticoAtacante  = $this->gifsEnemigo()->gif_critico ?? 'critico.gif';
                $gifEspecialAtacante = $this->gifsEnemigo()->gif_especial ?? 'especial.gif';
                $gifDefensaAtacante  = $this->gifsEnemigo()->gif_defensa ?? 'defensa.gif';
                $gifAtaqueAtacante   = $this->gifsEnemigo()->gif_ataque ?? 'normal.gif';
                $gifContraAtacante   = $this->gifsEnemigo()->gif_ataque ?? 'normal.gif';

                $statsDefensor       = $statsPersonaje;
                $nivelDefensor       = $nivelPersonaje;
                $tipoDefensor        = $tipoPersonaje;
                $nombreDefensor      = $this->personaje->nombre;
                $gifCriticoDefensor  = $this->personaje->post->gif_critico ?? 'critico.gif';
                $gifEspecialDefensor = $this->personaje->post->gif_especial ?? 'especial.gif';
                $gifDefensaDefensor  = $this->personaje->post->gif_defensa ?? 'defensa.gif';
                $gifAtaqueDefensor   = $this->personaje->post->gif_ataque ?? 'normal.gif';
                $gifContraDefensor   = $this->personaje->post->gif_ataque ?? 'normal.gif';

                $poderesDefensor = $this->personaje->poderes;
            }




            $tipoAtaque = $determinarTipoAtaque($statsAtacante);
            $danios     = $calcularDanioSegunTipo($tipoAtacante, $statsAtacante, $nivelAtacante, $tipoAtaque, $atacante);

            // Aplicar MÁXIMA POTENCIA si es un ataque especial
            $buffEspecial = 0;
            if ($tipoAtaque === 'especial') {
                $buffEspecial = obtenerBuffMaximaPotencia($atacante === 'personaje' ? $poderesPersonaje : $this->enemigo->poderes ?? []);
                if ($buffEspecial > 0) {
                    $danios['fisico']    = round($danios['fisico'] * (1 + $buffEspecial / 100));
                    $danios['elemental'] = round($danios['elemental'] * (1 + $buffEspecial / 100));
                }
            }

            $danioExtraRondaPersonaje = $this->danioPoderesPersonaje;
            $danioExtraRondaEnemigo   = $this->danioPoderesEnemigo;
$defensaDefensor = ($statsDefensor['defensa'] ?? 0) * (1 + ($nivelDefensor * 0.03));

// Determinar poderes defensor antes que nada
$esPersonajeDefensor = $nombreDefensor === $this->personaje->nombre;
$poderesDefensor = $esPersonajeDefensor ? $this->personaje->poderes : ($this->enemigo->poderes ?? []);

$dados = null; // dados del crítico contra la defensa (de este golpe)
if ($tipoAtaque === 'defensa') {
    $danioFisicoFinal    = 0;
    $danioElementalFinal = 0;
    $danioFinal          = 0;
    $textoTipoDanio      = "$nombreAtacante se defiende completamente este turno.";
    $gif                 = $gifDefensaAtacante;
    $tipoAtaque          = 'bloqueo';

} else {
    // Si el ataque no supera la defensa, se bloquea entero (contra enemigos y en PvP, igual).
    // El ataque (y la fuerza del crítico) suben un 3% por nivel, igual que la defensa: a mismo nivel y mismos puntos, parejo
    $bonusNivelAtacante = 1 + ($nivelAtacante * 0.03);
    $puedeDefender = ($statsAtacante['ataque'] ?? 0) * $bonusNivelAtacante <= $defensaDefensor;

    // El crítico se mide con la fuerza del que pega contra la defensa del que recibe: el que tenga claramente más
    // gana; si están parejos (dentro del 10%), se tiran dados (1 a 6, si empatan se vuelven a tirar)
    $dados = null;
    if ($tipoAtaque === 'critico') {
        $fuerzaCritico = ($statsAtacante['fuerza'] ?? 0) * $bonusNivelAtacante;
        if ($fuerzaCritico > $defensaDefensor * 1.1) {
            $puedeDefender = false;
        } elseif ($defensaDefensor > $fuerzaCritico * 1.1) {
            $puedeDefender = true;
        } else {
            do {
                $dadoAtacante = random_int(1, 6);
                $dadoDefensor = random_int(1, 6);
            } while ($dadoAtacante === $dadoDefensor);
            $puedeDefender = $dadoDefensor > $dadoAtacante;
            $dados = [
                'atacante' => $dadoAtacante, 'defensor' => $dadoDefensor,
                'nombre_atacante' => $nombreAtacante, 'nombre_defensor' => $nombreDefensor,
            ];
        }
    }

    if ($puedeDefender) {
        $bloqueoPorDefensa   = true; // bloqueó un golpe: puede contraatacar (ver más abajo)
        $danioFisicoFinal    = 0;
        $danioElementalFinal = 0;
        $danioFinal          = 0;
        $textoTipoDanio      = "$nombreDefensor se defiende y bloquea el ataque.";
        $gif                 = $gifDefensaDefensor;
        $tipoAtaque          = 'bloqueo';

    } else {
        // Aplico reducción según tipo
        if ($tipoAtacante === 'hibrido') {
            $danioFisicoFinal    = $this->aplicarReduccionDanioPorTipo($danios['fisico'], $poderesDefensor, 'fisico');
            $danioElementalFinal = $this->aplicarReduccionDanioPorTipo($danios['elemental'], $poderesDefensor, 'elemental');
        } elseif ($tipoAtacante === 'elemental') {
            $danioElementalFinal = $this->aplicarReduccionDanioPorTipo($danios['elemental'], $poderesDefensor, 'elemental');
            $danioFisicoFinal = 0;
        } else {
            $danioFisicoFinal = $this->aplicarReduccionDanioPorTipo($danios['fisico'], $poderesDefensor, 'fisico');
            $danioElementalFinal = 0;
        }

        $danioFinal     = $danioFisicoFinal + $danioElementalFinal;
        $textoTipoDanio = "Físico: " . round($danioFisicoFinal) . " / Elemental: " . round($danioElementalFinal);

        $gif = match ($tipoAtaque) {
            'critico' => $gifCriticoAtacante,
            'especial' => $gifEspecialAtacante,
            default => $gifAtaqueAtacante,
        };
    }
}

// Contraataque: la chance sale de la defensa del que recibe el golpe, si tiene más de 30 (ver CONTRA_POR_DEFENSA).
// Puede salir cuando el golpe le hace daño o cuando lo bloqueó con la defensa (no en la guardia de su propio turno)
$defensaContra = $statsDefensor['defensa'] ?? 0;
$chanceContraataque = $defensaContra > self::MINIMO_CONTRA_REBOTE ? min(self::CONTRA_TOPE, $defensaContra * self::CONTRA_POR_DEFENSA) : 0;
$contraataqueOcurre = mt_rand(1, 10000) <= $chanceContraataque * 100 && ($danioFinal > 0 || ! empty($bloqueoPorDefensa));
$bloqueoPorDefensa = false;

if ($contraataqueOcurre) {
    $daniosContraataque = $calcularContraataque($tipoDefensor, $statsDefensor, $nivelDefensor, $nombreDefensor, $poderesDefensor);

    $danioContraataqueFisico    = $daniosContraataque['fisico'];
    $danioContraataqueElemental = $daniosContraataque['elemental'];
    $danioExtraContra           = $daniosContraataque['extra'];

    $receptorPoderes = $atacante === 'personaje' ? $this->personaje->poderes : ($this->enemigo->poderes ?? []);

    // Aplico reducción para daño físico y elemental en contraataque
    $danioContraataqueFisico = $this->aplicarReduccionDanioPorTipo($danioContraataqueFisico, $receptorPoderes, 'fisico');
    $danioContraataqueElemental = $this->aplicarReduccionDanioPorTipo($danioContraataqueElemental, $receptorPoderes, 'elemental');

    // Verificar si el defensor tiene el poder daño directo
    if (! $this->tieneDanioDirecto($poderesDefensor)) {
        $danioExtraContra = 0; // Si no tiene, anulamos el daño extra
    }

    $danioContraataqueTotal = $danioContraataqueFisico + $danioContraataqueElemental + $danioExtraContra;

    // El ataque original no hace daño ni texto ni gif
    $danioFinal = 0;

    // Acumular daño total
    if ($atacante === 'personaje') {
        $this->totalDanioEnemigo += $danioContraataqueTotal;
        $danioExtraTotalEnemigo += $danioExtraContra;
    } else {
        $this->totalDanioPersonaje += $danioContraataqueTotal;
        $danioExtraTotalPersonaje += $danioExtraContra;
    }

    // Mostrar solo defensa y contraataque sin ataque bloqueado ni texto de ataque
    $res[] = [
        'ronda'            => $r,
        'atacante'         => $nombreDefensor === $this->personaje->nombre ? 'personaje' : 'enemigo',
        'danio'            => 0,
        'gif'              => $gifDefensaDefensor,
        'tipo_ataque'      => 'bloqueo',
        'texto_tipo_danio' => "$nombreDefensor bloquea el golpe.",
        'dados'            => $dados ?? null, // crítico parejo con la defensa: los dados que se tiraron
    ];

    $res[] = [
        'ronda'            => $r,
        'atacante'         => $nombreDefensor === $this->personaje->nombre ? 'personaje' : 'enemigo',
        'danio_fisico'     => $danioContraataqueFisico,
        'danio_elemental'  => $danioContraataqueElemental,
        'danio'            => $danioContraataqueTotal,
        'gif'              => $gifContraDefensor,
        'tipo_ataque'      => 'contraataque',
        'texto_tipo_danio' => "$nombreDefensor realiza un contraataque!",
    ];
} else {
    // Rebote: la chance sale de la resistencia del que recibe el golpe, si tiene más de 30 (ver REBOTE_POR_RESISTENCIA).
    // Resiste el golpe entero (no recibe daño) y le rebota al atacante una parte
    $resistenciaRebote = $statsDefensor['resistencia'] ?? 0;
    $chanceRebote = $resistenciaRebote > self::MINIMO_CONTRA_REBOTE ? min(self::REBOTE_TOPE, $resistenciaRebote * self::REBOTE_POR_RESISTENCIA) : 0;
    $danioRebote = 0;
    if ($danioFinal > 0 && mt_rand(1, 10000) <= $chanceRebote * 100) {
        $danioRebote = (int) round($danioFinal * self::REBOTE_PORCENTAJE);
        $danioFisicoFinal    = 0;
        $danioElementalFinal = 0;
        $danioFinal          = 0;
        $textoTipoDanio      = "$nombreDefensor resiste el golpe y no recibe daño.";
    }

    // Acumular daño total + daño extra de poderes (y el rebote, para el que lo resistió)
    if ($atacante === 'personaje') {
        $this->totalDanioPersonaje += round($danioFinal) + $danioExtraRondaPersonaje;
        $danioExtraTotalPersonaje += $danioExtraRondaPersonaje;
        $this->totalDanioEnemigo += $danioRebote;
    } else {
        $this->totalDanioEnemigo += round($danioFinal) + $danioExtraRondaEnemigo;
        $danioExtraTotalEnemigo += $danioExtraRondaEnemigo;
        $this->totalDanioPersonaje += $danioRebote;
    }

    $gif = match ($tipoAtaque) {
        'critico' => $gifCriticoAtacante,
        'especial' => $gifEspecialAtacante,
        'defensa' => $gifDefensaAtacante,
        default => $gifAtaqueAtacante,
    };

    $textoDanio = $textoTipoDanio ?? "Físico: " . round($danioFisicoFinal) . " / Elemental: " . round($danioElementalFinal);

    $res[] = [
        'ronda'            => $r,
        'atacante'         => $nombreAtacante === $this->personaje->nombre ? 'personaje' : 'enemigo',
        'danio_fisico'     => round($danioFisicoFinal),
        'danio_elemental'  => round($danioElementalFinal),
        'danio'            => round($danioFinal),
        'gif'              => $gif,
        'tipo_ataque'      => $tipoAtaque,
        'texto_tipo_danio' => $textoDanio,
        'dados'            => $dados ?? null, // crítico parejo con la defensa: los dados que se tiraron
    ];

    // El rebote va como una acción aparte del que resistió (en la pantalla: su gif base y el de derrota del otro)
    if ($danioRebote > 0) {
        $defensorEsPersonaje = $atacante !== 'personaje';
        $res[] = [
            'ronda'            => $r,
            'atacante'         => $defensorEsPersonaje ? 'personaje' : 'enemigo',
            'danio'            => $danioRebote,
            'gif'              => $defensorEsPersonaje ? ($this->personaje->postDeCombate()?->gif) : ($this->gifsEnemigo()?->gif),
            'tipo_ataque'      => 'rebote',
            'texto_tipo_danio' => "$nombreDefensor resiste el golpe y le rebota $danioRebote de daño a $nombreAtacante.",
        ];
    }
}
            } // fin del ataque de la ronda

        }

$statsPersonaje = $aplicarReduccionPorCongelado($statsPersonaje, $this->personaje);
$statsEnemigo   = $aplicarReduccionPorCongelado($statsEnemigo, $this->enemigo);

$statsPersonaje = $aplicarReduccionPorAturdido($statsPersonaje, $this->personaje);
$statsEnemigo   = $aplicarReduccionPorAturdido($statsEnemigo, $this->enemigo);


        // --- Lógica round extra ATAQUE DESESPERADO ---

// Función para calcular round extra
        $calcularRoundExtra = function (bool $atacanteEsPersonaje) use (
            $statsPersonaje, $statsEnemigo, $nivelPersonaje, $nivelEnemigo,
            $tipoPersonaje, $tipoEnemigo,
            $determinarTipoAtaque, $calcularDanioSegunTipo,
            $gifCriticoAtacante, $gifEspecialAtacante, $gifDefensaAtacante, $gifAtaqueAtacante,
            $gifCriticoDefensor, $gifEspecialDefensor, $gifDefensaDefensor, $gifAtaqueDefensor
        ) {
            $atacanteStr = $atacanteEsPersonaje ? 'personaje' : 'enemigo';

            // Datos atacante
            $statsAtacante  = $atacanteEsPersonaje ? $statsPersonaje : $statsEnemigo;
            $nivelAtacante  = $atacanteEsPersonaje ? $nivelPersonaje : $nivelEnemigo;
            $tipoAtacante   = $atacanteEsPersonaje ? $tipoPersonaje : $tipoEnemigo;
            $nombreAtacante = $atacanteEsPersonaje ? 'Personaje' : 'Enemigo';

            // Datos defensor (el opuesto)
            $statsDefensor = $atacanteEsPersonaje ? $statsEnemigo : $statsPersonaje;
            $nivelDefensor = $atacanteEsPersonaje ? $nivelEnemigo : $nivelPersonaje;

            // Determinar tipo ataque
            $tipoAtaqueExtra = $determinarTipoAtaque($statsAtacante);

            // Calcular daño según tipo
            $daniosExtra = $calcularDanioSegunTipo(
                $tipoAtacante,
                $statsAtacante,
                $nivelAtacante,
                $tipoAtaqueExtra,
                $atacanteStr
            );

            $danioExtraFisico    = $daniosExtra['fisico'];
            $danioExtraElemental = ($tipoAtacante === 'hibrido' || $tipoAtacante === 'elemental')
            ? $daniosExtra['elemental'] : 0;

            $danioTotalExtra = $danioExtraFisico + $danioExtraElemental;

            // Armar gif según tipo ataque
            $gif = match ($tipoAtaqueExtra) {
                'critico' => $atacanteEsPersonaje ? $gifCriticoAtacante : $gifCriticoDefensor,
                'especial' => $atacanteEsPersonaje ? $gifEspecialAtacante : $gifEspecialDefensor,
                'defensa' => $atacanteEsPersonaje ? $gifDefensaAtacante : $gifDefensaDefensor,
                default => $atacanteEsPersonaje ? $gifAtaqueAtacante : $gifAtaqueDefensor,
            };

            // Retornar arreglo para agregar a resultados
            return [
                'ronda'             => 6, // ronda extra
                'atacante'          => $atacanteStr,
                'danio_fisico'      => $danioExtraFisico,
                'danio_elemental'   => $danioExtraElemental,
                'danio'             => $danioTotalExtra,
                'gif'               => $gif,
                'tipo_ataque'       => 'extra',
                'texto_tipo_danio'  => '¡Ataque desesperado extra de ' . $nombreAtacante . '!',
                'danio_total_extra' => $danioTotalExtra,
            ];
        };

// 1. Definición de funciones para chequear poderes (ya las tienes)
$atacanteTieneAtaqueDesesperado = function ($poderes) {
    foreach ($poderes as $poder) {
        $mods = is_array($poder['modificadores']) ? $poder['modificadores'] : (json_decode($poder['modificadores'] ?? '[]', true) ?? []);
        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'ataque_extra' && ($mod['condicion'] ?? '') === 'perdiendo' && ($mod['chance'] ?? 0) === 100) {
                return true;
            }
        }
    }
    return false;
};

$tieneAtaqueTraicionero = function ($poderes) {
    foreach ($poderes as $poder) {
        $mods = is_array($poder['modificadores']) ? $poder['modificadores'] : json_decode($poder['modificadores'] ?? '[]', true);
        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'ataque_extra' &&
                ($mod['condicion'] ?? '') === 'empate' &&
                ($mod['chance'] ?? 0) === 100
            ) {
                return true;
            }
        }
    }
    return false;
};

// 2. Guardamos los daños actuales antes de aplicar ataques extra
$danioPersonajeOriginal = $this->totalDanioPersonaje;
$danioEnemigoOriginal = $this->totalDanioEnemigo;

// 3. Detectar empate **antes** de ataques desesperados
$esEmpateInicial = ($danioPersonajeOriginal == $danioEnemigoOriginal);

// 4. Aplicar ATAQUE TRAICIONERO si hay empate inicial
if ($esEmpateInicial) {
    if ($tieneAtaqueTraicionero($poderesPersonaje)) {
        $resultadoExtra = $calcularRoundExtra(true);
        $this->totalDanioPersonaje += $resultadoExtra['danio'];
        $res[] = $resultadoExtra;
    }
    if ($tieneAtaqueTraicionero($this->enemigo->poderes ?? [])) {
        $resultadoExtra = $calcularRoundExtra(false);
        $this->totalDanioEnemigo += $resultadoExtra['danio'];
        $res[] = $resultadoExtra;
    }
}

// 5. Recalcular si hay un nuevo ganador o empate después del traicionero
$esEmpateDespuesTraicionero = ($this->totalDanioPersonaje == $this->totalDanioEnemigo);

// 6. Aplicar ATAQUE DESESPERADO si alguien va perdiendo (después de traicionero)
$ataqueDesesperadoPersonaje = $atacanteTieneAtaqueDesesperado($poderesPersonaje);
$ataqueDesesperadoEnemigo   = $atacanteTieneAtaqueDesesperado($this->enemigo->poderes ?? []);

if ($this->totalDanioPersonaje < $this->totalDanioEnemigo && $ataqueDesesperadoPersonaje) {
    $resultadoExtra = $calcularRoundExtra(true);
    $this->totalDanioPersonaje += $resultadoExtra['danio'];
    $res[] = $resultadoExtra;
}

if ($this->totalDanioEnemigo < $this->totalDanioPersonaje && $ataqueDesesperadoEnemigo) {
    $resultadoExtra = $calcularRoundExtra(false);
    $this->totalDanioEnemigo += $resultadoExtra['danio'];
    $res[] = $resultadoExtra;
}

// Acumular daño extra global (si quieres usar para mostrar resumen u otra cosa)
        $this->danioExtraTotalPersonaje = $danioExtraTotalPersonaje;
        $this->danioExtraTotalEnemigo   = $danioExtraTotalEnemigo;

// Guardar daño original antes de absorción
        $this->danioEjercidoTotalPersonaje = $this->totalDanioPersonaje;
        $this->danioEjercidoTotalEnemigo   = $this->totalDanioEnemigo;

// Absorción / regeneración (tu foreach para personaje y enemigo)
        $this->absorcionTotalPersonaje = 0;
        $this->absorcionTotalEnemigo   = 0;
    foreach (['personaje', 'enemigo'] as $tipo) {
    $esPersonaje = $tipo === 'personaje';
    $poderes     = $esPersonaje ? $poderesPersonaje : ($this->enemigo->poderes ?? []);

    $danioRecibido = $esPersonaje ? $this->totalDanioEnemigo : $this->totalDanioPersonaje;
    $danioDirectoRecibido = $esPersonaje ? $this->danioExtraTotalEnemigo : $this->danioExtraTotalPersonaje;
    $danioRegenerable = max(0, $danioRecibido - $danioDirectoRecibido);

    $danioHecho = $esPersonaje ? $this->danioEjercidoTotalPersonaje : $this->danioEjercidoTotalEnemigo;
//ABSOVER SALUD Y ROBAR VIDA
if ($danioRegenerable > 0) {
    foreach ($poderes as $poder) {
        $modificadores = is_array($poder['modificadores'])
            ? $poder['modificadores']
            : json_decode($poder['modificadores'], true);

        foreach ($modificadores as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'porcentaje_regeneracion' &&
                ($mod['stat_base'] ?? '') === 'danio_ejercido'
            ) {
                $porcentaje = ($mod['valor'] ?? 0) / 100;

                $absorcionCalculada = round($danioHecho * $porcentaje);
                $absorcion = min($danioRegenerable, $absorcionCalculada);

                if ($esPersonaje) {
                    $this->absorcionTotalPersonaje += $absorcion;
                } else {
                    $this->absorcionTotalEnemigo += $absorcion;
                }

                // Reducir daño final (sin tocar daño directo)
                $danioFinal = $danioRegenerable - $absorcion + $danioDirectoRecibido;

                if ($esPersonaje) {
                    $this->totalDanioEnemigo = $danioFinal;
                } else {
                    $this->totalDanioPersonaje = $danioFinal;
                }
            }
        }
    }
}
    // --- Ahora regenerar por REGENERAR SUPERIOR (daño recibido) ---
   if ($danioRegenerable > 0) {
    foreach ($poderes as $poder) {
        $nombrePoder = strtoupper($poder['nombre'] ?? '');

        // Solo considerar poderes que sean de tipo 'regeneracion' sobre 'danio_recibido'
        if (in_array($nombrePoder, ['REGENERAR SUPERIOR', 'REGENERAR'])) {
            $modificadores = is_array($poder['modificadores'])
                ? $poder['modificadores']
                : json_decode($poder['modificadores'], true);

            foreach ($modificadores as $mod) {
                if (
                    ($mod['tipo'] ?? '') === 'regeneracion' &&
                    ($mod['base'] ?? '') === 'danio_recibido'
                ) {
                    $porcentaje = ($mod['porcentaje'] ?? 0) / 100;

                    $regeneracionCalculada = round($danioRegenerable * $porcentaje);
                    $regeneracion = min($danioRegenerable, $regeneracionCalculada);

                    if ($esPersonaje) {
                        $this->absorcionTotalPersonaje += $regeneracion;
                    } else {
                        $this->absorcionTotalEnemigo += $regeneracion;
                    }

                    // Reducir daño final con la regeneración acumulada
                    $danioFinal = $danioRegenerable - $this->absorcionTotalPersonaje + $danioDirectoRecibido;

                    if ($esPersonaje) {
                        $this->totalDanioEnemigo = $danioFinal;
                    } else {
                        $this->totalDanioPersonaje = $danioFinal;
                    }
                }
            }
        }
    }
}
}

foreach (['personaje', 'enemigo'] as $tipo) {
    $esPersonaje = $tipo === 'personaje';
    $poderes     = $esPersonaje ? $poderesPersonaje : ($this->enemigo->poderes ?? []);
    $stats       = $esPersonaje ? $statsPersonaje : $statsEnemigo;
    $nivel       = $esPersonaje ? $nivelPersonaje : $nivelEnemigo;
    $tipoDanio   = $esPersonaje ? $tipoPersonaje : $tipoEnemigo;

    // Calcular daño realizado sin contar el daño directo extra ya sumado
    $danioSinExtra = ($esPersonaje
        ? $this->totalDanioPersonaje - $this->danioExtraTotalPersonaje
        : $this->totalDanioEnemigo - $this->danioExtraTotalEnemigo
    );

    $danioRecibido = $esPersonaje ? $this->totalDanioEnemigo : $this->totalDanioPersonaje;

    $aplicarDanioDirecto = $danioSinExtra <= 0; // Solo aplicar si no hizo daño normal

    foreach ($poderes as $poder) {
        $modificadores = is_array($poder['modificadores'])
            ? $poder['modificadores']
            : json_decode($poder['modificadores'], true) ?? [];

        foreach ($modificadores as $mod) {

            // --- DAÑO DIRECTO ---
            if (($mod['tipo'] ?? '') === 'daño_directo' && $aplicarDanioDirecto) {
                $statBase   = $mod['stat_base'] ?? null;
                $porcentaje = ($mod['porcentaje'] ?? 0) / 100;

                if ($statBase && isset($stats[$statBase])) {
                    $multiplicadorNivel = 1 + ($nivel * self::DANIO_POR_NIVEL);
                    $danioBase = rand(
                        round($stats[$statBase] * $multiplicadorNivel * 0.8),
                        round($stats[$statBase] * $multiplicadorNivel * 1.2)
                    );
                    $danioExtra = round($danioBase * $porcentaje);

                    if ($esPersonaje) {
                        $this->totalDanioPersonaje += $danioExtra;
                        $this->danioExtraTotalPersonaje += $danioExtra;
                    } else {
                        $this->totalDanioEnemigo += $danioExtra;
                        $this->danioExtraTotalEnemigo += $danioExtra;
                    }

                    $this->resultadosRondas[] = [
                        'ronda'            => 'final',
                        'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
                        'danio_fisico'     => $tipoDanio === 'elemental' ? 0 : $danioExtra,
                        'danio_elemental'  => $tipoDanio === 'elemental' ? $danioExtra : 0,
                        'danio'            => $danioExtra,
                        'gif'              => $esPersonaje
                            ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                            : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
                        'tipo_ataque'      => 'directo_final',
                        'tipo_accion'      => 'especial',
                        'texto_tipo_danio' => ($esPersonaje ? $this->personaje->nombre : $this->enemigo->nombre)
                            . ' activa daño directo al final del combate.',
                    ];
                }
            }

            // --- ESTADO: CONGELADO ---
            if (($mod['tipo'] ?? '') === 'estado' && ($mod['estado'] ?? '') === 'Congelado') {
                $chance = $mod['chance'] ?? 0;

                if (rand(1, 100) <= $chance) {
                    $objetivo = $esPersonaje ? $this->enemigo : $this->personaje;

                    // Solo los personajes guardan estados (el enemigo PvE es un set y no tiene)
                    if ($objetivo instanceof Personaje) $objetivo->estadosTemporales()->updateOrCreate(
                        ['estado' => 'Congelado'],
                        [
                            'expira_en'       => now()->addMinutes(30),
                            'porcentaje'      => 15,
                            'stats_afectados' => ['energia', 'velocidad'],
                        ]
                    );

                    if (!$esPersonaje) {
                        $this->fueCongeladoEnEstaRonda = true;
                    }

                    $this->resultadosRondas[] = [
                        'ronda'            => 'final',
                        'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
                        'danio'            => 0,
                        'gif'              => $esPersonaje
                            ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                            : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
                        'tipo_ataque'      => 'estado',
                        'texto_tipo_danio' => '❄️ El oponente quedó congelado al final del combate.',
                    ];
                }
            }

           // --- ESTADO: ATURDIR ---
if (($mod['tipo'] ?? '') === 'estado' && ($mod['estado'] ?? '') === 'Aturdido') {
    $chance = $mod['chance'] ?? 0;

    // Verifico que el que tiene el poder haya hecho daño en el combate
    $hizoDanio = ($esPersonaje && $this->totalDanioPersonaje > 0) || (!$esPersonaje && $this->totalDanioEnemigo > 0);

    if ($hizoDanio && rand(1, 100) <= $chance) {
        // Aturdir al oponente (el que recibió daño)
        $objetivo = $esPersonaje ? $this->enemigo : $this->personaje;

        // Solo los personajes guardan estados (el enemigo PvE es un set y no tiene)
                    if ($objetivo instanceof Personaje) $objetivo->estadosTemporales()->updateOrCreate(
            ['estado' => 'Aturdido'],
            [
                'expira_en'       => now()->addMinutes(30),
                'porcentaje'      => 15,
                'stats_afectados' => ['fuerza', 'velocidad'],
            ]
        );

        if (!$esPersonaje) {
            $this->fueAturdidoEnEstaRonda = true;
        }

        $this->resultadosRondas[] = [
            'ronda'            => 'final',
            'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
            'danio'            => 0,
            'gif'              => $esPersonaje
                ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
            'tipo_ataque'      => 'estado',
            'texto_tipo_danio' => '💫 El oponente quedó aturdido al final del combate.',
        ];
    }
}

// --- ESTADO: ENVENENADO ---
if (($mod['tipo'] ?? '') === 'estado' && ($mod['estado'] ?? '') === 'Envenenado') {
    $chance = $mod['chance'] ?? 0;

    // Verifico que el que tiene el poder haya hecho daño en el combate
    $hizoDanio = ($esPersonaje && $this->totalDanioPersonaje > 0) || (!$esPersonaje && $this->totalDanioEnemigo > 0);

    if ($hizoDanio && rand(1, 100) <= $chance) {
        // Aplicar estado Envenenado al oponente (el que recibió daño)
        $objetivo = $esPersonaje ? $this->enemigo : $this->personaje;

        // Solo los personajes guardan estados (el enemigo PvE es un set y no tiene)
                    if ($objetivo instanceof Personaje) $objetivo->estadosTemporales()->updateOrCreate(
            ['estado' => 'Envenenado'],
            [
                'expira_en'       => now()->addMinutes(30),
                'porcentaje'      => 15, // Podés ajustar %
                'stats_afectados' => ['fuerza', 'defensa'],
            ]
        );

        if (!$esPersonaje) {
            // Guardar info para UI o lógica extra si quieres
            $this->fueEnvenenadoEnEstaRonda = true;
        }

        $this->resultadosRondas[] = [
            'ronda'            => 'final',
            'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
            'danio'            => 0,
            'gif'              => $esPersonaje
                ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
            'tipo_ataque'      => 'estado',
            'texto_tipo_danio' => '☠️ El oponente quedó envenenado al final del combate.',
        ];
    }
}

        // --- DEVOLUCION DE DAÑO (ESPINAS) ---
if (($mod['tipo'] ?? '') === 'daño_directo') {
    $porcentaje = ($mod['porcentaje'] ?? 0) / 100;

    $danioRecibido = $esPersonaje ? $this->totalDanioEnemigo : $this->totalDanioPersonaje;

    if ($danioRecibido > 0) {
        $danioDevuelto = round($danioRecibido * $porcentaje);

            

        if ($esPersonaje) {
            $this->totalDanioPersonaje += $danioDevuelto;
            $this->danioExtraTotalPersonaje += $danioDevuelto;
        } else {
            $this->totalDanioEnemigo += $danioDevuelto;
            $this->danioExtraTotalEnemigo += $danioDevuelto;
        }

        $porcentajeText = $porcentaje * 100;

        $this->resultadosRondas[] = [
            'ronda'            => 'final',
            'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
            'danio_fisico'     => $tipoDanio === 'elemental' ? 0 : $danioDevuelto,
            'danio_elemental'  => $tipoDanio === 'elemental' ? $danioDevuelto : 0,
            'danio'            => $danioDevuelto,
            'gif'              => $esPersonaje
                ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
            'tipo_ataque'      => 'daño_directo',
            'tipo_accion'      => 'especial',
            'texto_tipo_danio' => ($esPersonaje ? $this->personaje->nombre : $this->enemigo->nombre)
                . " devuelve daño por Espinas ({$porcentajeText}%).",
        ];
    }
}

// --- PODER: HEMORRAGIA ---
if (($mod['tipo'] ?? '') === 'daño_directo' && ($mod['stat_base'] ?? '') === 'ataque') {
    
    $porcentaje = ($mod['porcentaje'] ?? 0) / 100;

    // El ataque base depende de quién es el atacante
    $ataqueBase = $esPersonaje
        ? $this->personaje->ataque_total
        : $this->enemigo->ataque_total;

    $danioDirecto = round($ataqueBase * $porcentaje);


    if ($danioDirecto > 0) {
        if ($esPersonaje) {
            $this->totalDanioPersonaje += $danioDirecto;
            $this->danioExtraTotalPersonaje += $danioDirecto;
        } else {
            $this->totalDanioEnemigo += $danioDirecto;
            $this->danioExtraTotalEnemigo += $danioDirecto;
        }

        $this->resultadosRondas[] = [
            'ronda'            => 'final',
            'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
            'danio_fisico'     => $tipoDanio === 'elemental' ? 0 : $danioDirecto,
            'danio_elemental'  => $tipoDanio === 'elemental' ? $danioDirecto : 0,
            'danio'            => $danioDirecto,
            'gif'              => $esPersonaje
                ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
            'tipo_ataque'      => 'daño_directo',
            'tipo_accion'      => 'especial',
            'texto_tipo_danio' => ($esPersonaje ? $this->personaje->nombre : $this->enemigo->nombre)
                . " provoca Hemorragia e inflige {$danioDirecto} de daño directo.",
        ];
    }
}

// --- PODER: SANGRADO ---
if (($mod['tipo'] ?? '') === 'daño_directo' && ($mod['stat_base'] ?? '') === 'ataque') {
    $porcentaje = ($mod['porcentaje'] ?? 0) / 100;

    // Tirada base de ataque según quién es
    $ataqueBase = $esPersonaje ? $this->personaje->ataque_total : $this->enemigo->ataque_total;

    // Calcular daño directo
    $danioDirecto = round($ataqueBase * $porcentaje);

    if ($esPersonaje) {
        $this->totalDanioPersonaje += $danioDirecto;
        $this->danioExtraTotalPersonaje += $danioDirecto;
    } else {
        $this->totalDanioEnemigo += $danioDirecto;
        $this->danioExtraTotalEnemigo += $danioDirecto;
    }

    $this->resultadosRondas[] = [
        'ronda'            => 'final',
        'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
        'danio'            => $danioDirecto,
        'gif'              => $esPersonaje
            ? ($this->personaje->post->gif_especial ?? 'especial.gif')
            : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
        'tipo_ataque'      => 'daño_directo',
        'tipo_accion'      => 'especial',
        'texto_tipo_danio' => ($esPersonaje ? $this->personaje->nombre : $this->enemigo->nombre)
            . " inflige {$danioDirecto} de daño directo con SANGRADO.",
    ];
}


// --- ESTADO: DESANGRADO ---
if (($mod['tipo'] ?? '') === 'estado' && ($mod['estado'] ?? '') === 'Desangrado') {
    $chance = $mod['chance'] ?? 0;

    // Verificar que hizo daño en el combate
    $hizoDanio = ($esPersonaje && $this->totalDanioPersonaje > 0) || (!$esPersonaje && $this->totalDanioEnemigo > 0);

    if ($hizoDanio && rand(1, 100) <= $chance) {
        // Aplicar estado Desangrado al oponente
        $objetivo = $esPersonaje ? $this->enemigo : $this->personaje;

        // Solo los personajes guardan estados (el enemigo PvE es un set y no tiene)
                    if ($objetivo instanceof Personaje) $objetivo->estadosTemporales()->updateOrCreate(
            ['estado' => 'Desangrado'],
            [
                'expira_en'       => now()->addMinutes(30),
                'porcentaje'      => 15, // 10% menos de FUE y ENE
                'stats_afectados' => ['fuerza', 'energia'],
            ]
        );

        if (!$esPersonaje) {
            $this->fueDesangradoEnEstaRonda = true;
        }

        $this->resultadosRondas[] = [
            'ronda'            => 'final',
            'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
            'danio'            => 0,
            'gif'              => $esPersonaje
                ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
            'tipo_ataque'      => 'estado',
            'texto_tipo_danio' => '🩸 El oponente queda desangrado: pierde 10% de FUE y ENE por 30 minutos.',
        ];
    }
}

// --- ESTADO: PARALIZADO ---
if (($mod['tipo'] ?? '') === 'estado' && ($mod['estado'] ?? '') === 'Paralizado') {
    $chance = $mod['chance'] ?? 0;

    // Verifico que el que tiene el poder haya hecho daño en el combate
    $hizoDanio = ($esPersonaje && $this->totalDanioPersonaje > 0) || (!$esPersonaje && $this->totalDanioEnemigo > 0);

    if ($hizoDanio && rand(1, 100) <= $chance) {
        // Aplicar estado Paralizado al oponente
        $objetivo = $esPersonaje ? $this->enemigo : $this->personaje;

        // Solo los personajes guardan estados (el enemigo PvE es un set y no tiene)
                    if ($objetivo instanceof Personaje) $objetivo->estadosTemporales()->updateOrCreate(
            ['estado' => 'Paralizado'],
            [
                'expira_en'       => now()->addMinutes(30),
                'porcentaje'      => 15, // 15% menos de velocidad y ataque
                'stats_afectados' => ['velocidad', 'ataque'],
            ]
        );

        if (!$esPersonaje) {
            $this->fueParalizadoEnEstaRonda = true;
        }

        $this->resultadosRondas[] = [
            'ronda'            => 'final',
            'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
            'danio'            => 0,
            'gif'              => $esPersonaje
                ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
            'tipo_ataque'      => 'estado',
            'texto_tipo_danio' => '⚡ El oponente queda paralizado: pierde 15% de Velocidad y Ataque por 30 minutos.',
        ];
    }
}

// --- PODER: QUEMAR ---
if (($mod['tipo'] ?? '') === 'daño_directo' && ($mod['stat_base'] ?? '') === 'ataque') {
    $porcentaje = ($mod['porcentaje'] ?? 0) / 100;

    // Tirada base de ataque según quién es
    $ataqueBase = $esPersonaje ? $this->personaje->ataque_total : $this->enemigo->ataque_total;

    // Calcular daño directo
    $danioDirecto = round($ataqueBase * $porcentaje);

        

    if ($esPersonaje) {
        $this->totalDanioPersonaje += $danioDirecto;
        $this->danioExtraTotalPersonaje += $danioDirecto;
    } else {
        $this->totalDanioEnemigo += $danioDirecto;
        $this->danioExtraTotalEnemigo += $danioDirecto;
    }

    $this->resultadosRondas[] = [
        'ronda'            => 'final',
        'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
        'danio'            => $danioDirecto,
        'gif'              => $esPersonaje
            ? ($this->personaje->post->gif_especial ?? 'especial.gif')
            : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
        'tipo_ataque'      => 'daño_directo',
        'tipo_accion'      => 'especial',
        'texto_tipo_danio' => ($esPersonaje ? $this->personaje->nombre : $this->enemigo->nombre)
            . " inflige {$danioDirecto} de daño directo con QUEMAR.",
    ];
}

// --- ESTADO: QUEMADO ---
if (($mod['tipo'] ?? '') === 'estado' && ($mod['estado'] ?? '') === 'Quemado') {
    $chance = $mod['chance'] ?? 0;

    // Debe haber hecho daño para aplicar el estado
    $hizoDanio = ($esPersonaje && $this->totalDanioPersonaje > 0) || (!$esPersonaje && $this->totalDanioEnemigo > 0);

    if ($hizoDanio && rand(1, 100) <= $chance) {
        $objetivo = $esPersonaje ? $this->enemigo : $this->personaje;

        // Solo los personajes guardan estados (el enemigo PvE es un set y no tiene)
                    if ($objetivo instanceof Personaje) $objetivo->estadosTemporales()->updateOrCreate(
            ['estado' => 'Quemado'],
            [
                'expira_en'       => now()->addMinutes(30),
                'porcentaje'      => 15, // 15% menos resistencia y defensa
                'stats_afectados' => ['resistencia', 'defensa'],
            ]
        );

        if (!$esPersonaje) {
            $this->fueQuemadoEnEstaRonda = true;
        }

        $this->resultadosRondas[] = [
            'ronda'            => 'final',
            'atacante'         => $esPersonaje ? 'personaje' : 'enemigo',
            'danio'            => 0,
            'gif'              => $esPersonaje
                ? ($this->personaje->post->gif_especial ?? 'especial.gif')
                : ($this->gifsEnemigo()->gif_especial ?? 'especial.gif'),
            'tipo_ataque'      => 'estado',
            'texto_tipo_danio' => '🔥 El oponente queda quemado: pierde 10% de Resistencia y Defensa por 30 minutos.',
        ];
    }
}




        }


    }
}

// Se vuelven a poner los poderes de cada uno (la Anulación de poder los pudo haber recortado para las rondas):
// los del set con el que pelea, no los del set base
unset($this->personaje->poderes);
$this->personaje->setRelation('poderes', $this->personaje->postDeCombate()?->poderes ?? collect());
if ($this->enemigo instanceof Personaje) {
    unset($this->enemigo->poderes);
    $this->enemigo->setRelation('poderes', $this->gifsEnemigo()?->poderes ?? collect());
} else {
    $this->enemigo->load('poderes');
}


// Bloque que procesa reducción de daño:
foreach (['personaje', 'enemigo'] as $tipoAbsorvedor) {
    $esPersonajeAbsorvedor = $tipoAbsorvedor === 'personaje';
    $actor = $esPersonajeAbsorvedor ? $this->personaje : $this->enemigo;

    // Garantizamos que poderes sea colección para evitar errores
   $poderesActor = $actor instanceof Personaje ? ($actor->postDeCombate()?->poderes ?? collect()) : collect();

    foreach ($poderesActor as $poder) {
        $modsRaw = $poder['modificadores'] ?? '[]';
        $mods = is_array($modsRaw) ? $modsRaw : (json_decode($modsRaw, true) ?? []);

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'reduccion_danio' && ($mod['tipo_danio'] ?? '') === 'daño_directo') {
                $porcentaje = $mod['porcentaje'] ?? 0;
                if ($porcentaje <= 0) continue;

                // Variables para daño extra del OPONENTE
                if ($esPersonajeAbsorvedor) {
                    $danioExtraOponente = $this->danioExtraTotalEnemigo;
                } else {
                    $danioExtraOponente = $this->danioExtraTotalPersonaje;
                }

                if ($danioExtraOponente <= 0) continue;

                $reducir = (int) round($danioExtraOponente * ($porcentaje / 100));
                if ($reducir <= 0) continue;

                if ($esPersonajeAbsorvedor) {
                    $this->danioExtraTotalEnemigo = max(0, $this->danioExtraTotalEnemigo - $reducir);
                    $this->totalDanioEnemigo = max(0, $this->totalDanioEnemigo - $reducir);
                } else {
                    $this->danioExtraTotalPersonaje = max(0, $this->danioExtraTotalPersonaje - $reducir);
                    $this->totalDanioPersonaje = max(0, $this->totalDanioPersonaje - $reducir);
                }
            }
        }
    }
}
       
        $this->combateActivo    = false;
        $this->resultadosRondas = $res;
        $this->determinarGanador();
        $this->mostrarRanking = false;

        // Bloque que procesa reducción de tiempo de recuperación
foreach (['personaje', 'enemigo'] as $tipoReducidor) {
    $esPersonaje = $tipoReducidor === 'personaje';
    $actor = $esPersonaje ? $this->personaje : $this->enemigo;

    $poderesActor = $actor->poderes ?? collect();

    foreach ($poderesActor as $poder) {
        $modsRaw = $poder['modificadores'] ?? '[]';
        $mods = is_array($modsRaw) ? $modsRaw : (json_decode($modsRaw, true) ?? []);

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'reduccion_tiempo_recuperacion') {
                $porcentaje = $mod['porcentaje'] ?? 0;
                if ($porcentaje <= 0) continue;

                // Variable de tiempo de recuperación (ajusta según tu lógica)
                if ($esPersonaje) {
                    $this->tiempoRecuperacionPersonaje = max(0, $this->tiempoRecuperacionPersonaje - ($this->tiempoRecuperacionPersonaje * $porcentaje / 100));
                } else {
                    $this->tiempoRecuperacionEnemigo = max(0, $this->tiempoRecuperacionEnemigo - ($this->tiempoRecuperacionEnemigo * $porcentaje / 100));
                }

                // Registro del evento especial
                $res[] = [
                    'ronda' => 'final',
                    'atacante' => $esPersonaje ? 'personaje' : 'enemigo',
                    'danio' => 0,
                    'gif' => $poder->imagen ?? 'especial.gif',
                    'tipo_ataque' => 'reduccion_tiempo_recuperacion',
                    'tipo_accion' => 'especial',
                    'texto_tipo_danio' => ($esPersonaje ? $this->personaje->nombre : $this->enemigo->nombre)
                        . " reduce su tiempo de recuperación un {$porcentaje}%.",
                ];
            }
        }
    }
}
    }

// Función para determinar el tipo combinado del personaje
    private function determinarTipoPersonaje($equipo, $entrenamiento, $accesorio)
    {
        $tipos = [];

        if ($equipo?->post?->tipo) {
            $tipos[] = $equipo->post->tipo;
        }
        if ($entrenamiento?->post?->tipo) {
            $tipos[] = $entrenamiento->post->tipo;
        }
        if ($accesorio?->post?->tipo) {
            $tipos[] = $accesorio->post->tipo;
        }

        $tipos = array_unique($tipos);

        if (in_array('hibrido', $tipos)) {
            return 'hibrido';
        }

        if (in_array('fisico', $tipos) && in_array('elemental', $tipos)) {
            return 'hibrido';
        }

        if (count($tipos) === 1) {
            return $tipos[0];
        }

        // Si no se determina un tipo claro, por defecto físico
        return 'fisico';
    }

    private function tipoDeAtaque()
    {
        $tipos = ['normal', 'critico', 'especial'];
        return $tipos[array_rand($tipos)];
    }

    private function multiplicadorPorTipoAtaque($tipo)
    {
        return match ($tipo) {
            'critico' => 1.5,
            'especial' => 1.2,
            default => 1.0,
        };
    }

    private function getDefensaEnemigo()
    {
        return property_exists($this->enemigo, 'defensa') ? $this->enemigo->defensa : 0;
    }

    private function getDefensaPersonaje()
    {
        return $this->personaje->post->defensa ?? 0;
    }

    private function decodificarCampoArray($valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }

        return json_decode($valor ?? '{}', true) ?: [];
    }

    public function obtenerPorcentajeBuff(string $tipoBuff, int $personajeId): int
    {
        $buffGlobal = Buff::where('tipo', $tipoBuff)
            ->whereNull('personaje_id')
            ->where('fin', '>=', now())
            ->sum('porcentaje');

        $buffPersonal = Buff::where('tipo', $tipoBuff)
            ->where('personaje_id', $personajeId)
            ->where('fin', '>=', now())
            ->sum('porcentaje');

        $totalBuff = $buffGlobal + $buffPersonal;

        // Limitar a 100%
        return min($totalBuff, 100);
    }

    private function asignarRecompensas(int $minutos, ?array $statsOriginal = null): array
    {
        $minutosOriginales = $minutos;
// Reducir minutos reales si tiene exploración rápida activa
        if (ExploracionRapida::activaPara($this->personaje->id)) {
            if ($minutos == 5) {
                $minutos = 2;
            } elseif ($minutos == 10) {
                $minutos = 5;
            } elseif ($minutos == 15) {
                $minutos = 10;
            }
        }

        // Oro por zona: El Comienzo (nivel 0) da 50 y cada zona suma 50 más (La Revuelta 100, El Barco 150, ... Dojo 1050)
        $oroBase        = self::ORO_ZONA_INICIAL + 10 * (int) ($this->ciudadActual->nivel ?? 0);
        $oro            = $oroBase;
        $diamantesExtra = 0;
        $nivelPersonaje = $this->personaje->nivel ?? 1;
        $nivelCiudad    = $this->ciudadActual->nivel ?? 1;

        $expNivelActual    = 10000 * pow($nivelPersonaje - 1, 2);
        $expSiguienteNivel = 10000 * pow($nivelPersonaje, 2);
        $expNecesaria      = $expSiguienteNivel - $expNivelActual;

        // Exp por victoria = % de la exp que pide el nivel actual (ver porcentajeExpPorNivel).
        // Contra el enemigo especial de bienvenida se gana un nivel entero por pelea.
        $esEnemigoEspecial = ($this->enemigo->es_enemigo ?? null) == self::ENEMIGO_ESPECIAL;
        $porcentajeExp = match (true) {
            $esEnemigoEspecial => self::EXP_ENEMIGO_ESPECIAL,
            // PvP: 4% si el rival está a 5 niveles o menos, 1% si la diferencia es mayor
            $this->esPvp => self::fraccionExpPvp($nivelPersonaje, (int) ($this->enemigo->nivel ?? $nivelPersonaje)),
            // Misiones: el doble que una pelea común
            $this->misionActiva() !== null => self::porcentajeExpPorNivel($nivelPersonaje) * self::MISION_MULTIPLICADOR_EXP,
            // Torre: el doble que una pelea común
            $this->torreActiva() !== null => self::porcentajeExpPorNivel($nivelPersonaje) * \App\Support\RecompensasTorre::MULTIPLICADOR_EXP,
            // Mazmorra: la de una pelea común (sin el descuento por zona)
            $this->mazmorraActiva() !== null => self::porcentajeExpPorNivel($nivelPersonaje),
            // Exploración y caza: menos exp si la zona es de menor nivel que el personaje
            default => self::porcentajeExpPorNivel($nivelPersonaje) * self::factorExpZona($nivelPersonaje, (int) $nivelCiudad),
        };

// 🔥 Esta es la experiencia real a sumar
        $exp = round($expNecesaria * $porcentajeExp);

        $bonus = 0;

        // ✅ Usar el nuevo método
        $buffExp  = $this->obtenerPorcentajeBuff('xp', $this->personaje->id);
        $buffOro  = $this->obtenerPorcentajeBuff('oro', $this->personaje->id);
        $buffDrop = $this->obtenerPorcentajeBuff('drop', $this->personaje->id);

// 🔄 Aplicar buffExp
        if ($buffExp > 0) {
            $bonus = intval($exp * ($buffExp / 100));
            $exp += $bonus;
        }

// 🔄 Aplicar buffOro
        if ($buffOro > 0) {
            $oro += intval($oroBase * ($buffOro / 100));
        }

        // Obtener poderes del personaje (de donde los tengas guardados)
$poderesPersonaje = collect($this->personaje->postDeCombate()?->poderes ?? [])
    ->map(fn($p) => strtoupper($p['nombre'] ?? ''));

// Si el personaje tiene el poder SUERTUDO
if ($poderesPersonaje->contains('SUERTUDO')) {
    // Multiplicar oro por 2 (100%)
    $oro = intval($oro * 2);

    // Subir el porcentaje de drop a 10%
    $buffDrop = max($buffDrop, 10);
}

        //  Buff DROP
        $buffDrop = Buff::where('tipo', 'drop')
            ->where('inicio', '<=', now())
            ->where('fin', '>=', now())
            ->where(function ($query) {
                $query->where('personaje_id', $this->personaje->id)
                    ->orWhere(function ($q) {
                        $q->whereNull('personaje_id')->where('global', 1);
                    });
            })->sum('porcentaje');

        // Más oro contra el enemigo especial de bienvenida (la exp ya es un nivel entero, ver arriba)
        if ($esEnemigoEspecial) {
            $oro *= 6;
        }

        if ($statsOriginal && is_array($statsOriginal)) {
            switch ($statsOriginal['afecta'] ?? '') {
                case 'oro':
                    // Poción de Oro: duplica el oro de esta victoria
                    $oro *= 2;
                    break;
                case 'diamante':
                    $diamantesExtra += 100;
                    break;
            }
        }

        if ($this->personaje->nivel < 100) {
            $this->personaje->experiencia += $exp;

            $nivelesAntes = $this->personaje->nivel;

            while (
                $this->personaje->nivel < 100 &&
                $this->personaje->experiencia >= 10000 * pow($this->personaje->nivel, 2)
            ) {
                $this->personaje->nivel++;
            }

            $nivelesGanados = $this->personaje->nivel - $nivelesAntes;

            // Asignar 5 puntos de stats por nivel ganado
            if ($nivelesGanados > 0) {
                $this->personaje->puntos_stats += $nivelesGanados * 5;
            }

            // Limitar si se pasó
            if ($this->personaje->nivel >= 100) {
                $this->personaje->nivel       = 100;
                $this->personaje->experiencia = 10000 * pow(100, 2);
            }
        }

        // 🎯 Caza: bonus de oro y diamantes según la rareza de la presa
        $caza = $this->cazaActiva();
        if ($caza) {
            $infoRareza      = $caza->rarezaInfo();
            $oro             = intval($oro * $infoRareza['oro']);
            $diamantesExtra += $infoRareza['diamantes'];
        }

        // 📜 Misión: premio fijo de oro y diamantes (y sin drops, ver más abajo)
        $mision = $this->misionActiva();
        if ($mision) {
            $oro             = $mision->recompensa_oro;
            $diamantesExtra += $mision->recompensa_diamantes;
        }

        // 🗼 Torre: oro propio según el nivel del piso (100 en el primero, sube con cada piso)
        if ($pisoOroTorre = $this->torreActiva()) {
            $oro = \App\Support\RecompensasTorre::oro((int) $pisoOroTorre->nivel);
        }

        // 🕳️ Mazmorra: oro según el nivel del rival y la dificultad; el jefe además da esmeraldas
        if ($mazmorraOro = $this->mazmorraActiva()) {
            $oro = $mazmorraOro->oroPorVictoria((int) ($this->enemigo->nivel ?? 1));
            if (is_array($statsOriginal) && ($statsOriginal['afecta'] ?? '') === 'oro') {
                $oro *= 2; // la Poción de Oro también vale acá
            }
            $diamantesExtra += $mazmorraOro->esmeraldasPorVictoria();
        }

        $this->personaje->oro += $oro;
        $this->personaje->diamante += $diamantesExtra;
        $this->personaje->save();

        $this->expGanada      = $exp;
        $this->oroGanado      = $oro;
        $this->diamantesExtra = $diamantesExtra;

        $drop = null;

        // Solo generar drops si el enemigo NO es el post 68
        // (las misiones y el PvP no dan drops: el premio de las misiones es oro y diamantes)
        if (! $esEnemigoEspecial && ! $mision && ! $this->esPvp && ! $this->torreActiva() && ! $this->mazmorraActiva()) { // la Torre tampoco da drops (sus recompensas se definen aparte)

            // Definición de pociones normales
            $pocionesNormales = [
                ['nombre' => 'Poción de Defensa', 'afecta' => 'defensa', 'multiplicador' => 1.5, 'usos_restantes' => 1, 'usos_totales' => 1, 'imagen' => 'pocion-defensa.png', 'descripcion' => 'Multiplica la defensa por 1.5'],
                ['nombre' => 'Poción de Ataque', 'afecta' => 'ataque', 'multiplicador' => 1.5, 'usos_restantes' => 1, 'usos_totales' => 1, 'imagen' => 'pocion-ataque.png', 'descripcion' => 'Multiplica el ataque por 1.5'],
                ['nombre' => 'Poción de Energía', 'afecta' => 'energia', 'multiplicador' => 1.5, 'usos_restantes' => 1, 'usos_totales' => 1, 'imagen' => 'pocion-energia.png', 'descripcion' => 'Multiplica la Energia por 1.5'],
                ['nombre' => 'Poción de Velocidad', 'afecta' => 'velocidad', 'multiplicador' => 1.5, 'usos_restantes' => 1, 'usos_totales' => 1, 'imagen' => 'pocion-velocidad.png', 'descripcion' => 'Multiplica la velocidad por 1.5'],
                ['nombre' => 'Poción de Fuerza', 'afecta' => 'fuerza', 'multiplicador' => 1.5, 'usos_restantes' => 1, 'usos_totales' => 1, 'imagen' => 'pocion-fuerza.png', 'descripcion' => 'Multiplica la fuerza por 1.5'],
                ['nombre' => 'Poción de Resistencia', 'afecta' => 'resistencia', 'multiplicador' => 1.5, 'usos_restantes' => 1, 'usos_totales' => 1, 'imagen' => 'pocion-resistencia.png', 'descripcion' => 'Multiplica la Resistencia por 1.5'],
                ['nombre' => 'Poción de Recuperacion', 'afecta' => 'recuperacion', 'multiplicador' => 15, 'usos_restantes' => 1, 'usos_totales' => 1, 'imagen' => 'pocion-recuperacion.png', 'descripcion' => 'Recuperación de 15 segundos'],
            ];

            $pocionDiamante = [
                'nombre'         => 'Poción de Esmeraldas',
                'afecta'         => 'diamante',
                'multiplicador'  => 1,
                'usos_restantes' => 1,
                'usos_totales'   => 1,
                'imagen'         => 'pocion-diamantes.png',
                'descripcion'    => 'Otorga 100 esmeraldas',
            ];

            $pocionOro = [
                'nombre'         => 'Poción de Oro',
                'afecta'         => 'oro',
                'multiplicador'  => 2,
                'usos_restantes' => 1,
                'usos_totales'   => 1,
                'imagen'         => 'pocion-oro.png',
                'descripcion'    => 'Duplica el oro de la próxima victoria',
            ];

                                     // 🎯 Probabilidades
            $diamanteChance     = 1; // 1%
            $oroChance          = 1; // 1%
            $pocionChance       = 1; // 1%
            $recuperacionChance = 1;
            $parteChance        = $buffDrop > 0 ? $buffDrop : 10;

            // 🎯 Con la poción de búsqueda equipada la parte del enemigo sale siempre (no compite con las pociones)
            // (en una caza la parte elegida también es segura)
            $dropParteSeguro = $caza || ($statsOriginal && is_array($statsOriginal) && ($statsOriginal['afecta'] ?? '') === 'drop_partes');
            if ($dropParteSeguro) {
                $recuperacionChance = $diamanteChance = $oroChance = $pocionChance = 0;
            }

            // 🔁 Drop de pociones especiales, solo si aún no hay drop asignado
            if ($drop === null && rand(1, 100) <= $recuperacionChance) {
                $drop = [
                    'tipo'        => 'pocion',
                    'nombre'      => 'Poción de Recuperacion',
                    'stats'       => [
                        'usos_restantes' => 1,
                        'usos_totales'   => 1,
                        'multiplicador'  => 15,
                        'afecta'         => 'recuperacion',
                    ],
                    'imagen'      => 'pocion-recuperacion.png',
                    'descripcion' => 'Recuperación de 15 segundos',
                    'nivel'       => 1,
                    'requisitos'  => [],
                ];
            }

            if ($drop === null && rand(1, 50) <= $diamanteChance) {
                $drop = [
                    'tipo'        => 'pocion',
                    'nombre'      => 'Poción de Esmeraldas',
                    'stats'       => [
                        'usos_restantes' => 1,
                        'usos_totales'   => 1,
                        'multiplicador'  => 1,
                        'afecta'         => 'diamante',
                    ],
                    'imagen'      => 'pocion-diamantes.png',
                    'descripcion' => 'Otorga 100 esmeraldas',
                    'nivel'       => 1,
                    'requisitos'  => [],
                ];
            }

            if ($drop === null && rand(1, 100) <= $oroChance) {
                $drop = [
                    'tipo'        => 'pocion',
                    'nombre'      => 'Poción de Oro',
                    'stats'       => [
                        'usos_restantes' => 1,
                        'usos_totales'   => 1,
                        'multiplicador'  => 2,
                        'afecta'         => 'oro',
                    ],
                    'imagen'      => 'pocion-oro.png',
                    'descripcion' => 'Duplica el oro de la próxima victoria',
                    'nivel'       => 1,
                    'requisitos'  => [],
                ];
            }

            if ($drop === null && rand(1, 100) <= $pocionChance) {
                $e    = $pocionesNormales[array_rand($pocionesNormales)];
                $drop = [
                    'tipo'        => 'pocion',
                    'nombre'      => $e['nombre'],
                    'stats'       => [
                        'usos_restantes' => $e['usos_restantes'],
                        'usos_totales'   => $e['usos_totales'],
                        'multiplicador'  => $e['multiplicador'],
                        'afecta'         => $e['afecta'],
                    ],
                    'imagen'      => $e['imagen'],
                    'descripcion' => $e['descripcion'],
                    'nivel'       => 1,
                    'requisitos'  => [],
                ];
            }

            // En PvP el enemigo es un personaje: las partes salen de su set
            $setEnemigo = $this->esPvp ? ($this->enemigo->post ?? null) : $this->enemigo;

            if ($drop === null && $setEnemigo && ($dropParteSeguro || ($minutosOriginales >= 5 && rand(1, 100) <= $parteChance))) {
                $tipo = $caza ? $caza->parte : match (true) {
                    $minutosOriginales >= 15 => 'accesorio',
                    $minutosOriginales >= 10 => 'entrenamiento',
                    default => 'equipo',
                };

                switch ($tipo) {
                    case 'equipo':
                        $nombre = $setEnemigo->equipo_nombre ?? 'Objeto Equipo';
                        $stats  = $this->decodificarCampoArray($setEnemigo->ajustes_manuales_equipo);
                        $img    = $setEnemigo->equipo_imagen ?? 'default_equipo.png';
                        $req    = $this->decodificarCampoArray($setEnemigo->requisitos_equipo);
                        break;
                    case 'entrenamiento':
                        $nombre = $setEnemigo->entrenamiento_nombre ?? 'Objeto Entrenamiento';
                        $stats  = $this->decodificarCampoArray($setEnemigo->ajustes_manuales_entrenamiento);
                        $img    = $setEnemigo->entrenamiento_imagen ?? 'default_entrenamiento.png';
                        $req    = $this->decodificarCampoArray($setEnemigo->requisitos_entrenamiento);
                        break;
                    case 'accesorio':
                        $nombre = $setEnemigo->accesorio_nombre ?? 'Objeto Accesorio';
                        $stats  = $this->decodificarCampoArray($setEnemigo->ajustes_manuales_accesorio);
                        $img    = $setEnemigo->accesorio_imagen ?? 'default_accesorio.png';
                        $req    = $this->decodificarCampoArray($setEnemigo->requisitos_accesorio);
                        break;
                }

                $drop = [
                    'tipo'       => $tipo,
                    'nombre'     => $nombre,
                    'stats'      => $stats,
                    'imagen'     => $img,
                    'nivel'      => $setEnemigo->nivel,
                    'requisitos' => $req,
                    'origen_post_id' => $setEnemigo->id,
                ];
            }
        } // fin chequeo id !== 68

        // 🗼 Torre: en los pisos de nivel 20, 30, ..., 100 cae un cofre o una joya (solo la primera vez que se supera)
        $pisoTorre = $this->torreActiva();
        if ($pisoTorre && \App\Support\RecompensasTorre::esNivelEspecial((int) $pisoTorre->nivel)
            && $pisoTorre->piso > (int) $this->personaje->torre_piso) {
            $drop = \App\Support\RecompensasTorre::premio((int) $pisoTorre->nivel);
        }

        // 🕳️ Mazmorra: los enemigos (no el jefe) a veces tiran una poción, de cualquier tipo
        $mazmorraDrop = $this->mazmorraActiva();
        if ($mazmorraDrop && ! $mazmorraDrop->esJefe() && rand(1, 100) <= \App\Models\Mazmorra::CHANCE_POCION) {
            $drop = \App\Models\Mazmorra::pocionAlAzar();
        }

        // 🌱 Variante de la zona inicial: siempre suelta su parte fija del set original (Black = equipo, normal = entrenamiento, Gold = accesorio)
        if (! $this->esPvp && ($this->enemigo->es_enemigo ?? null) == Post::VARIANTE_ZONA && $this->enemigo->variante_de_post_id) {
            $setOriginal = Post::find($this->enemigo->variante_de_post_id);
            $parte = $this->enemigo->variante_parte;
            if ($setOriginal && in_array($parte, ['equipo', 'entrenamiento', 'accesorio'], true)) {
                $drop = [
                    'tipo'           => $parte,
                    'nombre'         => $setOriginal->{$parte . '_nombre'} ?: (ucfirst($parte) . ' de ' . $setOriginal->titulo),
                    'stats'          => $this->decodificarCampoArray($setOriginal->{'ajustes_manuales_' . $parte}),
                    'imagen'         => $setOriginal->{$parte . '_imagen'} ?: preg_replace('#^posts/#', '', (string) $setOriginal->imagen),
                    'nivel'          => $setOriginal->nivel,
                    'requisitos'     => $this->decodificarCampoArray($setOriginal->{'requisitos_' . $parte}),
                    'origen_post_id' => $setOriginal->id,
                ];
            }
        }

        // 📜 Misiones: la primera misión que llega a cada nivel 10, 20, ..., 100 también da un cofre o una joya
        $misionPremio = $this->misionActiva();
        $nivelPremioMision = $misionPremio ? (\App\Support\RecompensasTorre::misionesConPremio()[$misionPremio->id] ?? null) : null;
        if ($nivelPremioMision && ! $misionPremio->completadaPor($this->personaje->id)) {
            $drop = \App\Support\RecompensasTorre::premio($nivelPremioMision);
        }

        // Inventario lleno: el drop se pierde (y se avisa)
        if ($drop && ! $this->personaje->tieneLugar()) {
            $this->dispatch('error', ['message' => '🎒 Inventario lleno: se perdió ' . ($drop['nombre'] ?? 'el objeto') . '. Hacé lugar o comprá más lugares.']);
            $drop = null;
        }

        // Las pociones salen con sus 5 usos (así también se ve 5/5 en el resultado de la pelea)
        if ($drop && ($drop['tipo'] ?? '') === 'pocion') {
            $drop['stats'] = Objeto::conUsosDePocion($drop['stats'] ?? []);
        }

        // Crear el objeto en BD si hay drop
        if ($drop) {
            Objeto::create([
                'personaje_id'             => $this->personaje->id,
                'nombre'                   => $drop['nombre'],
                'tipo'                     => $drop['tipo'],
                'nivel'                    => $drop['nivel'] ?? 1,
                'stats'                    => $drop['stats'], // ✅ Array directo
                'imagen'                   => $drop['imagen'],
                // Cofres y joyas de la Torre no pertenecen a ningún set (traen origen_post_id = null a propósito)
                'origen_post_id'           => array_key_exists('origen_post_id', $drop) ? $drop['origen_post_id'] : ($this->esPvp ? null : ($this->enemigo->id ?? null)),
                'requisitos_equipo'        => $drop['tipo'] === 'equipo' ? $drop['requisitos'] : [],
                'requisitos_entrenamiento' => $drop['tipo'] === 'entrenamiento' ? $drop['requisitos'] : [],
                'requisitos_accesorio'     => $drop['tipo'] === 'accesorio' ? $drop['requisitos'] : [],
                'pocion'                   => $drop['tipo'] === 'pocion',
                'descripcion'              => $drop['descripcion'] ?? null,
                'usos_restantes'           => $drop['stats']['usos_restantes'] ?? 1,
                'usos_totales'             => $drop['stats']['usos_totales'] ?? 1,
            ]);
        }

        $this->recompensas = [
            'exp'       => $exp,
            'bonus_exp' => $bonus,
            'oro'       => $oro,
            'diamante'  => $diamantesExtra,
            'drop'      => $drop,
        ];

        $this->dispatch('recompensasActualizadas');
//     dd('DEBUG Final Drop', [
//     'minutos_reducidos' => $minutos,
//     'minutos_originales' => $minutosOriginales,
//     'parteChance' => $parteChance,
//     'exploracion_rapida_activa' => ExploracionRapida::activaPara($this->personaje->id),
//     'drop' => $drop,
//     'tipo_drop' => $drop['tipo'] ?? null,
//     'nombre_drop' => $drop['nombre'] ?? null,
// ]);

        return $this->recompensas;
    }

    // Misión que se está peleando (su rival es el enemigo actual), o null si es un combate normal
    // PvP perdido: el rival (que no está peleando, solo lo atacaron) cobra la exp de la victoria
    // con la misma regla que el que ataca: 4% de la exp de su nivel si la diferencia es de 5 o menos, 1% si es mayor
    private function darExpAlRivalPvp(): void
    {
        $rival = Personaje::find($this->enemigo->id ?? null);
        if (! $rival || $rival->nivel >= 100) {
            return;
        }
        $nivel = (int) $rival->nivel;
        $expNecesaria = 10000 * pow($nivel, 2) - 10000 * pow($nivel - 1, 2);
        $exp = (int) round($expNecesaria * self::fraccionExpPvp($nivel, (int) $this->personaje->nivel));
        $rival->agregarExperiencia($exp);
        $this->expDadaAlRival = $exp;
    }

    // PvP: aviso al atacado (campanita de arriba) de quién lo atacó y cómo le fue
    private function avisarAlRivalPvp(): void
    {
        $atacante = $this->personaje->nombre;
        [$icono, $mensaje] = match ($this->resultadoFinal) {
            'Derrota' => ['🏆', "{$atacante} te atacó y ganaste" . ($this->expDadaAlRival > 0 ? ': cobraste ' . number_format($this->expDadaAlRival, 0, ',', '.') . ' de exp.' : '.')],
            'Victoria' => ['⚔️', "{$atacante} te atacó y perdiste."],
            default => ['⚔️', "{$atacante} te atacó y empataron."],
        };
        NotificacionJuego::avisar($this->enemigo->id ?? null, $icono, $mensaje);
    }

    // PvP: el atacado queda en recuperación con la regla del PvP (su resultado es el inverso del que ataca).
    // Si está explorando, la exploración no se corta (fin_exploracion es su fin) y la recuperación va en
    // fin_recuperacion; si ya se estaba recuperando por más tiempo, queda el más largo. Con SIEMPRE EN PIE no espera.
    private function darRecuperacionAlRivalPvp(): void
    {
        $rival = Personaje::with('equipo', 'entrenamiento', 'accesorio', 'post.poderes')->find($this->enemigo->id ?? null);
        if (! $rival) {
            return;
        }
        $siempreEnPie = collect($rival->postDeCombate()?->poderes ?? [])
            ->contains(fn ($poder) => strtoupper($poder['nombre'] ?? '') === 'SIEMPRE EN PIE');
        if ($siempreEnPie) {
            return;
        }
        $resultadoRival = match ($this->resultadoFinal) {
            'Victoria' => 'Derrota',
            'Derrota'  => 'Victoria',
            default    => 'Empate',
        };
        $segundos = self::segundosRecuperacion((int) $rival->nivel, $resultadoRival, true);
        // Si ya se estaba recuperando por más tiempo, queda el más largo
        if ($rival->segundosRecuperacion() >= $segundos) {
            return;
        }
        // Solo esa columna, para no pisar nada de lo que el otro esté haciendo.
        // Explorando: la exploración sigue; la recuperación va en fin_recuperacion (ver generarYGuardarEnemigo)
        $columna = $rival->exploracion_duracion > 0 ? 'fin_recuperacion' : 'fin_exploracion';
        Personaje::whereKey($rival->id)->update([$columna => now()->addSeconds($segundos)]);
    }

    // Pelea de exploración: no es PvP, ni misión, ni torre, ni caza (lo que cuenta para el ranking PvE)
    public function esExploracion(): bool
    {
        return ! $this->esPvp && ! $this->misionActiva() && ! $this->torreActiva() && ! $this->cazaActiva() && ! $this->mazmorraActiva();
    }

    // Mazmorra en la que se está peleando (su rival del paso actual es el enemigo), o null si es otro combate
    public function mazmorraActiva(): ?\App\Models\Mazmorra
    {
        if ($this->esPvp || ! $this->enemigo || ! $this->personaje) {
            return null;
        }
        $mazmorra = \App\Models\Mazmorra::where('personaje_id', $this->personaje->id)->where('en_pelea', true)->first();

        return $mazmorra && (int) ($mazmorra->rivalActual()['post_id'] ?? 0) === (int) $this->enemigo->id ? $mazmorra : null;
    }

    public function misionActiva(): ?\App\Models\Mision
    {
        if ($this->esPvp || ! $this->enemigo || ! $this->personaje?->mision_activa_id) {
            return null;
        }

        $mision = \App\Models\Mision::find($this->personaje->mision_activa_id);

        return $mision && $mision->post_id === $this->enemigo->id ? $mision : null;
    }

    // 🌱 Zona inicial (El Comienzo, la ciudad de nivel 0): exploraciones cortas y variantes que sueltan una parte fija
    const MINUTOS_ZONA_INICIAL = 2;
    const MINUTOS_ZONA_INICIAL_RAPIDA = 1;
    const ORO_ZONA_INICIAL = 50; // oro por exploración en El Comienzo; cada zona suma 10 por nivel

    public function esZonaInicial(): bool
    {
        return $this->ciudadActual && (int) $this->ciudadActual->nivel === 0;
    }

    // Piso de la Torre que se está peleando (su rival es el enemigo actual), o null si es otro combate
    public function torreActiva(): ?\App\Models\TorrePiso
    {
        if ($this->esPvp || ! $this->enemigo || ! $this->personaje?->torre_piso_activo) {
            return null;
        }

        $piso = \App\Models\TorrePiso::where('piso', $this->personaje->torre_piso_activo)->first();

        return $piso && $piso->post_id === $this->enemigo->id ? $piso : null;
    }

    // De dónde salen los gifs del enemigo: su set si es un jugador (PvP), o el set mismo
    protected $cacheGifsEnemigo = null;

    public function gifsEnemigo()
    {
        if ($this->enemigo instanceof Personaje) {
            return $this->cacheGifsEnemigo ??= ($this->enemigo->postDeCombate() ?? $this->enemigo);
        }
        return $this->enemigo;
    }

    // Gif principal del enemigo: en PvP el del set completo que tiene equipado (no el del personaje con el que arrancó)
    public function gifEnemigo(): ?string
    {
        return $this->gifsEnemigo()?->gif ?? $this->enemigo?->gif;
    }

    // Caza lista cuya presa es el enemigo actual (null si es un combate normal)
    public function cazaActiva(): ?Caza
    {
        if ($this->esPvp || ! $this->enemigo || ! $this->personaje) {
            return null;
        }

        return Caza::where('personaje_id', $this->personaje->id)
            ->where('estado', 'lista')
            ->where('post_id', $this->enemigo->id)
            ->latest('id')
            ->first();
    }

    // Parte que está en juego contra el enemigo actual, si ya la sacaste antes (se muestra arriba del VS como referencia).
    // Misma regla que asignarRecompensas: variante → su parte fija; caza → la parte elegida; si no, según los minutos.
    public function parteYaSacada(): ?array
    {
        if ($this->esPvp || ! $this->enemigo || $this->misionActiva() || $this->torreActiva() || $this->mazmorraActiva()
            || ($this->enemigo->es_enemigo ?? null) == self::ENEMIGO_ESPECIAL) {
            return null;
        }

        $esVariante = ($this->enemigo->es_enemigo ?? null) == Post::VARIANTE_ZONA && $this->enemigo->variante_de_post_id;
        $minutos = (int) ($this->personaje->minutos_originales ?? $this->personaje->exploracion_duracion ?? 5);
        $tipo = match (true) {
            $esVariante => $this->enemigo->variante_parte,
            (bool) ($caza = $this->cazaActiva()) => $caza->parte,
            $minutos >= 15 => 'accesorio',
            $minutos >= 10 => 'entrenamiento',
            default => 'equipo',
        };
        $setId = $esVariante ? $this->enemigo->variante_de_post_id : $this->enemigo->id;

        $pelea = Pelea::where('personaje_id', $this->personaje->id)
            ->where('datos_combate->drop->tipo', $tipo)
            ->where('datos_combate->drop->origen_post_id', $setId)
            ->latest('realizada_en')
            ->first(['id', 'datos_combate']);
        $drop = $pelea?->datos_combate['drop'] ?? null;

        return $drop ? ['nombre' => $drop['nombre'], 'imagen' => $drop['imagen'] ?? null, 'tipo' => $tipo] : null;
    }

    public function huir()
    {
        // Huir de una presa: se escapa (la carga ya se gastó)
        $this->cazaActiva()?->update(['estado' => 'perdida']);
        if ($this->personaje) {
            $this->personaje->enemigo_actual_id = null; $this->personaje->enemigo_actual_personaje_id = null;
            $this->personaje->mision_activa_id  = null; // la misión se puede volver a intentar
            $this->personaje->torre_piso_activo = null; // el piso de la Torre también
            $this->personaje->save();
            // Mazmorra: sigue en el mismo rival (la energía ya se gastó)
            \App\Models\Mazmorra::where('personaje_id', $this->personaje->id)->update(['en_pelea' => false]);
        }
        $this->escenarioMision = null;

        // Sin combate: se limpia también la marca (si no, Misiones/Caza creen que seguís peleando)
        session()->forget(['enemigo', 'combate_activo']);
        $this->combateActivo         = false;
        $this->enemigo               = null;
        $this->rondaActual           = 1;
        $this->resultadoFinal        = null;
        $this->resultadosRondas      = [];
        // Listos para el próximo enemigo (solo se ven cuando hay uno)
        $this->mostrarBotonesBatalla = true;
    }

    public function render()
    {
       

        return view('livewire.explorar', [
            'personaje'           => $this->personaje,
            'ciudadActual'        => $this->ciudadActual,
            'mostrarOpciones'     => $this->mostrarOpciones,
            'tiempoExploracion'   => $this->tiempoExploracion,
            'combateActivo'       => $this->combateActivo,
            'enemigo'             => $this->enemigo,
            'resultadosRondas'    => $this->resultadosRondas,
            'rondaActual'         => $this->rondaActual,
            'resultadoFinal'      => $this->resultadoFinal,
            'danioTotalPersonaje' => $this->totalDanioPersonaje,
            'danioTotalEnemigo'   => $this->totalDanioEnemigo,
            'expGanada'           => $this->expGanada,
            'oroGanado'           => $this->oroGanado,
            'recompensas'         => $this->recompensas,
            'poderesEnemigo'      => $this->poderesEnemigo,
        ]);
    }
}
