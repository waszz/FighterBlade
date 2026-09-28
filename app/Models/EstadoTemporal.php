<?php

namespace App\Models;

use App\Models\Personaje;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EstadoTemporal extends Model
{
    use HasFactory;
    protected $table = 'estados_temporales';

    protected $fillable = [
        'personaje_id',
        'estado',
        'aplicado_en',
        'expira_en',
        'stats_afectados',
        'porcentaje',
    ];

    protected $casts = [
    'stats_afectados' => 'array',
    'expira_en' => 'datetime',
];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class);
    }
public function estaActivo()
{
    return $this->expira_en && now()->lessThanOrEqualTo($this->expira_en);
}
}
