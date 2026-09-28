<?php

namespace App\Models;

use App\Models\Post;
use App\Models\User;
use App\Models\Comentario;
use App\Models\CommentNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comentario extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'comentario',
        'parent_id',
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

public function respuestas()
{
    return $this->hasMany(Comentario::class, 'parent_id')->with('respuestas');
}

public function padre()
{
    return $this->belongsTo(Comentario::class, 'parent_id');
}
public function post()
{
    return $this->belongsTo(Post::class);
}
public function commentNotifications()
{
    return $this->hasMany(CommentNotification::class);
}
}