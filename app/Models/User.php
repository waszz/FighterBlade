<?php

namespace App\Models;
use App\Models\News;
use App\Models\Post;
use App\Models\Personaje;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nivel',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

  
    

    public function getIsAdminAttribute(): bool
{
    return $this->role === 'admin';
}

public function personaje()
{
    return $this->hasOne(Personaje::class);
}

public function isAdmin()
{
    return $this->role === 'admin';
}

// Ve el oro, las esmeraldas y los stats de los demás en los modales de jugador (admins o cuentas con el permiso;
// se da con: php artisan jugadores:ver-datos {personaje})
public function puedeVerDatosDeOtros(): bool
{
    return $this->isAdmin() || (bool) $this->ver_datos_jugadores;
}
public function personajes()
{
    return $this->hasMany(Personaje::class);
}
}
