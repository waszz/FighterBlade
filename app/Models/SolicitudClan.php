<?php

// app/Models/SolicitudClan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudClan extends Model
{
    protected $fillable = ['usuario_id', 'clan_id', 'estado'];

    public function usuario() {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function clan() {
        return $this->belongsTo(Clan::class);
    }
}
