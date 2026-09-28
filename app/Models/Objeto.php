<?php

namespace App\Models;

use App\Models\Post;
use App\Models\Personaje;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Objeto extends Model
{
    use HasFactory;

    protected $fillable = [
        'personaje_id',
        'nombre',
        'tipo',
        'nivel',
        'stats',
        'imagen',
        'origen_post_id',
        'requisitos_equipo',
        'requisitos_entrenamiento',
        'requisitos_accesorio',
        'precio_venta',
        'estilo',
        'pocion',
        'descripcion',
        
    ];

    protected $casts = [
        'stats' => 'array',
        'requisitos_equipo' => 'array',
        'requisitos_entrenamiento' => 'array',
        'requisitos_accesorio' => 'array',
        'precio_venta' => 'integer',
        'pocion' => 'boolean',
    ];

    public function personajes()
    {
        return $this->belongsToMany(Personaje::class)->withPivot(['cantidad'])->withTimestamps();
    }
    public function personaje()
{
    return $this->belongsTo(Personaje::class, 'personaje_id');
}
    public function post()
    {
        return $this->belongsTo(Post::class, 'origen_post_id');
    }

    public function getEstiloAttribute()
{
    return $this->post?->tipo ?? $this->tipo ?? 'desconocido';
}
}
