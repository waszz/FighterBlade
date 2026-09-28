<?php

namespace App\Livewire;

use App\Models\Ciudad;
use Livewire\Component;
use Livewire\WithFileUploads;

class NewsCreate extends Component
{
    use WithFileUploads;
public $titulo;
public $gif;
public $nivel; // ⬅️ nuevo campo
public $isUploading = false;

protected $rules = [
    'titulo' => 'required|string|max:255',
    'nivel' => 'required|integer|min:0|max:100', // ajusta el rango según tu juego
    'gif' => 'required|mimes:gif|max:4096',
];


    public function mount()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Acceso no autorizado');
        }
    }

    public function crearCiudad()
    {
        $this->validate();

        $this->isUploading = true;

        // Guardar el gif
        $nombreGif = time() . '.' . $this->gif->getClientOriginalExtension();
        $this->gif->storeAs('posts', $nombreGif, 'public');

        // Crear la ciudad
       Ciudad::create([
    'nombre' => $this->titulo,
    'gif' => $nombreGif,
    'nivel' => $this->nivel, // ⬅️ nuevo campo
    'user_id' => auth()->id(),
]);

        $this->isUploading = false;
        $this->reset();

        session()->flash('message', 'Ciudad creada correctamente.');
        return redirect()->route('news.index'); // Cambia esta ruta si es diferente
    }

    public function render()
    {
        return view('livewire.ciudad-crear')->layout('layouts.app');
    }
}