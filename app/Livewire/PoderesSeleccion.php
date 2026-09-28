<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Poder;

class PoderesSeleccion extends Component
{
    public $seleccionados = [];

    public function updatedSeleccionados()
    {
        if (count($this->seleccionados) > 3) {
            array_pop($this->seleccionados);
            session()->flash('message', 'Solo puedes seleccionar hasta 3 poderes.');
        }

        // Emitir los seleccionados al componente padre
        $this->dispatch('poderesSeleccionados', $this->seleccionados);
    }

    public function render()
    {
        return view('livewire.poderes-seleccion', [
            'poderes' => Poder::all(),
        ]);
    }
}