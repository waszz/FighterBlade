<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

class SesionUnica
{
    const MENSAJE = 'Te conectaste desde otro lugar: esta sesión se cerró.';

    /**
     * Una sola sesión por cuenta: si la cuenta inició sesión en otro navegador o dispositivo
     * (users.last_session_id ya no es esta sesión), esta se cierra y vuelve a la portada con el aviso.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $currentSessionId = $request->session()->getId();

            // Sesiones previas a este control, o que se acaban de loguear solas con "Recordarme"
            // (la sesión anterior venció: es un ingreso nuevo, no otro dispositivo): adoptar la actual
            if (!$user->last_session_id || Auth::viaRemember()) {
                $user->last_session_id = $currentSessionId;
                $user->save();
            } elseif ($user->last_session_id !== $currentSessionId) {
                // Solo este dispositivo: logout() cambiaría la clave de "Recordarme" y le cortaría el recordarme al que entró último
                Auth::guard('web')->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                // El aviso queda en la sesión nueva (vacía) y lo muestra la portada
                $request->session()->flash('errors', (new ViewErrorBag)->put('default', new MessageBag(['email' => self::MENSAJE])));
                // Pase de un solo uso (30 min) para volver a jugar acá con un toque, sin la contraseña
                // (este dispositivo ya tenía la sesión abierta): ver AuthenticatedSessionController::retomar
                $pase = \Illuminate\Support\Str::random(40);
                \Illuminate\Support\Facades\Cache::put('retomar-sesion:' . $pase, $user->id, now()->addMinutes(30));
                $request->session()->flash('retomar_sesion', $pase);

                // Peticiones de Livewire/AJAX: 409 → el layout lleva a la portada (ver layouts/app: 'sesion-desplazada')
                if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                    return response()->json(['message' => self::MENSAJE], 409);
                }

                return redirect()->route('home');
            }
        }

        return $next($request);
    }
}
