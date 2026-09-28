<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\Personaje;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class HomePosts extends Component
{
    use WithPagination;

     private function obtenerImagenCombinada($personaje)
    {
        $equipo = $personaje->equipo;
        $entrenamiento = $personaje->entrenamiento;
        $accesorio = $personaje->accesorio;

        if ($equipo && $entrenamiento && $accesorio) {
            if (
                $equipo->origen_post_id &&
                $equipo->origen_post_id === $entrenamiento->origen_post_id &&
                $equipo->origen_post_id === $accesorio->origen_post_id
            ) {
                $post = Post::find($equipo->origen_post_id);
                if ($post && $post->imagen) {
                    return $post->imagen;
                }
            }
        }

        return $personaje->imagen; // si no hay combinación, mostrar imagen base
    }

    public function render()
    {
        $posts = Post::personajesBase()->limit(5)->get();

        // Traer personajes del ranking con relaciones
        $ranking = Personaje::with(['equipo', 'entrenamiento', 'accesorio'])
            ->orderBy('nivel', 'desc')
            ->orderBy('experiencia', 'desc')
            ->take(10)
            ->get();

        // Procesar imagen combinada para cada personaje del ranking
        foreach ($ranking as $personaje) {
            $personaje->imagen_final = $this->obtenerImagenCombinada($personaje);
        }

        $personajeUsuario = null;
        $imagenPersonajeEquipado = null;

        if (Auth::check()) {
            $personajeUsuario = Personaje::with(['equipo', 'entrenamiento', 'accesorio'])
                ->where('user_id', Auth::id())
                ->first();

            if ($personajeUsuario) {
                $imagenPersonajeEquipado = $this->obtenerImagenCombinada($personajeUsuario);
            }
        }

        return view('livewire.home-posts', [
            'posts' => $posts,
            'ranking' => $ranking,
            'personajeUsuario' => $personajeUsuario,
            'imagenPersonajeEquipado' => $imagenPersonajeEquipado,
        ]);
    }

   
}
