<?php

namespace App\Livewire;

use App\Models\Mensaje;
use Livewire\Component;
use App\Models\Personaje;
use Illuminate\Support\Facades\Auth;

class ChatComponent extends Component
{
    public $mensaje = '';

    protected $rules = [
        'mensaje' => 'required|string|max:255',
    ];

    public function mount()
    {
        // Asegura que haya un personaje_id en sesión (ideal para pruebas)
        if (!session()->has('personaje_id')) {
            $personaje = Personaje::where('user_id', auth()->id())->first();
            if ($personaje) {
                session(['personaje_id' => $personaje->id]);
            }
        }
    }

    public function enviarMensaje()
    {
        $this->validate();

        $personajeId = session('personaje_id');

        if (!$personajeId) {
            session()->flash('error', 'Debes seleccionar un personaje para enviar mensajes.');
            return;
        }

        Mensaje::create([
            'contenido' => $this->mensaje,
            'user_id' => auth()->id(),
            'personaje_id' => $personajeId,
        ]);

        $this->mensaje = ''; // Limpia el input después de enviar
    }

    public function reiniciarChat()
{
    // Solo admin puede
    if (auth()->user()->role !== 'admin') {
        session()->flash('error', 'No tienes permiso para reiniciar el chat.');
        return;
    }

    Mensaje::truncate();

    session()->flash('success', 'Chat reiniciado correctamente.');
}

    public function render()
    {
        $mensajes = Mensaje::with(['personaje.post', 'personaje.equipo', 'personaje.entrenamiento', 'personaje.accesorio'])->latest()->take(50)->get()->reverse();

        return view('livewire.chat-component', compact('mensajes'));
    }
}