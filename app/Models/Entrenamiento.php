<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrenamiento extends Model
{
    protected $table = 'entrenamientos';

    protected $fillable = ['nombre', 'stats'];

    protected $casts = [
        'stats' => 'array',  // Para que Laravel transforme JSON a array automáticamente
    ];
}
