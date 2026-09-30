@php
    $panel3d = 'bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]';
    $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
    // Pared de piedra de la torre (ladrillos con degradados)
    $piedra = 'background-color:#3b3f4a;background-image:linear-gradient(#2a2d35 2px,transparent 2px),linear-gradient(90deg,#2a2d35 2px,transparent 2px),linear-gradient(90deg,#2a2d35 2px,transparent 2px);background-size:100% 22px,44px 22px,44px 22px;background-position:0 0,0 0,22px 11px;';
    $puedeEntrar = ($personaje->nivel ?? 0) >= $nivelMinimo;
@endphp

<div class="text-white p-4 max-w-3xl mx-auto space-y-4"
     x-data="{ recup: {{ $recuperacion }}, finRecup: Date.now() / 1000 + {{ $recuperacion }} }"
     x-init="setInterval(() => recup = Math.max(0, Math.ceil(finRecup - Date.now() / 1000)), 500)">
    <x-aviso-recuperacion />

    {{-- Encabezado y progreso --}}
    <div class="{{ $panel3d }} rounded-xl border border-violet-500 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-2xl font-bold text-violet-300 [text-shadow:0_2px_0_#000]">🗼 La Torre</h2>
                <p class="text-sm text-gray-300">Subí de a un piso derrotando a su rival. Si perdés, podés volver a intentar el mismo piso.</p>
                <p class="text-xs text-violet-200 mt-1">Cada piso da oro y el <b>doble de exp</b>. Cada 10 niveles (10, 20, 30… 100) además cae un <b>🎁 cofre o una joya</b>.</p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm font-bold text-violet-200">Piso {{ $superado }} / {{ $total }}</p>
                <div class="mt-1 w-48 h-2.5 rounded-full bg-gray-800 border border-black overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-violet-600 to-fuchsia-400" style="width: {{ $total ? round($superado / $total * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @if ($terminada)
            <p class="mt-3 text-center font-bold text-yellow-300">🏆 ¡Llegaste a la cima de la Torre!</p>
        @elseif (! $puedeEntrar)
            <p class="mt-3 text-center font-bold text-amber-300">🔒 Necesitás nivel {{ $nivelMinimo }} para entrar a la Torre.</p>
        @endif
    </div>

    {{-- La torre: los pisos apilados (el más alto arriba) sobre la pared de piedra --}}
    <div class="mx-auto max-w-xl">

        <div class="mx-auto w-[88%] px-3 py-3 space-y-3 rounded-xl border-4 border-black" style="{{ $piedra }}">
            @if ($pisos->max('piso') < $total)
                <p class="text-center text-xs text-gray-300 font-bold [text-shadow:0_1px_0_#000]">⋮ faltan {{ $total - $pisos->max('piso') }} pisos más arriba ⋮</p>
            @endif

            @foreach ($pisos as $piso)
                @php
                    $rival = $piso->rival;
                    $superadoPiso = $piso->piso <= $superado;
                    $esActual = $piso->piso === $actual && ! $terminada;
                    $bloqueado = ! $superadoPiso && ! $esActual;
                @endphp
                <div wire:key="piso-{{ $piso->id }}" @if ($esActual) data-actual @endif
                     class="relative overflow-hidden rounded-lg border-2
                            {{ $esActual ? 'border-violet-400 shadow-[0_0_14px_rgba(167,139,250,0.7)]' : ($superadoPiso ? 'border-emerald-600' : 'border-black') }}">
                    {{-- La zona del piso de fondo --}}
                    <img src="{{ asset('storage/posts/' . $piso->escenario) }}" alt="" loading="lazy"
                         class="absolute inset-0 w-full h-full object-cover {{ $bloqueado ? 'grayscale brightness-[0.35]' : 'brightness-[0.55]' }}">

                    {{-- Tocando la card (no el botón ni los iconos) se abre el modal del rival --}}
                    <div class="relative flex items-center gap-3 p-2 {{ $bloqueado ? '' : 'cursor-pointer' }}"
                         @unless ($bloqueado) x-on:click="if (! $event.target.closest('button, [tabindex]')) $wire.verRival({{ $piso->piso }})" title="Ver rival" @endunless>
                        {{-- Número de piso --}}
                        <div class="w-12 shrink-0 text-center">
                            <p class="text-[10px] uppercase text-gray-300 leading-none">Piso</p>
                            <p class="text-2xl font-extrabold leading-none {{ $esActual ? 'text-violet-300' : ($superadoPiso ? 'text-emerald-300' : 'text-gray-400') }} [text-shadow:0_2px_0_#000]">{{ $piso->piso }}</p>
                        </div>

                        {{-- Rival --}}
                        @if ($bloqueado)
                            <div class="w-12 h-12 shrink-0 rounded-full border-2 border-black bg-black/70 flex items-center justify-center text-xl">🔒</div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-300 [text-shadow:0_1px_0_#000]">???</p>
                                <p class="text-xs text-gray-400">Rival nivel {{ $rival?->nivel ?? $piso->nivel }}@if (\App\Support\RecompensasTorre::esNivelEspecial((int) $piso->nivel)) · <span class="text-amber-300 font-bold">🎁 Cofre o joya</span>@endif</p>
                            </div>
                        @else
                            <img src="{{ asset('storage/' . $rival?->imagen) }}" alt="" class="w-12 h-12 shrink-0 rounded-full object-cover border-2 border-black">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold truncate [text-shadow:0_1px_0_#000]">{{ $rival?->titulo ?? 'Rival' }}</p>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="px-1.5 py-px rounded-full text-[10px] font-bold text-yellow-300 {{ $etiqueta3d }}">Nv {{ $rival?->nivel }}</span>
                                    @if (\App\Support\RecompensasTorre::esNivelEspecial((int) $piso->nivel) && ! $superadoPiso)
                                        <span class="px-1.5 py-px rounded-full text-[10px] font-bold text-amber-200 bg-amber-900/70 border border-amber-500">🎁 Cofre o joya</span>
                                    @endif
                                    @if ($esActual && $rival)
                                        <x-icono-tipo :tipo="$rival->tipo" tam="w-6 h-6" class="cursor-pointer" />
                                        @foreach ($rival->poderes as $poder)
                                            <x-icono-poder :poder="$poder" tam="w-6 h-6" class="cursor-pointer" />
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Estado / botón --}}
                        <div class="shrink-0">
                            @if ($superadoPiso)
                                <span class="px-2 py-1 rounded-lg text-xs font-bold text-emerald-200 bg-emerald-900/70 border border-emerald-600">✔ Superado</span>
                            @elseif ($esActual)
                                <button wire:click.stop="subir" wire:loading.attr="disabled" x-bind:disabled="recup > 0 || {{ $puedeEntrar ? 'false' : 'true' }}"
                                        x-bind:title="recup > 0 ? 'Te estás recuperando' : ''"
                                        class="px-4 py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-violet-500 to-violet-800
                                               shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all
                                               disabled:opacity-40 disabled:cursor-not-allowed disabled:active:translate-y-0">
                                    ⚔️ Subir
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if ($pisos->min('piso') > 1)
                <p class="text-center text-xs text-gray-300 font-bold [text-shadow:0_1px_0_#000]">⋮ {{ $pisos->min('piso') - 1 }} pisos superados más abajo ⋮</p>
            @endif
        </div>
    </div>

    @include('livewire.partials.modal-rival')
</div>
