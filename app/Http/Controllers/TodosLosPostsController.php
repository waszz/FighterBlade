<?php
namespace App\Http\Controllers;

use App\Models\Post;

class TodosLosPostsController extends Controller
{
    public function index()
    {
        // Obtiene los posts, limitando los resultados para la vista
        $posts = Post::latest()->paginate(6);  // Ajusta la cantidad según sea necesario

        return view('todos-los-posts.index', compact('posts'));
    }

    
}