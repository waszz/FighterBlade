<?php
namespace App\Livewire;

use App\Models\Buff;
use Livewire\Component;
use App\Models\Personaje;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class JuegoInterfaz extends Component
{
    public $mostrarOpcionesExplorar = false;
    public $personaje;
    public $personajes;
    public $ciudadActual;
    public $seccion = 'inicio';
    public $ciudadSeleccionada;
    public $tiempoSeleccionado;
    public $stats = [];  // Aquí guardamos los stats decodificados
    public bool $sidebarVisible = false;
    public bool $chatVisible = false;
    public $modalGuardarOro = false;
    public $montoOro = 0;
    public $oroGuardado = 0;
   public $reloadCounters = [
    'inicio' => 0,
    'viajar' => 0,
    'inventario' => 0,
    'mercado' => 0,
    'ranking' => 0,
    'extra' => 0,
    'sets' => 0,
    'poderes' => 0,
    'galeria' => 0,
    'peleas' => 0,
    'drops' => 0,
    'casino' => 0,
    'caza' => 0,
];


    
   


public function toggleSidebar()
{
    $this->sidebarVisible = !$this->sidebarVisible;
}

public function toggleChat()
{
    $this->chatVisible = !$this->chatVisible;
}



 protected $listeners = [
    'statsActualizados' => 'recargarPersonaje',
    'irAViajar' => 'mostrarViajar',
    'abrirInventario' => 'mostrarInventario',
    'verMercado' => 'mostrarMercado',
    'cambiarSeccion' => 'cambiarSeccion', //Agregado
    'verPeleaCompartida' => 'verPeleaCompartida',
];

    // Pelea compartida en el chat que se abre en Mis peleas
    public ?int $peleaCompartidaId = null;

    public function verPeleaCompartida($peleaId)
    {
        $this->peleaCompartidaId = (int) $peleaId;
        $this->seccion = 'peleas';
        $this->reloadCounters['peleas']++;
    }

    public function mount($personajeId)
    {
        $this->personajes = Personaje::where('user_id', Auth::id())->get();

        $this->personaje = $this->personajes->firstWhere('id', $personajeId);

        if (!$this->personaje) {
            abort(404, 'Personaje no encontrado o no pertenece al usuario.');
        }

        $this->ciudadActual = $this->personaje->ciudadActual ?? null;

        $this->cargarStatsDesdePersonaje();
        $this->oroGuardado = $this->personaje->oro_guardado ?? 0;
         $this->cargarBuffsExperiencia();
        
    }
public function cambiarSeccion(string $nuevaSeccion)
{
    $this->seccion = $nuevaSeccion;
    $this->peleaCompartidaId = null;

    // Si la sección seleccionada es 'inicio', disparamos el evento para recargar la página
    if ($this->seccion === 'inicio') {
        $this->dispatch('recargar-pagina');
    }
}


public function calcularStatsTotales()
{
    $statsBase = is_array($this->personaje->stats)
        ? $this->personaje->stats
        : json_decode($this->personaje->stats ?? '{}', true);

    $statsTotales = $statsBase;

    $fuentes = [
        $this->personaje->equipo,
        $this->personaje->entrenamiento,
        $this->personaje->accesorio,
        $this->personaje->joya, // joya de la Torre
    ];

    foreach ($fuentes as $fuente) {
        if ($fuente) {
            $statsObjeto = is_array($fuente->stats) ? $fuente->stats : json_decode($fuente->stats, true);
            foreach ($statsObjeto ?? [] as $stat => $valor) {
                if (!isset($statsTotales[$stat])) {
                    $statsTotales[$stat] = 0;
                }
                $statsTotales[$stat] += intval($valor);
            }
        }
    }

    // Poción equipada
    if ($this->personaje->objeto_consumible_id) {
        $pocion = \App\Models\Objeto::find($this->personaje->objeto_consumible_id);
        if ($pocion) {
            $statsPocion = is_array($pocion->stats) ? $pocion->stats : json_decode($pocion->stats, true);
            if (isset($statsPocion['afecta'], $statsPocion['multiplicador'])) {
                $statAFectar = $statsPocion['afecta'];
                $multiplicador = $statsPocion['multiplicador'];

                // Recalcular valor original antes del multiplicador
                $valorBase = $statsBase[$statAFectar] ?? 0;
                foreach ($fuentes as $fuente) {
                    if ($fuente) {
                        $statsObjeto = is_array($fuente->stats) ? $fuente->stats : json_decode($fuente->stats, true);
                        if (isset($statsObjeto[$statAFectar])) {
                            $valorBase += intval($statsObjeto[$statAFectar]);
                        }
                    }
                }

                $statsTotales[$statAFectar] = intval(round($valorBase * $multiplicador));
            }
        }
    }

    // Poder ENERGIZADO
    $poderes = $this->personaje->poderes ?? [];

foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'ENERGIZADO') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $statsTotales[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores); // ordenar de mayor a menor
                    $claves = array_keys($valores);
                    $mayor = $valores[$claves[0]];
                    $menor = $valores[$claves[1]];
                    $claveMayor = $claves[0];
                    $claveMenor = $claves[1];

                    // Aplicar efecto solo si hay diferencia real
                    $nuevoValor = intval(round($menor + $mayor * $factor));

                    if ($nuevoValor > $menor) {
                        $statsTotales[$claveMenor] = $nuevoValor;
                    }
                }
            }
        }
    }
}

   // Aplicar poder GOLPES VELOCES si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'GOLPES VELOCES') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor         = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $statsTotales[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores);
                    $claves     = array_keys($valores);
                    $mayor      = $valores[$claves[0]];
                    $menor      = $valores[$claves[1]];
                    $claveMayor = $claves[0];
                    $claveMenor = $claves[1];

                    $nuevoValor = intval(round($menor + $mayor * $factor));

                    if ($nuevoValor > $menor) {
                        $statsTotales[$claveMenor] = $nuevoValor;
                    }
                }
            }
        }
    }
}

