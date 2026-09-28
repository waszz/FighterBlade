<div class="relative">
    <button wire:click="toggle" class="relative focus:outline-none top-1">
        <!-- Icono de campanita -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
          </svg>
          
          
        @if(count($notificaciones) > 0)
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                {{ count($notificaciones) }}
            </span>
        @endif
    </button>

    <!-- Panel de notificaciones con animación Tailwind -->
    <div
        class="transition-all duration-300 ease-out transform
               {{ $show ? 'opacity-100 scale-100 visible' : 'opacity-0 scale-95 invisible' }}
               absolute top-full right-0 mt-2 w-80 bg-white shadow-lg rounded-lg z-50 border border-gray-300"
    >
        @forelse($notificaciones as $notificacion)
            <button wire:click="marcarComoLeidoYRedirigir({{ $notificacion->id }})"
                    class="block w-full text-left px-4 py-2 hover:bg-gray-100">
                <p class="text-sm text-gray-700">Tienes una nueva respuesta a tu comentario.</p>
            </button>
        @empty
            <p class="px-4 py-2 text-sm text-gray-500">No tienes notificaciones nuevas.</p>
        @endforelse
    </div>
</div>