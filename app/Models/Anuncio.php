<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Anuncio del panel "Anuncios" de la ciudad. Los crean los admins en Administración → Anuncios
class Anuncio extends Model
{
    protected $table = 'anuncios';

    protected $fillable = ['user_id', 'titulo', 'detalle', 'texto', 'llamado', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    // Los que se muestran en el juego (los más nuevos primero; el panel tiene scroll)
    const MAXIMO_EN_PANEL = 10;

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Cuentas que le dieron "me gusta"
    public function likes()
    {
        return $this->belongsToMany(User::class, 'anuncio_likes')->withTimestamps();
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->latest();
    }

    // Foto del autor: la del chat de su personaje más usado (ruta dentro de storage), o null
    public function fotoAutor(): ?string
    {
        return $this->autor?->personajes()->latest('updated_at')->first()?->fotoChat();
    }
}