// Aplicar poder OFENSIVO EXPERTO si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'OFENSIVO EXPERTO') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor         = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $statsTotales[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores);
                    $claves     = array_keys($valores);
                    $mayor      = $valores[$claves[0]];
                    $menor      = $valores[$claves[1]];
                    $claveMayor = $claves[0];
                    $claveMenor = $claves[1];

                    $nuevoValor = intval(round($menor + $mayor * $factor));

                    if ($nuevoValor > $menor) {
                        $statsTotales[$claveMenor] = $nuevoValor;
                    }
                }
            }
        }
    }
}


// Aplicar poder MOLE si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'MOLE') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (($mod['tipo'] ?? '') === 'optimizar_stats') {
                $statsOptimizar = $mod['stats'] ?? [];
                $factor         = $mod['factor'] ?? 0;

                $valores = [];
                foreach ($statsOptimizar as $stat) {
                    $valores[$stat] = $statsTotales[$stat] ?? 0;
                }

                if (count($valores) >= 2) {
                    arsort($valores);
                    $claves     = array_keys($valores);
                    $mayor      = $valores[$claves[0]];
                    $menor      = $valores[$claves[1]];
                    $claveMayor = $claves[0];
                    $claveMenor = $claves[1];

                    $nuevoValor = intval(round($menor + $mayor * $factor));

                    if ($nuevoValor > $menor) {
                        $statsTotales[$claveMenor] = $nuevoValor;
                    }
                }
            }
        }
    }
}

    return $statsTotales;
}


 public function cargarStatsDesdePersonaje()
{
     $this->stats = $this->calcularStatsTotales();
    
}
    // Cancelar la exploración en curso desde el panel lateral
    public function cancelarExploracion()
    {
        $personaje = Personaje::find($this->personaje->id);
        if ($personaje->exploracion_duracion > 0 && $personaje->fin_exploracion && now()->lt($personaje->fin_exploracion)) {
            $personaje->fin_exploracion = null;
            $personaje->exploracion_duracion = 0;
            $personaje->save();
            session()->forget(['enemigo', 'combate_activo']);
            $this->dispatch('success', ['message' => 'Cancelaste la exploración.']);
        }
        // Recarga para que la Ciudad vuelva a mostrar el botón Explorar
        $this->dispatch('recargar-pagina');
    }

    // Saltear la espera de recuperación pagando oro (nivel × 20)
    public function recuperarConOro()
    {
        $personaje = Personaje::find($this->personaje->id);
        $costo = $personaje->nivel * 20;

        if ($personaje->segundosRecuperacion() <= 0) {
            $this->recargarPersonaje();
            return;
        }
        if ($personaje->oro < $costo) {
            $this->dispatch('error', ['message' => 'No tenés suficiente oro para recuperarte.']);
            return;
        }

        $personaje->oro -= $costo;
        if ($personaje->exploracion_duracion > 0) {
            // Lo atacaron mientras exploraba: la exploración sigue. Si ya había terminado y solo se estiraba
            // por la recuperación (ver Explorar::generarYGuardarEnemigo), el enemigo aparece ya
            if ($personaje->fin_recuperacion && $personaje->fin_exploracion?->eq($personaje->fin_recuperacion)) {
                $personaje->fin_exploracion = now();
            }
        } else {
            $personaje->fin_exploracion = null;
        }
        $personaje->fin_recuperacion = null;
        $personaje->save();

        $this->dispatch('success', ['message' => "Te recuperaste pagando $costo de oro."]);
        // Recarga para que la Ciudad también quede lista para pelear
        $this->dispatch('recargar-pagina');
    }

    public function recargarPersonaje()
    {
        $this->personaje = Personaje::find($this->personaje->id);
        $this->cargarStatsDesdePersonaje();
    }

    public function mostrarViajar()
    {
        $this->seccion = 'viajar';
    }

    public function mostrarInventario()
    {
        $this->seccion = 'inventario';
    }

    public function mostrarMercado()
    {
        $this->seccion = 'mercado';
    }

    public function cambiarDireccion()
    {
        if ($this->personaje->direccion === 'derecha') {
            $this->personaje->direccion = 'izquierda';
        } else {
            $this->personaje->direccion = 'derecha';
        }

        $this->personaje->save();
    }

    public function abrirModalGuardarOro()
{
    $this->modalGuardarOro = true;
    $this->montoOro = 0;
}

