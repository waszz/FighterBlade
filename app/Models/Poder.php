<?php

namespace App\Models;

use App\Models\Post;
use App\Models\Personaje;
use Illuminate\Database\Eloquent\Model;

class Poder extends Model
{
    protected $table = 'poderes';
    protected $fillable = ['post_id', 'nombre', 'descripcion'];
    protected $casts = [
    'modificadores' => 'array',
];

public function posts()
{
    return $this->belongsToMany(Post::class, 'poder_post', 'poder_id', 'post_id');
}



}