<?php
namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarPosts extends Component
{
    use WithPagination;

    public $search = '';  // Propiedad para la búsqueda
    public $categoria = ''; // Propiedad para la categoría
    public $isAdmin = false; // Verificar si el usuario es admin
    public $nivelSeleccionado = '';

    protected $listeners = ['eliminarPost'];

    public function mount($categoria = null)
    {
        $this->categoria = $categoria;  // Establecer la categoría desde la URL
        $this->isAdmin = auth()->user() && auth()->user()->role === 'admin';  // Verificar si el usuario es admin
    }
    public function buscarPosts()
{
    // Livewire volverá a renderizar y aplicará automáticamente el filtro porque `search` ya se usa en el render()
    $this->resetPage(); // Opcional, para volver a la página 1 al buscar
}

    public function eliminarPost(Post $id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $id->delete();
    }

   public function render()
{
    // Los rivales de misión y el enemigo de bienvenida están en Personajes especiales
    $posts = Post::where('user_id', auth()->user()->id)
        ->where('titulo', 'like', '%' . $this->search . '%')
        ->when($this->categoria, function($query) {
            $query->where('categoria', $this->categoria);
        })
        ->when($this->nivelSeleccionado, function($query) {
            $query->where('nivel', $this->nivelSeleccionado);
        })
        ->latest()
        ->paginate(10);

    return view('livewire.mostrar-posts', [
        'posts' => $posts
    ])->layout('layouts.app');
}
}
