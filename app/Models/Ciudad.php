<?php

namespace App\Models;

use App\Models\Post;
use Tests\Models\User;
use App\Models\Personaje;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ciudad extends Model
{
    use HasFactory;

    protected $table = 'ciudades'; // <- correcto si tu tabla no se llama "ciudads"

    protected $fillable = [
        'nombre',
        'gif',
        'user_id',
        'nivel',
        'variante',
    ];
    
      protected $casts = [
        'stats' => 'array',
    ];

    // Dos zonas por nivel (variante 0 y 1): los sets que se encuentran explorando se reparten entre las dos (por id,
    // alternados), así cada zona tiene sus propios rivales y suelta sus propias partes
    public function setsDeLaZona(\Illuminate\Support\Collection $sets): \Illuminate\Support\Collection
    {
        if (self::where('nivel', $this->nivel)->count() < 2) {
            return $sets;
        }
        $mios = $sets->sortBy('id')->values()->filter(fn ($s, $i) => $i % 2 === (int) $this->variante % 2)->values();
        return $mios->isNotEmpty() ? $mios : $sets;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con posts (enemigos)
    public function posts()
    {
        return $this->hasMany(Post::class, 'ciudad_id');
    }

    // Relación con personajes (habitantes o exploradores)
    public function personajes()
    {
        return $this->hasMany(Personaje::class, 'ciudad_id');
    }
}