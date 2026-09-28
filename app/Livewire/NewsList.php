<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\News;

class NewsList extends Component
{
    public $news;

    public function mount()
    {
        // Obtener las últimas 3 noticias, ordenadas desde la más reciente
        $this->news = News::latest()->take(3)->get();
    }

    public function render()
    {
        return view('livewire.news-list', [
            'news' => $this->news
        ])->layout('layouts.app');
    }
}