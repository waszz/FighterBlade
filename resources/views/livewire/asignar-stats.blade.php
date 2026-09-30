<div class="p-2 rounded-md w-full max-w-sm sm:max-w-[320px] mx-auto text-white text-xs ">
@if($estadosTemporalesActivos->contains('estado', 'Congelado'))
      <div class="text-center text-blue-300 text-xs font-semibold bg-blue-900 bg-opacity-30 p-1.5 rounded mb-2 border border-blue-400">
        <div class="text-blue-200 font-bold text-sm mb-1">❄️ Congelado</div>
        <div class="text-[11px] text-blue-100 mb-0.5">No podés viajar. Sí podés explorar y pelear.</div>
        
        <div class="text-[11px] text-blue-300">
            Tiempo restante: <span id="contador-congelado">--:--</span>
        </div>

        <button wire:click="quitarEstado('Congelado')" class="mt-1 text-yellow-400 underline hover:text-yellow-300 text-[11px]">
            Quitar por 25 esmeraldas
        </button>
    </div>
    @endif

    @if($estadosTemporalesActivos->contains('estado', 'Aturdido'))
    <div class="text-center text-red-200 text-xs font-semibold bg-red-900 bg-opacity-30 p-1.5 rounded mb-2 border border-red-400">
        <div class="text-red-100 font-bold text-sm mb-1">💫 Aturdido</div>
        <div class="text-[11px] text-red-100 mb-0.5">No podés explorar ni pelear. Sí podés viajar.</div>

        <div class="text-[11px] text-red-200">
            Tiempo restante: <span id="contador-aturdido">--:--</span>
        </div>

        <button wire:click="quitarEstado('Aturdido')" class="mt-1 text-yellow-300 underline hover:text-yellow-200 text-[11px]">
            Quitar por 25 esmeraldas
        </button>
    </div>
@endif

@if($estadosTemporalesActivos->contains('estado', 'Envenenado'))
    <div class="text-center text-green-300 text-xs font-semibold bg-green-900 bg-opacity-30 p-1.5 rounded mb-2 border border-green-400">
        <div class="text-green-200 font-bold text-sm mb-1">☠️ Envenenado</div>
        <div class="text-[11px] text-green-100 mb-0.5">Podés hacer todo normalmente.</div>
        
        <div class="text-[11px] text-green-300">
            Tiempo restante: <span id="contador-envenenado">--:--</span>
        </div>

        <button wire:click="quitarEstado('Envenenado')" class="mt-1 text-yellow-400 underline hover:text-yellow-300 text-[11px]">
            Quitar por 25 esmeraldas
        </button>
    </div>
@endif

@if($estadosTemporalesActivos->contains('estado', 'Desangrado'))
    <div class="text-center text-purple-300 text-xs font-semibold bg-purple-900 bg-opacity-30 p-1.5 rounded mb-2 border border-purple-400">
        <div class="text-purple-200 font-bold text-sm mb-1">🩸 Desangrado</div>
        <div class="text-[11px] text-purple-100 mb-0.5">Podés hacer todo normalmente.</div>
        
        <div class="text-[11px] text-purple-300">
            Tiempo restante: <span id="contador-desangrado">--:--</span>
        </div>

        <button wire:click="quitarEstado('Desangrado')" class="mt-1 text-yellow-400 underline hover:text-yellow-300 text-[11px]">
            Quitar por 25 esmeraldas
        </button>
    </div>
@endif

@if($estadosTemporalesActivos->contains('estado', 'Paralizado'))
    <div class="text-center text-yellow-300 text-xs font-semibold bg-yellow-900 bg-opacity-30 p-1.5 rounded mb-2 border border-yellow-400">
        <div class="text-yellow-200 font-bold text-sm mb-1">⚡ Paralizado</div>
        <div class="text-[11px] text-yellow-100 mb-0.5">No podés explorar, pelear ni viajar.</div>
        
        <div class="text-[11px] text-yellow-300">
            Tiempo restante: <span id="contador-paralizado">--:--</span>
        </div>

        <button wire:click="quitarEstado('Paralizado')" class="mt-1 text-yellow-400 underline hover:text-yellow-300 text-[11px]">
            Quitar por 25 esmeraldas
        </button>
    </div>
@endif

@if($estadosTemporalesActivos->contains('estado', 'Quemado'))
    <div class="text-center text-red-400 text-xs font-semibold bg-red-900 bg-opacity-30 p-1.5 rounded mb-2 border border-red-500">
        <div class="text-red-300 font-bold text-sm mb-1">🔥 Quemado</div>
        
        <div class="text-[11px] text-red-400">
            Tiempo restante: <span id="contador-quemado">--:--</span>
        </div>

        <button wire:click="quitarEstado('Quemado')" class="mt-1 text-yellow-400 underline hover:text-yellow-300 text-[11px]">
            Quitar por 25 esmeraldas
        </button>
    </div>
