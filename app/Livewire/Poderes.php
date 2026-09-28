<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Poder;

class Poderes extends Component
{
    public $poderes;
    public $busqueda = '';

    public function mount()
    {
        $this->buscar();
    }

    public function updatedBusqueda()
    {
        $this->buscar();
    }

    public function buscar()
    {
        $query = Poder::query();

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('descripcion', 'like', '%' . $this->busqueda . '%');
            });
        }

        $this->poderes = $query->orderBy('nombre')->get();
    }

    public function render()
    {
        return view('livewire.poderes');
    }
}
