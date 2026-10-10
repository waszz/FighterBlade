@php
    $panel3d = 'border border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.6)]';
    $caja3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
    $boton3d = 'px-5 py-2 rounded-lg border border-black text-white font-bold bg-gradient-to-b shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100 disabled:opacity-50 disabled:cursor-not-allowed';
    $abrev = ['fuerza' => 'FUE', 'resistencia' => 'RES', 'ataque' => 'ATA', 'defensa' => 'DEF', 'velocidad' => 'VEL', 'energia' => 'ENE'];
    $icono = "<span class=\"inline-block w-7 h-7 bg-current align-[-0.2em]\" style=\"-webkit-mask: url(" . asset('images/iconos/jefe.svg') . ") center / contain no-repeat; mask: url(" . asset('images/iconos/jefe.svg') . ") center / contain no-repeat;\"></span>";
@endphp

<div class="max-w-3xl mx-auto text-white space-y-4">
    <div class="p-4 rounded-xl {{ $panel3d }}">
        <h2 class="text-2xl font-bold text-rose-400 text-center [text-shadow:0_2px_0_#000]">{!! $icono !!} Jefe de la semana</h2>
        <p class="text-center text-sm text-gray-300 mt-1">
            Cambia cada lunes · el próximo en
            <span class="font-bold text-white" x-data="{ fin: {{ $proximo->timestamp }} * 1000, ahora: Date.now() }" x-init="setInterval(() => ahora = Date.now(), 1000)"
                  x-text="(() => { const s = Math.max(0, Math.floor((fin - ahora) / 1000)); const d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60); return (d ? d + 'd ' : '') + h + 'h ' + m + 'm'; })()"></span>
        </p>
        <ul class="mt-3 text-xs text-gray-300 space-y-1 max-w-xl mx-auto list-disc pl-5">
            <li>Pelea a tu mismo nivel, pero es mucho más fuerte que un rival de la Torre.</li>
            <li>Tenés <b class="text-white">{{ \App\Models\JefeSemanal::INTENTOS }} intentos</b> por semana. Para pelearlo tenés que estar en su zona: el viaje es <b class="text-white">gratis</b>.</li>
            <li>Premio: <b class="num-esmeralda">{{ number_format(\App\Models\JefeSemanal::PREMIO_ESMERALDAS, 0, ',', '.') }} esmeraldas</b> y un <b class="text-white">cofre o un anillo</b>.</li>
            <li>En la zona del jefe atacar a otros jugadores <b class="text-white">no da exp</b>.</li>
        </ul>
    </div>

    @if (! $jefe)
        <p class="text-center text-gray-400 italic">No hay jefe esta semana.</p>
    @else
        <div class="p-4 rounded-xl {{ $panel3d }}">
            {{-- El jefe en su zona --}}
            <div class="relative w-full max-w-[650px] mx-auto min-h-[260px] rounded-xl overflow-hidden border-2 border-gray-900 shadow-lg select-none bg-gray-800">
                @if ($jefe->ciudad?->gif)
                    <img src="{{ asset('storage/posts/' . $jefe->ciudad->gif) }}" alt="{{ $jefe->ciudad->nombre }}" class="absolute inset-0 w-full h-full object-cover object-bottom">
                @endif
                <span class="absolute top-2 left-2 z-20 px-2 py-0.5 rounded bg-black/60 text-[11px] font-bold text-yellow-300">Zona: {{ $jefe->ciudad->nombre }}</span>
                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 z-10 scale-x-[-1]">
                    <img src="{{ asset('storage/' . $jefe->post->gif) }}" alt="{{ $jefe->post->titulo }}" style="{{ \App\Models\Post::estiloGif($jefe->post->gif) }}"
                         class="block max-w-none [filter:drop-shadow(0_0_10px_rgba(244,63,94,0.7))]">
                </div>
            </div>

            <div class="mt-3 text-center">
                <p class="text-xl font-extrabold text-rose-300 [text-shadow:0_2px_0_#000]">{{ $jefe->post->titulo }}</p>
                <p class="text-xs text-gray-400">Nivel {{ $personaje->nivel }} (el tuyo)</p>
                <div class="flex flex-wrap justify-center gap-1 mt-2">
                    @foreach ($statsJefe as $stat => $valor)
                        <span class="px-1.5 py-0.5 rounded text-[11px] font-bold {{ $caja3d }}"><span class="text-gray-400">{{ $abrev[$stat] ?? $stat }}</span> {{ $valor }}</span>
                    @endforeach
                </div>
                <div class="flex flex-wrap justify-center items-center gap-1.5 mt-2">
                    <x-icono-tipo :tipo="$jefe->post->tipo ?: 'fisico'" tam="w-9 h-9" class="cursor-pointer" />
                    @foreach ($jefe->post->poderes as $poder)
                        <x-icono-poder :poder="$poder" tam="w-9 h-9" class="cursor-pointer" />
                    @endforeach
                </div>
            </div>

            {{-- Intentos y acciones --}}
            <div class="mt-4 text-center">
                @if ($intento->derrotado)
                    <p class="text-lg font-bold text-emerald-300"><i class="fa-solid fa-crown"></i> ¡Ya lo venciste esta semana!</p>
                @else
                    <p class="text-sm">Intentos:
                        @for ($i = 0; $i < \App\Models\JefeSemanal::INTENTOS; $i++)
                            <i class="fa-solid fa-heart {{ $i < $intento->restantes() ? 'text-red-500' : 'text-gray-600' }}"></i>
                        @endfor
                        <span class="text-gray-300">{{ $intento->restantes() }}/{{ \App\Models\JefeSemanal::INTENTOS }}</span>
                    </p>
                    <div class="mt-3">
                        @if (! $enSuCiudad)
                            <button wire:click="viajar" wire:loading.attr="disabled" class="{{ $boton3d }} from-sky-500 to-sky-800">
                                <i class="fa-solid fa-plane"></i> Viajar a {{ $jefe->ciudad->nombre }} (gratis)
                            </button>
                            @if ($jefe->ciudad->nivel > $personaje->nivel)
                                <p class="text-xs text-red-400 mt-1">Necesitás nivel {{ $jefe->ciudad->nivel }} para viajar a esa zona.</p>
                            @endif
                        @elseif ($intento->restantes() > 0)
                            <button wire:click="pelear" wire:loading.attr="disabled" class="{{ $boton3d }} from-red-500 to-red-800">
                                <i class="fa-solid fa-hand-fist"></i> Pelear contra el jefe
                            </button>
                        @else
                            <p class="text-sm text-red-400">No te quedan intentos. El lunes aparece otro jefe.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
