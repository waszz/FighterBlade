@php
    use App\Models\Post;

    $panel3d = 'bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]';
    $boton3d = 'font-bold rounded border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100';
    $colorTipo = ['fisico' => 'text-red-400', 'elemental' => 'text-blue-400', 'hibrido' => 'text-purple-400'];
    $total = $misiones->count();
    $hechas = count($completadas);
@endphp

<div class="text-white p-4 max-w-4xl mx-auto space-y-4"
     x-data="{ recup: {{ $recuperacion }}, finRecup: Date.now() / 1000 + {{ $recuperacion }} }"
     x-init="setInterval(() => recup = Math.max(0, Math.ceil(finRecup - Date.now() / 1000)), 500)">
    <x-aviso-recuperacion />
    {{-- Encabezado y progreso --}}
    <div class="{{ $panel3d }} rounded-xl border border-amber-600 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-2xl font-bold text-amber-300 [text-shadow:0_2px_0_#000]">📜 Misiones</h2>
                <p class="text-sm text-gray-300">Derrotá a cada rival para desbloquear el siguiente. Cada misión se gana una sola vez.</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-bold text-amber-200">{{ $hechas }} / {{ $total }} completadas</p>
                <div class="mt-1 w-48 h-2.5 rounded-full bg-gray-800 border border-black overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-amber-500 to-yellow-300" style="width: {{ $total ? round($hechas / $total * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @if (! $siguiente && $total)
            <p class="mt-3 text-center font-bold text-yellow-300">🏆 ¡Completaste todas las misiones!</p>
        @endif
    </div>

    {{-- Escalera --}}
    <div class="space-y-2 max-h-[70vh] overflow-y-auto pr-1 [scrollbar-width:thin] [scrollbar-color:#4b5563_transparent]"
         x-data x-init="$nextTick(() => $el.querySelector('[data-siguiente]')?.scrollIntoView({ block: 'center' }))">
        @foreach ($misiones as $mision)
            @php
                $rival = $mision->rival;
                $hecha = isset($completadas[$mision->id]);
                $esSiguiente = $siguiente && $siguiente->id === $mision->id;
                $bloqueada = ! $hecha && ! $esSiguiente;
            @endphp
            <div @if ($esSiguiente) data-siguiente @endif
                 class="relative overflow-hidden rounded-xl border-2 flex items-stretch
                        {{ $esSiguiente ? 'border-amber-400 shadow-[0_0_14px_rgba(251,191,36,0.45)]' : ($hecha ? 'border-green-700' : 'border-gray-700') }}">
                {{-- Escenario de fondo --}}
                <img src="{{ asset('storage/posts/' . $mision->escenario) }}" alt="" loading="lazy"
                     class="absolute inset-0 w-full h-full object-cover {{ $bloqueada ? 'opacity-20 grayscale' : 'opacity-40' }}">
                <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-black/80"></div>

                {{-- Tocando la card (no el botón) se abre el modal del rival --}}
                <div class="relative flex items-center gap-3 p-2 w-full {{ $bloqueada ? '' : 'cursor-pointer' }}"
                     @unless ($bloqueada) wire:click="verRival({{ $mision->id }})" title="Ver rival" @endunless>
                    {{-- Número --}}
                    <div class="w-10 shrink-0 text-center text-lg font-extrabold {{ $esSiguiente ? 'text-amber-300' : ($hecha ? 'text-green-400' : 'text-gray-500') }} [text-shadow:0_2px_0_#000]">
                        {{ $mision->orden }}
                    </div>

                    {{-- Rival --}}
                    <div class="h-20 w-20 shrink-0 flex items-end justify-center overflow-hidden">
                        @if ($bloqueada)
                            <div class="h-16 w-16 rounded-lg bg-black/60 border border-gray-700 flex items-center justify-center text-3xl">🔒</div>
                        @else
                            <img src="{{ asset('storage/' . $rival->gif) }}" alt="{{ $rival->titulo }}" loading="lazy"
                                 style="{{ Post::estiloGif($rival->gif, 0.6) }}" class="block max-w-none scale-x-[-1]">
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-lg truncate [text-shadow:0_2px_0_#000] {{ $bloqueada ? 'text-gray-400' : 'text-white' }}">
                            {{ $bloqueada ? '???' : $rival->titulo }}
                        </p>
                        <p class="text-xs">
                            <span class="text-gray-300">Nivel {{ $rival->nivel }}</span>
                            @unless ($bloqueada)
                                · <span class="font-semibold {{ $colorTipo[$rival->tipo] ?? 'text-gray-300' }}">{{ ucfirst($rival->tipo) }}</span>
                                @if ($rival->poderes->isNotEmpty())
                                    · <span class="text-sky-300">{{ $rival->poderes->pluck('nombre')->join(' · ') }}</span>
                                @endif
                            @endunless
                        </p>
                        <p class="mt-0.5 text-xs flex items-center gap-2">
                            <span class="flex items-center gap-1 text-yellow-300 font-bold">
                                <img src="{{ asset('images/oro.png') }}" alt="" class="h-3.5 w-3.5">{{ number_format($mision->recompensa_oro, 0, ',', '.') }}
                            </span>
                            <span class="flex items-center gap-1 text-cyan-300 font-bold">
                                <img src="{{ asset('images/diamante.png') }}" alt="" class="h-3.5 w-3.5">{{ $mision->recompensa_diamantes }}
                            </span>
                            <span class="text-gray-400">+ exp</span>
                            @if (isset(\App\Support\RecompensasTorre::misionesConPremio()[$mision->id]) && ! $hecha)
                                <span class="px-1.5 py-px rounded-full text-[10px] font-bold text-amber-200 bg-amber-900/70 border border-amber-500">🎁 Cofre o joya</span>
                            @endif
                        </p>
                    </div>

                    {{-- Estado --}}
                    <div class="shrink-0 w-28 text-center">
                        @if ($hecha)
                            <span class="text-green-400 font-bold text-sm">✅ Completada</span>
                        @elseif ($esSiguiente)
                            <button wire:click.stop="pelear({{ $mision->id }})" wire:loading.attr="disabled" x-bind:disabled="recup > 0"
                                class="{{ $boton3d }} w-full px-3 py-2 bg-gradient-to-b from-red-500 to-red-800 text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                ⚔️ Pelear
                            </button>
                        @else
                            <span class="text-gray-500 text-sm">🔒 Bloqueada</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @include('livewire.partials.modal-rival')
</div>
