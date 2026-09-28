<?php

namespace App\Http\Controllers;

use App\Models\Caza;
use App\Models\Personaje;

// PvP: atacar a otro jugador. Deja al rival como enemigo actual y lleva a la Ciudad,
// donde se pelea con el mismo combate que al explorar (rondas, poderes, premio y registro).
class PvpController extends Controller
{
    public function iniciar($personajeId, $objetivoId)
    {
        $personaje = Personaje::with('estadosTemporales')->where('user_id', auth()->id())->findOrFail($personajeId);
        $volver = redirect()->route('juego.mostrar', ['personajeId' => $personaje->id]);

        $objetivo = Personaje::find($objetivoId);
        if (! $objetivo || $objetivo->id === $personaje->id || $objetivo->user_id === $personaje->user_id) {
            return $volver->with('toast_error', 'No podés atacar a ese personaje.');
        }

        // Protección de novatos: hasta terminar las peleas con el enemigo especial no hay PvP
        if ($personaje->esNovato()) {
            return $volver->with('toast_error', 'Primero terminá tu entrenamiento: podés pelear contra otros jugadores desde el nivel ' . (\App\Livewire\Explorar::NIVEL_MAX_ENEMIGO_ESPECIAL + 1) . '.');
        }
        if ($objetivo->esNovato()) {
            return $volver->with('toast_error', "{$objetivo->nombre} es un jugador nuevo: no se lo puede atacar todavía.");
        }

        // Solo se ataca a jugadores de la misma zona
        if ((int) $objetivo->ciudad_id !== (int) $personaje->ciudad_id) {
            return $volver->with('toast_error', "{$objetivo->nombre} está en otra zona: viajá a " . ($objetivo->ciudadActual?->nombre ?? 'su zona') . ' para atacarlo.');
        }

        if ($motivo = self::motivoBloqueo($personaje)) {
            return $volver->with('toast_error', $motivo);
        }

        $personaje->enemigo_actual_personaje_id = $objetivo->id;
        $personaje->enemigo_actual_id = null;
        $personaje->save();
        session()->forget(['enemigo', 'combate_activo']);
        // La Ciudad ve esta marca y arranca la pelea sola
        session(['pvp_auto_atacar' => $objetivo->id]);

        return $volver->with('toast_success', "¡Desafiaste a {$objetivo->nombre}!");
    }

    // Mismas restricciones que para explorar, cazar o hacer misiones
    public static function motivoBloqueo(Personaje $personaje): ?string
    {
        $estados = $personaje->estadosTemporales->filter(fn ($e) => $e->estaActivo())->pluck('estado');
        foreach (['Aturdido', 'Congelado', 'Paralizado'] as $estado) {
            if ($estados->contains($estado)) {
                return "No podés pelear porque estás $estado.";
            }
        }
        if ($personaje->viajando_hasta && now()->lt($personaje->viajando_hasta)) {
            return 'Estás viajando. Peleá cuando llegues.';
        }
        if ($personaje->fin_exploracion && now()->lt($personaje->fin_exploracion)) {
            return $personaje->exploracion_duracion > 0
                ? 'Estás explorando. Terminá la exploración antes de pelear.'
                : 'Te estás recuperando. Esperá para pelear.';
        }
        if ($personaje->enemigo_actual_id || $personaje->enemigo_actual_personaje_id || $personaje->mision_activa_id
            || (session('combate_activo') === true && session()->has('enemigo'))) {
            return 'Terminá tu combate actual antes de pelear.';
        }
        if (Caza::activaDe($personaje->id)) {
            return 'Tenés una caza en curso. Terminala antes de pelear.';
        }

        return null;
    }
}
