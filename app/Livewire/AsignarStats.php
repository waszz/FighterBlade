<?php
namespace App\Livewire;

use App\Models\Objeto;
use Livewire\Component;

class AsignarStats extends Component
{
    public $personaje;
    public $stats;
    public $statsBase; // Para guardar los stats base iniciales
    public $statsConfirmados = []; // Piso: valores ya guardados, no se pueden bajar de acá
    public $puntos_stats;
    public $confirmandoReset        = false;
    public $cantidadSeleccionada    = 1;
    public $statSeleccionadoEnModal = null;
    public $estadosTemporalesActivos = [];
    public $tiempoRestanteCongelado = null;
    public $tiempoRestanteAturdido = null;
    public $tiempoRestanteEnvenenado = null;
    public $tiempoRestanteDesangrado = null;
    public $tiempoRestanteParalizado = null;
    public $tiempoRestanteQuemado = null;

    public $statsReducidos = [];
    public $coloresStatsReducidos = [];
    // Puntos que quitan los estados activos, por stat
    public $statsPerdidos = [];

    // Para manejar el modal de sumar/restar varios puntos
    public $modalVisible     = false;
    public $statSeleccionado = null;
    public $modo             = null; // 'sumar' o 'restar'
    public $guardado         = false;

    protected $listeners = ['abrirConfirmacionReset' => 'abrirConfirmacionReset'];

    public function abrirConfirmacionReset()
    {
        $this->confirmandoReset = true;
    }

    public function mount($personaje)
    {
        $this->personaje = $personaje;

        $stats_permitidos = ['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'];

        $statsRaw = $this->personaje->stats;

        if (is_string($statsRaw)) {
            $statsRaw = json_decode($statsRaw, true) ?? [];
        }

        $this->statsBase = [];
        foreach ($stats_permitidos as $stat) {
            $this->statsBase[$stat] = isset($statsRaw[$stat]) ? intval($statsRaw[$stat]) : 5;
        }

        // Piso actual = lo que ya está confirmado al cargar la página
        $this->statsConfirmados = $this->statsBase;

        $this->puntos_stats = isset($personaje->puntos_stats)
        ? $personaje->puntos_stats
        : max(0, ($personaje->nivel - 1) * 5);

        // NUEVO: Para mantener los botones deshabilitados si ya se guardaron
        $this->guardado = $this->personaje->stats_guardados ?? false;

         // --- Agregar estados temporales activos para la vista (antes de calcular, para aplicar sus reducciones) ---
        $this->estadosTemporalesActivos = $this->personaje->estadosTemporales
        ->filter(fn($estado) => $estado->estaActivo());

        // Calculás los stats totales solo para mostrar, sin guardar
        $this->stats = $this->calcularStatsTotales();
        $estadoCongelado = $this->estadosTemporalesActivos->firstWhere('estado', 'Congelado');
        $this->tiempoRestanteCongelado = $estadoCongelado ? $estadoCongelado->expira_en->timestamp : null;

        // Estado: Aturdido
        $estadoAturdido = $this->estadosTemporalesActivos->firstWhere('estado', 'Aturdido');
        $this->tiempoRestanteAturdido = $estadoAturdido ? $estadoAturdido->expira_en->timestamp : null;

        // Estado: Envenenado
        $estadoEnvenenado = $this->estadosTemporalesActivos->firstWhere('estado', 'Envenenado');
        $this->tiempoRestanteEnvenenado = $estadoEnvenenado ? $estadoEnvenenado->expira_en->timestamp : null;

        // Estado: Desangrado
        $estadoDesangrado = $this->estadosTemporalesActivos->firstWhere('estado', 'Desangrado');
        $this->tiempoRestanteDesangrado = $estadoDesangrado ? $estadoDesangrado->expira_en->timestamp : null;

        // Estado: Paralizado
        $estadoParalizado = $this->estadosTemporalesActivos->firstWhere('estado', 'Paralizado');
        $this->tiempoRestanteParalizado = $estadoParalizado ? $estadoParalizado->expira_en->timestamp : null;

        // Estado: Quemado
        $estadoQuemado = $this->estadosTemporalesActivos->firstWhere('estado', 'Quemado');
        $this->tiempoRestanteQuemado = $estadoQuemado ? $estadoQuemado->expira_en->timestamp : null;

        

      // Construir array con colores por stat reducido según el estado que lo afecta
    $coloresPorEstado = [
        'Congelado' => 'text-blue-400',
        'Aturdido'  => 'text-red-400',
        'Envenenado' => 'text-green-400',
        'Desangrado' => 'text-purple-400',
        'Paralizado' => 'text-yellow-400',
        'Quemado' => 'text-red-500',
    ];

    $this->coloresStatsReducidos = [];
    $reducidos = [];

    foreach ($this->estadosTemporalesActivos as $estado) {
        $statsAfectados = $estado->stats_afectados;

        // En caso que venga como JSON string, decodificarlo
        if (is_string($statsAfectados)) {
            $statsAfectados = json_decode($statsAfectados, true) ?: [];
        }

        if (is_array($statsAfectados)) {
            foreach ($statsAfectados as $stat) {
                $reducidos[] = $stat;
                if (!isset($this->coloresStatsReducidos[$stat])) {
                    $this->coloresStatsReducidos[$stat] = $coloresPorEstado[$estado->estado] ?? '';
                }
            }
        }
    }

    $this->statsReducidos = array_unique($reducidos);
}

    

