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
    ];
    
      protected $casts = [
        'stats' => 'array',
    ];

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