public function guardarOro()
{
    $monto = intval($this->montoOro);

    if ($monto <= 0) {
        $this->dispatch('error', ['message' => 'Debes ingresar un monto válido.']);
        return;
    }

    if ($this->personaje->oro < $monto) {
        $this->dispatch('error', ['message' => 'No tienes suficiente oro para guardar.']);
        return;
    }

    $this->personaje->oro -= $monto;
    $this->personaje->oro_guardado += $monto;
    $this->personaje->save();

    $this->oroGuardado = $this->personaje->oro_guardado;
    $this->montoOro = 0;

    $this->modalGuardarOro = false;  // Aquí cerramos el modal

    $this->dispatch('success', ['message' => 'Oro guardado correctamente.']);
}


public function retirarOro()
{
    $monto = intval($this->montoOro);

    if ($monto <= 0) {
        $this->dispatch('error', ['message' => 'Debes ingresar un monto válido.']);
        return;
    }

    if ($this->personaje->oro_guardado < $monto) {
        $this->dispatch('error', ['message' => 'No tienes suficiente oro guardado para retirar.']);
        return;
    }

    $this->personaje->oro += $monto;
    $this->personaje->oro_guardado -= $monto;
    $this->personaje->save();

    $this->oroGuardado = $this->personaje->oro_guardado;
    $this->montoOro = 0;

    $this->modalGuardarOro = false;  // <--- Cierra el modal aquí

    $this->dispatch('success', ['message' => 'Oro retirado correctamente.']);
}
public function verificarNivel()
{
    // ⚠️ Si ya es nivel 100, no hacer nada
    if ($this->personaje->nivel >= 100) {
        $this->personaje->nivel = 100;

        // Truncar la experiencia al máximo para ese nivel
        $this->personaje->exp = min(
            $this->personaje->exp,
            $this->expMaximaNivel(100)
        );

        $this->personaje->save();
        return;
    }

    // Acumulador de niveles ganados
    $nivelesGanados = 0;

    // Mientras tenga experiencia suficiente y no haya llegado a 100
    while ($this->personaje->nivel < 100 && $this->personaje->exp >= $this->expNecesaria()) {
        $this->personaje->exp -= $this->expNecesaria();
        $this->personaje->nivel += 1;
        $nivelesGanados++;
    }

    // 🧠 Asignar 5 puntos por nivel subido
    if ($nivelesGanados > 0) {
        $this->personaje->puntos_stats += $nivelesGanados * 5;
    }

    // ✅ Guardar cambios
    $this->personaje->save();
}