    public function seleccionarCantidad($valor)
    {
        $this->cantidadSeleccionada = $valor;
    }

public function quitarEstado($nombreEstado)
{
    // Buscar el estado temporal activo por nombre y personaje
    $estado = $this->personaje->estadosTemporales()
        ->where('estado', $nombreEstado)
        ->where(function($query) {
            $query->whereNull('expira_en')
                  ->orWhere('expira_en', '>', now());
        })
        ->first();

    if (!$estado) {
        $this->dispatch('error', ['message' => 'El estado no está activo.']);
        return;
    }

    // Verificar si el usuario tiene 25 diamantes
    if ($this->personaje->diamante < 25) {
        $this->dispatch('error', ['message' => 'No tienes suficientes esmeraldas.']);
        return;
    }

    // Quitar los diamantes y eliminar el estado
    $this->personaje->diamante -= 25;
    $this->personaje->save();

    $estado->delete();

    // Recargar los estados temporales activos para actualizar la vista
    $this->personaje->unsetRelation('estadosTemporales');
    $this->estadosTemporalesActivos = $this->personaje->estadosTemporales
        ->filter(fn($e) => $e->estaActivo());
    $this->stats = $this->calcularStatsTotales();

    $this->dispatch('success', ['message' => 'Estado ' . mb_strtolower($nombreEstado) . ' quitado correctamente.']);
}

