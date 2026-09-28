<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mision extends Model
{
    protected $table = 'misiones';

    protected $fillable = ['orden', 'post_id', 'escenario', 'recompensa_oro', 'recompensa_diamantes'];

    // Rival de la misión (set oculto, es_enemigo = Post::RIVAL_MISION)
    public function rival()
    {
        return $this->belongsTo(Post::class, 'post_id')->withoutGlobalScope(Post::SCOPE_SIN_RIVALES);
    }

    public function completadaPor(int $personajeId): bool
    {
        return \DB::table('mision_personaje')->where('mision_id', $this->id)->where('personaje_id', $personajeId)->exists();
    }

    // Siguiente misión de la escalera para el personaje (la primera sin completar), o null si terminó todas
    public static function siguientePara(int $personajeId): ?self
    {
        return self::whereNotIn('id', \DB::table('mision_personaje')->where('personaje_id', $personajeId)->select('mision_id'))
            ->orderBy('orden')
            ->first();
    }
}
