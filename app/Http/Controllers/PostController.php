<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Post::class);

        return view('posts.index');
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Post::class);
        return view('posts.create');
    }

   
    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        return view('Posts.show', [
            'post' => $post
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        Gate::authorize('update', $post);
        // Los personajes especiales tienen su propio editor (nivel libre, stats sin reparto de puntos)
        if (in_array((int) $post->es_enemigo, [Post::ENEMIGO_ESPECIAL, Post::RIVAL_MISION], true)) {
            return redirect()->route('personajes.especiales.editar', $post->id);
        }
        return view('posts.edit', [
            'post' => $post
        ]);
    }

}