@endif

  @push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOMContentLoaded ejecutado');

    function iniciarContador(id, timestamp) {
        let elem = document.getElementById(id);
        if (!elem || !timestamp) return;

        if (window[`intervalo_${id}`]) clearInterval(window[`intervalo_${id}`]);

        function actualizar() {
            let ahora = Math.floor(Date.now() / 1000);
            let diff = timestamp - ahora;

            if (diff <= 0) {
                elem.textContent = '00:00';
                clearInterval(window[`intervalo_${id}`]);
                return;
            }

            let minutos = Math.floor(diff / 60);
            let segundos = diff % 60;
            elem.textContent = `${minutos.toString().padStart(2,'0')}:${segundos.toString().padStart(2,'0')}`;
        }

        actualizar();
        window[`intervalo_${id}`] = setInterval(actualizar, 1000);
    }

    iniciarContador('contador-congelado', @json($tiempoRestanteCongelado));
    iniciarContador('contador-aturdido', @json($tiempoRestanteAturdido));
    iniciarContador('contador-envenenado', @json($tiempoRestanteEnvenenado));
    iniciarContador('contador-desangrado', @json($tiempoRestanteDesangrado));
    iniciarContador('contador-paralizado', @json($tiempoRestanteParalizado));
    iniciarContador('contador-quemado', @json($tiempoRestanteQuemado));




    Livewire.hook('message.processed', () => {
        iniciarContador('contador-congelado', @json($tiempoRestanteCongelado));
        iniciarContador('contador-aturdido', @json($tiempoRestanteAturdido));
        iniciarContador('contador-envenenado', @json($tiempoRestanteEnvenenado));
        iniciarContador('contador-desangrado', @json($tiempoRestanteDesangrado));
        iniciarContador('contador-paralizado', @json($tiempoRestanteParalizado));
        iniciarContador('contador-quemado', @json($tiempoRestanteQuemado));



    });
});
</script>
@endpush

    <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-white/10 font-mono">
        <h3 class="text-sm font-bold uppercase tracking-wide text-yellow-300">Atributos</h3>
        <span class="px-2 py-0.5 rounded-full border border-pink-500 text-pink-400 text-[11px] font-bold">{{ $puntos_stats }} pts</span>
    </div>

      {{-- DEBUG para comparar stats base y stats totales --}}
    {{-- <pre class="text-xs bg-black bg-opacity-50 p-2 rounded mb-4 overflow-auto" style="max-height:150px;">
        Stats base (sin multiplicar): {{ json_encode($statsBase, JSON_PRETTY_PRINT) }}
        Stats totales (con multiplicadores): {{ json_encode($stats, JSON_PRETTY_PRINT) }}
    </pre> --}}


    @php
        function colorBarraPorStat($valor) {
            if ($valor <= 40) return 'bg-orange-400';
            if ($valor <= 100) return 'bg-green-400';
            if ($valor <= 150) return 'bg-blue-400';
            if ($valor < 200) return 'bg-indigo-400';
            return 'bg-purple-400';
        }

        $abreviaturas = [
            'fuerza' => 'FUE',
            'resistencia' => 'RES',
            'ataque' => 'ATA',
            'defensa' => 'DEF',
            'velocidad' => 'VEL',
            'energia' => 'ENE',
        ];
    @endphp

  

    <div class="space-y-1.5 font-mono">
        {{-- Orden fijo: FUE, RES, ATA, DEF, VEL, ENE --}}
        @foreach (array_merge(array_intersect_key($abreviaturas, $stats), $stats) as $statName => $value)

            @php
                $abreviatura = $abreviaturas[$statName] ?? strtoupper(substr($statName, 0, 3));
                $baseValue = $statsBase[$statName] ?? 0;
                $perdido = $statsPerdidos[$statName] ?? 0;
                // El bonus del equipo sin contar lo que quitan los estados
                $bonus = $value + $perdido - $baseValue;
            @endphp

            <div class="flex items-center gap-2 select-none text-sm cursor-pointer" wire:click="abrirConfirmacionReset">
                <div class="w-8 h-6 flex items-center justify-center border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)] font-bold text-yellow-300 tracking-wide">{{ $abreviatura }}</div>

                <div class="flex-1 flex items-center gap-2">
                    <span class="font-bold text-white {{ $coloresStatsReducidos[$statName] ?? '' }}">+{{ $baseValue }}</span>
                    @if($bonus != 0)
                        <span class="font-bold text-yellow-400">{{ $bonus > 0 ? '+'.$bonus : $bonus }}</span>
                    @endif
                    @if($perdido > 0)
                        <span class="font-bold text-red-500 [text-shadow:1px_1px_0_#000]" title="Reducido por estado">-{{ $perdido }}</span>
                    @endif
                </div>

                <button wire:click.stop="abrirModal('{{ $statName }}')"
                        class="px-2 py-0.5 rounded border border-black bg-gradient-to-b from-green-500 to-green-800 hover:brightness-125 disabled:opacity-50 disabled:active:translate-y-0 text-xs text-white font-bold shadow-[inset_1px_1px_0_rgba(255,255,255,0.4),inset_-1px_-1px_0_rgba(0,0,0,0.5),0_2px_0_#000] active:translate-y-[2px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.5)] transition-all duration-100"
                        @if($puntos_stats <= 0) disabled @endif>+</button>
            </div>
        @endforeach
    </div>
 
    @if(count($stats) === 0)
        <p class="text-center text-gray-400 mt-4">No hay stats para mostrar.</p>
    @endif


