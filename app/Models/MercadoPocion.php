<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPocion extends Model
{
    protected $table = 'mercado_pociones';

    protected $fillable = [
        'nombre',
        'tipo',
        'imagen',
        'nivel',
        'requisitos',
        'stats',
        'precio',
        'descripcion',
        'moneda', // si usás moneda 'oro' / 'diamante', agregalo también
    ];

    protected $casts = [
        'requisitos' => 'array',
        'stats' => 'array',
    ];
}
