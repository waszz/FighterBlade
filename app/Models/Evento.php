<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Evento del calendario cargado por el admin (ver App\Livewire\Calendario)
class Evento extends Model
{
    protected $table = 'eventos';
    protected $fillable = ['titulo', 'tipo', 'descripcion', 'inicio', 'fin'];
    protected $casts = ['inicio' => 'datetime', 'fin' => 'datetime'];

    // Tipos: ícono (Font Awesome) y color. Los primeros dos también los usan los eventos automáticos
    const TIPOS = [
        'mercado'       => ['nombre' => 'Mercado',       'icono' => 'fa-store',            'color' => 'text-amber-400'],
        'buff'          => ['nombre' => 'Buff global',   'icono' => 'fa-bolt',             'color' => 'text-emerald-400'],
        'torneo'        => ['nombre' => 'Torneo',        'icono' => 'fa-trophy',           'color' => 'text-yellow-300'],
        'jefe'          => ['nombre' => 'Jefe / evento', 'icono' => 'fa-skull',            'color' => 'text-rose-400'],
        'doble'         => ['nombre' => 'Doble premio',  'icono' => 'fa-gift',             'color' => 'text-fuchsia-400'],
        'mantenimiento' => ['nombre' => 'Mantenimiento', 'icono' => 'fa-screwdriver-wrench', 'color' => 'text-sky-400'],
        'otro'          => ['nombre' => 'Otro',          'icono' => 'fa-star',             'color' => 'text-violet-300'],
    ];
}
