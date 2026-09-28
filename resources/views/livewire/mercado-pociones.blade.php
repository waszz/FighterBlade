<div>
  <div class="pocion-mercado grid grid-cols-2 gap-3 max-w-md mx-auto p-3 bg-gradient-to-b from-[#1c2533] to-[#0a0e14] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] rounded-lg" wire:key="mercado-{{ $reloadMercadoPocion }}">

    @if ($mensajeTemporal)
      <div
          class="fixed top-4 right-4 z-[100] max-w-xs w-full"
          x-data="{ show: true }"
          x-init="setTimeout(() => show = false, 3000)"
          x-show="show"
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0 translate-x-8"
          x-transition:enter-end="opacity-100 translate-x-0"
          x-transition:leave="transition ease-in duration-200"
          x-transition:leave-start="opacity-100 translate-x-0"
          x-transition:leave-end="opacity-0 translate-x-8"
      >
          <div
              class="relative px-4 py-3 rounded-lg shadow-2xl text-white w-full border-l-4
                  {{ $esError ? 'bg-red-600 border-red-300' : 'bg-green-600 border-green-300' }}"
          >
              <div class="flex items-center gap-3 pr-5">
                  <i class="fas {{ $esError ? 'fa-times-circle' : 'fa-check-circle' }} text-xl"></i>
                  <p class="text-sm font-semibold">{{ $mensajeTemporal }}</p>
              </div>

              <button @click="show = false"
                  class="absolute top-1.5 right-2 text-white/70 hover:text-white transition text-sm">
                  <i class="fas fa-times"></i>
              </button>
          </div>
      </div>
    @endif

    @foreach($pociones as $pocion)
      @php
          $stats = is_string($pocion->stats) ? json_decode($pocion->stats, true) : ($pocion->stats ?? []);
          $usosTotales = $stats['usos_totales'] ?? 1;
          $usosRestantes = $stats['usos_restantes'] ?? $usosTotales;
          $moneda = $pocion->moneda ?? 'oro';
          $esBusqueda = $pocion->nombre === 'Poción de Búsqueda';
          $bloqueadaPorNivel = $esBusqueda && $personaje->nivel < 20;
      @endphp

      @continue(!$esBusqueda && $pocion->nombre !== 'Poción de Recuperación')

      <div class="bg-gradient-to-b from-[#2a3240] to-[#10141b] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] rounded-lg p-2.5 flex flex-col items-center text-center space-y-1">
          <h4 class="text-yellow-400 font-bold text-sm">{{ $pocion->nombre }}</h4>
          <img src="{{ asset('images/' . $pocion->imagen) }}" alt="{{ $pocion->nombre }}" class="w-14 h-14 mx-auto mb-1 rounded-md" />
          <p class="text-xs text-white mb-1">Nivel: {{ $pocion->nivel }}</p>
          <p class="text-xs text-pink-400 mb-1">Usos: {{ $usosRestantes }}/{{ $usosTotales }}</p>
          <p class="text-[11px] text-gray-300 italic mb-1">{{ $pocion->descripcion }}</p>

          <div class="flex items-center justify-center gap-1 mb-2 font-semibold text-sm">
              @if($moneda === 'diamante')
                  <span class="text-lg text-center"><img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-4 w-4 align-[-0.2em]"></span>
                  <span class="text-blue-400 text-center">{{ number_format($pocion->precio) }}</span>
              @else
                  <span class="text-xs text-yellow-400 mb-2 mt-2 flex items-center gap-1 justify-center">
                      <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-4 h-4 inline-block" />
                  </span>
                  <span class="text-yellow-400">{{ number_format($pocion->precio) }}</span>
              @endif
          </div> 
<div 
    x-data="{ cantidad: @entangle('cantidades.' . $pocion->id) }"
    class="flex items-center justify-center gap-2 mb-2 w-full max-w-xs mx-auto"
>
    <button 
        type="button"
        class="bg-gradient-to-b from-red-500 to-red-800 text-white font-bold px-2 py-1 rounded border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0"
        @click="cantidad = Math.max(1, cantidad - 1)"
    >−</button>

<input 
    type="number" 
    min="1"
    readonly
    x-model="cantidad"
    class="w-12 text-center rounded bg-gray-900 text-white border border-black text-sm shadow-[inset_0_2px_3px_rgba(0,0,0,0.8),0_1px_0_rgba(255,255,255,0.15)] 
           appearance-none [&::-webkit-outer-spin-button]:appearance-none 
           [&::-webkit-inner-spin-button]:appearance-none 
           [&::-moz-appearance]:textfield"
/>
    <button 
        type="button"
        class="bg-gradient-to-b from-green-500 to-green-800 text-white font-bold px-2 py-1 rounded border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0"
        @click="cantidad++"
    >+</button>
</div>


          @if($bloqueadaPorNivel)
              <button disabled class="bg-gradient-to-b from-gray-500 to-gray-700 border border-black text-white py-1 px-3 rounded w-full text-xs cursor-not-allowed opacity-50 shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6)]">
                  Requiere nivel 20
              </button>
          @else
              <button wire:click="comprarPocion({{ $pocion->id }})" class="bg-gradient-to-b from-green-500 to-green-800 text-white font-bold border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0 py-1 px-3 rounded w-full text-xs">
                  Comprar
              </button>
          @endif
      </div>
    @endforeach

  </div>
</div>
