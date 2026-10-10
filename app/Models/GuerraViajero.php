<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Un jugador que viajó a la zona de la guerra de clanes (y desde dónde: al terminar vuelve ahí)
class GuerraViajero extends Model
{
    protected $table = 'guerra_viajeros';
    protected $guarded = [];
}
