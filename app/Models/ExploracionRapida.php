<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExploracionRapida extends Model
{
    use HasFactory;

    protected $fillable = ['personaje_id', 'inicio', 'fin'];
    protected $table = 'exploracion_rapida';

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
    ];

    public function estaActiva()
    {
        return now()->between($this->inicio, $this->fin);
    }
public static function activaPara($personajeId)
{
    return self::where('personaje_id', $personajeId)
        ->where('inicio', '<=', now()) // <- esto es clave
        ->where('fin', '>', now())
        ->first();
}

}