    public function calcularStatsTotales()
    {
//    dd($this->personaje->post->poderes);
        // Comenzamos desde los stats base (sin mutar el original)
        $statsTotales = $this->statsBase;

        $fuentes = [
            $this->personaje->equipo,
            $this->personaje->entrenamiento,
            $this->personaje->accesorio,
            $this->personaje->joya, // anillo de la Torre / Misiones (en las peleas ya se sumaba)
        ];

        // Sumamos los stats de objetos
        foreach ($fuentes as $fuente) {
            if ($fuente) {
                $statsObjeto = is_array($fuente->stats) ? $fuente->stats : json_decode($fuente->stats, true);
                foreach ($statsObjeto ?? [] as $stat => $valor) {
                    if (! isset($statsTotales[$stat])) {
                        $statsTotales[$stat] = 0;
                    }
                    $statsTotales[$stat] += intval($valor);
                }
            }
        }

        // Aplicar multiplicador de la poción equipada, si existe
        if ($this->personaje->objeto_consumible_id) {
            $pocion = Objeto::find($this->personaje->objeto_consumible_id);
            if ($pocion) {
                $statsPocion = is_array($pocion->stats) ? $pocion->stats : json_decode($pocion->stats, true);
                // Solo pociones que afectan un stat real (no recuperacion, drop, etc.)
                if (isset($statsPocion['afecta'], $statsPocion['multiplicador']) && array_key_exists($statsPocion['afecta'], $this->statsBase)) {
                    $statAFectar   = $statsPocion['afecta'];
                    $multiplicador = $statsPocion['multiplicador'];

                    // Volver a calcular el valor original antes de aplicar el multiplicador
                    $valorBase = $this->statsBase[$statAFectar] ?? 0;

                    foreach ($fuentes as $fuente) {
                        if ($fuente) {
                            $statsObjeto = is_array($fuente->stats) ? $fuente->stats : json_decode($fuente->stats, true);
                            if (isset($statsObjeto[$statAFectar])) {
                                $valorBase += intval($statsObjeto[$statAFectar]);
                            }
                        }
                    }

                    // Aplicar multiplicador solo una vez sobre la suma original
                    $statsTotales[$statAFectar] = intval($valorBase * $multiplicador);
                }
            }
        }

        // Aplicar poder ENERGIZADO si lo tiene el personaje
        $poderes = $this->personaje->post->poderes ?? collect();

        foreach ($poderes as $poder) {
            if (strtoupper($poder->nombre) === 'ENERGIZADO') {
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

// Aplicar poder TÉCNICAS CERTERAS si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'TÉCNICAS CERTERAS') {
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


// Aplicar poder SUPER ATAQUE si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'SUPER ATAQUE') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'multiplicador_stat' &&
                ($mod['stat'] ?? '') === 'ataque' &&
                isset($statsTotales['ataque'])
            ) {
                $factor = $mod['factor'] ?? 1;
                $statsTotales['ataque'] = intval(round($statsTotales['ataque'] * $factor));
            }
        }
    }
}

// Aplicar poder SUPER DEFENSA si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'SUPER DEFENSA') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'multiplicador_stat' &&
                strtolower($mod['stat'] ?? '') === 'defensa'
            ) {
                $factor = $mod['factor'] ?? 1;
                if (isset($statsTotales['defensa'])) {
                    $statsTotales['defensa'] = round($statsTotales['defensa'] * $factor);
                }
            }
        }
    }
}

// Aplicar poder SUPER ENERGÍA si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'SUPER ENERGÍA') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'multiplicador_stat' &&
                strtolower($mod['stat'] ?? '') === 'energia'
            ) {
                $factor = $mod['factor'] ?? 1;
                if (isset($statsTotales['energia'])) {
                    $statsTotales['energia'] = round($statsTotales['energia'] * $factor);
                }
            }
        }
    }
}

// Aplicar poder SUPER FUERZA si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'SUPER FUERZA') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'multiplicador_stat' &&
                strtolower($mod['stat'] ?? '') === 'fuerza'
            ) {
                $factor = $mod['factor'] ?? 1;
                if (isset($statsTotales['fuerza'])) {
                    $statsTotales['fuerza'] = round($statsTotales['fuerza'] * $factor);
                }
            }
        }
    }
}

// Aplicar poder SUPER RESISTENCIA si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'SUPER RESISTENCIA') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'multiplicador_stat' &&
                strtolower($mod['stat'] ?? '') === 'resistencia'
            ) {
                $factor = $mod['factor'] ?? 1;
                if (isset($statsTotales['resistencia'])) {
                    $statsTotales['resistencia'] = round($statsTotales['resistencia'] * $factor);
                }
            }
        }
    }
}