{{-- Los modales se mandan al body: dentro del panel del perfil (celular) el panel los recortaba y tapaba --}}
@if ($modalVisible)
    @teleport('body')
    <div class="fixed inset-0 flex items-center justify-center z-[60] bg-black bg-opacity-60  px-2" wire:click.self="cerrarModal">
        <div class="bg-gradient-to-b from-[#1c2533] to-[#0a0e14] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] p-3 rounded-lg w-full max-w-xs space-y-3 text-xs">


            <h2 class="text-sm font-bold text-center text-yellow-400">Asignar Stats</h2>
            <p class="text-[11px] text-blue-300 text-center">Puntos disponibles: {{ $puntos_stats }}</p>

            {{-- Selector de cantidad para sumar --}}
            <div class="flex justify-center gap-1">
                @foreach ([1, 5, 10] as $n)
                    <button wire:click="setCantidadSeleccionada({{ $n }})"
                        class="px-2 py-0.5 rounded bg-gradient-to-b border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] active:translate-y-[2px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 hover:brightness-125 disabled:opacity-50 disabled:active:translate-y-0 {{ $cantidadSeleccionada == $n ? 'from-yellow-400 to-yellow-600 text-black' : 'from-gray-600 to-gray-800 text-white' }}">
                        +{{ $n }}
                    </button>
                @endforeach
            </div>

            {{-- Selector de cantidad para restar --}}
            <div class="flex justify-center gap-1">
                @foreach ([-1, -5, -10] as $n)
                    <button wire:click="setCantidadSeleccionada({{ $n }})"
                        class="px-2 py-0.5 rounded bg-gradient-to-b border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] active:translate-y-[2px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 hover:brightness-125 disabled:opacity-50 disabled:active:translate-y-0 {{ $cantidadSeleccionada == $n ? 'from-red-500 to-red-700 text-white' : 'from-gray-600 to-gray-800 text-white' }}">
                        {{ $n }}
                    </button>
                @endforeach
            </div>

            {{-- Lista de stats --}}
            <div class="space-y-1.5 max-h-48 overflow-y-auto mt-2 pb-1">
                @foreach (array_merge(array_intersect_key($abreviaturas, $statsBase), $statsBase) as $statName => $baseValue)
                    @php
                        $colorBarra = colorBarraPorStat($baseValue);
                        $valorMinimoAbsoluto = $personaje->stats_base[$statName] ?? 5;
                        $valorMinimo = max($valorMinimoAbsoluto, $statsConfirmados[$statName] ?? $valorMinimoAbsoluto);
                        $absCantidad = abs($cantidadSeleccionada);
                        $abreviaturas = [
                             'fuerza' => 'FUE',
                            'resistencia' => 'RES',
                            'ataque' => 'ATA',
                            'defensa' => 'DEF',
                            'velocidad' => 'VEL',
                            'energia' => 'ENE',
                        ];
                        $abrev = $abreviaturas[$statName] ?? strtoupper(substr($statName, 0, 3));
                    @endphp

                    <div class="flex items-center gap-2">
                        {{-- Abreviatura --}}
                        <span class="w-10 h-6 flex items-center justify-center font-bold text-yellow-300 border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">{{ $abrev }}</span>

                        {{-- Barra --}}
                       <div class="h-2.5 bg-gray-900 overflow-hidden rounded-sm border border-black shadow-[inset_0_1px_2px_rgba(0,0,0,0.8),0_1px_0_rgba(255,255,255,0.15)]" style="width: 100px;">
                            <div class="h-full {{ $colorBarra }} rounded transition-all duration-300"
                                style="width: {{ max(0, min($baseValue, 100)) }}%;">
                            </div>
                        </div>

                        {{-- Valor --}}
                        <span class="w-6 text-right font-mono text-white">{{ $baseValue }}</span>

                        {{-- Botón + --}}
                        <button wire:click="asignarDesdeModal('{{ $statName }}')"
                            class="bg-gradient-to-b from-green-400 to-green-700 px-2 py-0.5 rounded text-black font-bold text-[11px] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] active:translate-y-[2px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 hover:brightness-125 disabled:opacity-50 disabled:active:translate-y-0"
                            @if($puntos_stats < $absCantidad || $cantidadSeleccionada <= 0) disabled @endif>
                            +{{ $absCantidad }}
                        </button>

                        {{-- Botón - --}}
                        <button wire:click="asignarDesdeModal('{{ $statName }}')"
                            class="bg-gradient-to-b from-red-500 to-red-800 px-2 py-0.5 rounded text-white font-bold text-[11px] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] active:translate-y-[2px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 hover:brightness-125 disabled:opacity-50 disabled:active:translate-y-0"
                            @if(($statsBase[$statName] ?? 0) - $absCantidad < $valorMinimo || $cantidadSeleccionada >= 0) disabled @endif>
                            -{{ $absCantidad }}
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Botón Guardar --}}
            <button wire:click="cerrarModal"
                class="w-full py-1 bg-gradient-to-b from-green-500 to-green-800 text-white font-bold rounded text-xs border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] active:translate-y-[2px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 hover:brightness-125 disabled:opacity-50 disabled:active:translate-y-0">Guardar</button>
        </div>
    </div>
    @endteleport
