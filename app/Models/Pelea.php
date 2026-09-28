<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pelea extends Model
{
    use HasFactory;

    protected $fillable = [
        'personaje_id',
        'enemigo_id',
        'resultado',
        'exp_ganada',
        'oro_ganado',
        'realizada_en',
        'datos_combate',
        'ciudad_actual',
    ];

    protected $casts = [
        'realizada_en' => 'datetime',
        'datos_combate' => 'array',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class);
    }

    public function enemigo()
    {
        // Incluye a los rivales de misión en el historial
        return $this->belongsTo(Post::class, 'enemigo_id')->withoutGlobalScope(Post::SCOPE_SIN_RIVALES);
    }
}
