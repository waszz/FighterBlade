<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Hostinger está detrás de un proxy/CDN: sin esto Laravel ve http y las URLs firmadas (la vista previa de las
        // imágenes al crear sets, livewire/preview-file) no validan → 401
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\SesionUnica::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

 