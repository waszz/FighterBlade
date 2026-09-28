<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasinoTirada extends Model
{
    protected $table = 'casino_tiradas';

    protected $fillable = ['personaje_id', 'rodillos', 'moneda', 'apuesta', 'premio', 'especial'];

    protected $casts = [
        'rodillos' => 'array',
        'especial' => 'array',
    ];

    // Formato que usa la vista del casino
    public function paraVista(): array
    {
        return [
            'rodillos' => $this->rodillos,
            'neto'     => $this->premio - $this->apuesta,
            'icono'    => $this->moneda === 'oro' ? '🪙' : '💚',
            'especial' => $this->especial,
        ];
    }
}
