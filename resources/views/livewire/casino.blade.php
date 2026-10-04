@php
    // HTML de cada símbolo: imagen si tiene, si no el emoji (tamaño = font-size del contenedor)
    $iconoHtml = fn ($s) => isset($s['imagen'])
        ? '<img src="' . asset($s['imagen']) . '" alt="" class="inline-block w-[1em] h-[1em] object-contain align-[-0.15em]">'
        : e($s['icono']);
    $iconos = collect($simbolos)->map($iconoHtml);
@endphp
<div class="relative w-full py-3 px-2 text-white font-mono select-none flex flex-col"
    @saldo-ruleta="saldo.oro = $event.detail.oro"
    x-data="{
        simbolos: @js($iconos),
        apuestas: @js($apuestas),
        saldo: { oro: {{ $oro }}, diamante: {{ $diamante }} },
        moneda: 'oro',
        apuesta: {{ $apuestas['oro'][0] }},
        rodillos: ['diamante', 'diamante', 'diamante'],
        girando: false,
        ganadores: [false, false, false],
        mensaje: '¡Probá tu suerte!',
        tipoMensaje: 'neutro',
        historial: @js($historial),
        historialMax: {{ $historialMax }},
        setGanado: null,
        // Símbolo del set: en el rodillo se ve la cara de un personaje al azar
        carasSets: @js($carasSets),
        carasRodillo: [null, null, null],
        caraAzar() { return this.carasSets.length ? this.carasSets[Math.floor(Math.random() * this.carasSets.length)] : null; },
        iconoRodillo(s, i) {
            if (s !== 'set' || !this.carasRodillo[i]) return this.simbolos[s];
            return `<img src='${this.carasRodillo[i]}' alt='' class='inline-block w-[1.3em] h-[1.3em] object-cover rounded-md border-2 border-yellow-400 shadow-[0_2px_0_#000] align-middle'>`;
        },
        vidas: @js($vidas),
        vidasMax: {{ $vidasMax }},
        segundosVida: {{ $vidaHoras * 3600 }},
        vidasTipos: @js($vidasTipos),
        costoVidaNormal: {{ $costoVidaNormal }},
        costoVida(tipo) { return tipo === 'normal' ? this.costoVidaNormal : this.vidasTipos[tipo].costo; },
        nombreVida(tipo) { return tipo === 'normal' ? 'Normal' : this.vidasTipos[tipo].nombre; },
        tipoVida: 'normal',
        comprandoVida: false,

        vidasDisponibles(tipo) {
            return tipo === 'normal' ? this.vidas.actuales : (this.vidas.compradas[tipo] ?? 0);
        },

        init() {
            setInterval(() => {
                if (this.vidas.siguiente === null) return;
                this.vidas.siguiente--;
                if (this.vidas.siguiente <= 0) {
                    this.vidas.actuales = Math.min(this.vidasMax, this.vidas.actuales + 1);
                    this.vidas.siguiente = this.vidas.actuales < this.vidasMax ? this.segundosVida : null;
                }
            }, 1000);
        },
        reloj(seg) {
            const h = Math.floor(seg / 3600), m = Math.floor(seg % 3600 / 60), s = seg % 60;
            return [h, m, s].map(n => String(n).padStart(2, '0')).join(':');
        },

        cambiarMoneda(m) {
            if (this.girando) return;
            this.moneda = m;
            this.apuesta = this.apuestas[m][0];
        },
        formatear(n) { return Number(n).toLocaleString('es-AR'); },
        azar() {
            const claves = Object.keys(this.simbolos);
            return claves[Math.floor(Math.random() * claves.length)];
        },
        async comprarVida(tipo) {
            if (this.girando || this.comprandoVida) return;
            if (this.saldo.diamante < this.costoVida(tipo)) {
                this.mensaje = 'No tenés suficientes esmeraldas';
                this.tipoMensaje = 'perdio';
                return;
            }
            this.comprandoVida = true;
            const res = await $wire.comprarVida(tipo);
            this.comprandoVida = false;
            if (!res) return;
            this.saldo = { oro: res.oro, diamante: res.diamante };
            this.vidas = res.vidas;
            this.tipoVida = tipo;
            this.mensaje = `¡Compraste 1 vida ${this.nombreVida(tipo)}!`;
            this.tipoMensaje = 'gano';
            Livewire.dispatch('statsActualizados');
        },
        async girar() {
            if (this.girando) return;
            if (this.vidasDisponibles(this.tipoVida) < 1) {
                this.mensaje = this.tipoVida === 'normal' ? 'Sin vidas: esperá la recarga' : `No tenés vidas ${this.vidasTipos[this.tipoVida].nombre}`;
                this.tipoMensaje = 'perdio';
                return;
            }
            if (this.saldo[this.moneda] < this.apuesta) {
                this.mensaje = this.moneda === 'oro' ? 'No tenés suficiente oro' : 'No tenés suficientes esmeraldas';
                this.tipoMensaje = 'perdio';
                return;
            }

            this.girando = true;
            this.ganadores = [false, false, false];
            this.mensaje = 'Girando...';
            this.tipoMensaje = 'neutro';

            const giros = [0, 1, 2].map(i => setInterval(() => {
                this.rodillos[i] = this.azar();
                if (this.rodillos[i] === 'set') this.carasRodillo[i] = this.caraAzar();
            }, 70));
            const res = await $wire.girar(this.moneda, this.apuesta, this.tipoVida);

            if (!res) {
                giros.forEach(clearInterval);
                this.girando = false;
                this.mensaje = '¡Probá tu suerte!';
                return;
            }

            // Frenar cada rodillo de a uno
            for (let i = 0; i < 3; i++) {
                await new Promise(r => setTimeout(r, i === 0 ? 700 : 400));
                clearInterval(giros[i]);
                this.rodillos[i] = res.rodillos[i];
                // Triple set: los 3 rodillos muestran al personaje ganado; set suelto: una cara al azar
                if (res.rodillos[i] === 'set') this.carasRodillo[i] = res.set?.imagen ?? this.caraAzar();
            }

            const apostado = this.apuesta;
            this.saldo = { oro: res.oro, diamante: res.diamante };
            this.vidas = res.vidas;
            const conteo = {};
            res.rodillos.forEach(s => conteo[s] = (conteo[s] || 0) + 1);
            this.ganadores = res.rodillos.map(s => res.premio > 0 && conteo[s] >= 2);

            // Marca que el cartel reemplaza por la imagen de la moneda (oro o esmeralda)
            const icono = this.moneda === 'oro' ? '{oro}' : '{diamante}';
            const tripleSet = res.rodillos.every(s => s === 'set');
            const tripleBuff = res.rodillos.every(s => s === 'buff');
            const tripleRapida = res.rodillos.every(s => s === 'rapida');
            const triplePocion = res.rodillos.every(s => s === 'pocion');
            const tripleVida = res.rodillos.every(s => s === 'vida');
            const tripleCaza = res.rodillos.every(s => s === 'caza');
            const triplePotaEsmeralda = res.rodillos.every(s => s === 'pota_esmeralda');
            const tripleObjeto = res.rodillos.every(s => s === 'cofre') || res.rodillos.every(s => s === 'anillo');
            if (tripleSet || tripleBuff || tripleRapida || triplePocion || tripleVida || tripleCaza || triplePotaEsmeralda || tripleObjeto) this.ganadores = [true, true, true];

            if (tripleObjeto && res.objetoGanado) {
                this.mensaje = `¡GANASTE ${res.objetoGanado.nombre}!`;
                this.tipoMensaje = 'gano';
            } else if (triplePotaEsmeralda && res.potasEsmeralda) {
                this.mensaje = `¡GANASTE ${res.potasEsmeralda} POCIONES DE ESMERALDAS!`;
                this.tipoMensaje = 'gano';
            } else if (tripleCaza && res.cargasCaza) {
                this.mensaje = `¡GANASTE ${res.cargasCaza} CARGAS DE CAZA!`;
                this.tipoMensaje = 'gano';
            } else if (tripleVida && res.vidasGanadas) {
                this.mensaje = `¡GANASTE ${res.vidasGanadas} VIDAS EXTRA!`;
                this.tipoMensaje = 'gano';
            } else if (triplePocion && res.pociones) {
                this.mensaje = `¡GANASTE ${res.pociones.cantidad} POCIONES DE DROP!`;
                this.tipoMensaje = 'gano';
            } else if (triplePocion) {
                this.mensaje = 'No hay pociones disponibles';
                this.tipoMensaje = 'perdio';
            } else if (tripleRapida && res.rapida) {
                this.mensaje = res.rapida.extendida
                    ? `¡EXPLORACIÓN RÁPIDA +${res.rapida.horas} h! (hasta ${res.rapida.fin})`
                    : `¡EXPLORACIÓN RÁPIDA ${res.rapida.horas} h!`;
                this.tipoMensaje = 'gano';
            } else if (tripleBuff && res.buff) {
                this.mensaje = `¡BUFF ${res.buff.tipo} +${res.buff.porcentaje}% por ${res.buff.horas} h!`;
                this.tipoMensaje = 'gano';
            } else if (tripleBuff) {
                this.mensaje = `Buffs al máximo: ¡GANASTE ${this.formatear(res.premio)} ${icono}!`;
                this.tipoMensaje = 'gano';
            } else if (tripleSet && res.set) {
                this.setGanado = res.set;
                this.mensaje = `¡GANASTE EL SET ${res.set.titulo}!`;
                this.tipoMensaje = 'gano';
            } else if (tripleSet) {
                this.mensaje = 'No hay sets disponibles';
                this.tipoMensaje = 'perdio';
            } else if (res.premio > apostado) {
                this.mensaje = `¡GANASTE ${this.formatear(res.premio)} ${icono}!`;
                this.tipoMensaje = 'gano';
            } else if (res.premio === apostado) {
                this.mensaje = 'Par: recuperás tu apuesta';
                this.tipoMensaje = 'neutro';
            } else {
                this.mensaje = `Perdiste ${this.formatear(apostado)} ${icono}`;
                this.tipoMensaje = 'perdio';
            }

            // El servidor ya guardó la tirada en el historial
            this.historial.unshift(res.tirada);
            this.historial = this.historial.slice(0, this.historialMax);
            this.girando = false;

            // Actualizar oro/diamantes del sidebar
            Livewire.dispatch('statsActualizados');
        },
    }">

    {{-- Probabilidades por tirada: al costado en pantallas anchas; en las más chicas, al final (debajo de la máquina) --}}
    <div class="order-last mt-3 w-full mx-auto mb-3 max-w-md min-[1650px]:order-none min-[1650px]:mt-0 min-[1650px]:absolute min-[1650px]:top-3 min-[1650px]:right-3 min-[1650px]:mb-0 min-[1650px]:w-64 relative z-10 rounded border-2 border-[#16203a] bg-black/70 p-3 text-[13px] shadow-[inset_0_0_8px_rgba(0,0,0,0.6),0_0_0_1px_rgba(90,130,200,0.35)]">
        <h3 class="text-center text-sm font-bold uppercase tracking-wide text-yellow-300 mb-2 pb-1.5 border-b border-white/10">Tus chances por tirada</h3>
        @foreach ($probabilidades as $vida => $porMoneda)
            @foreach ($porMoneda as $moneda => $lista)
                <div class="space-y-1" x-show="tipoVida === '{{ $vida }}' && moneda === '{{ $moneda }}'" @if($vida !== 'normal' || $moneda !== 'oro') x-cloak @endif>
                    @foreach ($lista as $prob)
                        @php
                            $esEsp = in_array($prob['clave'] ?? null, $especiales);
                            $x2 = $vida === 'normal' && $moneda === 'diamante' && $esEsp;
                            $mejorado = $vida !== 'normal' && $esEsp;
                        @endphp
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate {{ $x2 ? 'text-blue-300 font-bold' : ($mejorado ? 'text-yellow-300 font-bold' : 'text-gray-300') }}">{!! isset($prob['clave']) ? $iconos[$prob['clave']] : e($prob['icono']) !!} {{ $prob['nombre'] }}@if($x2) <span class="text-[10px] text-blue-200">x2</span>@endif</span>
                            <span class="font-bold {{ $loop->first ? 'text-green-400' : ($x2 ? 'text-blue-300' : ($mejorado ? 'text-yellow-300' : 'text-white')) }}">
                                {{ $prob['valor'] >= 1 ? number_format($prob['valor'], 1, ',', '.') : number_format($prob['valor'], 3, ',', '.') }}%
                            </span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endforeach
        <p class="mt-2 pt-1.5 border-t border-white/10 text-center text-[11px]"
            :class="tipoVida !== 'normal' ? 'text-yellow-300 font-bold' : (moneda === 'diamante' ? 'text-blue-300 font-bold' : 'text-gray-400')">
            <span x-text="tipoVida !== 'normal'
                ? 'Vida ' + vidasTipos[tipoVida].nombre + ': ' + (moneda === 'diamante'
                    ? Math.round(100 - (100 - vidasTipos[tipoVida].chance) ** 2 / 100) + '% (💚 x2) de'
                    : vidasTipos[tipoVida].chance + '% de')
                : (moneda === 'diamante' ? '💚 x2 chances de' : 'Jugá con 💚 para x2 chances de')"></span>
            @foreach ($especiales as $clave) {!! $iconos[$clave] !!} @endforeach
        </p>
    </div>

    <div class="relative max-w-md mx-auto">

    {{-- De fondo, a los costados de la máquina: Mai (izquierda) y Sakura (derecha). Solo decoran: no tapan clics.
         En celular no entran al lado de la máquina, así que se ven desde pantallas medianas --}}
    <img src="{{ asset('images/casino/mai.gif') }}" alt="" aria-hidden="true"
         class="pointer-events-none hidden md:block absolute right-full -top-10 lg:-top-24 mr-2 h-[420px] lg:h-[520px] w-auto max-w-none opacity-90 [filter:drop-shadow(0_6px_8px_rgba(0,0,0,0.7))]">
    <img src="{{ asset('images/casino/sakura.gif') }}" alt="" aria-hidden="true"
         class="pointer-events-none hidden md:block absolute left-full top-40 ml-2 h-[220px] lg:h-[260px] w-auto max-w-none opacity-90 [image-rendering:pixelated] [filter:drop-shadow(0_6px_8px_rgba(0,0,0,0.7))]">

    {{-- Título --}}
    <style>
        @keyframes casinoColores {
            from { background-position: 0% 50%; }
            to   { background-position: 200% 50%; }
        }
        .casino-titulo {
            background-image: linear-gradient(90deg, #facc15, #f97316, #ef4444, #ec4899, #a855f7, #3b82f6, #22c55e, #facc15);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: casinoColores 3s linear infinite;
            filter: drop-shadow(0 0 6px rgba(250, 204, 21, 0.45)) drop-shadow(2px 2px 0 #7f1d1d);
        }
    </style>
    {{-- En celular Mai y Sakura van chiquitas a cada costado del título (en pantallas medianas están grandes a los costados de la máquina) --}}
    <div class="flex items-end justify-center gap-1 mb-3">
        <img src="{{ asset('images/casino/mai.gif') }}" alt="" aria-hidden="true" class="md:hidden pointer-events-none h-28 w-auto max-w-none -my-2 [filter:drop-shadow(0_3px_4px_rgba(0,0,0,0.7))]">
        <h2 class="casino-titulo text-center text-3xl sm:text-4xl font-extrabold tracking-[0.2em] sm:tracking-[0.25em]">
            CASINO
        </h2>
        <img src="{{ asset('images/casino/sakura.gif') }}" alt="" aria-hidden="true" class="md:hidden pointer-events-none h-16 w-auto max-w-none [image-rendering:pixelated] [filter:drop-shadow(0_3px_4px_rgba(0,0,0,0.7))]">
    </div>

    {{-- Saldos --}}
    <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
        <div class="flex items-center justify-center gap-1.5 py-1 rounded border-2 border-[#16203a] bg-black/60 shadow-[0_0_0_1px_rgba(90,130,200,0.35)]">
            <img src="{{ asset('images/oro.png') }}" class="h-4" />
            <span class="text-yellow-400 font-bold" x-text="formatear(saldo.oro)"></span>
        </div>
        <div class="flex items-center justify-center gap-1.5 py-1 rounded border-2 border-[#16203a] bg-black/60 shadow-[0_0_0_1px_rgba(90,130,200,0.35)]">
            <img src="{{ asset('images/diamante.png') }}" class="h-4" />
            <span class="font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]" x-text="formatear(saldo.diamante)"></span>
        </div>
    </div>

    {{-- Vidas --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 px-3 py-1.5 rounded border-2 border-[#16203a] bg-black/60 shadow-[0_0_0_1px_rgba(90,130,200,0.35)] text-xs">
        <div class="flex items-center gap-1">
            <span class="font-bold uppercase text-gray-300 mr-1">Vidas</span>
            <template x-for="i in Math.max(vidasMax, vidas.actuales)" :key="i">
                <img src="{{ asset('images/casino-vida.png') }}" alt="Vida" class="h-6 w-6 object-contain transition-all"
                    :class="i <= vidas.actuales ? '' : 'grayscale opacity-30'">
            </template>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-gray-400" x-show="vidas.siguiente !== null">
                +1 en <span class="font-bold text-pink-300" x-text="reloj(vidas.siguiente)"></span>
            </span>
            <span class="text-green-400 font-bold" x-show="vidas.siguiente === null" x-text="vidas.actuales > vidasMax ? '¡+' + (vidas.actuales - vidasMax) + ' extra!' : '¡Vidas llenas!'"></span>

        </div>
    </div>

    {{-- Tipo de vida para girar (normal gratis o compradas con más chance de premio especial) --}}
    @php
        $estilosVida = [
            'normal' => ['borde' => 'border-pink-400 shadow-[0_0_10px_rgba(244,114,182,0.6)]',  'texto' => 'text-pink-300',   'filtro' => ''],
            'comun'  => ['borde' => 'border-green-400 shadow-[0_0_10px_rgba(74,222,128,0.6)]',  'texto' => 'text-green-300',  'filtro' => '[filter:hue-rotate(110deg)]'],
            'super'  => ['borde' => 'border-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.6)]',    'texto' => 'text-sky-300',    'filtro' => '[filter:hue-rotate(200deg)]'],
            'ultra'  => ['borde' => 'border-fuchsia-400 shadow-[0_0_12px_rgba(232,121,249,0.7)]', 'texto' => 'text-fuchsia-300', 'filtro' => '[filter:hue-rotate(280deg)_saturate(1.4)]'],
        ];
    @endphp
    <div class="mb-3 grid grid-cols-4 gap-1.5 text-[11px]">
        @foreach ($estilosVida as $tipo => $estilo)
            <div role="button" @click="if (!girando) tipoVida = '{{ $tipo }}'"
                class="flex flex-col items-center gap-0.5 px-1 pt-1.5 pb-1 rounded-lg border-2 bg-black/60 cursor-pointer transition-all hover:brightness-125"
                :class="tipoVida === '{{ $tipo }}' ? '{{ $estilo['borde'] }}' : 'border-[#16203a]'">
                <div class="relative">
                    <img src="{{ asset('images/casino-vida.png') }}" alt="" class="h-7 w-7 object-contain {{ $estilo['filtro'] }}">
                    <span class="absolute -bottom-1 -right-2 min-w-[1.25rem] px-1 rounded-full bg-black border border-white/30 text-[10px] font-bold text-white text-center"
                        x-text="vidasDisponibles('{{ $tipo }}')"></span>
                </div>
                <span class="font-extrabold uppercase {{ $estilo['texto'] }} [text-shadow:1px_1px_0_#000]">{{ $tipo === 'normal' ? 'Normal' : $vidasTipos[$tipo]['nombre'] }}</span>
                @php $costoTipo = $tipo === 'normal' ? $costoVidaNormal : $vidasTipos[$tipo]['costo']; @endphp
                @if ($tipo === 'normal')
                    <span class="text-[10px] text-gray-400">Se recarga sola</span>
                @else
                    <span class="text-[10px] font-bold text-yellow-300">{{ $vidasTipos[$tipo]['chance'] }}% especial</span>
                @endif
                <button type="button" @click.stop="comprarVida('{{ $tipo }}')" :disabled="girando || comprandoVida"
                    title="Comprar 1 vida {{ $tipo === 'normal' ? 'Normal' : $vidasTipos[$tipo]['nombre'] }} por {{ $costoTipo }} esmeraldas"
                    class="flex items-center gap-0.5 h-5 pl-1 pr-1.5 rounded-full border border-gray-400 bg-gradient-to-b from-gray-600 to-gray-900 shadow-[0_2px_0_#000] hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all disabled:opacity-50">
                    <span class="font-extrabold text-white">+</span>
                    <img src="{{ asset('images/diamante.png') }}" alt="" class="h-3 w-3">
                    <span class="font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">{{ $costoTipo }}</span>
                </button>
            </div>
        @endforeach
    </div>

    {{-- Máquina --}}
    <div class="rounded-xl border-4 border-yellow-600 bg-gradient-to-b from-red-800 via-red-900 to-[#2a0707] p-3 shadow-[inset_0_2px_0_rgba(255,255,255,0.25),0_6px_0_#000,0_0_24px_rgba(234,179,8,0.35)]">

        {{-- Luces --}}
        <div class="flex justify-between px-1 mb-2">
            @for ($i = 0; $i < 9; $i++)
                <span class="w-2 h-2 rounded-full"
                    :class="girando ? 'animate-pulse bg-yellow-300 shadow-[0_0_6px_#fde047]' : 'bg-yellow-700'"
                    style="animation-delay: {{ $i * 120 }}ms"></span>
            @endfor
        </div>

        {{-- Rodillos --}}
        <div class="grid grid-cols-3 gap-2 p-2 rounded-lg bg-black border-2 border-black shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
            <template x-for="(s, i) in rodillos" :key="i">
                <div class="h-20 sm:h-24 flex items-center justify-center rounded-md bg-gradient-to-b from-gray-200 via-white to-gray-300 border-2 transition-all duration-200 shadow-[inset_0_6px_8px_rgba(0,0,0,0.35),inset_0_-6px_8px_rgba(0,0,0,0.35)]"
                    :class="ganadores[i] ? 'border-yellow-400 shadow-[0_0_14px_#facc15] scale-105' : 'border-gray-500'">
                    <span class="text-4xl sm:text-5xl leading-none" :class="girando ? 'blur-[1px]' : ''" x-html="iconoRodillo(rodillos[i], i)"></span>
                </div>
            </template>
        </div>

        {{-- Mensaje --}}
        <div class="mt-3 h-8 flex items-center justify-center rounded bg-black/70 border border-black text-sm font-bold"
            :class="{
                'text-green-400 animate-pulse': tipoMensaje === 'gano',
                'text-red-400': tipoMensaje === 'perdio',
                'text-yellow-200': tipoMensaje === 'neutro',
            }"
            >
            <span x-text="mensaje.replace(/\s*\{(oro|diamante)\}!?/g, '')"></span>
            <img x-show="mensaje.includes('{oro}')" src="{{ asset('images/oro.png') }}" alt="Oro" class="ml-1 h-5 w-5 [filter:drop-shadow(1px_1px_0_#000)]">
            <img x-show="mensaje.includes('{diamante}')" src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="ml-1 h-5 w-5 [filter:drop-shadow(1px_1px_0_#000)]">
            <span x-show="/\{(oro|diamante)\}!/.test(mensaje)">!</span>
        </div>

        {{-- Moneda --}}
        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
            <button type="button" @click="cambiarMoneda('oro')" :disabled="girando"
                class="py-1.5 rounded-md border-2 font-bold uppercase bg-gradient-to-b transition-all shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] active:translate-y-[3px] active:shadow-none disabled:opacity-60"
                :class="moneda === 'oro' ? 'from-yellow-300 to-yellow-600 text-yellow-950 border-yellow-200' : 'from-neutral-700 to-black text-yellow-400 border-black'">
                Jugar con oro
            </button>
            <button type="button" @click="cambiarMoneda('diamante')" :disabled="girando"
                class="py-1.5 rounded-md border-2 font-bold uppercase bg-gradient-to-b transition-all shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] active:translate-y-[3px] active:shadow-none disabled:opacity-60"
                :class="moneda === 'diamante' ? 'from-blue-400 to-blue-700 text-white border-blue-200' : 'from-neutral-700 to-black text-blue-300 border-black'">
                Jugar con esmeraldas <span class="ml-0.5 px-1 rounded bg-black/40 text-[10px] text-yellow-300">x2</span>
            </button>
        </div>

        {{-- Apuesta --}}
        <div class="mt-2 grid grid-cols-4 gap-1.5 text-xs">
            <template x-for="a in apuestas[moneda]" :key="moneda + a">
                <button type="button" @click="if (!girando) apuesta = a" :disabled="girando"
                    class="py-1 rounded border-2 font-bold bg-gradient-to-b transition-all shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000] active:translate-y-[2px] active:shadow-none disabled:opacity-60"
                    :class="apuesta === a ? 'from-green-400 to-green-700 text-black border-green-200' : 'from-gray-600 to-gray-800 text-white border-black'"
                    x-text="formatear(a)"></button>
            </template>
        </div>

        {{-- Botón girar --}}
        <button type="button" @click="girar()" :disabled="girando || vidasDisponibles(tipoVida) < 1"
            class="mt-3 w-full py-2.5 rounded-lg border-2 border-yellow-200 bg-gradient-to-b from-yellow-300 via-yellow-500 to-orange-600 text-red-950 text-lg font-extrabold tracking-[0.3em] shadow-[inset_1px_1px_0_rgba(255,255,255,0.6),0_4px_0_#000,0_0_12px_rgba(250,204,21,0.5)] hover:brightness-110 active:translate-y-[4px] active:shadow-none transition-all disabled:opacity-60 disabled:cursor-not-allowed">
            <span x-text="girando ? '...' : (vidasDisponibles(tipoVida) < 1 ? 'SIN VIDAS' : 'GIRAR')"></span>
        </button>
    </div>

    {{-- Tabla de premios --}}
    <div class="mt-4 rounded border-2 border-[#16203a] bg-black/60 p-2 shadow-[0_0_0_1px_rgba(90,130,200,0.35)]">
        <h3 class="text-center text-sm font-bold uppercase tracking-wide text-yellow-300 mb-2">Premios</h3>
        @php
            $premiosTexto = [
                'pocion' => ['texto' => $pocionesPremio . ' potas drop', 'color' => 'text-purple-300'],
                'set'    => ['texto' => 'Set Nv. ' . $nivelSetMin . '–' . $nivelSetMax, 'color' => 'text-yellow-300'],
                'buff'   => ['texto' => 'Buff ' . $buffPorcentajes['xp'] . '% · ' . $buffHoras . 'h', 'color' => 'text-cyan-300'],
                'rapida' => ['texto' => 'Explo rápida ' . $rapidaHoras . 'h', 'color' => 'text-orange-300'],
                'vida'   => ['texto' => '+' . $vidasPremio . ' vidas', 'color' => 'text-pink-300'],
                'caza'   => ['texto' => '+' . $cazaCargasPremio . ' cargas caza', 'color' => 'text-emerald-300'],
                'pota_esmeralda' => ['texto' => \App\Livewire\Casino::POCIONES_ESMERALDA_PREMIO . ' potas esmeralda', 'color' => 'text-green-300'],
                'cofre'  => ['texto' => 'Cofre Nv. 5–100', 'color' => 'text-amber-300'],
                'anillo' => ['texto' => 'Anillo Nv. 5–100', 'color' => 'text-sky-300'],
            ];
        @endphp
        <div class="grid grid-cols-2 gap-1.5 text-xs">
            @foreach (array_reverse($simbolos, true) as $clave => $simbolo)
                <div class="flex items-center justify-between gap-1 px-2 py-1 rounded border border-white/10 bg-white/5 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]">
                    <span class="{{ in_array($clave, ['pocion', 'pota_esmeralda']) ? 'text-2xl leading-none' : 'text-base' }} tracking-tight whitespace-nowrap">{!! str_repeat($iconos[$clave], 3) !!}</span>
                    @if (isset($premiosTexto[$clave]))
                        <span class="font-bold text-right leading-tight {{ $premiosTexto[$clave]['color'] }}">{{ $premiosTexto[$clave]['texto'] }}</span>
                    @else
                        <span class="font-bold text-green-400">x{{ $simbolo['pago'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-center text-[11px] text-gray-400">2 iguales: x{{ $pagoPar }} (doble de tu apuesta)</p>
        <p class="mt-1 text-center text-[11px] text-gray-400"><img src="{{ asset('images/casino-vida.png') }}" alt="" class="inline-block h-3.5 w-3.5 align-[-0.2em]"> Cada giro gasta 1 vida · se recupera 1 cada {{ $vidaHoras }} h (máx. {{ $vidasMax }})</p>
        <p class="mt-1 text-center text-[11px] text-gray-400">Vidas compradas: {{ collect($vidasTipos)->map(fn ($t) => $t['nombre'] . ' ' . $t['chance'] . '%')->join(' · ') }} de premio especial</p>
    </div>

    {{-- Premio: set ganado --}}
    <div x-show="setGanado" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex flex-col sm:flex-row items-center justify-center gap-1 bg-black/75 px-3" @click.self="setGanado = null">
        {{-- Chun-Li festejando al costado, con su globo de "¡Felicidades!" --}}
        <div class="pointer-events-none relative shrink-0 flex flex-col items-center"
             x-show="setGanado" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 -translate-x-10" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="relative mb-1 px-3 py-1.5 rounded-xl border-2 border-black bg-white text-black text-xs sm:text-sm font-extrabold text-center shadow-[0_3px_0_#000] animate-bounce">
                ¡Felicidades!<br><span class="font-bold text-[10px] sm:text-xs text-pink-600">¡Te ganaste un set!</span>
                <span class="absolute left-1/2 -bottom-2 -translate-x-1/2 w-3 h-3 rotate-45 bg-white border-r-2 border-b-2 border-black"></span>
            </div>
            <img src="{{ asset('images/casino/chunli.gif') }}" alt=""
                 class="h-36 sm:h-72 w-auto max-w-none mt-2 [filter:drop-shadow(0_6px_8px_rgba(0,0,0,0.8))]">
        </div>
        <div class="w-full min-w-0 max-w-xs text-center rounded-xl border-4 border-yellow-500 bg-gradient-to-b from-[#1c2533] to-[#0a0e14] p-4 shadow-[0_0_30px_rgba(234,179,8,0.6)]">
            <img src="{{ asset('images/casino-set.png') }}" alt="Set" class="mx-auto mb-1 h-16 w-16 object-contain" />
            <h3 class="text-lg font-extrabold tracking-widest text-yellow-400 [text-shadow:0_0_8px_rgba(250,204,21,0.7)]">¡SET GANADO!</h3>
            <template x-if="setGanado?.gif">
                <img :src="setGanado.gif" class="mx-auto my-3 max-h-40 rounded" />
            </template>
            <p class="text-base font-bold text-white" x-text="setGanado?.titulo"></p>
            <p class="text-xs text-sky-300 mb-3" x-text="'Nivel ' + setGanado?.nivel"></p>
            <p class="text-[11px] text-gray-400 mb-3">Equipo, entrenamiento y accesorio agregados a tu inventario.</p>
            <button type="button" @click="setGanado = null"
                class="w-full py-1.5 rounded-md border-2 border-green-200 bg-gradient-to-b from-green-400 to-green-700 text-black font-bold shadow-[0_3px_0_#000] active:translate-y-[3px] active:shadow-none">
                Reclamar
            </button>
        </div>
    </div>

    {{-- Últimas tiradas --}}
    <div class="mt-3" x-show="historial.length" x-cloak>
        <h3 class="text-xs font-bold uppercase text-gray-400 mb-1">
            Últimas tiradas <span class="font-normal normal-case text-gray-500" x-text="'(' + historial.length + ')'"></span>
        </h3>
        {{-- Con muchas tiradas aparece el scroll --}}
        <div class="space-y-1 max-h-56 overflow-y-auto pr-1 [scrollbar-width:thin] [scrollbar-color:#4b5563_transparent]">
        <template x-for="(h, i) in historial" :key="i">
            <div class="flex items-center justify-between px-2 py-0.5 rounded bg-black/50 text-xs">
                <span x-html="h.rodillos.map(s => simbolos[s]).join(' ')"></span>
                <span class="font-bold" :class="h.especial ? 'text-yellow-300' : (h.neto > 0 ? 'text-green-400' : (h.neto < 0 ? 'text-red-400' : 'text-gray-300'))">
                    <template x-if="h.especial">
                        <span><span x-html="simbolos[h.especial.clave]"></span> <span x-text="h.especial.texto"></span></span>
                    </template>
                    <template x-if="!h.especial">
                        <span x-text="(h.neto > 0 ? '+' : '') + formatear(h.neto) + ' ' + h.icono"></span>
                    </template>
                </span>
            </div>
        </template>
        </div>
    </div>

    {{-- Ruleta: solo oro, sin vidas, sin límite --}}
    @livewire('casino-ruleta', ['personaje' => \App\Models\Personaje::find($this->personajeId)], key('ruleta-' . $this->personajeId))
    </div>
</div>
