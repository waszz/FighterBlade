<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Aviso del juego para un personaje (campanita de arriba, ver App\Livewire\AvisosJuego)
class NotificacionJuego extends Model
{
    protected $table = 'notificaciones_juego';

    protected $fillable = ['personaje_id', 'icono', 'mensaje', 'leida'];

    protected $casts = ['leida' => 'boolean'];

    // Los avisos viejos se borran solos
    const DIAS_GUARDADAS = 30;

    public static function avisar(?int $personajeId, string $icono, string $mensaje): void
    {
        if (! $personajeId) {
            return;
        }
        static::create(['personaje_id' => $personajeId, 'icono' => $icono, 'mensaje' => mb_substr($mensaje, 0, 500)]);
        static::where('personaje_id', $personajeId)->where('created_at', '<', now()->subDays(self::DIAS_GUARDADAS))->delete();
    }
}
