<?php

namespace App\Livewire;

use App\Models\Anuncio;
use Livewire\Component;

// Administración → Anuncios: crear, editar, ocultar y borrar los anuncios del panel de la ciudad
class AdminAnuncios extends Component
{
    public string $titulo = '';
    public string $texto = '';
    public ?int $editandoId = null;

    public function mount()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    protected function rules(): array
    {
        return [
            'titulo' => 'required|string|max:120',
            'texto'  => 'required|string|max:1000',
        ];
    }

    protected $messages = [
        'titulo.required' => 'Poné un título.',
        'titulo.max'      => 'El título puede tener hasta 120 caracteres.',
        'texto.required'  => 'Escribí el anuncio.',
        'texto.max'       => 'El anuncio puede tener hasta 1000 caracteres.',
    ];

    public function guardar()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $datos = $this->validate();

        if ($this->editandoId) {
            Anuncio::findOrFail($this->editandoId)->update($datos);
            session()->flash('mensaje', 'Anuncio actualizado.');
        } else {
            Anuncio::create($datos + ['user_id' => auth()->id()]);
            session()->flash('mensaje', 'Anuncio publicado.');
        }

        $this->cancelar();
    }

    public function editar(int $id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $anuncio = Anuncio::findOrFail($id);
        $this->editandoId = $anuncio->id;
        $this->titulo = $anuncio->titulo;
        $this->texto = $anuncio->texto;
    }

    public function cancelar()
    {
        $this->reset(['titulo', 'texto', 'editandoId']);
        $this->resetValidation();
    }

    public function alternarActivo(int $id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $anuncio = Anuncio::findOrFail($id);
        $anuncio->update(['activo' => ! $anuncio->activo]);
    }

    public function borrar(int $id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        Anuncio::whereKey($id)->delete();
        if ($this->editandoId === $id) {
            $this->cancelar();
        }
        session()->flash('mensaje', 'Anuncio borrado.');
    }

    public function render()
    {
        return view('livewire.admin-anuncios', [
            'anuncios' => Anuncio::with('autor')->latest()->get(),
        ])->layout('layouts.app');
    }
}
