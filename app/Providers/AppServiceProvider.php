<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // En producción todo va por https (si no, las URLs firmadas se generan con http y no validan)
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Vista previa de una imagen recién elegida (crear/editar sets, noticias...) metida en la página como data: URI.
        // En Hostinger la vista previa de Livewire (livewire/preview-file, URL firmada) responde 401
        \Illuminate\Http\UploadedFile::macro('vistaPrevia', function () {
            /** @var \Illuminate\Http\UploadedFile $this */
            try {
                return 'data:' . ($this->getMimeType() ?: 'image/png') . ';base64,' . base64_encode($this->get());
            } catch (\Throwable $e) {
                return '';
            }
        });
    }
}
