<?php
namespace App\Models;

use App\Models\User;
use App\Models\Personaje;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Mensaje del chat: general (destinatario_id null) o privado entre dos personajes.
// tipo: texto | gif (adjunto.ruta) | objeto | pelea (adjunto: lo compartido, ver App\Support\ChatCompartir)
class Mensaje extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'personaje_id',
        'destinatario_id',
        'contenido',
        'tipo',
        'adjunto',
        'leido_en',
    ];

    protected $casts = [
        'adjunto'  => 'array',
        'leido_en' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function personaje()
    {
        return $this->belongsTo(Personaje::class);
    }

    public function destinatario()
    {
        return $this->belongsTo(Personaje::class, 'destinatario_id');
    }

    public function scopeGeneral($query)
    {
        return $query->whereNull('destinatario_id');
    }

    // Conversación privada entre dos personajes (en los dos sentidos)
    public function scopeEntre($query, int $a, int $b)
    {
        return $query->where(fn ($w) => $w
            ->where(fn ($q) => $q->where('personaje_id', $a)->where('destinatario_id', $b))
            ->orWhere(fn ($q) => $q->where('personaje_id', $b)->where('destinatario_id', $a)));
    }
}
