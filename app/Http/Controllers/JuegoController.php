<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class JuegoController extends Controller
{
    public function mostrar($personajeId)
    {
        $personaje = Post::where('id', $personajeId)
                         ->where('user_id', Auth::id())
                         ->firstOrFail();

        // Decodificar stats JSON a array para usar en la vista
        $stats = is_array($personaje->stats) ? $personaje->stats : (json_decode($personaje->stats ?? '[]', true) ?: []);

        return view('juego.interfaz', compact('personaje', 'stats'));
    }
}