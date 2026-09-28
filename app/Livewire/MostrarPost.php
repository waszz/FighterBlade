<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Post;

class MostrarPost extends Component
{
    public $post;
    public $mismoNivel;
    public $postSiguiente;
    public $postAnterior;

    public function mount($post)
    {
        $this->cargarPost($post);
    }

    public function cargarPost($post)
    {
        // Acepta ID o instancia del modelo
        $this->post = is_numeric($post) ? Post::conRivales()->findOrFail($post) : $post;
        $this->post->loadMissing('poderes');

        // Otros personajes del mismo nivel
        $this->mismoNivel = Post::where('nivel', $this->post->nivel)
                                ->where('id', '!=', $this->post->id)
                                ->orderBy('titulo')
                                ->get(['id', 'titulo', 'gif', 'tipo']);

        // Navegación por nivel y título (igual que se listan los sets)
        $orden = fn ($q, $dir) => $q->orderBy('nivel', $dir)->orderBy('titulo', $dir)->orderBy('id', $dir);
        $despues = fn ($q) => $q->where('nivel', '>', $this->post->nivel)
            ->orWhere(fn ($q) => $q->where('nivel', $this->post->nivel)->where('titulo', '>', $this->post->titulo));
        $antes = fn ($q) => $q->where('nivel', '<', $this->post->nivel)
            ->orWhere(fn ($q) => $q->where('nivel', $this->post->nivel)->where('titulo', '<', $this->post->titulo));

        $this->postSiguiente = $orden(Post::where($despues), 'asc')->first(['id', 'titulo', 'nivel']);
        $this->postAnterior  = $orden(Post::where($antes), 'desc')->first(['id', 'titulo', 'nivel']);
    }

    // Marca/desmarca una animación como "mira a la izquierda" (el juego la endereza con estiloGif)
    public function girarGif(string $campo)
    {
        if (! auth()->user()?->isAdmin() || ! in_array($campo, Post::CAMPOS_GIF, true)) {
            return;
        }

        $girados = $this->post->gifs_girados ?? [];
        $girados = in_array($campo, $girados, true)
            ? array_values(array_diff($girados, [$campo]))
            : array_merge($girados, [$campo]);

        $this->post->forceFill(['gifs_girados' => $girados ?: null])->saveQuietly();
    }

    // Ajuste manual de una sola animación: tamaño ±5% (50%-200%) o altura ±4 px (-80 a 80)
    public function ajustarAnimacion(string $campo, string $tipo, int $direccion)
    {
        if (! auth()->user()?->isAdmin() || ! in_array($campo, Post::CAMPOS_GIF, true)) {
            return;
        }

        $ajustes = $this->post->gif_ajustes ?? [];
        $actual = $this->post->ajusteGif($campo);

        if ($tipo === 'escala') {
            $actual['escala'] = max(0.5, min(2, round($actual['escala'] + ($direccion > 0 ? 0.05 : -0.05), 2)));
        } elseif ($tipo === 'subir') {
            $actual['subir'] = max(-80, min(80, $actual['subir'] + ($direccion > 0 ? 4 : -4)));
        } elseif ($tipo === 'reiniciar') {
            $actual = ['escala' => 1, 'subir' => 0];
        }

        if ($actual['escala'] == 1 && $actual['subir'] == 0) {
            unset($ajustes[$campo]);
        } else {
            $ajustes[$campo] = $actual;
        }
        $this->post->forceFill(['gif_ajustes' => $ajustes ?: null])->saveQuietly();
    }

    // Ajuste manual del tamaño del set en las peleas: ±10% por toque (entre 50% y 200%)
    public function ajustarEscala(int $direccion)
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $escala = round((float) ($this->post->gif_escala ?: 1) + ($direccion > 0 ? 0.1 : -0.1), 2);
        $this->post->forceFill(['gif_escala' => max(0.5, min(2, $escala))])->saveQuietly();
    }

    public function render()
    {
        return view('livewire.mostrar-post');
    }
}
