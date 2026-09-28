<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;
use App\Livewire\MisPosts;


class MisPosts extends Component
{
   


    public function render()
    {
        return view('livewire.mis-posts');
    }
}
