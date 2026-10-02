<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Una pelea de una ronda del torneo (b_id null: "a" pasó la ronda sin pelear). detalle: golpes y daño total
class TorneoPelea extends Model
{
    protected $table = 'torneo_peleas';
    protected $guarded = [];
    protected $casts = ['detalle' => 'array'];

    public function a()
    {
        return $this->belongsTo(TorneoParticipante::class, 'a_id');
    }

    public function b()
    {
        return $this->belongsTo(TorneoParticipante::class, 'b_id');
    }
}
