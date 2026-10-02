@php
    $panel3d = 'border border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.6)]';
    $caja3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
    $boton3d = 'px-4 py-2 rounded-lg border border-black text-white text-sm font-bold bg-gradient-to-b shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100';
    $abrev = ['fuerza' => 'FUE', 'resistencia' => 'RES', 'ataque' => 'ATA', 'defensa' => 'DEF', 'velocidad' => 'VEL', 'energia' => 'ENE'];
    $dias = ['viernes', 'sábado'];
    // Cuenta regresiva (Alpine) hasta un momento (timestamp en segundos)
    $cuenta = fn ($ts) => "x-data=\"{ fin: {$ts} * 1000, ahora: Date.now() }\" x-init=\"setInterval(() => ahora = Date.now(), 1000)\" x-text=\"(() => { const s = Math.max(0, Math.floor((fin - ahora) / 1000)); const h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60; return (h ? h + 'h ' : '') + String(m).padStart(2, '0') + ':' + String(x).padStart(2, '0'); })()\"";
    $corazones = fn ($vidas) => str_repeat('<i class="fa-solid fa-heart text-red-500"></i>', max(0, $vidas)) . str_repeat('<i class="fa-regular fa-heart text-gray-600"></i>', max(0, \App\Models\Torneo::VIDAS - $vidas));
    $estado = $torneo?->estado;
@endphp

