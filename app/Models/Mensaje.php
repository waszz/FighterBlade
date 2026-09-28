<?php
namespace App\Models;

use App\Models\User;
use App\Models\Personaje;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mensaje extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'personaje_id',
        'contenido',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function personaje()
    {
        return $this->belongsTo(Personaje::class);
    }
}