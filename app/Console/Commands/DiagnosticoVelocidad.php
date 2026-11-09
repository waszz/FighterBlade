<?php

namespace App\Console\Commands;

use App\Livewire\JuegoInterfaz;
use App\Models\Personaje;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// Mide en el servidor dónde se va el tiempo: PHP (OPcache), base de datos, caché y cada sección del juego.
// Uso: php artisan diagnostico:velocidad {personaje_id?}  (sin id usa el primer personaje de un admin)
class DiagnosticoVelocidad extends Command
{
    protected $signature = 'diagnostico:velocidad {personaje?}';
    protected $description = 'Mide cuánto tarda cada sección del juego en el servidor';

    public function handle(): int
    {
        $ms = fn (float $t) => round((microtime(true) - $t) * 1000);

        $opcache = function_exists('opcache_get_status') ? (opcache_get_status(false)['opcache_enabled'] ?? false) : false;
        $this->line('PHP ' . PHP_VERSION . ' | OPcache: ' . ($opcache ? 'sí' : 'NO') . ' | memoria: ' . ini_get('memory_limit'));
        $this->line('APP_DEBUG: ' . (config('app.debug') ? 'SÍ (lento)' : 'no') . ' | sesión: ' . config('session.driver')
            . ' | caché: ' . config('cache.default') . ' | config cacheada: ' . (app()->configurationIsCached() ? 'sí' : 'no'));

        $t = microtime(true);
        for ($i = 0; $i < 20; $i++) {
            DB::select('select 1');
        }
        $this->line('Base de datos: 20 consultas simples en ' . $ms($t) . ' ms');

        $t = microtime(true);
        for ($i = 0; $i < 20; $i++) {
            cache()->get('diagnostico_' . $i);
        }
        $this->line('Caché: 20 lecturas en ' . $ms($t) . ' ms');

        $t = microtime(true);
        \App\Models\Post::estiloGif('posts/x.gif');
        $this->line('Estilos de gifs: ' . $ms($t) . ' ms');

        $pj = $this->argument('personaje')
            ? Personaje::find($this->argument('personaje'))
            : Personaje::whereHas('user', fn ($q) => $q->where('role', 'admin'))->orderBy('id')->first();
        if (! $pj) {
            $this->error('No encontré el personaje');
            return self::FAILURE;
        }
        auth()->login($pj->user);
        $this->line("Personaje: {$pj->nombre} (id {$pj->id})");

        $t = microtime(true);
        $lw = Livewire::test(JuegoInterfaz::class, ['personajeId' => $pj->id]);
        $this->line('Entrar al juego: ' . $ms($t) . ' ms');

        foreach (['inicio', 'viajar', 'inventario', 'mercado', 'ranking', 'clan', 'misiones', 'torre', 'caza', 'mazmorra', 'calendario', 'torneo', 'jefe', 'peleas', 'drops', 'casino'] as $seccion) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $t = microtime(true);
            $lw->call('cambiarSeccion', $seccion);
            $html = $lw->html();
            $tiempo = $ms($t);
            $consultas = DB::getQueryLog();
            $tiempoDb = round(array_sum(array_column($consultas, 'time')));
            $this->line(sprintf('  %-11s %5d ms  (base de datos %4d ms en %3d consultas, %3d KB)', $seccion, $tiempo, $tiempoDb, count($consultas), strlen($html) / 1024));
        }
        DB::disableQueryLog();

        return self::SUCCESS;
    }
}