{{-- Mientras hay torneo hoy, se refresca solo (las rondas se juegan cada pocos minutos) --}}
<div class="max-w-3xl mx-auto text-white space-y-4" @if ($torneo && in_array($estado, ['inscripcion', 'en_curso'], true)) wire:poll.10s @endif>

    {{-- Cabecera y reglas --}}
    <div class="p-4 rounded-xl {{ $panel3d }}">
        <h2 class="text-2xl font-bold text-yellow-400 text-center [text-shadow:0_2px_0_#000]"><i class="fa-solid fa-trophy"></i> Torneo</h2>
        <p class="text-center text-sm text-gray-300 mt-1">Viernes y sábados a las {{ \App\Models\Torneo::HORA }}:00</p>
        <ul class="mt-3 text-xs text-gray-300 space-y-1 max-w-xl mx-auto list-disc pl-5">
            <li>Tenés {{ \App\Models\Torneo::MINUTOS_INSCRIPCION }} minutos para anotarte: te toca un <b class="text-white">set al azar</b> (nivel {{ \App\Models\Torneo::NIVEL_SET_MIN }} a {{ \App\Models\Torneo::NIVEL_SET_MAX }}).</li>
            <li>Todos pelean como <b class="text-white">nivel {{ \App\Models\Torneo::NIVEL }}</b> con ese set y sus poderes; cuentan tu joya y tu poción de stat equipadas.</li>
            <li>Las peleas son solas: cada {{ \App\Models\Torneo::MINUTOS_RONDA }} minutos se arman parejas al azar. Tenés <b class="text-white">{{ \App\Models\Torneo::VIDAS }} vidas</b>: cada derrota saca una.</li>
            <li>No se gana exp ni oro en las peleas. El último que queda gana <b class="num-esmeralda">{{ number_format(\App\Models\Torneo::PREMIO_ESMERALDAS, 0, ',', '.') }} esmeraldas</b> y un <b class="text-white">set completo de su nivel</b>.</li>
        </ul>
    </div>

    {{-- Estado --}}
    <div class="p-4 rounded-xl text-center {{ $panel3d }}">
        @if (! $torneo || in_array($estado, ['terminado', 'cancelado'], true))
            <p class="text-sm text-gray-300">Próximo torneo: <b class="text-white">{{ $dias[$proximo->dayOfWeek === \Carbon\Carbon::FRIDAY ? 0 : 1] }} {{ $proximo->format('d/m') }} a las {{ $proximo->format('H:i') }}</b></p>
            <p class="text-3xl font-bold text-yellow-300 mt-1 font-mono" {!! $cuenta($proximo->timestamp) !!}></p>
        @elseif ($estado === 'inscripcion')
            <p class="text-sm text-gray-300">¡Inscripción abierta! Cierra en</p>
            <p class="text-3xl font-bold text-emerald-300 mt-1 font-mono" {!! $cuenta($torneo->finInscripcion()->timestamp) !!}></p>
            @if (! $yo)
                <button wire:click="inscribirme" wire:loading.attr="disabled" class="mt-3 {{ $boton3d }} from-emerald-500 to-emerald-800">Anotarme</button>
            @endif
        @elseif ($estado === 'en_curso')
            <p class="text-sm text-gray-300">Ronda {{ $torneo->ronda }} jugada · la próxima en</p>
            <p class="text-3xl font-bold text-sky-300 mt-1 font-mono" {!! $cuenta($torneo->proxima_ronda_at?->timestamp ?? now()->timestamp) !!}></p>
            <p class="text-xs text-gray-400 mt-1">Quedan {{ $participantes->where('vidas', '>', 0)->count() }} de {{ $participantes->count() }}</p>
        @endif

        {{-- Resultado del último torneo (o del de hoy si ya terminó) --}}
        @if ($mostrar?->estado === 'terminado' && $mostrar->ganador)
            <div class="mt-4 p-3 rounded-lg {{ $caja3d }}">
                <p class="text-xs text-gray-400">{{ $mostrar->fecha->isSameDay(\App\Models\Torneo::ahoraLocal()) ? 'Ganador de hoy' : 'Ganador del último torneo (' . $mostrar->fecha->format('d/m') . ')' }}</p>
                <p class="text-xl font-bold text-yellow-300 [text-shadow:0_2px_0_#000]"><i class="fa-solid fa-crown"></i> <span class="{{ $mostrar->ganador->claseNombre() }}">{{ $mostrar->ganador->nombre }}</span></p>
                <p class="text-xs text-gray-300 mt-1">Premio: <span class="num-esmeralda">{{ number_format(\App\Models\Torneo::PREMIO_ESMERALDAS, 0, ',', '.') }} esmeraldas</span>@if ($mostrar->premio_set) y el set {{ $mostrar->premio_set }}@endif</p>
            </div>
        @elseif ($mostrar?->estado === 'cancelado')
            <p class="mt-3 text-xs text-gray-400">El último torneo ({{ $mostrar->fecha->format('d/m') }}) se canceló: se necesitan al menos 2 jugadores.</p>
        @endif
    </div>

    {{-- Mi set --}}
    @if ($yo && $torneo)
        <div class="p-4 rounded-xl {{ $panel3d }}">
            <h3 class="text-lg font-bold text-yellow-300 text-center mb-3 [text-shadow:0_2px_0_#000]">Tu set del torneo</h3>
            <div class="flex items-center gap-4">
                <img src="{{ asset('storage/' . $yo->post->imagen) }}" alt="{{ $yo->post->titulo }}" class="w-20 h-20 rounded-lg object-cover border-2 border-black shrink-0">
                <div class="min-w-0 flex-1">
                    <p class="font-bold truncate">{{ $yo->post->titulo }} <span class="text-xs text-gray-400">(set nivel {{ $yo->post->nivel }}, pelea como nivel {{ \App\Models\Torneo::NIVEL }})</span></p>
                    <p class="text-sm mt-0.5">{!! $corazones($yo->vidas) !!} <span class="text-xs text-gray-400 ml-1">{{ $yo->victorias }} victorias · {{ $yo->derrotas }} derrotas</span></p>
                    @if ($statsYo)
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach ($statsYo as $stat => $valor)
                                <span class="px-1.5 py-0.5 rounded text-[11px] font-bold {{ $caja3d }}"><span class="text-gray-400">{{ $abrev[$stat] ?? $stat }}</span> {{ $valor }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if ($yo->vidas === 0)
                        <p class="text-xs text-red-400 mt-1">Quedaste afuera en la ronda {{ $yo->eliminado_en_ronda }}.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Participantes --}}
    @if ($participantes->isNotEmpty())
        <div class="p-4 rounded-xl {{ $panel3d }}">
            <h3 class="text-lg font-bold text-yellow-300 text-center mb-3 [text-shadow:0_2px_0_#000]">Participantes ({{ $participantes->count() }})</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach ($participantes as $p)
                    <div class="flex items-center gap-2 p-2 rounded-lg {{ $caja3d }} {{ $p->vidas === 0 ? 'opacity-50' : '' }} {{ $p->personaje_id == $personajeId ? 'ring-2 ring-yellow-400' : '' }}">
                        <img src="{{ asset('storage/' . $p->post->imagen) }}" alt="" class="w-10 h-10 rounded object-cover border border-black shrink-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold truncate"><span class="{{ $p->personaje?->claseNombre() }}">{{ $p->personaje?->nombre }}</span></p>
                            <p class="text-[11px] text-gray-400 truncate">{{ $p->post->titulo }} (Nv {{ $p->post->nivel }})</p>
                        </div>
                        <div class="text-xs shrink-0 text-right">
                            <div>{!! $corazones($p->vidas) !!}</div>
                            <div class="text-gray-400">{{ $p->victorias }}V {{ $p->derrotas }}D</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Rondas (la última arriba). Tocar una pelea muestra sus golpes --}}
    @foreach ($rondas as $numero => $peleas)
        <div class="p-4 rounded-xl {{ $panel3d }}">
            <h3 class="text-base font-bold text-sky-300 mb-2">Ronda {{ $numero }}</h3>
            <div class="space-y-2">
                @foreach ($peleas as $pelea)
                    @if (! $pelea->b_id)
                        <p class="text-xs text-gray-400 italic px-2"><span class="text-white">{{ $pelea->a?->personaje?->nombre }}</span> pasa esta ronda sin pelear.</p>
                    @else
                        @php
                            $d = $pelea->detalle ?? [];
                            $ganoA = $pelea->ganador_id === $pelea->a_id;
                        @endphp
                        <div class="rounded-lg {{ $caja3d }}">
                            <button type="button" wire:click="verPelea({{ $pelea->id }})" class="w-full flex items-center gap-2 p-2 text-left text-sm hover:brightness-125">
                                <span class="flex-1 min-w-0 truncate {{ $ganoA ? 'text-emerald-300 font-bold' : 'text-gray-300' }}">{{ $pelea->a?->personaje?->nombre }} <span class="text-[11px] text-gray-400">({{ $d['sets']['a'] ?? $pelea->a?->post?->titulo }})</span></span>
                                <span class="shrink-0 font-mono text-xs text-yellow-300">{{ number_format($d['danio']['a'] ?? 0, 0, ',', '.') }} - {{ number_format($d['danio']['b'] ?? 0, 0, ',', '.') }}</span>
                                <span class="flex-1 min-w-0 truncate text-right {{ ! $ganoA ? 'text-emerald-300 font-bold' : 'text-gray-300' }}">{{ $pelea->b?->personaje?->nombre }} <span class="text-[11px] text-gray-400">({{ $d['sets']['b'] ?? $pelea->b?->post?->titulo }})</span></span>
                                <i class="fa-solid {{ $peleaAbiertaId === $pelea->id ? 'fa-chevron-up' : 'fa-chevron-down' }} text-gray-400 text-xs"></i>
                            </button>
                            @if ($peleaAbiertaId === $pelea->id)
                                <ol class="px-3 pb-2 space-y-0.5 text-xs text-gray-300 list-decimal list-inside">
                                    @foreach ($d['golpes'] ?? [] as $golpe)
                                        <li class="{{ $golpe['quien'] === 'a' ? '' : 'text-sky-200' }}">{{ $golpe['texto'] }}</li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>
