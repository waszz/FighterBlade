<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DonacionController extends Controller
{
    // Página principal de donaciones
    public function index()
    {
        return view('donacion');
    }

    // Método para MercadoPago (puedes integrar SDK aquí)
    public function mercadoPago(Request $request)
    {
        $amount = $request->query('amount');
        return view('donar.mercadopago', compact('amount'));
    }

    // Método para PayPal (puedes redirigir a la API de PayPal aquí)
    public function paypal(Request $request)
    {
        $amount = $request->query('amount');
        return view('donar.paypal', compact('amount'));
    }

    // Método para Transferencia Bancaria (muestra datos bancarios)
    public function transferencia(Request $request)
    {
        $amount = $request->query('amount');
        return view('donar.transferencia', compact('amount'));
    }

    // Método para tarjeta (puedes integrar un formulario con Stripe o MercadoPago)
    public function tarjeta(Request $request)
    {
        $amount = $request->query('amount');
        return view('donaciones.tarjeta', compact('amount'));
    }
}