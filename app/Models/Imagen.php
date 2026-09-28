<?php

namespace App\Models;

use App\Models\News;
use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    protected $table = 'imagenes';
    protected $fillable = ['news_id', 'ruta'];

    public function news()
    {
        return $this->belongsTo(News::class);
    }
}