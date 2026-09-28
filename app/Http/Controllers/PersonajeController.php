<?php

namespace App\Http\Controllers;

use App\Models\Personaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonajeController extends Controller
{
    public function create()
    {
        return view('personajes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
            'tipo' => 'required|string|max:50',
        ]);

        Personaje::create([
            'user_id' => Auth::id(),
            'nombre' => $request->nombre,
            'tipo' => $request->tipo,
        ]);

        return redirect()->route('personajes.elegir')->with('success', '¡Personaje creado exitosamente!');
    }

public function update(Request $request, Personaje $personaje)
{
    $validated = $request->validate([
        'nivel' => 'required|integer|min:1',
        'oro' => 'required|integer|min:0',
        'diamante' => 'required|integer|min:0',
        'stats' => 'required|array',
        'stats.fuerza' => 'required|integer|min:0',
        'stats.ataque' => 'required|integer|min:0',
        'stats.defensa' => 'required|integer|min:0',
        'stats.velocidad' => 'required|integer|min:0',
        'stats.resistencia' => 'required|integer|min:0',
        'stats.energia' => 'required|integer|min:0',
    ]);

    $personaje->nivel = $validated['nivel'];
    $personaje->oro = $validated['oro'];
    $personaje->diamante = $validated['diamante'];
    $personaje->stats = $validated['stats'];
    $personaje->save();

    return back()->with('success', 'Stats actualizados correctamente.');
}
}