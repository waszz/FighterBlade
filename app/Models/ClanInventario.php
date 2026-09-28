<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClanInventario extends Model
{
    protected $table = 'clan_inventario';

    protected $fillable = ['clan_id', 'objeto_id', 'cantidad'];

    public function objeto()
    {
        return $this->belongsTo(Objeto::class, 'objeto_id');
    }

    public function clan()
    {
        return $this->belongsTo(Clan::class, 'clan_id');
    }
}
