<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\Personaje;
use Livewire\Component;

class GaleriaPersonajes extends Component
{
    public $personajeId;
    public $personajeModal = null;
    public $mostrarModalPost = false;
    public $gifPost;
    public $nombrePost;
    public $poderesPersonaje;
    public $statsPost = [];
    public $postIdSeleccionado = null;
    public $personajeBaseId;
    public $equipadoPostsIds = [];


public function abrirModalPost($postId)
{
    $post = Post::find($postId);
    if (!$post) return;

    $personaje = Personaje::with('postsUsados')->find($this->personajeId);
    if (!$personaje) return;

    $esBase = $personaje->post_id === $post->id;
    $desbloqueado = $personaje->postsUsados->contains('id', $post->id);

    if (! $esBase && ! $desbloqueado) {
        return; // ❌ No abrir el modal si no es base ni usado
    }

    $this->postIdSeleccionado = $postId;
    $this->nombrePost = $post->titulo ?? $post->nombre;
    $this->gifPost = $post->gif;
    $this->poderesPersonaje = collect($post->poderes ?? []);
    // Lo que da el set completo equipado (la suma de sus 3 partes)
    $this->statsPost = $post->statsSetCompleto();
    $this->mostrarModalPost = true;
}



    public function cerrarModalPost()
    {
        $this->mostrarModalPost = false;
        $this->postIdSeleccionado = null;

        $this->nombrePost = null;
        $this->gifPost = null;
        $this->poderesPersonaje = collect();
        $this->statsPost = [];
    }

   public function render()
{
    $personaje = Personaje::with('postsUsados')->find($this->personajeId);

    $personajesDisponibles = Post::whereBetween('nivel', [5, 100])
        ->orderBy('nivel', 'asc')
        ->get();

    $postsDesbloqueados = $personaje->postsUsados->pluck('id')->toArray();

    return view('livewire.galeria-personajes', compact('personaje', 'personajesDisponibles', 'postsDesbloqueados'));
}
}
