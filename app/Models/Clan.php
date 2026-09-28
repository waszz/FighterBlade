<?php

namespace App\Models;

use App\Models\Personaje;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Clan extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'tag', 'imagen', 'fundador_id', 'prestigio'];

    public function fundador()
    {
        return $this->belongsTo(User::class, 'fundador_id');
    }

    public function personajes()
    {
        return $this->hasMany(Personaje::class);
    }
}
