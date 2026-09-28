<?php

use App\Livewire\Clan;
use App\Livewire\Poderes;
use App\Livewire\Explorar;
use App\Livewire\JuegoInterfaz;
use App\Livewire\ElegirPersonaje;
use Illuminate\Support\Facades\Route;
use App\Livewire\AtacarPersonaje;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NoticiasController;
use App\Http\Controllers\PersonajeController;
use App\Http\Controllers\ComentarioController;
use App\Http\Controllers\MercadoPagoController;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', [PostController::class, 'index'] )->middleware(['auth', 'verified', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('posts.index');
Route::get('/posts/create', [PostController::class, 'create'] )->middleware(['auth', 'verified', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('posts.create');
Route::get('/posts/{post}/edit', [PostController::class, 'edit'] )->middleware(['auth', 'verified', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('posts.edit');
Route::get('/posts/{post}', [PostController::class, 'show'] )->name('posts.show');
Route::get('/comentario/{comentario}', [ComentarioController::class, 'mostrarConResaltado'])->name('comentario.resaltado');

//NOTICIAS
Route::get('/news', [NoticiasController::class, 'index'] )->middleware(['auth', 'verified', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('news.index');
Route::get('/news/create', [NoticiasController::class, 'create'] )->middleware(['auth', 'verified', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('news.create');
Route::get('/news/{ciudades}/edit', [NoticiasController::class, 'edit'] )->middleware(['auth', 'verified', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('news.edit');
Route::get('/news/{ciudades}', [NoticiasController::class, 'show'] )->name('news.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//PERSONAJES 
Route::middleware(['auth'])->group(function () {
     Route::get('/elegir-personaje', ElegirPersonaje::class)->name('personajes.elegir');
     Route::put('/personajes/{personaje}', [PersonajeController::class, 'update'])->name('personajes.update');

    Route::get('/personajes/crear', [PersonajeController::class, 'create'])->name('personajes.create');
    Route::post('/personajes', [PersonajeController::class, 'store'])->name('personajes.store');
});

Route::middleware('auth')->get('/juego/{personajeId}', JuegoInterfaz::class)->name('juego.mostrar');

//EXPLORAR 
Route::get('/explorar/{personajeId}', Explorar::class)->name('explorar');

//ATACAR

// PvP: deja al rival como enemigo y lleva a la Ciudad (mismo combate que al explorar)
Route::get('/atacar/{personajeId}/{objetivoId}', [\App\Http\Controllers\PvpController::class, 'iniciar'])
    ->middleware('auth')
    ->name('atacar.personaje');

Route::get('/poderes', Poderes::class)->name('poderes');
Route::get('/personajes-especiales', \App\Livewire\PersonajesEspeciales::class)->middleware(['auth', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('personajes.especiales');
Route::get('/admin/anuncios', \App\Livewire\AdminAnuncios::class)->middleware(['auth', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('admin.anuncios');
Route::get('/personajes-especiales/{post}/editar', \App\Livewire\EditarEspecial::class)->middleware(['auth', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->name('personajes.especiales.editar');

require __DIR__.'/auth.php';