// Aplicar poder SUPER VELOCIDAD si lo tiene el personaje
foreach ($poderes as $poder) {
    if (strtoupper($poder->nombre) === 'SUPER VELOCIDAD') {
        $mods = is_array($poder->modificadores) ? $poder->modificadores : json_decode($poder->modificadores, true) ?? [];

        foreach ($mods as $mod) {
            if (
                ($mod['tipo'] ?? '') === 'multiplicador_stat' &&
                strtolower($mod['stat'] ?? '') === 'velocidad'
            ) {
                $factor = $mod['factor'] ?? 1;
                if (isset($statsTotales['velocidad'])) {
                    $statsTotales['velocidad'] = round($statsTotales['velocidad'] * $factor);
                }
            }
        }
    }
}

// Estados (Aturdido, Desangrado, etc.): se guarda cuánto quitan para mostrarlo en rojo
$this->statsPerdidos = [];
foreach ($this->estadosTemporalesActivos as $estado) {
    if ($estado->estaActivo()) {
        $porcentaje = $estado->porcentaje ?? 0;
        $statsAfectados = $estado->stats_afectados ?? [];

        foreach ($statsAfectados as $stat) {
            if (isset($statsTotales[$stat])) {
                $reducido = round($statsTotales[$stat] * (1 - $porcentaje / 100));
                $this->statsPerdidos[$stat] = ($this->statsPerdidos[$stat] ?? 0) + ($statsTotales[$stat] - $reducido);
                $statsTotales[$stat] = $reducido;
            }
        }
    }
}

        return $statsTotales;
    }

    /**
     * Asignar o quitar puntos a una stat.
     *
     * @param string $stat Nombre de la stat
     * @param int $cantidad Cantidad de puntos a asignar (positivo para sumar, negativo para restar)
     */

    public function asignar($stat, $cantidad = 1)
    {
        $stats_permitidos = ['fuerza', 'ataque', 'velocidad', 'resistencia', 'defensa', 'energia'];

        if (! in_array($stat, $stats_permitidos)) {
            return;
        }

        if (! isset($this->statsBase[$stat])) {
            $this->statsBase[$stat] = 0;
        }

        $limiteStat     = $this->personaje->nivel * 2;
        $statBaseActual = $this->statsBase[$stat] ?? 0;

        if ($cantidad > 0) {
            if ($this->puntos_stats < $cantidad) {
                $this->dispatch('error', ['message' => 'No tienes suficientes puntos disponibles']);
                return;
            }

            if ($statBaseActual + $cantidad > $limiteStat) {
                $this->dispatch('error', [
                    'message' => "No puedes superar los {$limiteStat} puntos base en {$stat} a este nivel.",
                ]);
                return;
            }

            $this->statsBase[$stat] += $cantidad;
            $this->puntos_stats -= $cantidad;
            $this->guardado = false;

        } elseif ($cantidad < 0) {
            $cantidadAbs = abs($cantidad);

            // Aquí quitamos chequeo puntosStat porque al restar recuperás puntos
            if ($statBaseActual < $cantidadAbs) {
                $this->dispatch('error', ['message' => "No puedes restar más puntos de los que tiene la stat"]);
                return;
            }

            $valorMinimoAbsoluto = $this->personaje->stats_base[$stat] ?? 5;
            $valorMinimo         = max($valorMinimoAbsoluto, $this->statsConfirmados[$stat] ?? $valorMinimoAbsoluto);

            if ($statBaseActual - $cantidadAbs < $valorMinimo) {
                $this->dispatch('error', ['message' => "No puedes restar puntos que ya guardaste. Solo podés bajar los puntos que sumaste sin guardar."]);
                return;
            }

            $this->statsBase[$stat] -= $cantidadAbs;
            $this->puntos_stats += $cantidadAbs;
        } else {
            return;
        }

        $this->personaje->stats        = json_encode($this->statsBase);
        $this->personaje->puntos_stats = $this->puntos_stats;
        $this->personaje->save();

        $this->personaje = $this->personaje->fresh();

        $statsRaw        = is_string($this->personaje->stats) ? json_decode($this->personaje->stats, true) : $this->personaje->stats;
        $this->statsBase = $statsRaw ?: $this->statsBase;

        $this->stats = $this->calcularStatsTotales();

        $this->dispatch('statsActualizados', $this->stats);
    }

    public function abrirModal()
    {
        $this->modalVisible = true;

    }

    public function cerrarModal()
    {
        $this->personaje->stats           = json_encode($this->statsBase);
        $this->personaje->stats_guardados = true;
        $this->personaje->save();

        $this->modalVisible      = false;
        $this->guardado          = true;
        $this->statsConfirmados  = $this->statsBase;
    }

    public function setCantidadSeleccionada($cantidad)
    {
        $this->cantidadSeleccionada = $cantidad;
    }

    public function asignarDesdeModal($stat)
    {
        if (! $this->cantidadSeleccionada || $this->cantidadSeleccionada == 0) {
            return;
        }

        $this->asignar($stat, $this->cantidadSeleccionada);

        // Refrescar datos para mantener todo sincronizado
        $this->personaje = $this->personaje->fresh();
        $this->stats     = $this->calcularStatsTotales();

        $statsRaw        = is_string($this->personaje->stats) ? json_decode($this->personaje->stats, true) : $this->personaje->stats;
        $this->statsBase = $statsRaw ?: $this->statsBase;

        $this->puntos_stats = $this->personaje->puntos_stats;
    }

    public function resetearStats($recurso = 'oro')
    {
        $personaje = $this->personaje;
        $costo     = $personaje->nivel * 10;

        // Verificar oro o diamantes
        if ($recurso === 'oro') {
            if ($personaje->oro < $costo) {
                $this->dispatch('error', ['message' => 'No tienes suficiente oro.']);
                $this->confirmandoReset = false;
                return;
            }
            $personaje->oro -= $costo;
        } elseif ($recurso === 'diamante') {
            if ($personaje->diamante < $costo) {
                $this->dispatch('error', ['message' => 'No tienes suficientes esmeraldas.']);
                $this->confirmandoReset = false;
                return;
            }
            $personaje->diamante -= $costo;
        }

        // Obtener los stats base desde la base de datos o valores por defecto
        $statsBase = $personaje->stats_base ?? [
            'fuerza'      => 5,
            'ataque'      => 5,
            'velocidad'   => 5,
            'resistencia' => 5,
            'defensa'     => 5,
            'energia'     => 5,
        ];

        // Resetear los stats al valor base
        $personaje->stats = $statsBase;

        // Calcular puntos disponibles según niveles ganados (nivel 1 tiene 0)
        $personaje->puntos_stats = max(0, ($personaje->nivel - 1) * 5);

        // Desequipar equipo y objetos
        $personaje->equipo_id        = null;
        $personaje->entrenamiento_id = null;
        $personaje->accesorio_id     = null;

        // **Desequipar poción**: Si hay una poción equipada, eliminarla
        if ($personaje->objeto_consumible_id) {
            $personaje->objeto_consumible_id = null; // Limpiar poción equipada
            $personaje->stats_modificados    = null; // Limpiar stats modificados
        }
        $this->personaje->stats_guardados = false;

        // Guardar los cambios en el personaje
        $personaje->save();

        // Refrescar datos en el componente
        $this->confirmandoReset = false;
        $this->personaje        = $personaje;
        $this->stats            = $statsBase;
        $this->puntos_stats     = $personaje->puntos_stats;

        // Feedback al usuario
        $this->dispatch('success', ['message' => 'Stats reseteados, equipo y poción removidos.']);

        // Actualizar la vista del inventario y otros componentes
        $this->dispatch('recargarPagina');
    }

    public function render()
    {
        // Siempre mostrar los stats totales (base + objetos)
        $this->stats = $this->calcularStatsTotales();

        return view('livewire.asignar-stats', [
            'stats'        => $this->stats,
            'puntos_stats' => $this->puntos_stats,
        ]);
    }

}
