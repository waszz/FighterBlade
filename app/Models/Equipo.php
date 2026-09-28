<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $table = 'equipos';

    protected $fillable = ['nombre', 'stats'];

    protected $casts = [
        'stats' => 'array',  // Para que Laravel transforme JSON a array automáticamente
    ];
}
