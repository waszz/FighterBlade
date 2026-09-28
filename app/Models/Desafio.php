<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Duelo (pelea amistosa) o intercambio entre dos jugadores. Ver App\Livewire\Desafios
class Desafio extends Model
{
    protected $table = 'desafios';

    // Segundos para aceptar o rechazar
    const SEGUNDOS_RESPUESTA = 30;
    // Minutos que dura abierta la ventana de intercambio
    const MINUTOS_INTERCAMBIO = 5;
    // Objetos que puede poner cada uno en un intercambio
    const MAX_OBJETOS = 3;

    protected $fillable = [
        'tipo', 'de_id', 'para_id', 'estado', 'expira_en', 'pelea_id',
        'oferta_de', 'oferta_para', 'listo_de', 'listo_para', 'visto_de',
    ];

    protected $casts = [
        'expira_en'   => 'datetime',
        'oferta_de'   => 'array',
        'oferta_para' => 'array',
        'listo_de'    => 'boolean',
        'listo_para'  => 'boolean',
        'visto_de'    => 'boolean',
    ];

    public function de()
    {
        return $this->belongsTo(Personaje::class, 'de_id');
    }

    public function para()
    {
        return $this->belongsTo(Personaje::class, 'para_id');
    }

    public function vencido(): bool
    {
        return $this->expira_en && now()->gte($this->expira_en);
    }

    public function segundosRestantes(): int
    {
        return $this->expira_en ? max(0, $this->expira_en->timestamp - now()->timestamp) : 0;
    }

    // Oferta vacía de un lado del intercambio
    public static function ofertaVacia(): array
    {
        return ['objetos' => [], 'oro' => 0, 'diamante' => 0];
    }
}