public function expNecesaria()
{
    $nivel = max(1, $this->personaje->nivel);
    $expNivelActual = 10000 * pow($nivel - 1, 2);
    $expSiguienteNivel = 10000 * pow($nivel, 2);

    return $expSiguienteNivel - $expNivelActual;
}

// 📏 Experiencia total acumulada máxima posible para un nivel dado
public function expMaximaNivel($nivel)
{
    return 10000 * pow($nivel, 2);
}

public function cargarBuffsExperiencia()
{
    $this->buffsExperienciaActivos = Buff::where('tipo', 'xp')
        ->where('inicio', '<=', now())
        ->where('fin', '>=', now())
        ->where(function ($q) {
            $q->where('personaje_id', $this->personaje->id)
              ->orWhereNull('personaje_id'); // global
        })
        ->orderByDesc('fin')
        ->get()
        ->map(function ($buff) {
            return [
                'id' => $buff->id,
                'porcentaje' => $buff->porcentaje,
                'finTimestamp' => $buff->fin->timestamp,
                'global' => $buff->global,
            ];
        })->toArray();

    $this->dispatch('iniciar-timers-buff-exp', [
        'buffs' => $this->buffsExperienciaActivos,
    ]);
}


    public function iniciarExploracion()
    {
        if (!$this->ciudadSeleccionada || !$this->tiempoSeleccionado) return;

        // Si el usuario es admin, mostrar enemigo directo sin temporizador
        if (auth()->user()->is_admin) {  // Cambia 'is_admin' por el campo que tengas para admin
            $this->generarEnemigo();
            return;
        }

        $this->tiempoRestante = $this->tiempoSeleccionado;
        Cache::put("explorar_tiempo_{$this->personaje->id}", $this->tiempoRestante, now()->addSeconds($this->tiempoSeleccionado));

        $this->dispatchBrowserEvent('iniciar-timer', ['segundos' => $this->tiempoRestante]);
    }

    // Método para asignar un punto a un stat si hay puntos disponibles
    public function asignar($stat)
    {
        if ($this->personaje->puntos_stats <= 0) {
            return; // No hay puntos para asignar
        }

        if (!isset($this->stats[$stat])) {
            $this->stats[$stat] = 0;
        }

        $this->stats[$stat]++;
        $this->personaje->puntos_stats--;

        // Guardar los cambios en BD
        $this->personaje->stats = $this->stats;
        $this->personaje->save();

        // Actualizar vista
        $this->emit('statsActualizados');
    }

    public function render()
    {
        return view('livewire.juego-interfaz')->layout('layouts.app');
    }
}