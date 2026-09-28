<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ciudad;
use App\Models\Post;
use App\Models\Personaje;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ViajarCiudades extends Component
{
    public $personaje;
    public $ciudadSeleccionada;
    public $ciudades = [];
    public $enemigo;

    public $personajesUsuario = [];
    public $personajesCrearPost = [];

    public $resultadosRondas = [];

    public $explorarSeleccionado = false;
    public $tiempoExplorar = null;
    public $tiempoRestante = 0;
    public $timerActivo = false;

    public $ganador = null;
    public $recompensas = [];

    public $oro = 0;
    public $experiencia = 0;

    protected $listeners = ['actualizarTiempoRestante'];

    public function mount($personaje)
    {
        $this->personaje = $personaje;
        $this->ganador = null;
        $this->recompensas = [];
        $this->actualizarDatos();
        $this->cargarPersonajes();
        $this->actualizarStatsPublicos();

        // Cargar enemigo si ya hay uno asignado
        if ($this->personaje->enemigo_actual_id) {
            $this->enemigo = Post::find($this->personaje->enemigo_actual_id);
        }

        // Revisar si hay exploración en curso
        if ($this->personaje->exploracion_finaliza_en) {
            $segundosRestantes = Carbon::now()->diffInSeconds(Carbon::parse($this->personaje->exploracion_finaliza_en), false);

            if ($segundosRestantes > 0) {
                $this->tiempoRestante = $segundosRestantes;
                $this->explorarSeleccionado = true;
                $this->timerActivo = true;

                $this->dispatch('iniciar-timer', ['segundos' => $segundosRestantes]);
            } else {
                // Tiempo expirado, reiniciar exploración y cargar enemigo
                $this->personaje->exploracion_finaliza_en = null;
                $this->personaje->save();

                if (!$this->personaje->enemigo_actual_id) {
                    $this->cargarEnemigo();
                }
            }
        }
    }

    public function updatedPersonaje()
    {
        $this->actualizarDatos();
        $this->cargarPersonajes();
        $this->actualizarStatsPublicos();
    }

    // Actualiza ciudades y ciudad seleccionada según personaje
    public function actualizarDatos()
    {
        $this->ciudadSeleccionada = Ciudad::find($this->personaje->ciudad_id);
        $this->ciudades = Ciudad::where('nivel', '<=', $this->personaje->nivel)->get();
    }

    // Cambiar ciudad del personaje
    public function viajar($ciudadId)
    {
        $this->ciudadSeleccionada = Ciudad::find($ciudadId);
        $this->personaje->ciudad_id = $ciudadId;
        $this->personaje->save();

        $this->actualizarDatos();
        $this->cargarPersonajes();
    }

    // Carga personajes del usuario y posts (enemigos)
    public function cargarPersonajes()
    {
        $user = Auth::user();
        $this->personajesUsuario = $user ? Personaje::where('user_id', $user->id)->get() : collect();
        $this->personajesCrearPost = Post::all();
    }

    // Carga un enemigo aleatorio acorde al nivel de la ciudad seleccionada
    public function cargarEnemigo()
    {
        if (!$this->ciudadSeleccionada) {
            $this->enemigo = null;
            return;
        }

        $nivelObjetivo = $this->ciudadSeleccionada->nivel + 5;
        $enemigo = Post::where('nivel', $nivelObjetivo)->inRandomOrder()->first();

        if ($enemigo) {
            $this->enemigo = $enemigo;
            $this->personaje->enemigo_actual_id = $enemigo->id;
            $this->personaje->save();
        }
    }

    // Seleccionar tiempo de exploración y activar timer
    public function seleccionarTiempo($minutos)
    {
        $this->tiempoExplorar = $minutos;
        $this->tiempoRestante = $minutos * 60;
        $this->explorarSeleccionado = true;
        $this->timerActivo = true;

        $this->personaje->exploracion_finaliza_en = Carbon::now()->addMinutes($minutos);
        $this->personaje->save();

        $this->dispatch('iniciar-timer', ['segundos' => $this->tiempoRestante]);
    }

    // Listener para actualizar el tiempo restante del timer JS
    public function actualizarTiempoRestante($segundos)
    {
        $this->tiempoRestante = $segundos;

        if ($this->tiempoRestante <= 0 && !$this->personaje->enemigo_actual_id) {
            $this->timerActivo = false;
            $this->personaje->exploracion_finaliza_en = null;
            $this->personaje->save();

            $this->cargarEnemigo();
            $this->dispatch('tiempo-finalizado');
        }
    }

    // Simulación de combate entre personaje y enemigo
    public function atacar()
    {
        if (!$this->enemigo || !$this->personaje) {
            session()->flash('error', 'No se pudo iniciar el combate.');
            return;
        }

        $this->resultadosRondas = [];
        $this->ganador = null;
        $this->recompensas = [];

        $personajeStats = $this->personaje->stats ?? [];
        $enemigoStats = $this->enemigo->stats ?? [];

        $velocidadPersonaje = $personajeStats['velocidad'] ?? 0;
        $velocidadEnemigo = $enemigoStats['velocidad'] ?? 0;
        $nivelPersonaje = $this->personaje->nivel ?? 1;
        $nivelEnemigo = $this->enemigo->nivel ?? 1;

        $iniciativaPersonaje = $velocidadPersonaje + ($nivelPersonaje * 2);
        $iniciativaEnemigo = $velocidadEnemigo + ($nivelEnemigo * 2);

        $atacante = $iniciativaPersonaje > $iniciativaEnemigo ? 'personaje' :
                    ($iniciativaEnemigo > $iniciativaPersonaje ? 'enemigo' :
                    (rand(0, 1) === 0 ? 'personaje' : 'enemigo'));

        $totalDanioPersonaje = 0;
        $totalDanioEnemigo = 0;

        $fuerzaPersonaje = $personajeStats['fuerza'] ?? 10;
        $fuerzaEnemigo = $enemigoStats['fuerza'] ?? 8;

        for ($i = 1; $i <= 5; $i++) {
            // El atacante cambia cada ronda
            $atacanteActual = $atacante === 'personaje' ? 'personaje' : 'enemigo';

            $danioPersonaje = $atacanteActual === 'personaje' ? rand(1, $fuerzaPersonaje + ($nivelPersonaje * 2)) : 0;
            $danioEnemigo = $atacanteActual === 'enemigo' ? rand(1, $fuerzaEnemigo + ($nivelEnemigo * 2)) : 0;

            $totalDanioPersonaje += $danioPersonaje;
            $totalDanioEnemigo += $danioEnemigo;

            $this->resultadosRondas[] = [
                'ronda' => $i,
                'atacante' => $atacanteActual,
                'danio_personaje' => $danioPersonaje,
                'danio_enemigo' => $danioEnemigo,
            ];

            // Alterna atacante para siguiente ronda
            $atacante = $atacante === 'personaje' ? 'enemigo' : 'personaje';
        }

        $nivelCiudad = $this->ciudadSeleccionada->nivel ?? 1;
        $expGanada = rand(10, 20) * $nivelCiudad;
        $oroGanado = rand(5, 15) * $nivelCiudad;

        if ($totalDanioPersonaje > $totalDanioEnemigo) {
            $this->ganador = 'personaje';

            $personajeStats['experiencia'] = ($personajeStats['experiencia'] ?? 0) + $expGanada;
            $personajeStats['oro'] = ($personajeStats['oro'] ?? 0) + $oroGanado;

            $experienciaActual = $personajeStats['experiencia'];
            $nivelActual = $this->personaje->nivel;
            $experienciaRequerida = $nivelActual * 100;

            if ($experienciaActual >= $experienciaRequerida) {
                $this->personaje->nivel += 1;
                $personajeStats['experiencia'] = $experienciaActual - $experienciaRequerida;
                session()->flash('level_up', '¡Has subido al nivel ' . $this->personaje->nivel . '!');
            }

            $this->personaje->stats = $personajeStats;
            $this->personaje->save();

            $this->actualizarStatsPublicos();

            $this->recompensas = [
                'exp' => $expGanada,
                'oro' => $oroGanado,
                'items' => [],
            ];

            session()->flash('message', "¡Ganaste el combate contra {$this->enemigo->titulo}! +{$expGanada} exp, +{$oroGanado} oro");
        } elseif ($totalDanioPersonaje < $totalDanioEnemigo) {
            $this->ganador = 'enemigo';
            session()->flash('error', 'Perdiste contra ' . $this->enemigo->titulo . '...');
        } else {
            $this->ganador = null;
            session()->flash('error', '¡Empate! Nadie gana esta vez.');
        }

        // Limpieza tras el combate
        $this->personaje->enemigo_actual_id = null;
        $this->personaje->exploracion_finaliza_en = null;
        $this->personaje->save();

        $this->enemigo = null;
        $this->explorarSeleccionado = false;
        $this->timerActivo = false;
        $this->tiempoRestante = 0;
        $this->tiempoExplorar = null;
    }

    public function actualizarStatsPublicos()
    {
        $stats = $this->personaje->stats ?? [];
        $this->oro = $stats['oro'] ?? 0;
        $this->experiencia = $stats['experiencia'] ?? 0;
    }

    public function render()
    {
        return view('livewire.viajar-ciudades')->layout('layouts.app');
    }
}