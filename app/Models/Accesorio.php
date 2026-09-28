<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accesorio extends Model
{
    protected $table = 'accesorios';

    protected $fillable = ['nombre', 'stats'];

    protected $casts = [
        'stats' => 'array',  // Para que Laravel transforme JSON a array automáticamente
    ];
}
