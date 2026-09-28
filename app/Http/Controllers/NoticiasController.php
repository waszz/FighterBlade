<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Ciudad;
use Illuminate\Http\Request;

class NoticiasController extends Controller
{
    // Mostrar todas las noticias
    public function index()
    {
        $news = News::all();
        return view('news.index', compact('news'));
    }

   // Mostrar una noticia individual
   public function show(News $news)
   {
       // Aquí la variable $news ya contiene la instancia de la noticia solicitada
       return view('news.show', compact('news'));
   }
    // Mostrar el formulario para editar una noticia
    public function edit(Ciudad $ciudades)
    {
        return view('news.edit', compact('ciudades'));
    }

    
 // Método para mostrar el formulario de creación de noticia
 public function create()
 {
     return view('news.create');
 }


}

