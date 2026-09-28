<?php

// app/Models/CommentNotification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentNotification extends Model
{
    protected $fillable = ['user_id', 'comentario_id', 'leido'];

    public function comentario()
    {
        return $this->belongsTo(Comentario::class, 'comentario_id');
    }

    protected $casts = [
        'leido' => 'boolean',
    ];
}