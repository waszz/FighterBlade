<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Un jugador anotado en un torneo, con el set que le tocó y sus vidas (ver App\Models\Torneo)
class TorneoParticipante extends Model
{
    protected $table = 'torneo_participantes';
    protected $guarded = [];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class);
    }

    public function personaje()
    {
        return $this->belongsTo(Personaje::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
