<?php

namespace App\Livewire;

use App\Models\Mision;
use App\Models\Post;
use Livewire\Component;

// Admin: personajes que no están en la lista normal de sets (enemigo de bienvenida y rivales de Misiones),
// para poder verlos y cargarles los gifs.
class PersonajesEspeciales extends Component
{
    public string $search = '';
    public string $filtro = 'todos'; // todos | sin_gifs | completos

    public function mount()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    // Cuántas animaciones tienen un gif propio (los rivales se crean con la foto en todas)
    public static function gifsCargados(Post $post): int
    {
        return collect(Post::CAMPOS_GIF)
            ->filter(fn ($campo) => str_ends_with(strtolower((string) $post->$campo), '.gif'))
            ->count();
    }

    public function render()
    {
        $ordenMision = Mision::pluck('orden', 'post_id');

        $especiales = Post::conRivales()
            ->whereIn('es_enemigo', [Post::ENEMIGO_ESPECIAL, Post::RIVAL_MISION])
            ->when($this->search, fn ($q) => $q->where('titulo', 'like', '%' . $this->search . '%'))
            ->get()
            ->map(function (Post $post) use ($ordenMision) {
                $post->gifs_cargados = self::gifsCargados($post);
                $post->orden_mision = $ordenMision[$post->id] ?? null;
                return $post;
            })
            ->filter(fn ($post) => match ($this->filtro) {
                'sin_gifs'  => $post->gifs_cargados < count(Post::CAMPOS_GIF),
                'completos' => $post->gifs_cargados === count(Post::CAMPOS_GIF),
                default     => true,
            })
            // Primero el enemigo de bienvenida, después las misiones en orden
            ->sortBy(fn ($post) => $post->es_enemigo == Post::ENEMIGO_ESPECIAL ? 0 : $post->orden_mision)
            ->values();

        return view('livewire.personajes-especiales', [
            'especiales' => $especiales,
            'totalGifs'  => count(Post::CAMPOS_GIF),
        ])->layout('layouts.app');
    }
}
