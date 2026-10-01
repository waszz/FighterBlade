{{-- Confirmación con el estilo del juego (en vez del confirm() del navegador).
     El contenido (un botón) abre el cartel; "Confirmar" llama al método de Livewire $accion del componente.
     Uso: <x-confirmar titulo="¿Abandonar?" texto="..." accion="abandonar"><button type="button">Abandonar</button></x-confirmar> --}}
@props(['titulo', 'texto' => null, 'accion', 'boton' => 'Confirmar', 'color' => 'from-red-500 to-red-800'])
<span class="contents" x-data="{ abierto: false, confirmar() { this.abierto = false; this.$wire.call(@js($accion)) } }">
    <span class="contents" x-on:click="abierto = true">{{ $slot }}</span>

    <template x-teleport="body">
        <div x-show="abierto" x-cloak x-transition.opacity x-on:click.self="abierto = false" x-on:keydown.escape.window="abierto = false"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 px-3">
            <div class="w-full max-w-xs p-4 text-center rounded-xl border border-black text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                        shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
                <h3 class="text-lg font-extrabold text-yellow-300 [text-shadow:0_2px_0_#000]">{{ $titulo }}</h3>
                @if ($texto)
                    <p class="mt-1 text-sm text-gray-300">{!! $texto !!}</p>
                @endif
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button type="button" x-on:click="confirmar()"
                            class="py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b {{ $color }} shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">{{ $boton }}</button>
                    <button type="button" x-on:click="abierto = false"
                            class="py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-gray-600 to-gray-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Cancelar</button>
                </div>
            </div>
        </div>
    </template>
</span>
