{{-- 💰 El Comerciante Khonshu (ver App\Support\Comerciante): saludo y partes en venta. Recibe $oferta --}}
@php
    $abrev = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'velocidad' => 'VEL', 'resistencia' => 'RES', 'defensa' => 'DEF', 'energia' => 'ENE'];
    $colores = ['equipo' => ['border-indigo-500', 'text-indigo-300'], 'entrenamiento' => ['border-green-500', 'text-green-300'], 'accesorio' => ['border-pink-500', 'text-pink-300']];
    $quedan = collect($oferta['partes'] ?? [])->where('comprada', false)->count();
@endphp
<div class="mx-auto mb-3 w-full max-w-3xl rounded-xl border-2 border-amber-500 p-3 text-left bg-gradient-to-b from-[#2a1d0a] to-[#0f0a04]
            shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),0_4px_0_#000,0_0_18px_rgba(245,158,11,0.3)]">
    {{-- Lo que dice va en el globo de arriba, en la ciudad --}}
    {{-- Partes (como en el Mercado) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        @foreach ($oferta['partes'] ?? [] as $i => $parte)
            @php [$borde, $texto] = $colores[$parte['tipo']] ?? ['border-black', 'text-gray-300']; @endphp
            <div wire:key="comerciante-{{ $i }}" class="flex flex-col items-center gap-1 p-2 rounded-lg text-center border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b]
                        shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] {{ $parte['comprada'] ? 'opacity-50' : '' }}">
                <img src="{{ asset('storage/posts/' . $parte['imagen']) }}" alt="{{ $parte['nombre'] }}" loading="lazy"
                     class="w-14 h-14 object-cover rounded-md bg-black/50 border-2 {{ $borde }} shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_3px_0_#000]">
                <p class="text-[10px] font-bold uppercase {{ $texto }}">{{ ucfirst($parte['tipo']) }}</p>
                <p class="w-full text-xs font-bold text-white truncate" title="{{ $parte['nombre'] }}">{{ $parte['nombre'] }}</p>
                <p class="text-[10px] text-gray-400 truncate w-full">{{ $parte['set'] }} · Nv {{ $parte['nivel'] }}</p>
                <div class="flex flex-wrap justify-center gap-1">
                    @foreach ($parte['stats'] as $stat => $valor)
                        <span class="border border-black bg-gradient-to-b from-green-600 to-green-900 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000] text-white text-[10px] font-bold px-1.5 rounded">{{ $abrev[$stat] ?? strtoupper(substr($stat, 0, 3)) }} +{{ $valor }}</span>
                    @endforeach
                </div>
                @if ($parte['requisitos'])
                    <p class="text-[10px] text-purple-400 italic">Requisitos: {{ collect($parte['requisitos'])->map(fn ($v, $s) => $v . ' ' . ($abrev[$s] ?? strtoupper(substr($s, 0, 3))))->join(', ') }}</p>
                @endif
                @if ($parte['comprada'])
                    <span class="mt-auto w-full py-1 rounded-lg text-xs font-bold text-emerald-300 bg-black/40 border border-emerald-700">Comprada</span>
                @else
                    <button type="button" wire:click="comprarAlComerciante({{ $i }})" wire:loading.attr="disabled"
                            class="mt-auto w-full py-1 rounded-lg border border-black text-xs font-bold text-white bg-gradient-to-b from-green-500 to-green-800 flex items-center justify-center gap-1
                                   shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all disabled:opacity-50">
                        Comprar · <img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-3.5 w-3.5"><span class="text-yellow-200">{{ number_format($parte['precio'], 0, ',', '.') }}</span>
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Despedirse: lo que no compraste se pierde --}}
    <div class="mt-3 flex justify-center">
        @if ($quedan)
            <x-confirmar titulo="¿Despedirte del comerciante?" accion="despedirComerciante" boton="Despedirme" color="from-amber-500 to-amber-800"
                texto="Las partes que no compraste se van con él.">
                <button type="button" class="px-4 py-1.5 rounded-lg border border-black text-sm font-bold text-white bg-gradient-to-b from-gray-600 to-gray-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Despedirse y seguir</button>
            </x-confirmar>
        @else
            <button type="button" wire:click="despedirComerciante" class="px-4 py-1.5 rounded-lg border border-black text-sm font-bold text-white bg-gradient-to-b from-amber-500 to-amber-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Seguir explorando</button>
        @endif
    </div>
</div>
