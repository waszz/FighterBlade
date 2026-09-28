<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FighterBlade') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        {{-- Alpine lo trae Livewire 3 (@livewireScripts): no cargarlo aparte, dos copias se pisan y rompen $wire --}}

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('styles')

    </head>
   
    <body class="font-sans antialiased {{ request()->routeIs('juego.mostrar') ? 'overflow-hidden' : '' }}">

      
        <div class="min-h-screen bg-white">
           
            {{-- En la pantalla del juego no va la barra de arriba (logo, Inicio, usuario): el juego ocupa toda la ventana --}}
            @unless (request()->routeIs('juego.mostrar'))
                @include('layouts.navigation')
            @endunless
            
           
            <div class="relative">
                <!-- Contenedor para la sección de usuario con campanita -->
                <div class="absolute bottom-4 right-6  sm:right-4 z-50 mr-24">
                    @yield('usuario-con-campana')
                </div>
            </div>

            <div class=" absolute top-4 right-14 z-50 block sm:hidden">
                @yield('campana-movil')
            </div>
            


            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                       
                    </div>
                </header>
            @endisset
    
            <!-- Page Content -->
            <main>
                
                {{ $slot }}
            </main>
        </div>
        
        {{-- Toasts globales (esquina superior derecha, se van solos) --}}
        <div x-data="{
                toasts: [],
                add(tipo, mensaje) {
                    if (!mensaje) return;
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, tipo, mensaje });
                    setTimeout(() => this.remove(id), 3000);
                },
                remove(id) {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }
             }"
             @toast.window="add($event.detail.tipo, $event.detail.mensaje)"
             class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-xs w-full pointer-events-none">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 translate-x-8"
                     class="relative px-4 py-3 rounded-lg shadow-2xl text-white w-full border-l-4 pointer-events-auto"
                     :class="toast.tipo === 'error' ? 'bg-red-600 border-red-300' : 'bg-green-600 border-green-300'">
                    <div class="flex items-center gap-3 pr-5">
                        <i class="fas text-xl" :class="toast.tipo === 'error' ? 'fa-times-circle' : 'fa-check-circle'"></i>
                        <p class="text-sm font-semibold" x-text="toast.mensaje"></p>
                    </div>
                    <button @click="remove(toast.id)"
                            class="absolute top-1.5 right-2 text-white/70 hover:text-white transition text-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </template>
        </div>

        @livewireScripts

        {{-- Puente: eventos Livewire -> toasts --}}
        <script>
            document.addEventListener('livewire:init', () => {
                const leerMensaje = (payload) => {
                    const d = Array.isArray(payload) ? payload[0] : payload;
                    return typeof d === 'string' ? d : (d?.message ?? null);
                };
                const emitir = (tipo, mensaje) => {
                    if (!mensaje) return;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { tipo, mensaje } }));
                };

                Livewire.on('error', (p) => emitir('error', leerMensaje(p)));
                Livewire.on('success', (p) => emitir('success', leerMensaje(p)));
                Livewire.on('alert', (p) => {
                    const d = Array.isArray(p) ? p[0] : p;
                    emitir(d?.type === 'error' ? 'error' : 'success', leerMensaje(p));
                });
            });
        </script>

        {{-- Aviso guardado para mostrar al cargar la página (ej. no se pudo iniciar un PvP) --}}
        @foreach (['error' => 'toast_error', 'success' => 'toast_success'] as $tipoToast => $claveToast)
            @if (session($claveToast))
                <script>
                    document.addEventListener('DOMContentLoaded', () => setTimeout(() => window.dispatchEvent(new CustomEvent('toast', {
                        detail: { tipo: @js($tipoToast), mensaje: @js(session($claveToast)) }
                    })), 400));
                </script>
            @endif
        @endforeach

        @stack('scripts')
    </body>

{{-- @include('footer.index') --}}
</html>
