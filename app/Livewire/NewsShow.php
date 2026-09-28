<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\News;

class NewsShow extends Component
{
    public $news;

    public function mount($id)
    {
        $this->news = News::findOrFail($id);  // Obtener una noticia por su ID
    }

    public function render()
    {
      

        return view('livewire.news-show')->layout('layouts.app');  // Vista para mostrar una sola noticia
    }
}