<?php

namespace App\Livewire;

use App\Models\Mensaje;
use Livewire\Component;

// Punto rojo en el botón del chat del celular: hay mensajes privados sin leer, o mensajes nuevos en el chat general
// desde la última vez que se abrió el chat (eso lo recuerda el navegador). Se revisa solo cada tanto
class AvisoChat extends Component
{
    public $personajeId;

    public function mount($personajeId)
    {
        $this->personajeId = $personajeId;
    }

    public function render()
    {
        return view('livewire.aviso-chat', [
            'privados'      => Mensaje::where('destinatario_id', $this->personajeId)->whereNull('leido_en')->count(),
            // Último mensaje del chat general que no es mío
            'ultimoGeneral' => (int) Mensaje::general()->where('personaje_id', '!=', $this->personajeId)->max('id'),
        ]);
    }
}
