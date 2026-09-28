<?php

namespace App\Http\Controllers\Auth;


use App\Models\Personaje;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Auth\LoginRequest;




class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
     
         return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();

    $user = Auth::user();

    // 🔒 Verifica si el correo está verificado
    if (!$user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }

    // 🚫 Una sola sesión por cuenta: el último login gana y desconecta al anterior
    $currentSessionId = $request->session()->getId();

    if ($user->last_session_id && $user->last_session_id !== $currentSessionId
        && config('session.driver') === 'database') {
        DB::table(config('session.table', 'sessions'))
            ->where('id', $user->last_session_id)
            ->delete();
    }

    // Guardar la sesión actual
    $user->last_session_id = $currentSessionId;
    $user->save();

    // ✅ Redirección según rol
    if ($user->is_admin) {
        return redirect()->route('posts.index');
    }

    // Si ya tiene personaje, entra directo al juego (con el último que usó); si no, a elegir uno
    $personaje = Personaje::where('user_id', $user->id)->latest('updated_at')->first();

    return $personaje
        ? redirect()->route('juego.mostrar', ['personajeId' => $personaje->id])
        : redirect()->route('personajes.elegir');
}


    /**
     * Destroy an authenticated session.
     */
   public function destroy(Request $request): RedirectResponse
{
    $user = Auth::user();

    if ($user) {
        // Limpiar el ID de la sesión activa al cerrar sesión
        $user->last_session_id = null;
        $user->save();
    }

    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
}

}
