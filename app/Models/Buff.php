<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Buff extends Model
{
    use HasFactory;

    protected $fillable = [
        'personaje_id',
        'tipo',
        'frase',
        'global',
        'costo',
        'inicio',
        'fin',
        'porcentaje',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'global' => 'boolean',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class);
    }

    public function isAdmin()
{
    return $this->role === 'admin';
}
    public function getIsAdminAttribute(): bool
{
    return $this->role === 'admin';
}
}