@endif

{{-- Modal de reset --}}
@if($confirmandoReset)
    @teleport('body')
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[60] px-2" wire:click.self="$set('confirmandoReset', false)">
        <div class="bg-gradient-to-b from-[#1c2533] to-[#0a0e14] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] text-white p-3 rounded-lg w-full max-w-[280px] space-y-4 text-center mx-auto">
            <h2 class="text-base font-bold">¿Resetear stats?</h2>

            @php
                // 100 de oro por nivel o esmeraldas desde el nivel 5 (ver AsignarStats::costoReset...)
                $costoOro = \App\Livewire\AsignarStats::costoResetOro((int) $personaje->nivel);
                $costoEsmeraldas = \App\Livewire\AsignarStats::costoResetEsmeraldas((int) $personaje->nivel);
                $oroSuficiente = $personaje->oro >= $costoOro;
                $diamantesSuficientes = $personaje->diamante >= $costoEsmeraldas;
            @endphp

            

            {{-- Métodos de pago centrados y uno debajo del otro --}}
            <div class="flex flex-col items-center gap-3 text-[11px]">
                <div class="flex items-center gap-2 px-4 py-2 rounded w-full justify-center bg-gradient-to-b border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.4),inset_-1px_-1px_0_rgba(0,0,0,0.5),0_3px_0_#000] transition-all duration-100
                    {{ $oroSuficiente ? 'from-yellow-300 to-yellow-600 text-yellow-950 cursor-pointer hover:brightness-110 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.5)]' : 'from-yellow-200 to-yellow-400 text-yellow-700 opacity-50 cursor-not-allowed' }}"
                    @if($oroSuficiente) wire:click="resetearStats('oro')" @endif>
                    <img src="{{ asset('images/oro.png') }}" class="h-5 w-5" />
                    <span class="text-sm font-semibold">{{ number_format($costoOro, 0, ",", ".") }} Oro</span>
                </div>

                <div class="flex items-center gap-2 px-4 py-2 rounded w-full justify-center bg-gradient-to-b border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.4),inset_-1px_-1px_0_rgba(0,0,0,0.5),0_3px_0_#000] transition-all duration-100
                    {{ $diamantesSuficientes ? 'from-blue-500 to-blue-800 text-blue-50 cursor-pointer hover:brightness-110 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.5)]' : 'from-blue-300 to-blue-500 text-blue-100 opacity-50 cursor-not-allowed' }}"
                    @if($diamantesSuficientes) wire:click="resetearStats('diamante')" @endif>
                    <img src="{{ asset('images/diamante.png') }}" class="h-5 w-5" />
                    <span class="text-sm font-semibold"><span class="num-esmeralda">{{ $costoEsmeraldas }}</span> {{ $costoEsmeraldas === 1 ? "Esmeralda" : "Esmeraldas" }}</span>
                </div>
            </div>

            <button wire:click="$set('confirmandoReset', false)"
                class="w-full py-1 bg-gradient-to-b from-red-600 to-red-900 text-white rounded text-sm font-semibold mt-4 border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] active:translate-y-[2px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 hover:brightness-125 disabled:opacity-50 disabled:active:translate-y-0">Cancelar</button>
        </div>
    </div>
    @endteleport
@endif


    @push('scripts')
        <script>
            Livewire.on('recargarPagina', () => {
                location.reload();
            });
        </script>
    @endpush
</div>
