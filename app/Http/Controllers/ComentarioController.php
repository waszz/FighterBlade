<?php

namespace App\Http\Controllers;

use App\Models\Comentario;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ComentarioController extends Controller
{
    public function mostrarConResaltado(Comentario $comentario)
    {
        $postId = $comentario->post_id;
        return redirect()->route('posts.show', ['id' => $postId, 'comentario' => $comentario->id]);
    }
}
