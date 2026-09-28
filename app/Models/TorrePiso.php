<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Un piso de la Torre: su rival (set normal o especial) y la zona donde se pelea
class TorrePiso extends Model
{
    protected $table = 'torre_pisos';

    protected $fillable = ['piso', 'post_id', 'escenario', 'nivel'];

    // Nivel mínimo del personaje para entrar a la Torre
    const NIVEL_MINIMO = 5;

    public function rival()
    {
        return $this->belongsTo(Post::class, 'post_id')->withoutGlobalScope(Post::SCOPE_SIN_RIVALES);
    }

    // Piso que le toca pelear (el siguiente al más alto superado), o null si ya terminó la torre
    public static function siguientePara(Personaje $personaje): ?self
    {
        return self::where('piso', (int) $personaje->torre_piso + 1)->first();
    }
}
