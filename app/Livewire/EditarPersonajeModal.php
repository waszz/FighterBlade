<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Personaje;

class EditarPersonajeModal extends Component
{
   
    public Personaje $personaje;

    public $fuerza, $ataque, $velocidad, $resistencia, $defensa, $energia;
    public $oro, $diamante, $nivel;

    public $modalOpen = false;

    protected $rules = [
        'fuerza' => 'required|integer|min:0',
        'ataque' => 'required|integer|min:0',
        'velocidad' => 'required|integer|min:0',
        'resistencia' => 'required|integer|min:0',
        'defensa' => 'required|integer|min:0',
        'energia' => 'required|integer|min:0',
        'oro' => 'required|integer|min:0',
        'diamante' => 'required|integer|min:0',
        'nivel' => 'required|integer|min:1|max:100',
    ];

    public function mount(Personaje $personaje)
    {
        $this->personaje = $personaje;
    }

    public function openModal()
    {
        $this->personaje = $this->personaje->fresh();

        $stats = $this->personaje->stats ?? [];
        if (is_string($stats)) {
            $stats = json_decode($stats, true) ?? [];
        }

        $this->fuerza = $stats['fuerza'] ?? 0;
        $this->ataque = $stats['ataque'] ?? 0;
        $this->velocidad = $stats['velocidad'] ?? 0;
        $this->resistencia = $stats['resistencia'] ?? 0;
        $this->defensa = $stats['defensa'] ?? 0;
        $this->energia = $stats['energia'] ?? 0;

        $this->oro = $this->personaje->oro ?? 0;
        $this->diamante = $this->personaje->diamante ?? 0;
        $this->nivel = $this->personaje->nivel ?? 1;

        $this->modalOpen = true;
    }

    public function closeModal()
    {
        $this->modalOpen = false;
    }

    public function save()
    {
        $this->validate();

        $this->personaje->stats = [
            'fuerza' => $this->fuerza,
            'ataque' => $this->ataque,
            'velocidad' => $this->velocidad,
            'resistencia' => $this->resistencia,
            'defensa' => $this->defensa,
            'energia' => $this->energia,

        ];

        $nivelAnterior = $this->personaje->nivel ?? 1;

        $this->personaje->oro = $this->oro;
        $this->personaje->diamante = $this->diamante;
        $this->personaje->nivel = $this->nivel;

        if ($this->nivel > $nivelAnterior) {
            $nivelesGanados = $this->nivel - $nivelAnterior;
            $this->personaje->puntos_stats = ($this->personaje->puntos_stats ?? 0) + 5 * $nivelesGanados;
        }

        // La experiencia acompaña al nivel: queda al comienzo del nivel elegido
        // (si no, al bajar de nivel sobra exp y la próxima victoria te sube de golpe)
        if ((int) $this->nivel !== (int) $nivelAnterior) {
            $this->personaje->experiencia = 10000 * ((int) $this->nivel - 1) ** 2;
        }

        $this->personaje->save();

        $this->modalOpen = false;

        session()->flash('message', 'Personaje actualizado correctamente.');
    }

    public function render()
    {
        return view('livewire.editar-personaje-modal');
    }
}
