<?php

namespace App\Models;

use App\Models\Imagen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'contenido',
        'imagen',
        'user_id', // <-- Agregamos esto para que pueda guardarse automáticamente
    ];

    // Relación con el usuario que creó la noticia
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function imagenes()
{
    return $this->hasMany(Imagen::class);
}

}