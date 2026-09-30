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

    // Las pociones traen 5 usos (5/5), menos las de Oro, Esmeraldas y Búsqueda (drop), que se gastan de una
    const USOS_POCION = 5;
    const POCIONES_DE_UN_USO = ['oro', 'diamante', 'drop_partes'];

    // Stats de una poción con sus usos: si trae menos de 5 usos totales se completan (1/1 → 5/5; una a medio usar
    // de 3/5 que se vende en el mercado queda como está)
    public static function conUsosDePocion(array $stats): array
    {
        if (in_array($stats['afecta'] ?? '', self::POCIONES_DE_UN_USO, true)) {
            return $stats;
        }
        $totales = (int) ($stats['usos_totales'] ?? 1);
        if ($totales < self::USOS_POCION) {
            $stats['usos_restantes'] = (int) ($stats['usos_restantes'] ?? $totales) + (self::USOS_POCION - $totales);
            $stats['usos_totales']   = self::USOS_POCION;
        }
        return $stats;
    }

    protected static function booted()
    {
        // Toda poción nueva (drop, mercado, casino, cofres, torre) sale con sus 5 usos
        static::creating(function (Objeto $objeto) {
            if ($objeto->pocion || $objeto->tipo === 'pocion') {
                $stats = is_array($objeto->stats) ? $objeto->stats : (json_decode($objeto->stats ?? '[]', true) ?: []);
                $objeto->stats = self::conUsosDePocion($stats);
            }
        });
    }

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
