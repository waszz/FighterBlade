<?php

namespace App\Livewire;

use App\Models\Ciudad;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\File;

class NewsEdit extends Component
{
    use WithFileUploads;

    public $ciudad;
    public $titulo;
    public $nivel;
    public $gif;
    public $isUploading = false;
    public $id;

    public function mount($id)
    {
        $this->id = $id;
        $this->ciudad = Ciudad::findOrFail($this->id);
        $this->titulo = $this->ciudad->nombre;
        $this->nivel = $this->ciudad->nivel;
    }

    protected $rules = [
        'titulo' => 'required|string|max:255',
        'nivel' => 'required|integer|min:0|max:100',
        'gif' => 'nullable|mimes:gif|max:4096',
    ];

    public function editarCiudad()
    {
        $this->validate();
        $this->isUploading = true;

        // Si sube un nuevo gif, reemplazarlo
        if ($this->gif) {
            $rutaGif = storage_path('app/public/posts/' . $this->ciudad->gif);
            if (File::exists($rutaGif)) {
                File::delete($rutaGif);
            }

            $nombreGif = time() . '.' . $this->gif->getClientOriginalExtension();
            $this->gif->storeAs('posts', $nombreGif, 'public');
            $this->ciudad->gif = $nombreGif;
        }

        // Actualizar campos
        $this->ciudad->nombre = $this->titulo;
        $this->ciudad->nivel = $this->nivel;
        $this->ciudad->save();

        $this->isUploading = false;
        session()->flash('message', 'Ciudad actualizada correctamente.');
        return redirect()->route('news.index');
    }

    public function render()
    {
        return view('livewire.news-edit')->layout('layouts.app');
    }
}