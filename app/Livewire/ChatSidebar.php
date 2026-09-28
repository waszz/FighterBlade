<?php

namespace App\Livewire;

use Livewire\Component;

class ChatSidebar extends Component
{
    public $minimizado = false;

    // Personaje con el que se está jugando (lo usa el chat para firmar los mensajes)
    public $personajeId = null;

    public function mount($personajeId = null)
    {
        $this->personajeId = $personajeId;
    }

    public function toggle()
    {
        $this->minimizado = !$this->minimizado;
    }

    public function render()
    {
        return view('livewire.chat-sidebar');
    }
}
