<?php

namespace App\Models;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;

class PostImagen extends Model
{
    protected $table = 'post_imagenes'; // Aquí corriges el nombre esperado

    protected $fillable = ['ruta', 'post_id'];

public function post()
{
    return $this->belongsTo(Post::class);
}
}
