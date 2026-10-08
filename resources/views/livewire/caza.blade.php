@php
    use App\Models\Caza as CazaModel;
    use App\Models\Post;

    $panel3d = 'bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]';
    $card3d  = 'bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]';
    $boton3d = 'font-bold rounded border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:brightness-100 disabled:active:translate-y-0';
    $numDiamante = 'font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]';

    $estiloRareza = [
        'comun'      => 'border-gray-400 text-gray-200 bg-gray-700/80',
        'rara'       => 'border-sky-400 text-sky-200 bg-sky-800/80',
        'legendaria' => 'border-amber-400 text-amber-200 bg-amber-700/80 animate-pulse',
    ];
    $bordeRareza = [
        'comun'      => 'border-gray-500',
        'rara'       => 'border-sky-500',
        'legendaria' => 'border-amber-400',
    ];
    $colorTipo = ['fisico' => 'text-red-400', 'elemental' => 'text-blue-400', 'hibrido' => 'text-purple-400'];

    $formatoTiempo = fn ($s) => $s >= 3600 ? floor($s / 3600) . 'h ' . floor($s % 3600 / 60) . 'm' : floor($s / 60) . 'm ' . ($s % 60) . 's';
@endphp

<div class="text-white p-4 max-w-5xl mx-auto space-y-5"
     x-init="setInterval(() => recup = Math.max(0, Math.ceil(finRecup - Date.now() / 1000)), 500)"
     x-data="{
        recup: {{ $recuperacion }},
        finRecup: Date.now() / 1000 + {{ $recuperacion }},
        fmt(s) { s = Math.max(0, s); const h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60;
                 return h > 0 ? `${h}h ${m}m` : `${m}m ${String(x).padStart(2, '0')}s`; }
     }">

    <x-aviso-recuperacion />

    {{-- Encabezado: ciudad, cargas --}}
    <div class="{{ $panel3d }} rounded-xl border border-emerald-600 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="text-center sm:text-left">
                <h2 class="text-2xl font-bold text-emerald-300 [text-shadow:0_2px_0_#000]"><span class="inline-block w-7 h-7 bg-current align-[-0.15em]" style="-webkit-mask: url({{ asset('images/iconos/caza.svg') }}) center / contain no-repeat; mask: url({{ asset('images/iconos/caza.svg') }}) center / contain no-repeat;"></span> Caza</h2>
                <p class="text-sm text-gray-300">
                    Presas de <span class="font-bold text-white">{{ $ciudad->nombre ?? 'tu ciudad' }}</span>
                    · el tablero cambia en
                    <span class="font-bold text-emerald-200" wire:key="rotacion-{{ $segundosRotacion }}" x-data="{ s: {{ $segundosRotacion }} }"
                          x-init="setInterval(() => { if (s > 0 && --s === 0) $wire.$refresh() }, 1000)" x-text="fmt(s)">
                        {{ $formatoTiempo($segundosRotacion) }}
                    </span>
                    <span class="text-gray-400">(a las {{ CazaModel::horaProximaRotacion() }})</span>
                </p>
            </div>

            <div class="flex flex-col items-center sm:items-end gap-1">
                <div class="flex items-center gap-1" title="Cargas de caza">
                    @for ($i = 0; $i < max(CazaModel::CARGAS_MAX, $personaje->caza_cargas); $i++)
                        <span class="w-6 h-6 rounded-full border border-black flex items-center justify-center text-xs
                            {{ $i < $personaje->caza_cargas
                                ? 'bg-gradient-to-b from-emerald-400 to-emerald-700 shadow-[inset_1px_1px_0_rgba(255,255,255,0.4),0_2px_0_#000]'
                                : 'bg-gray-800 opacity-50' }}"><span class="inline-block w-4 h-4 bg-current" style="-webkit-mask: url({{ asset('images/iconos/caza.svg') }}) center / contain no-repeat; mask: url({{ asset('images/iconos/caza.svg') }}) center / contain no-repeat;"></span></span>
                    @endfor
                    <span class="ml-1 font-bold text-emerald-200">{{ $personaje->caza_cargas }}/{{ CazaModel::CARGAS_MAX }}</span>
                </div>
                @if ($proximaCarga !== null)
                    <p class="text-xs text-gray-400">
                        Próxima carga en
                        <span wire:key="carga-{{ $proximaCarga }}" x-data="{ s: {{ $proximaCarga }} }" x-init="setInterval(() => { if (s > 0 && --s === 0) $wire.$refresh() }, 1000)"
                              x-text="fmt(s)">{{ $formatoTiempo($proximaCarga) }}</span>
                    </p>
                @endif
                <button wire:click="comprarCarga" wire:loading.attr="disabled"
                    class="{{ $boton3d }} px-3 py-1 text-xs bg-gradient-to-b from-purple-500 to-purple-800 flex items-center gap-1">
                    +1 carga
                    <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="h-4 w-4">
                    <span class="{{ $numDiamante }}">{{ CazaModel::COSTO_CARGA_DIAMANTES }}</span>
                </button>
            </div>
        </div>
    </div>

    @if ($caza)
        {{-- Caza en curso --}}
        @php
            $info = $caza->rarezaInfo();
            $restante = max(0, $caza->fin_rastreo->timestamp - now()->timestamp);
        @endphp
        <div class="{{ $panel3d }} rounded-xl border-2 {{ $bordeRareza[$caza->rareza] ?? 'border-gray-500' }} p-4 max-w-md mx-auto text-center">
            <span class="inline-block px-2 py-0.5 rounded border text-xs font-bold uppercase {{ $estiloRareza[$caza->rareza] ?? '' }}">
                Presa {{ $info['nombre'] }}
            </span>
            <h3 class="mt-2 text-2xl font-bold [text-shadow:0_2px_0_#000]">{{ $caza->post->titulo }}</h3>
            <p class="text-xs text-gray-400">
                Nivel {{ $caza->post->nivel }} · stats ×{{ $info['stats'] }} · cazás su
                <span class="font-bold text-emerald-300">{{ CazaModel::PARTES[$caza->parte] }}</span>
            </p>

            <div class="mt-3 h-44 flex items-end justify-center rounded-lg border-2 border-black bg-black/40 overflow-hidden">
                <img src="{{ asset('storage/' . $caza->post->gif) }}" alt="{{ $caza->post->titulo }}"
                     style="{{ Post::estiloGif($caza->post->gif) }}" class="block max-w-none scale-x-[-1]">
            </div>

            @if ($caza->estado === 'lista')
                <p class="mt-3 text-sm text-amber-200">La presa te está esperando en la Ciudad.</p>
                <button wire:click="enfrentar" x-bind:disabled="recup > 0" class="{{ $boton3d }} mt-3 w-full px-4 py-2 bg-gradient-to-b from-red-500 to-red-800">
                    ⚔️ Ir a pelear
                </button>
            @elseif ($restante > 0)
                <div x-data="{ s: {{ $restante }} }" x-init="setInterval(() => { if (s > 0 && --s === 0) $wire.$refresh() }, 1000)">
                    <p class="mt-3 text-sm text-gray-300">
                        Rastreando... <span class="font-bold text-white" x-text="fmt(s)">{{ $formatoTiempo($restante) }}</span>
                    </p>
                    <div class="mt-2 h-2 rounded-full bg-gray-800 border border-black overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-300"
                             :style="`width: ${Math.min(100, 100 - s / {{ max(1, $caza->fin_rastreo->timestamp - $caza->created_at->timestamp) }} * 100)}%`"></div>
                    </div>
                </div>
                {{-- Abandonar: pide confirmación en un modal 3D --}}
                <div x-data="{ confirmar: false }">
                    <button type="button" @click="confirmar = true"
                        class="{{ $boton3d }} mt-4 w-full px-4 py-2 text-sm bg-gradient-to-b from-gray-600 to-gray-800">
                        Abandonar caza
                    </button>

                    <div x-show="confirmar" x-cloak x-transition.opacity
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-3"
                         @click.self="confirmar = false" @keydown.escape.window="confirmar = false">
                        <div class="w-full max-w-xs p-5 rounded-xl border border-black text-center
                                    bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
                            <p class="text-3xl">⚠️</p>
                            <h3 class="mt-1 text-xl font-bold text-yellow-300 [text-shadow:0_2px_0_#000]">¿Abandonar la caza?</h3>
                            <p class="mt-2 text-sm text-gray-300">La presa se escapa y <b class="text-red-300">la carga no se devuelve</b>.</p>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <button type="button" @click="confirmar = false"
                                    class="{{ $boton3d }} px-3 py-2 text-sm bg-gradient-to-b from-gray-600 to-gray-800">
                                    Seguir cazando
                                </button>
                                <button type="button" @click="confirmar = false; $wire.cancelar()"
                                    class="{{ $boton3d }} px-3 py-2 text-sm bg-gradient-to-b from-red-500 to-red-800">
                                    Abandonar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm text-emerald-200 font-semibold">¡Encontraste el rastro!</p>
                <button wire:click="enfrentar" x-bind:disabled="recup > 0" class="{{ $boton3d }} mt-3 w-full px-4 py-2 bg-gradient-to-b from-red-500 to-red-800">
                    ⚔️ ¡Enfrentar!
                </button>
            @endif
        </div>
    @else
        {{-- Tablero de presas --}}
        {{-- Cambiar las presas (solo para vos), REFRESCOS_POR_DIA veces por día --}}
        <div class="flex items-center justify-end gap-2 mb-3">
            <span class="text-xs text-gray-300">Refrescos de hoy: <b class="{{ $refrescosRestantes > 0 ? 'text-emerald-300' : 'text-red-400' }}">{{ $refrescosRestantes }}/{{ CazaModel::REFRESCOS_POR_DIA }}</b></span>
            <button type="button" wire:click="refrescarTablero" wire:loading.attr="disabled" @disabled($refrescosRestantes < 1)
                class="{{ $boton3d }} px-3 py-1.5 text-sm bg-gradient-to-b from-sky-500 to-sky-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fa-solid fa-rotate"></i> Cambiar presas
            </button>
        </div>
        @if ($tablero->isEmpty())
            <p class="text-center text-gray-400 italic">No hay presas en esta ciudad.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ($tablero as $presa)
                    @php
                        $post = $presa['post'];
                        $info = CazaModel::RAREZAS[$presa['rareza']];
                        $elegida = $presaId === $post->id;
                    @endphp
                    <button type="button" wire:click="elegirPresa({{ $post->id }})"
                        class="{{ $card3d }} rounded-xl border-2 p-3 text-center transition-transform duration-200 hover:-translate-y-1
                               {{ $elegida ? 'border-emerald-400 ring-2 ring-emerald-400/60' : ($bordeRareza[$presa['rareza']] ?? 'border-gray-600') }}">
                        <span class="inline-block px-2 py-0.5 rounded border text-[11px] font-bold uppercase {{ $estiloRareza[$presa['rareza']] }}">
                            {{ $info['nombre'] }}
                        </span>
                        <div class="mt-2 h-40 flex items-end justify-center rounded-lg border-2 border-black bg-black/40 overflow-hidden">
                            <img src="{{ asset('storage/' . $post->gif) }}" alt="{{ $post->titulo }}" loading="lazy"
                                 style="{{ Post::estiloGif($post->gif) }}" class="block max-w-none scale-x-[-1]">
                        </div>
                        <h3 class="mt-2 font-bold text-lg truncate [text-shadow:0_2px_0_#000]">{{ $post->titulo }}</h3>
                        <p class="text-xs">
                            <span class="text-gray-300">Nv {{ $post->nivel }}</span> ·
                            <x-icono-tipo :tipo="$post->tipo" tam="w-4 h-4" :con-nombre="true" class="{{ $colorTipo[$post->tipo] ?? 'text-gray-300' }} font-semibold" />
                        </p>
                        <p class="mt-1 text-[11px] text-red-300">Stats ×{{ $info['stats'] }}</p>
                        <p class="text-[11px] text-yellow-300 flex items-center justify-center gap-1">
                            <img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-3.5 w-3.5"> Oro ×{{ $info['oro'] }}
                            @if ($info['diamantes'])
                                · <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="h-3.5 w-3.5">
                                <span class="{{ $numDiamante }}">+{{ $info['diamantes'] }}</span>
                            @endif
                        </p>
                        @if ($post->poderes->isNotEmpty())
                            <p class="mt-1 text-[10px] text-sky-300 truncate">{{ $post->poderes->pluck('nombre')->join(' · ') }}</p>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- Parte y rastreo --}}
            @php $seleccionada = $tablero->first(fn ($p) => $p['post']->id === $presaId); @endphp
            @if ($seleccionada)
                @php $post = $seleccionada['post']; @endphp
                <div class="{{ $panel3d }} rounded-xl border border-emerald-600 p-4 max-w-xl mx-auto">
                    <p class="text-center text-sm text-gray-300 mb-3">¿Qué parte de <span class="font-bold text-white">{{ $post->titulo }}</span> querés cazar?</p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (CazaModel::PARTES as $clave => $nombre)
                            @php $bonus = array_filter($post->{'ajustes_manuales_' . $clave} ?? [], fn ($v) => $v > 0); @endphp
                            <button type="button" wire:click="$set('parte', '{{ $clave }}')"
                                class="{{ $card3d }} rounded-lg border-2 p-2 text-center {{ $parte === $clave ? 'border-emerald-400' : 'border-gray-700' }}">
                                @if ($post->{$clave . '_imagen'})
                                    <img src="{{ asset('storage/posts/' . $post->{$clave . '_imagen'}) }}" alt="{{ $nombre }}"
                                         class="w-10 h-10 mx-auto rounded-full object-cover border border-gray-500">
                                @endif
                                <p class="mt-1 text-xs font-bold {{ $parte === $clave ? 'text-emerald-300' : 'text-gray-200' }}">{{ $nombre }}</p>
                                <p class="text-[10px] text-yellow-300 leading-tight">
                                    @foreach ($bonus as $stat => $valor)
                                        +{{ $valor }} {{ strtoupper(substr($stat, 0, 3)) }}@if (! $loop->last), @endif
                                    @endforeach
                                </p>
                            </button>
                        @endforeach
                    </div>

                    <button wire:click="rastrear" wire:loading.attr="disabled" x-bind:disabled="recup > 0 || {{ $personaje->caza_cargas < 1 ? 'true' : 'false' }}"
                        class="{{ $boton3d }} mt-4 w-full px-4 py-2 bg-gradient-to-b from-emerald-500 to-emerald-800">
                        <span class="inline-block w-5 h-5 bg-current align-[-0.15em]" style="-webkit-mask: url({{ asset('images/iconos/caza.svg') }}) center / contain no-repeat; mask: url({{ asset('images/iconos/caza.svg') }}) center / contain no-repeat;"></span> Rastrear ({{ $rastreoMinutos }} min) · gasta 1 carga
                    </button>
                    <p class="mt-2 text-center text-[11px] text-gray-400">Si perdés, empatás o huís, la presa se escapa y la carga no se devuelve.</p>
                </div>
            @else
                <p class="text-center text-sm text-gray-400">Elegí una presa para rastrearla.</p>
            @endif
        @endif
    @endif

    {{-- Últimas cazas --}}
    @if ($historial->isNotEmpty())
        <div class="{{ $panel3d }} rounded-xl border border-gray-700 p-3 max-w-xl mx-auto">
            <h3 class="text-sm font-bold text-gray-300 mb-2">Últimas cazas</h3>
            <ul class="space-y-1 text-sm">
                @foreach ($historial as $h)
                    <li class="flex items-center justify-between gap-2">
                        <span class="truncate">
                            {{ $h->estado === 'ganada' ? '✅' : '❌' }}
                            <span class="font-semibold">{{ $h->post->titulo ?? '—' }}</span>
                            <span class="text-xs text-gray-400">({{ CazaModel::RAREZAS[$h->rareza]['nombre'] ?? $h->rareza }} · {{ CazaModel::PARTES[$h->parte] ?? $h->parte }})</span>
                        </span>
                        <span class="text-xs text-gray-500 shrink-0">{{ $h->updated_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
