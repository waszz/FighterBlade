<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SesionUnica
{
    /**
     * Si la cuenta inició sesión en otro lugar, desconecta esta sesión.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $currentSessionId = $request->session()->getId();

            // Sesiones previas a este control: adoptar la actual
            if (!$user->last_session_id) {
                $user->last_session_id = $currentSessionId;
                $user->save();
            } elseif ($user->last_session_id !== $currentSessionId) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Peticiones de Livewire/AJAX: 419 hace que la página se recargue
                if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                    abort(419, 'Tu cuenta inició sesión en otro dispositivo.');
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta inició sesión en otro navegador o dispositivo.',
                ]);
            }
        }

        return $next($request);
    }
}
