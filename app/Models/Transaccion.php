<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Registro de lo que se pasa entre jugadores: compras del mercado e intercambios (ver App\Livewire\AvisosJuego)
class Transaccion extends Model
{
    protected $table = 'transacciones';

    protected $fillable = ['tipo', 'de_personaje_id', 'para_personaje_id', 'detalle'];

    protected $casts = ['detalle' => 'array'];

    public function de()
    {
        return $this->belongsTo(Personaje::class, 'de_personaje_id');
    }

    public function para()
    {
        return $this->belongsTo(Personaje::class, 'para_personaje_id');
    }

    // Lo que entrega un lado, a partir de los ids de objetos y las monedas: {"objetos": [nombres], "oro": n, "diamante": n}
    public static function lado(array $objetos, int $oro = 0, int $diamante = 0): array
    {
        return ['objetos' => array_values($objetos), 'oro' => $oro, 'diamante' => $diamante];
    }

    // Transacciones en las que participó el personaje, las más nuevas primero
    public function scopeDe($query, int $personajeId)
    {
        return $query->where(fn ($q) => $q->where('de_personaje_id', $personajeId)->orWhere('para_personaje_id', $personajeId));
    }
}
