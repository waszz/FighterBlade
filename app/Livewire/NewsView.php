<?php

namespace App\Livewire;

use App\Models\News;
use App\Models\Ciudad;
use Livewire\Component;

class NewsView extends Component
{
    protected $listeners = ['eliminarCiudad'];

    public function mount()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Acceso no autorizado');
        }
    }
    
    public function eliminarCiudad(Ciudad $id)
    {
        $id->delete();
    }
   public function render()
{
    // Solo entran admins (ver mount) y ven todas las noticias y ciudades, las haya creado quien sea
    $news = News::orderBy('created_at', 'desc')
        ->paginate(10);

    $ciudades = Ciudad::orderBy('created_at', 'desc')
        ->get();
        

    return view('livewire.news-view', [
        'news' => $news,
        'ciudades' => $ciudades,
    ]);
}
}
