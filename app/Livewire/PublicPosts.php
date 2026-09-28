<?php
namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;
use Livewire\WithPagination;

class PublicPosts extends Component
{
    use WithPagination;

    public $estado;  // Para filtrar por estado (adoptado o en adopción)
    public $categoria;  // Para filtrar por categoría (gato o perro)
    public $genero;  // Para filtrar por género (hembra o macho)
    public $edad;    // Para filtrar por edad (cachorro o adulto)
    public $search;  // Para buscar por nombre del post

    public function render()
    {
        $posts = Post::query()
            // Aplicar los filtros solo si se han establecido
            ->when($this->estado, function ($query) {
                $query->where('estado', $this->estado);
            })
            ->when($this->categoria, function ($query) {
                $query->where('categoria', $this->categoria);
            })
            ->when($this->genero, function ($query) {
                $query->where('genero', $this->genero);
            })
            ->when($this->edad, function ($query) {
                $query->where('edad', $this->edad);
            })
            ->when($this->search, function ($query) {
                $query->where('titulo', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.public-posts', [
            'posts' => $posts
        ])->layout('layouts.app');
    }

    // Método para aplicar los filtros al hacer clic en "Buscar"
    public function aplicarFiltros()
    {
        // Los filtros se aplican automáticamente al hacer clic en el botón
    }

    // Método para resetear los filtros
    public function resetFilters()
    {
        $this->estado = null;
        $this->categoria = null;
        $this->genero = null;
        $this->edad = null;
        $this->search = null;
    }
}