@php
    use App\Models\Mazmorra as M;

    $panel3d = 'bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]';
    $boton3d = 'font-bold rounded-lg border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:active:translate-y-0';
    $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
    $colorDificultad = ['normal' => 'from-emerald-500 to-emerald-800', 'dificil' => 'from-orange-500 to-orange-800', 'pesadilla' => 'from-red-600 to-red-900'];
    $bordeDificultad = ['normal' => 'border-emerald-500', 'dificil' => 'border-orange-500', 'pesadilla' => 'border-red-600'];
    $puedeEntrar = ($personaje->nivel ?? 0) >= M::NIVEL_MINIMO;
    $energiaPct = min(100, round($mazmorra->energia / M::ENERGIA_DIARIA * 100));
    $alcanza = $mazmorra->energia >= M::ENERGIA_POR_PELEA;
@endphp

<div class="text-white p-4 max-w-3xl mx-auto space-y-4"
     x-data="{ recup: {{ $recuperacion }}, finRecup: Date.now() / 1000 + {{ $recuperacion }} }"
     x-init="setInterval(() => recup = Math.max(0, Math.ceil(finRecup - Date.now() / 1000)), 500)">
    <x-aviso-recuperacion />

    {{-- Encabezado: qué es, energía y compra --}}
    <div class="{{ $panel3d }} rounded-xl border border-rose-700 p-4 space-y-3">
        <div>
            <h2 class="text-2xl font-bold text-rose-300 [text-shadow:0_2px_0_#000]">🕳️ Mazmorra</h2>
            <p class="text-sm text-gray-300">Elegí la dificultad, vencé a {{ M::ENEMIGOS }} enemigos y enfrentá al <b class="text-rose-300">jefe</b>. Si perdés, seguís en el mismo rival y lo podés volver a intentar.</p>
            <p class="text-xs text-rose-200 mt-1">Los enemigos dan oro y a veces una poción. El jefe da oro y <b>esmeraldas</b> ({{ collect(M::DIFICULTADES)->map(fn ($d) => $d['esmeraldas'] . ' en ' . $d['nombre'])->join(', ', ' y ') }}).</p>
        </div>

        {{-- Energía --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1">
                <div class="flex items-center justify-between text-sm font-bold mb-1">
                    <span class="text-yellow-300">⚡ Energía</span>
                    <span>{{ $mazmorra->energia }} / {{ M::ENERGIA_DIARIA }}</span>
                </div>
                <div class="h-3 rounded-full bg-gray-800 border border-black overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-yellow-500 to-amber-300" style="width: {{ $energiaPct }}%"></div>
                </div>
                <p class="mt-1 text-[11px] text-gray-400">Cada pelea gasta {{ M::ENERGIA_POR_PELEA }}. Se recarga a {{ M::ENERGIA_DIARIA }} todos los días.</p>
            </div>
            @if ($mazmorra->puedeComprar())
                <button type="button" wire:click="comprarEnergia" wire:loading.attr="disabled"
                        wire:confirm="¿Comprar {{ M::ENERGIA_COMPRA }} de energía por {{ number_format(M::PRECIO_COMPRA, 0, ',', '.') }} esmeraldas? (una vez por día)"
                        class="{{ $boton3d }} shrink-0 flex items-center justify-center gap-1.5 px-3 py-2 text-sm text-black bg-gradient-to-b from-yellow-300 to-yellow-600">
                    ⚡ +{{ M::ENERGIA_COMPRA }} ·
                    <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="h-4 w-4">{{ number_format(M::PRECIO_COMPRA, 0, ',', '.') }}
                </button>
            @else
                <span class="shrink-0 text-xs font-bold text-gray-400 text-center">Ya compraste energía hoy</span>
            @endif
        </div>

        @unless ($puedeEntrar)
            <p class="text-center font-bold text-amber-300">🔒 Necesitás nivel {{ M::NIVEL_MINIMO }} para entrar a la Mazmorra.</p>
        @endunless
    </div>

    @if (! $mazmorra->enCurso())
        {{-- Elegir dificultad --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach (M::DIFICULTADES as $clave => $dif)
                <div class="{{ $panel3d }} rounded-xl border-2 {{ $bordeDificultad[$clave] }} p-3 flex flex-col gap-2 text-center">
                    <p class="text-lg font-extrabold [text-shadow:0_2px_0_#000]">{{ $dif['icono'] }} {{ $dif['nombre'] }}</p>
                    <ul class="text-xs text-gray-300 space-y-0.5 flex-1">
                        <li>Rivales {{ $dif['stats'] == 1 ? 'normales' : 'un ' . round(($dif['stats'] - 1) * 100) . '% más fuertes' }}</li>
                        <li>Oro <b class="text-yellow-300">×{{ $dif['premio'] }}</b></li>
                        <li>Jefe: <b class="text-cyan-300">{{ $dif['esmeraldas'] }}</b> esmeraldas</li>
                    </ul>
                    <button type="button" wire:click="elegir('{{ $clave }}')" wire:loading.attr="disabled" @disabled(! $puedeEntrar)
                            class="{{ $boton3d }} w-full py-2 text-white bg-gradient-to-b {{ $colorDificultad[$clave] }}">
                        Entrar
                    </button>
                </div>
            @endforeach
        </div>
    @else
        @php $dif = $mazmorra->datosDificultad(); @endphp
        <div class="flex items-center justify-between gap-2">
            <p class="font-bold">
                {{ $dif['icono'] }} Mazmorra <span class="text-rose-300">{{ $dif['nombre'] }}</span>
                <span class="text-gray-400 text-sm">· rival {{ min($mazmorra->paso + 1, M::ENEMIGOS + 1) }} de {{ M::ENEMIGOS + 1 }}</span>
            </p>
            <button type="button" wire:click="abandonar" wire:confirm="¿Abandonar la mazmorra? Vas a perder el avance (la energía gastada no vuelve)."
                    class="{{ $boton3d }} px-2 py-1 text-xs text-white bg-gradient-to-b from-gray-600 to-gray-800">Abandonar</button>
        </div>

        {{-- El camino: los 4 enemigos y el jefe al final --}}
        <div class="space-y-2">
            @foreach ($rivales as $r)
                @php
                    $post = $r['post'];
                    $vencido = $r['paso'] < $mazmorra->paso;
                    $actual = $r['paso'] === $mazmorra->paso;
                    $oculto = ! $vencido && ! $actual;
                    $esJefe = $mazmorra->esJefe($r['paso']);
                @endphp
                <div wire:key="rival-mazmorra-{{ $r['paso'] }}"
                     class="relative overflow-hidden rounded-xl border-2
                            {{ $actual ? ($esJefe ? 'border-rose-500 shadow-[0_0_16px_rgba(244,63,94,0.7)]' : 'border-amber-400 shadow-[0_0_14px_rgba(251,191,36,0.45)]') : ($vencido ? 'border-emerald-600' : 'border-black') }}">
                    @if ($r['escenario'])
                        <img src="{{ asset('storage/posts/' . $r['escenario']) }}" alt="" loading="lazy"
                             class="absolute inset-0 w-full h-full object-cover {{ $oculto ? 'grayscale brightness-[0.3]' : 'brightness-[0.5]' }}">
                    @endif
                    <div class="relative flex items-center gap-3 p-2 {{ $oculto ? '' : 'cursor-pointer' }}"
                         @unless ($oculto) x-on:click="if (! $event.target.closest('button, [tabindex]')) $wire.verRival({{ $r['paso'] }})" title="Ver rival" @endunless>
                        <div class="w-12 shrink-0 text-center">
                            <p class="text-2xl leading-none">{{ $esJefe ? '💀' : '⚔️' }}</p>
                            <p class="text-[10px] uppercase font-bold {{ $esJefe ? 'text-rose-300' : 'text-gray-300' }}">{{ $esJefe ? 'Jefe' : 'Enemigo ' . ($r['paso'] + 1) }}</p>
                        </div>

                        {{-- Rival --}}
                        <div class="h-20 w-20 shrink-0 flex items-end justify-center overflow-hidden">
                            @if ($oculto || ! $post)
                                <div class="h-14 w-14 rounded-lg bg-black/60 border border-gray-700 flex items-center justify-center text-2xl">🔒</div>
                            @else
                                <img src="{{ asset('storage/' . $post->gif) }}" alt="{{ $post->titulo }}" loading="lazy"
                                     style="{{ \App\Models\Post::estiloGif($post->gif, 0.6) }}" class="block max-w-none scale-x-[-1]">
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-bold truncate [text-shadow:0_1px_0_#000] {{ $oculto ? 'text-gray-400' : 'text-white' }}">{{ $oculto ? '???' : ($post->titulo ?? 'Rival') }}</p>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-1.5 py-px rounded-full text-[10px] font-bold text-yellow-300 {{ $etiqueta3d }}">Nv {{ $post->nivel ?? '?' }}</span>
                                @if ($actual && $post)
                                    <x-icono-tipo :tipo="$post->tipo" tam="w-6 h-6" class="cursor-pointer" />
                                    @foreach ($post->poderes as $poder)
                                        <x-icono-poder :poder="$poder" tam="w-6 h-6" class="cursor-pointer" />
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if ($vencido)
                                <span class="px-2 py-1 rounded-lg text-xs font-bold text-emerald-200 bg-emerald-900/70 border border-emerald-600">✔ Vencido</span>
                            @elseif ($actual)
                                <button type="button" wire:click="pelear" wire:loading.attr="disabled"
                                        x-bind:disabled="recup > 0 || {{ $alcanza ? 'false' : 'true' }}"
                                        x-bind:title="recup > 0 ? 'Te estás recuperando' : '{{ $alcanza ? '' : 'No te alcanza la energía' }}'"
                                        class="{{ $boton3d }} px-3 py-2 text-sm text-white bg-gradient-to-b {{ $esJefe ? 'from-rose-500 to-rose-900' : 'from-red-500 to-red-800' }}">
                                    ⚔️ Pelear<br><span class="text-[10px] text-yellow-200">⚡ {{ M::ENERGIA_POR_PELEA }}</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @include('livewire.partials.modal-rival')
</div>
