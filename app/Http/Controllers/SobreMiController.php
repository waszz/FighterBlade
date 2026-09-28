<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SobreMiController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index()
    {
        return view('sobremi.index');
    }
}
