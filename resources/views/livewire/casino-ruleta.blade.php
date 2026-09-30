@php
    // Ruleta: 12 porciones iguales (de 30°) pintadas con un degradé cónico, con el icono de su premio
    $grados = 360 / count($casillas);
    $conica = collect($casillas)->map(fn ($c, $i) => $premios[$c]['color'] . ' ' . ($i * $grados) . 'deg ' . (($i + 1) * $grados) . 'deg')->join(', ');
@endphp

<div class="mt-6 rounded-xl border-4 border-yellow-600 bg-gradient-to-b from-[#3b0a0a] to-[#1a0505] p-3 shadow-[inset_0_0_14px_rgba(0,0,0,0.8),0_4px_0_#000,0_0_18px_rgba(234,179,8,0.25)]"
     x-data="{
        girando: false,
        angulo: 0,
        oro: {{ $oro }},
        costo: {{ $costo }},
        resultado: null,
        async girar() {
            if (this.girando) return;
            if (this.oro < this.costo) { this.resultado = { texto: 'No tenés suficiente oro', imagen: null, premio: 'nada' }; return; }
            this.girando = true;
            this.resultado = null;
            const res = await $wire.girar();
            if (! res) { this.girando = false; return; }
            // Frena con la porción ganadora debajo de la flecha de arriba (5 vueltas + un poco de azar dentro de la porción)
            const g = {{ $grados }};
            const destino = 360 - (res.casilla * g + g / 2) + (Math.random() * g * 0.6 - g * 0.3);
            const actual = ((this.angulo % 360) + 360) % 360;
            this.angulo += 360 * 5 + ((destino - actual + 360) % 360);
            setTimeout(() => {
                this.resultado = res;
                this.oro = res.oro;
                this.girando = false;
                $dispatch('saldo-ruleta', { oro: res.oro });
                Livewire.dispatch('statsActualizados');
            }, 4200);
        },
        formatear(n) { return Number(n).toLocaleString('es-AR'); }
     }">

    <h3 class="text-center text-2xl font-extrabold tracking-[0.2em] text-yellow-400 [text-shadow:0_0_8px_rgba(250,204,21,0.6),2px_2px_0_#7f1d1d]">RULETA</h3>
    <p class="text-center text-[11px] text-gray-300 mb-3">Solo oro · sin vidas · jugás las veces que quieras</p>

    {{-- La ruleta --}}
    <div class="relative mx-auto w-64 h-64 sm:w-72 sm:h-72">
        {{-- Flecha de arriba --}}
        <div class="absolute left-1/2 -top-1 -translate-x-1/2 z-20 w-0 h-0 border-l-[12px] border-r-[12px] border-t-[22px] border-l-transparent border-r-transparent border-t-yellow-300 [filter:drop-shadow(0_2px_0_#000)]"></div>

        <div class="absolute inset-0 rounded-full border-[6px] border-yellow-500 shadow-[0_0_0_3px_#000,0_6px_14px_rgba(0,0,0,0.8),inset_0_0_12px_rgba(0,0,0,0.7)] transition-transform duration-[4000ms] ease-[cubic-bezier(0.17,0.67,0.12,0.99)]"
             :style="`transform: rotate(${angulo}deg); background: conic-gradient({{ $conica }});`"
             style="background: conic-gradient({{ $conica }});">
            @foreach ($casillas as $i => $clave)
                @php $premio = $premios[$clave]; @endphp
                {{-- Cada icono va al centro de su porción, cerca del borde --}}
                <div class="absolute left-1/2 top-1/2 w-0 h-0" style="transform: rotate({{ $i * $grados + $grados / 2 }}deg)">
                    <div class="absolute w-10 -translate-x-1/2 flex flex-col items-center" style="top: -7.1rem">
                        @if ($premio['imagen'])
                            <img src="{{ asset($premio['imagen']) }}" alt="{{ $premio['nombre'] }}" class="w-7 h-7 sm:w-8 sm:h-8 max-w-none object-contain [filter:drop-shadow(0_1px_1px_#000)]">
                            @if ($clave === 'oro_x20')
                                <span class="-mt-1 text-[10px] font-extrabold text-yellow-200 [text-shadow:1px_1px_0_#000]">x{{ $multiplicadorOro }}</span>
                            @elseif ($clave === 'esmeraldas')
                                <span class="-mt-1 text-[10px] font-extrabold text-emerald-200 [text-shadow:1px_1px_0_#000]">x{{ $pocionesEsmeralda }}</span>
                            @endif
                        @else
                            <span class="text-lg text-gray-500 font-bold">✖</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Centro --}}
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-10 w-14 h-14 rounded-full border-4 border-yellow-500 bg-gradient-to-b from-red-600 to-red-900 shadow-[0_3px_0_#000] flex items-center justify-center">
            <img src="{{ asset('images/oro.png') }}" alt="" class="w-7 h-7">
        </div>
    </div>

    {{-- Resultado --}}
    <div class="mt-3 min-h-[2.5rem] flex items-center justify-center gap-2 rounded bg-black/50 px-2 py-1 text-center text-sm font-bold">
        <template x-if="girando"><span class="text-gray-300">Girando...</span></template>
        <template x-if="!girando && resultado">
            <span class="flex items-center gap-2" :class="resultado.premio === 'nada' ? 'text-gray-300' : 'text-yellow-300'">
                <template x-if="resultado.imagen"><img :src="resultado.imagen" alt="" class="w-7 h-7 object-contain"></template>
                <span x-text="resultado.texto"></span>
            </span>
        </template>
        <template x-if="!girando && !resultado"><span class="text-gray-400">¡Girá la ruleta!</span></template>
    </div>

    {{-- Girar --}}
    <button type="button" @click="girar()" :disabled="girando"
            class="mt-3 w-full py-2.5 rounded-lg border-2 border-yellow-200 bg-gradient-to-b from-yellow-300 to-orange-600 text-black text-lg font-extrabold tracking-widest shadow-[inset_0_2px_0_rgba(255,255,255,0.5),0_4px_0_#000] hover:brightness-110 active:translate-y-[4px] active:shadow-none transition-all disabled:opacity-60 disabled:active:translate-y-0 flex items-center justify-center gap-2">
        GIRAR · <img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-5 w-5"> {{ number_format($costo, 0, ',', '.') }}
    </button>

    {{-- Premios y chances --}}
    <div class="mt-3 grid grid-cols-2 gap-1.5 text-xs">
        @foreach ($premios as $clave => $premio)
            @continue($clave === 'nada')
            <div class="flex items-center gap-1.5 rounded bg-black/50 px-2 py-1">
                <img src="{{ asset($premio['imagen']) }}" alt="" class="w-5 h-5 object-contain">
                <span class="flex-1 truncate text-gray-200">
                    @if ($clave === 'oro_x20') x{{ $multiplicadorOro }} de oro ({{ number_format($costo * $multiplicadorOro, 0, ',', '.') }})
                    @else {{ $premio['nombre'] }}
                    @endif
                </span>
                <span class="font-bold text-yellow-300">{{ $premio['peso'] }}%</span>
            </div>
        @endforeach
    </div>
    <p class="mt-1 text-center text-[10px] text-gray-400">Poción normal: una de ataque, defensa, energía, fuerza, resistencia, velocidad o recuperación (al azar)</p>
</div>
