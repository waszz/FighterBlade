<?php

namespace App\Livewire;

use App\Models\Anuncio;
use Livewire\Component;

// Administración → Anuncios: crear, editar, ocultar y borrar los anuncios del panel de la ciudad
class AdminAnuncios extends Component
{
    public string $titulo = '';
    public string $detalle = '';
    public string $texto = '';
    public string $llamado = '';
    public ?int $editandoId = null;

    public function mount()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    protected function rules(): array
    {
        return [
            'titulo'  => 'required|string|max:120',
            'detalle' => 'nullable|string|max:120',
            'texto'   => 'required|string|max:1000',
            'llamado' => 'nullable|string|max:80',
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
        // Los opcionales vacíos se guardan como null
        $datos['detalle'] = trim($datos['detalle'] ?? '') ?: null;
        $datos['llamado'] = trim($datos['llamado'] ?? '') ?: null;

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
        $this->detalle = $anuncio->detalle ?? '';
        $this->texto = $anuncio->texto;
        $this->llamado = $anuncio->llamado ?? '';
    }

    public function cancelar()
    {
        $this->reset(['titulo', 'detalle', 'texto', 'llamado', 'editandoId']);
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
            'anuncios' => Anuncio::with('autor')->withCount('likes')->latest()->get(),
        ])->layout('layouts.app');
    }
}
