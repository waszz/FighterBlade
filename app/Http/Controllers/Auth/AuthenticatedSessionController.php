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

    // 🚫 Una sola sesión por cuenta: el último login gana. La sesión anterior no se borra: en su próxima
    // petición el middleware SesionUnica ve que ya no es la de la cuenta, la cierra y avisa "te conectaste desde otro lugar"
    $user->last_session_id = $request->session()->getId();
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
