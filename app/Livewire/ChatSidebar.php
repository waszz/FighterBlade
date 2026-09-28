<?php

namespace App\Livewire;

use Livewire\Component;

class ChatSidebar extends Component
{
    public $minimizado = false;

    public function toggle()
    {
        $this->minimizado = !$this->minimizado;
    }

    public function render()
    {
        return view('livewire.chat-sidebar');
    }
}
