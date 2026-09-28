<?php

// app/Livewire/Notificaciones.php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CommentNotification;
use Illuminate\Support\Facades\Auth;

class Notificaciones extends Component
{
    public $show = false;
    public $notificaciones = [];

    protected $listeners = ['nuevaNotificacion' => 'actualizarNotificaciones'];

    public function toggle()
    {
        $this->show = !$this->show;
    }

    public function actualizarNotificaciones()
    {
        $this->notificaciones = CommentNotification::with('comentario')
            ->where('user_id', Auth::id())
            ->where('leido', false)
            ->latest()
            ->get();
    }

    public function marcarComoLeidoYRedirigir($id)
    {
        $notificacion = CommentNotification::find($id);

        if ($notificacion && $notificacion->user_id === Auth::id()) {
            $notificacion->leido = true;
            $notificacion->save();
            $this->actualizarNotificaciones();

            return redirect()->route('posts.show', ['post' => $notificacion->comentario->post_id]);
        }
    }

    public function mount()
    {
        $this->actualizarNotificaciones();
    }

    public function render()
    {
        return view('livewire.notificaciones');
    }
}