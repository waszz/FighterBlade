<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Los intentos de un jugador contra el jefe de la semana (ver JefeSemanal)
class JefeIntento extends Model
{
    protected $table = 'jefe_intentos';
    protected $guarded = [];
    protected $casts = ['derrotado' => 'boolean', 'en_pelea' => 'boolean'];

    public function jefe()
    {
        return $this->belongsTo(JefeSemanal::class, 'jefe_semanal_id');
    }

    public function restantes(): int
    {
        return max(0, JefeSemanal::INTENTOS - (int) $this->intentos_usados);
    }
}
