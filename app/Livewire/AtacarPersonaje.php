<?php

namespace App\Livewire;



use Livewire\Component;
use App\Models\Personaje;

class AtacarPersonaje extends Component
{
    public Personaje $personaje;   // Personaje atacante
    public Personaje $objetivo;    // Personaje objetivo

    public $accionesRonda = [];
    public $resultadoFinal;
    public $recompensas = [];

    // Daños totales
    public $danioTotalPersonaje = 0;
    public $danioTotalObjetivo = 0;

    // GIFs (puedes asignar defaults y/o sobreescribir en mount)
    public $gifVictoriaPersonaje = '_gifVictoria.gif';
    public $gifDerrotaPersonaje = '_gifDerrota.gif';
    public $gifVictoriaEnemigo = 'gif_victoria_enemigo.gif';
    public $gifDerrotaEnemigo = 'gif_derrota_enemigo.gif';
    public $gifAtaquePersonaje = 'gif_ataque_personaje.gif';
    public $gifAtaqueEnemigo = 'gif_ataque_enemigo.gif';

    public $rondas = 5;

    public function mount($personajeId, $objetivoId)
    {
        $this->personaje = Personaje::findOrFail($personajeId);
        $this->objetivo  = Personaje::findOrFail($objetivoId);

        // Opcional: cargar gifs reales si existen en post
        $this->gifAtaquePersonaje = $this->personaje->post->gif_ataque ?? $this->gifAtaquePersonaje;
        $this->gifVictoriaPersonaje = $this->personaje->post->gif_victoria ?? $this->gifVictoriaPersonaje;
        $this->gifDerrotaPersonaje = $this->personaje->post->gif_derrota ?? $this->gifDerrotaPersonaje;

        $this->gifAtaqueEnemigo = $this->objetivo->post->gif_ataque ?? $this->gifAtaqueEnemigo;
        $this->gifVictoriaEnemigo = $this->objetivo->post->gif_victoria ?? $this->gifVictoriaEnemigo;
        $this->gifDerrotaEnemigo = $this->objetivo->post->gif_derrota ?? $this->gifDerrotaEnemigo;
    }

public function atacar()
{
    $this->accionesRonda = [];
    $this->danioTotalPersonaje = 0;
    $this->danioTotalObjetivo  = 0;
    $this->resultadoFinal = null;
    $this->recompensas = [];

    // Asegurarse de que los stats tengan valores numéricos
    $fuerzaPersonaje = $this->personaje->fuerza ?? 0;
    $ataquePersonaje = $this->personaje->ataque ?? 0;
    $nivelPersonaje  = $this->personaje->nivel ?? 1;

    $fuerzaObjetivo = $this->objetivo->fuerza ?? 0;
    $ataqueObjetivo = $this->objetivo->ataque ?? 0;
    $nivelObjetivo  = $this->objetivo->nivel ?? 1;
    $defensaObjetivo = $this->objetivo->defensa ?? 0;
    $resistenciaObjetivo = $this->objetivo->resistencia ?? 0;

    for ($r = 1; $r <= $this->rondas; $r++) {

        // --- Calcular daño del atacante ---
        $danioFisico = 0;
        $danioElemental = 0;

        switch($this->personaje->tipo_personaje) {
            case 'fisico':
                $danioFisico = intval(($fuerzaPersonaje + $ataquePersonaje) * 0.8 + $nivelPersonaje);
                break;
            case 'elemental':
                $danioElemental = intval(($fuerzaPersonaje + $ataquePersonaje) * 0.6 + $nivelPersonaje);
                break;
            case 'hibrido':
                $danioFisico = intval(($fuerzaPersonaje + $ataquePersonaje) * 0.5 + $nivelPersonaje);
                $danioElemental = intval(($fuerzaPersonaje + $ataquePersonaje) * 0.4 + $nivelPersonaje);
                break;
        }

        // --- Calcular daño recibido por el objetivo ---
        $danioRecibido = max(0, ($danioFisico - $defensaObjetivo) + ($danioElemental - $resistenciaObjetivo));

        // Guardar acción de esta ronda
        $this->accionesRonda[] = [
            'ronda' => $r,
            'atacante' => $this->personaje->nombre,
            'tipo_personaje' => $this->personaje->tipo_personaje,
            'danio_fisico' => $danioFisico,
            'danio_elemental' => $danioElemental,
            'enemigo' => $this->objetivo->nombre,
            'danio_recibido' => $danioRecibido,
            'gif_personaje' => $this->gifAtaquePersonaje,
            'gif_enemigo' => $this->gifAtaqueEnemigo,
        ];

        // Acumular totales
        $this->danioTotalPersonaje += $danioFisico + $danioElemental;
        $this->danioTotalObjetivo  += $danioRecibido;
    }

    // --- Resultado final ---
    if ($this->danioTotalPersonaje > $this->danioTotalObjetivo) {
        $this->resultadoFinal = 'Victoria';
    } elseif ($this->danioTotalPersonaje < $this->danioTotalObjetivo) {
        $this->resultadoFinal = 'Derrota';
    } else {
        $this->resultadoFinal = 'Empate';
    }

    // --- Recompensas de ejemplo ---
    $this->recompensas = [
        'exp' => rand(50, 120),
        'oro' => rand(20, 60),
    ];
}



    public function render()
    {
        // Muy importante: NO usar ->layout(...) si ese layout no existe o te da error.
        return view('livewire.atacar-personaje')->layout('layouts.app');
    }
}