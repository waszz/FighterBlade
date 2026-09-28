<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Post;

class Sets extends Component
{
    public $personajes;
    public $busquedaTitulo = '';
    public $nivelSeleccionado = 'todos';
    public $nivelesDisponibles = [];

    // Para pasar stats ya con color para cada personaje
    public $statsConColorPorPersonaje = [];

    public function mount()
    {
        $this->nivelesDisponibles = Post::select('nivel')->distinct()->orderBy('nivel')->pluck('nivel')->toArray();
        
        $this->personajes = collect(); // vacío al inicio
        $this->statsConColorPorPersonaje = [];
    }

    public function buscar()
    {
        if (empty($this->busquedaTitulo) && $this->nivelSeleccionado === 'todos') {
            $this->personajes = collect();
            $this->statsConColorPorPersonaje = [];
            return;
        }

        $query = Post::query();

        if ($this->busquedaTitulo) {
            $query->where('titulo', 'like', '%' . $this->busquedaTitulo . '%');
        }

        if ($this->nivelSeleccionado !== 'todos') {
            $query->where('nivel', $this->nivelSeleccionado);
        }

        $query->orderBy('titulo', 'asc');

        // Cargo personajes con poderes
        $this->personajes = $query->with('poderes')->get()->map(function ($personaje) {
            // Decodifico ajustes manuales
            foreach (['equipo', 'entrenamiento', 'accesorio'] as $tipo) {
                $campo = 'ajustes_manuales_' . $tipo;
                $personaje->$campo = is_string($personaje->$campo)
                    ? json_decode($personaje->$campo, true)
                    : ($personaje->$campo ?? []);
            }
            // Decodifico stats y preparo colores
            $stats = is_string($personaje->stats) ? json_decode($personaje->stats, true) : ($personaje->stats ?? []);
            $statsConColor = [];
            foreach ($stats as $stat => $valor) {
                $color = $this->colorBarraPorStat($valor);
                $statsConColor[] = [
                    'stat' => $stat,
                    'valor' => $valor,
                    'color' => $color,
                ];
            }
            // Lo guardo para la vista, indexado por id personaje
            $this->statsConColorPorPersonaje[$personaje->id] = $statsConColor;

            return $personaje;
        });
    }

    protected function colorBarraPorStat($valor)
    {
        if ($valor <= 40) return 'bg-orange-400';
        if ($valor <= 100) return 'bg-green-400';
        if ($valor <= 150) return 'bg-blue-400';
        if ($valor < 200) return 'bg-indigo-400';
        return 'bg-purple-400';
    }

    public function render()
    {
        return view('livewire.sets');
    }
}
