<?php



use App\Livewire\Clan;
use App\Livewire\Poderes;
use App\Livewire\Explorar;
use App\Livewire\PublicPosts;
use App\Livewire\JuegoInterfaz;
use App\Livewire\ElegirPersonaje;
use Illuminate\Support\Facades\Route;
use App\Livewire\AtacarPersonaje;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\FoooterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SobreMiController;
use App\Http\Controllers\DonacionController;
use App\Http\Controllers\NoticiasController;
use App\Http\Controllers\PersonajeController;
use App\Http\Controllers\ComentarioController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\TodosLosPostsController;


Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', [PostController::class, 'index'] )->middleware(['auth', 'verified'])->name('posts.index');
Route::get('/posts/create', [PostController::class, 'create'] )->middleware(['auth', 'verified'])->name('posts.create');
Route::get('/posts/{post}/edit', [PostController::class, 'edit'] )->middleware(['auth', 'verified'])->name('posts.edit');
Route::get('/posts/{post}', [PostController::class, 'show'] )->name('posts.show');
Route::get('/comentario/{comentario}', [ComentarioController::class, 'mostrarConResaltado'])->name('comentario.resaltado');
Route::get('/sobremi', [SobreMiController::class, 'index'])->name('sobremi.index');
Route::get('/todos-los-posts', [TodosLosPostsController::class, 'index'])->name('todos-los-posts.index');
Route::get('/footer', [FoooterController::class, 'index'])->name('footer.index');


//NOTICIAS
Route::get('/news', [NoticiasController::class, 'index'] )->middleware(['auth', 'verified'])->name('news.index');
Route::get('/news/create', [NoticiasController::class, 'create'] )->middleware(['auth', 'verified'])->name('news.create');
Route::get('/news/{ciudades}/edit', [NoticiasController::class, 'edit'] )->middleware(['auth', 'verified'])->name('news.edit');
Route::get('/news/{ciudades}', [NoticiasController::class, 'show'] )->name('news.show');

//MASCOTAS 
Route::get('/gatos', PublicPosts::class)->defaults('categoria', 'gato')->name('public.gatos');
Route::get('/perros', PublicPosts::class)->defaults('categoria', 'perro')->name('public.perros');
Route::get('/adoptados', PublicPosts::class)->defaults('estado', 'adoptado')->name('public.adoptados');


//DONAR
Route::get('/donar', [DonacionController::class, 'index'])->name('donar');
Route::get('/donar/mercadopago', [DonacionController::class, 'mercadoPago'])->name('donar.mercadopago');
Route::get('/donar/paypal', [DonacionController::class, 'paypal'])->name('donar.paypal');
Route::get('/donar/transferencia', [DonacionController::class, 'transferencia'])->name('donar.transferencia');
Route::get('/donar/tarjeta', [DonacionController::class, 'tarjeta'])->name('donar.tarjeta');


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
Route::get('/personajes-especiales', \App\Livewire\PersonajesEspeciales::class)->middleware('auth')->name('personajes.especiales');
Route::get('/admin/anuncios', \App\Livewire\AdminAnuncios::class)->middleware('auth')->name('admin.anuncios');
Route::get('/personajes-especiales/{post}/editar', \App\Livewire\EditarEspecial::class)->middleware('auth')->name('personajes.especiales.editar');

require __DIR__.'/auth.php';
