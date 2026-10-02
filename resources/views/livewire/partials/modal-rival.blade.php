{{-- Modal del rival de una Misión o de un piso de la Torre (se abre tocando su card). Recibe $rivalModal (Post),
     $escenarioModal (fondo) y $statsRivalModal (los stats con los que pelea) --}}
@if ($rivalModal)
  @php
    $abreviaturasRival = ['fuerza' => 'FUE', 'resistencia' => 'RES', 'ataque' => 'ATA', 'defensa' => 'DEF', 'velocidad' => 'VEL', 'energia' => 'ENE'];
  @endphp
  <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="$set('rivalModalId', null)">
    <div class="relative w-full max-w-xs max-h-[85vh] overflow-auto p-4 rounded-xl border border-black text-white
                bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]"
         wire:click.stop>

      <button wire:click="$set('rivalModalId', null)"
              class="absolute top-2 right-2 z-20 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                     bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                     hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

      {{-- Gif del rival sobre su escenario --}}
      <div class="relative mx-auto mt-5 mb-3 h-32 w-48 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50
                  shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
        @if ($escenarioModal)
          <img src="{{ asset('storage/posts/' . $escenarioModal) }}" alt="" class="absolute inset-0 w-full h-full object-cover object-bottom">
        @endif
        @if ($rivalModal->gif)
          <img src="{{ asset('storage/' . $rivalModal->gif) }}" alt="{{ $rivalModal->titulo }}"
               style="{{ \App\Models\Post::estiloGif($rivalModal->gif, 0.8) }}" class="relative z-10 block max-w-none scale-x-[-1]">
        @endif
      </div>

      <h2 class="text-xl font-bold text-center mb-1 [text-shadow:0_2px_0_#000]">{{ $rivalModal->titulo }}</h2>
      <p class="text-center mb-3">
        <span class="inline-block px-2 py-0.5 rounded-full border border-black text-xs font-bold text-yellow-300 bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]">
          Nivel {{ $rivalModal->nivel }}
        </span>
      </p>

      {{-- Stats con los que pelea --}}
      <div class="font-mono rounded-lg border border-black p-2 space-y-1.5 mb-3 bg-gradient-to-b from-[#2a3240] to-[#10141b]
                  shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
        @foreach ($abreviaturasRival as $stat => $abrev)
          <div class="flex items-center gap-2 select-none text-sm">
            <div class="w-9 h-6 flex items-center justify-center font-bold text-yellow-300 tracking-wide border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">{{ $abrev }}</div>
            <div class="flex-1 font-bold text-white">{{ $statsRivalModal[$stat] ?? 0 }}</div>
          </div>
        @endforeach
      </div>

      {{-- Tipo de daño y poderes: solo iconos (el nombre y la descripción al pasar el mouse o tocar) --}}
      @if ($rivalModal->tipo || $rivalModal->poderes->isNotEmpty())
        <div class="rounded-lg border border-black p-2 bg-gradient-to-b from-[#2a3240] to-[#10141b]
                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
          <div class="flex flex-wrap justify-center gap-2">
            @if ($rivalModal->tipo)
              <x-icono-tipo :tipo="$rivalModal->tipo" tam="w-11 h-11" class="cursor-pointer" />
            @endif
            @foreach ($rivalModal->poderes as $poder)
              <x-icono-poder :poder="$poder" tam="w-11 h-11" class="cursor-pointer" />
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>
@endif
