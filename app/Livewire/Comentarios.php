<?php

namespace App\Livewire;

use App\Models\Comentario;
use App\Models\CommentNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Comentarios extends Component
{
    public $comentario = '';
    public $comentarioEditado = '';
    public $comentarioIdEditar = null;
    public $comentarioPadreId = null;
    public $postId;
    public $inputKey = 0;

    public function mount($postId)
    {
        $this->postId = $postId;
    }

    public function enviar()
    {
        $this->validate([
            'comentario' => 'required|string|max:1000',
        ]);

        $nuevoComentario = Comentario::create([
            'user_id' => Auth::id(),
            'post_id' => $this->postId,
            'comentario' => $this->comentario,
            'parent_id' => $this->comentarioPadreId,
        ]);

        if ($nuevoComentario->parent_id) {
            $comentarioPadre = Comentario::find($nuevoComentario->parent_id);
            if ($comentarioPadre && $comentarioPadre->user_id !== Auth::id()) {
                CommentNotification::create([
                    'user_id' => $comentarioPadre->user_id,
                    'comentario_id' => $nuevoComentario->id,
                ]);
                $this->dispatch('notificaciones', 'nuevaNotificacion');
            }
        }

        $this->comentario = '';
        $this->comentarioPadreId = null;
        $this->inputKey++;

        session()->flash('mensaje', 'Comentario enviado correctamente.');
    }

    public function responder($comentarioId)
    {
        $this->comentarioPadreId = $comentarioId;
        $this->comentario = '';
    }

    public function editar($comentarioId)
    {
        $comentario = Comentario::findOrFail($comentarioId);

        if ($comentario->user_id !== Auth::id() && !Auth::user()?->is_admin) {
            abort(403);
        }

        $this->comentarioIdEditar = $comentario->id;
        $this->comentarioEditado = $comentario->comentario;
    }
    public $openStates = [];

    public function toggleRespuestas($comentarioId)
    {
        $this->openStates[$comentarioId] = !($this->openStates[$comentarioId] ?? false);
    }

    public function cancelarEdicion()
    {
        $this->comentarioIdEditar = null;
        $this->comentarioEditado = '';
    }

    public function actualizar()
    {
        $this->validate([
            'comentarioEditado' => 'required|string|max:1000',
        ]);

        $comentario = Comentario::findOrFail($this->comentarioIdEditar);

        if ($comentario->user_id !== Auth::id() && !Auth::user()?->is_admin) {
            abort(403);
        }

        $comentario->comentario = $this->comentarioEditado;
        $comentario->save();

        $this->cancelarEdicion();

        session()->flash('mensaje', 'Comentario actualizado correctamente.');
    }

    public function eliminar($comentarioId)
    {
        $comentario = Comentario::findOrFail($comentarioId);

        if ($comentario->user_id !== Auth::id() && !Auth::user()?->is_admin) {
            abort(403);
        }

        $comentario->delete();

        session()->flash('mensaje', 'Comentario eliminado.');
    }

    public function render()
    {
        $comentarios = Comentario::with(['user', 'respuestas.user'])
            ->where('post_id', $this->postId)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.comentarios', [
            'comentarios' => $comentarios,
        ]);
    }
}