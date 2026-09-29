{{-- Entrenamiento con el maestro: 8 horas y al terminar la mitad de la exp del nivel --}}
@php
    $gifPj = $personaje?->postDeCombate()?->gif ?? $personaje?->post?->gif;
    $gifMaestro = $maestro?->gif;
    $fotoMaestro = $maestro?->imagen;
    $expTexto = number_format($expPremio, 0, ',', '.');
    $boton = 'px-8 py-2 rounded-full border-2 border-black text-lg font-extrabold text-orange-400 bg-gradient-to-b from-neutral-800 to-black
              shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),0_4px_0_#000,0_6px_12px_rgba(0,0,0,0.7)] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all [text-shadow:0_2px_0_#000]';
@endphp

<div class="text-white p-2 sm:p-4 max-w-3xl mx-auto" x-data="{ confirmar: false, dejar: false }">
    <h2 class="text-center text-3xl font-extrabold text-orange-400 mb-2 [text-shadow:0_3px_0_#000]">Entrenamiento</h2>

    {{-- Escena: la zona de fondo, mi personaje a la izquierda y el maestro a la derecha --}}
    <div class="relative w-full min-h-[300px] sm:min-h-[340px] rounded-xl overflow-hidden border-2 border-black shadow-[0_6px_16px_rgba(0,0,0,0.7)] select-none"
         @if ($estado === 'entrenando')
             x-data="{ fin: Date.now() / 1000 + {{ $segundos }}, s: {{ $segundos }}, avisado: false }"
             x-init="setInterval(() => { s = Math.max(0, Math.ceil(fin - Date.now() / 1000)); if (s <= 0 && !avisado) { avisado = true; $wire.$refresh() } }, 500)"
         @endif>
        @if ($lugar?->gif)
            <img src="{{ asset('storage/posts/' . $lugar->gif) }}" alt="{{ $lugar->nombre }}" class="absolute inset-0 w-full h-full object-cover object-bottom">
        @endif
        <div class="absolute inset-0 bg-black/15"></div>

        {{-- Mi personaje --}}
        @if ($gifPj)
            <div class="absolute bottom-2 left-[6%] z-10">
                <img src="{{ asset('storage/' . $gifPj) }}" alt="{{ $personaje->nombre }}" style="{{ \App\Models\Post::estiloGif($gifPj) }}"
                     class="block max-w-none {{ $personaje->claseAura() }}">
            </div>
        @endif

        {{-- El maestro --}}
        @if ($gifMaestro)
            <div class="absolute bottom-2 right-[6%] z-10 scale-x-[-1]">
                <img src="{{ asset('storage/' . $gifMaestro) }}" alt="{{ $maestro->titulo }}" style="{{ \App\Models\Post::estiloGif($gifMaestro) }}" class="block max-w-none">
            </div>
        @endif

        {{-- Globo con la frase del maestro --}}
        <div class="absolute top-4 left-1/2 -translate-x-1/2 z-20 w-[min(22rem,88%)]">
            <div class="flex items-center gap-3 px-3 py-2 rounded-2xl border border-white/10 bg-black/75 shadow-[0_4px_12px_rgba(0,0,0,0.6)]">
                @if ($fotoMaestro)
                    <img src="{{ asset('storage/' . $fotoMaestro) }}" alt="" class="w-14 h-14 shrink-0 rounded-full object-cover border-2 border-orange-400">
                @endif
                <div class="min-w-0 text-sm leading-snug">
                    <p class="font-extrabold text-orange-400">{{ $maestro->titulo ?? 'Maestro' }}:</p>
                    @switch($estado)
                        @case('libre')
                            <p>Entrenar lleva {{ \App\Livewire\Entrenar::HORAS }} horas. Cuando termines te doy <b class="text-green-400">{{ $expTexto }} EXP</b>, la mitad de lo que pide tu nivel.</p>
                            @break
                        @case('entrenando')
                            <p>¡No aflojes! Te faltan
                                <b class="font-mono text-yellow-300" x-text="String(Math.floor(s / 3600)).padStart(2, '0') + ':' + String(Math.floor(s % 3600 / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')">{{ gmdate('H:i:s', $segundos) }}</b>.
                            </p>
                            @break
                        @case('terminado')
                            <p>¡Muy bien! Terminaste tu entrenamiento. Acá tenés tu recompensa.</p>
                            @break
                        @case('maximo')
                            <p>Ya llegaste al nivel máximo. No tengo nada más para enseñarte.</p>
                            @break
                    @endswitch
                </div>
            </div>
        </div>

        {{-- Botón / progreso abajo al medio --}}
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex flex-col items-center gap-1">
            @if ($estado === 'libre')
                <button type="button" x-on:click="confirmar = true" class="{{ $boton }}">Entrenar</button>
            @elseif ($estado === 'entrenando')
                <div class="w-56 h-3 rounded-full bg-black/70 border border-black overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-orange-600 to-yellow-400"
                         style="width: {{ round(100 - $segundos / (\App\Livewire\Entrenar::HORAS * 3600) * 100, 1) }}%"
                         :style="'width:' + (100 - s / {{ \App\Livewire\Entrenar::HORAS * 3600 }} * 100) + '%'"></div>
                </div>
                <button type="button" x-on:click="dejar = true"
                        class="text-[11px] text-gray-300 underline hover:text-white [text-shadow:0_1px_0_#000]">Dejar el entrenamiento</button>
            @elseif ($estado === 'terminado')
                <button type="button" wire:click="reclamar" wire:loading.attr="disabled" class="{{ $boton }} !text-green-400">Reclamar +{{ $expTexto }} EXP</button>
            @endif
        </div>
    </div>

    <p class="mt-2 text-center text-xs text-gray-400">Mientras entrenás no podés explorar, atacar, hacer misiones, torre ni caza, ni viajar. Te pueden atacar y podés aceptar duelos e intercambios. La recompensa es siempre la mitad de la experiencia que pide el nivel en el que estás al reclamarla.</p>

    {{-- Aviso antes de empezar: qué se bloquea durante las 8 horas --}}
    <div x-show="confirmar" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-3" x-on:click.self="confirmar = false">
        <div class="relative w-full max-w-sm p-4 rounded-xl border border-black text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
            <h3 class="text-center text-xl font-extrabold text-orange-400 mb-2 [text-shadow:0_2px_0_#000]">¿Empezar a entrenar?</h3>
            <p class="text-sm text-gray-200 text-center">El entrenamiento dura <b class="text-yellow-300">{{ \App\Livewire\Entrenar::HORAS }} horas</b> y al terminar ganás <b class="text-green-400">{{ $expTexto }} EXP</b>.</p>
            <ul class="mt-3 space-y-1 text-xs text-gray-300">
                <li>🚫 No vas a poder explorar, atacar, hacer misiones, torre ni caza, ni viajar.</li>
                <li>✅ Te pueden atacar y podés aceptar duelos e intercambios.</li>
                <li>↩️ Podés dejarlo cuando quieras, pero se pierde lo que llevás.</li>
            </ul>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <button type="button" wire:click="entrenar" x-on:click="confirmar = false" wire:loading.attr="disabled"
                        class="py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-emerald-500 to-emerald-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Aceptar</button>
                <button type="button" x-on:click="confirmar = false"
                        class="py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Cancelar</button>
            </div>
        </div>
    </div>

    {{-- Aviso antes de dejar el entrenamiento --}}
    <div x-show="dejar" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-3" x-on:click.self="dejar = false">
        <div class="relative w-full max-w-xs p-4 text-center rounded-xl border border-black text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
            <h3 class="text-lg font-extrabold text-red-400 [text-shadow:0_2px_0_#000]">¿Dejar el entrenamiento?</h3>
            <p class="mt-1 text-sm text-gray-300">Se pierde lo que llevás y no ganás la experiencia.</p>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <button type="button" wire:click="cancelar" x-on:click="dejar = false"
                        class="py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Sí, dejar</button>
                <button type="button" x-on:click="dejar = false"
                        class="py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-gray-600 to-gray-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Seguir</button>
            </div>
        </div>
    </div>
</div>
