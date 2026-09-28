<div class="flex h-full w-full text-white" wire:poll.5s.visible>
    @php
        $boton3d = 'flex items-center justify-center gap-1.5 rounded-lg border border-black text-xs font-bold bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all';
        // Color fijo del nombre para cada jugador (si no tiene un efecto de nombre comprado)
        $coloresNombre = ['text-emerald-400', 'text-sky-400', 'text-amber-400', 'text-pink-400', 'text-violet-400', 'text-lime-400', 'text-orange-400', 'text-cyan-300', 'text-rose-400', 'text-teal-300'];
        $colorNombre = fn ($pj) => $pj?->claseNombre() ?: $coloresNombre[($pj->id ?? 0) % count($coloresNombre)];
        // Foto de cada personaje, una consulta por personaje
        $fotosChat = [];
        $foto = function ($pj) use (&$fotosChat) {
            if (! $pj) return 'default.png';
            return $fotosChat[$pj->id] ??= ($pj->fotoChat() ?? 'default.png');
        };
        $bordeParte = ['equipo' => 'border-indigo-500', 'entrenamiento' => 'border-green-500', 'accesorio' => 'border-pink-500', 'joya' => 'border-amber-400', 'cofre' => 'border-amber-600', 'pocion' => 'border-fuchsia-500'];
        $abrev = ['fuerza' => 'FUE', 'resistencia' => 'RES', 'ataque' => 'ATA', 'defensa' => 'DEF', 'velocidad' => 'VEL', 'energia' => 'ENE'];
    @endphp

    <div class="relative w-full h-full min-h-0 p-3 md:p-4 flex flex-col">

        {{-- Barra de arriba: título (o con quién hablás en privado) · casilla · conectados --}}
        <div class="flex items-center gap-2 mb-3">
            @if ($con)
                <button type="button" wire:click="volverGeneral" aria-label="Volver al chat general" class="{{ $boton3d }} w-8 h-8 shrink-0 text-gray-200">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <img src="{{ asset('storage/' . $foto($con)) }}" alt="" class="w-8 h-8 shrink-0 rounded-full object-cover">
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="truncate font-bold text-sm {{ $colorNombre($con) }}">{{ $con->nombre }}</p>
                    <p class="text-[10px] uppercase tracking-wide text-gray-400">Privado</p>
                </div>
            @else
                <h3 class="flex-1 text-xl md:text-2xl font-bold text-white">Chat</h3>
            @endif

            <button type="button" wire:click="alternarPanel('casilla')" aria-label="Mensajes privados" title="Mensajes privados"
                class="{{ $boton3d }} relative w-9 h-8 shrink-0 {{ $panel === 'casilla' ? 'ring-2 ring-yellow-400' : '' }} text-gray-200">
                <i class="fa-regular fa-envelope"></i>
                @if ($sinLeer > 0)
                    <span class="absolute -top-1.5 -right-1.5 min-w-[1.1rem] h-[1.1rem] px-1 flex items-center justify-center rounded-full bg-red-600 border border-black text-[10px] font-extrabold text-white">{{ $sinLeer > 99 ? '99+' : $sinLeer }}</span>
                @endif
            </button>
            <button type="button" wire:click="alternarPanel('online')" title="Jugadores conectados"
                class="{{ $boton3d }} h-8 px-2.5 shrink-0 font-mono uppercase tracking-wide text-[11px] text-gray-100 {{ $panel === 'online' ? 'ring-2 ring-yellow-400' : '' }}">
                <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.9)]"></span>
                Online · {{ $conectados->count() }}
            </button>
        </div>

        {{-- Casilla: desplegable con las conversaciones privadas --}}
        @if ($panel === 'casilla')
            <div class="absolute left-3 right-3 top-14 z-30 max-h-72 overflow-y-auto sidebar-pj p-2 rounded-xl border border-black
                        bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),0_6px_0_#000,0_10px_20px_rgba(0,0,0,0.8)]">
                <p class="px-1 pb-1.5 text-[11px] font-bold uppercase tracking-wide text-yellow-300">Mensajes privados</p>
                    @forelse ($conversaciones as $conv)
                        @php $otro = $conv['personaje']; $ultimo = $conv['ultimo']; @endphp
                        <button type="button" wire:click="abrirPrivado({{ $otro->id }})" wire:key="conv-{{ $otro->id }}"
                            class="w-full flex items-center gap-2 p-1.5 rounded-lg text-left hover:bg-white/5">
                            <img src="{{ asset('storage/' . $foto($otro)) }}" alt="" class="w-9 h-9 shrink-0 rounded-full object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold {{ $colorNombre($otro) }}">{{ $otro->nombre }}</p>
                                <p class="truncate text-xs {{ $conv['sinLeer'] ? 'text-white font-semibold' : 'text-gray-400' }}">
                                    {{ $ultimo->personaje_id === $personajeId ? 'Vos: ' : '' }}{{ match ($ultimo->tipo) { 'gif' => 'GIF', 'objeto' => '📦 ' . ($ultimo->adjunto['nombre'] ?? 'Objeto'), 'pelea' => '⚔️ Pelea', default => $ultimo->contenido } }}
                                </p>
                            </div>
                            @if ($conv['sinLeer'])
                                <span class="shrink-0 min-w-[1.25rem] h-5 px-1 flex items-center justify-center rounded-full bg-red-600 border border-black text-[10px] font-extrabold">{{ $conv['sinLeer'] }}</span>
                            @endif
                        </button>
                    @empty
                        <p class="px-1 py-2 text-xs text-gray-400 italic">Todavía no tenés mensajes privados. Tocá "Online" para escribirle a alguien.</p>
                    @endforelse
            </div>
        @endif

        {{-- Mensajes: baja solo al último si estabas mirando el final --}}
        <div id="chat-box" x-data="{ pegado: true }"
             x-init="$el.scrollTop = $el.scrollHeight;
                     new MutationObserver(() => { if (pegado) $el.scrollTop = $el.scrollHeight }).observe($el, { childList: true, subtree: true })"
             @scroll="pegado = $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 40"
             class="flex-1 min-h-0 overflow-y-auto mb-3 space-y-2.5 pr-1 [scrollbar-width:thin] [scrollbar-color:#4b5563_transparent]">
            @forelse ($mensajes as $msg)
                @php
                    $pj = $msg->personaje;
                    $horaChat = $msg->created_at?->locale('es')->diffForHumans(['short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
                    $adj = $msg->adjunto ?? [];
                @endphp

                {{-- La foto va montada sobre la esquina superior izquierda de la tarjeta --}}
                <div wire:key="msg-{{ $msg->id }}" class="relative pl-5 pt-0.5 w-fit max-w-[92%]">
                    <img src="{{ asset('storage/' . $foto($pj)) }}" alt=""
                         class="absolute left-0 top-0 z-10 w-11 h-11 rounded-full object-cover bg-black/40 shadow-[0_2px_6px_rgba(0,0,0,0.7)]" loading="lazy" />

                    <div class="min-w-[10rem] min-h-[2.5rem] rounded-xl pl-8 pr-3 py-1 border border-white/5
                                bg-gradient-to-b from-[#1b2230] to-[#121722] shadow-[0_2px_6px_rgba(0,0,0,0.5)]
                                {{ $msg->tipo === 'texto' ? $pj?->claseChat() : '' }}">
                        <p class="flex items-baseline gap-2 leading-tight">
                            @if ($pj && ! $con && $pj->id !== $personajeId)
                                {{-- Tocar el nombre abre un privado con esa persona --}}
                                <button type="button" wire:click="abrirPrivado({{ $pj->id }})" title="Escribirle en privado"
                                    class="font-bold text-sm truncate hover:underline {{ $colorNombre($pj) }}">{{ $pj->nombre }}</button>
                            @else
                                <span class="font-bold text-sm truncate {{ $colorNombre($pj) }}">{{ $pj->nombre ?? 'Sin nombre' }}</span>
                            @endif
                            <span class="ml-auto text-[11px] text-gray-400 shrink-0">{{ $horaChat }}</span>
                        </p>

                        @switch($msg->tipo)
                            @case('gif')
                                <img src="{{ asset('storage/' . ($adj['ruta'] ?? '')) }}" alt="GIF" loading="lazy"
                                     class="mt-1 mb-1 max-h-44 max-w-full rounded-lg border border-black">
                                @break

                            @case('objeto')
                                {{-- Objeto compartido: foto, nombre, set · parte · nivel y stats --}}
                                <button type="button" wire:click="verObjeto({{ $msg->id }})" title="Ver el objeto"
                                    class="mt-1 mb-1 flex items-center gap-2 p-1.5 pr-3 rounded-lg border border-black border-l-4 text-left {{ $bordeParte[$adj['tipo'] ?? ''] ?? 'border-l-gray-500' }} bg-black/30 hover:bg-white/5 transition">
                                    @if (! empty($adj['imagen']))
                                        <img src="{{ asset($adj['imagen']) }}" alt="" loading="lazy"
                                             class="w-11 h-11 shrink-0 rounded-md object-cover bg-black/50 border-2 {{ $bordeParte[$adj['tipo'] ?? ''] ?? 'border-black' }}">
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-white">{{ $adj['nombre'] ?? 'Objeto' }}</p>
                                        <p class="truncate font-mono text-[11px] text-gray-400">
                                            {{ collect([$adj['set'] ?? null, ($adj['tipo'] ?? '') === 'pocion' ? 'Poción' : (\App\Support\ChatCompartir::NOMBRE_PARTE[$adj['tipo'] ?? ''] ?? null), ! empty($adj['nivel']) ? 'Nv ' . $adj['nivel'] : null])->filter()->implode(' · ') }}
                                        </p>
                                        @if (! empty($adj['stats']))
                                            <p class="font-mono text-[10px] text-emerald-300">
                                                {{ collect($adj['stats'])->map(fn ($v, $s) => ($abrev[$s] ?? strtoupper(substr($s, 0, 3))) . ' +' . $v)->implode('  ') }}
                                            </p>
                                        @elseif (! empty($adj['descripcion']))
                                            <p class="text-[11px] text-gray-300">{{ $adj['descripcion'] }}</p>
                                        @endif
                                    </div>
                                </button>
                                @break

                            @case('pelea')
                                {{-- Pelea compartida: los dos personajes, resultado, tipo de pelea y premio --}}
                                @php $gano = ($adj['resultado'] ?? '') === 'victoria'; @endphp
                                <div class="mt-1 mb-1 p-1.5 rounded-lg border border-black border-l-4 {{ $gano ? 'border-l-emerald-500' : 'border-l-red-500' }} bg-black/30">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 shrink-0 rounded-md bg-black/50 border border-black flex items-end justify-center overflow-hidden">
                                            @if (! empty($adj['gif_personaje']))<img src="{{ asset('storage/' . $adj['gif_personaje']) }}" alt="" class="max-w-full max-h-full object-contain" loading="lazy">@endif
                                        </div>
                                        <div class="min-w-0 flex-1 text-center leading-tight">
                                            <p class="truncate text-[11px] font-bold text-white">{{ $adj['nombre_personaje'] ?? '' }} <span class="text-yellow-300">vs</span> {{ $adj['nombre_enemigo'] ?? '' }}</p>
                                            <span class="inline-block mt-0.5 px-1.5 rounded-full border border-black text-[10px] font-extrabold uppercase {{ $gano ? 'bg-gradient-to-b from-emerald-500 to-emerald-800' : 'bg-gradient-to-b from-red-500 to-red-800' }}">{{ ucfirst($adj['resultado'] ?? '') }}</span>
                                            <span class="text-[10px] text-gray-400">{{ \App\Support\ChatCompartir::ORIGEN_PELEA[$adj['origen'] ?? ''] ?? 'Pelea' }}</span>
                                        </div>
                                        <div class="w-10 h-10 shrink-0 rounded-md bg-black/50 border border-black flex items-end justify-center overflow-hidden">
                                            @if (! empty($adj['gif_enemigo']))<img src="{{ asset('storage/' . $adj['gif_enemigo']) }}" alt="" class="max-w-full max-h-full object-contain scale-x-[-1]" loading="lazy">@endif
                                        </div>
                                    </div>
                                    @if (($adj['exp'] ?? 0) || ($adj['oro'] ?? 0))
                                        <p class="mt-1 flex justify-center items-center gap-2 text-[11px] font-bold">
                                            <span class="text-green-400">EXP +{{ number_format($adj['exp'] ?? 0, 0, ',', '.') }}</span>
                                            <span class="flex items-center gap-0.5 text-yellow-400"><img src="{{ asset('images/oro.png') }}" alt="" class="w-3.5 h-3.5">+{{ number_format($adj['oro'] ?? 0, 0, ',', '.') }}</span>
                                        </p>
                                    @endif
                                </div>
                                @break

                            @default
                                <p class="text-sm leading-snug break-words whitespace-pre-wrap">{{ $msg->contenido }}</p>
                        @endswitch
                    </div>
                </div>
            @empty
                <p class="text-center text-xs text-gray-400 italic mt-4">
                    {{ $con ? 'Todavía no hablaron. ¡Escribile algo!' : 'No hay mensajes todavía.' }}
                </p>
            @endforelse
        </div>

        @error('mensaje') <p class="mb-1 text-xs text-red-400">{{ $message }}</p> @enderror
        @error('gif') <p class="mb-1 text-xs text-red-400">{{ $message }}</p> @enderror

        {{-- Escribir: texto + subir GIF + enviar --}}
        <form wire:submit.prevent="enviarMensaje" class="space-y-2">
            <div class="flex items-center gap-2">
                <input type="text" wire:model.defer="mensaje" maxlength="255"
                    placeholder="{{ $con ? 'Mensaje privado a ' . $con->nombre . '...' : 'Escribí un mensaje...' }}"
                    class="min-w-0 flex-1 rounded-xl px-4 py-2.5 text-sm bg-[#121722] text-white placeholder-gray-500 border border-white/10
                           shadow-[inset_0_2px_6px_rgba(0,0,0,0.7)] focus:outline-none focus:ring-2 focus:ring-indigo-500" />

                {{-- Solo GIFs: al elegirlo se sube y se manda --}}
                <label title="Subir un GIF" class="{{ $boton3d }} w-10 h-10 shrink-0 cursor-pointer text-gray-100" wire:loading.class="opacity-50 pointer-events-none" wire:target="gif">
                    <span class="px-1 rounded border border-gray-300 font-mono text-[9px] leading-tight" wire:loading.remove wire:target="gif">GIF</span>
                    <i class="fa-solid fa-spinner fa-spin text-sm" wire:loading wire:target="gif"></i>
                    <input type="file" accept="image/gif" wire:model="gif" class="hidden">
                </label>

                <button type="submit" aria-label="Enviar" class="{{ $boton3d }} w-10 h-10 shrink-0 text-gray-100 hover:!bg-indigo-600">
                    <i class="fa-solid fa-paper-plane text-sm"></i>
                </button>
            </div>

            @if(! $con && auth()->user()->role === 'admin')
                <button type="button" wire:click="reiniciarChat"
                    class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg whitespace-nowrap text-sm"
                    onclick="confirm('¿Estás seguro de reiniciar el chat? Se borrarán todos los mensajes, también los privados.') || event.stopImmediatePropagation()">
                    Reiniciar Chat
                </button>
            @endif
        </form>
        {{-- Modal: conectados (tocando a alguien se abre el privado) --}}
        @if ($panel === 'online')
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-3" wire:click="alternarPanel('online')">
                <div wire:click.stop class="relative w-full max-w-xs max-h-[80vh] flex flex-col p-4 rounded-xl border border-black text-white
                            bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
                    <button type="button" wire:click="alternarPanel('online')" aria-label="Cerrar"
                        class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black font-bold bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000] hover:brightness-125">&times;</button>
                    <h2 class="flex items-center justify-center gap-2 mb-3 text-lg font-bold text-yellow-300 [text-shadow:0_2px_0_#000]">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.9)]"></span>
                        Conectados · {{ $conectados->count() }}
                    </h2>
                    <div class="min-h-0 overflow-y-auto sidebar-pj space-y-1 pr-1">
                    @forelse ($conectados as $pjOnline)
                        @php $soyYo = $pjOnline->id === $personajeId || $pjOnline->user_id === auth()->id(); @endphp
                        <button type="button" @unless ($soyYo) wire:click="abrirPrivado({{ $pjOnline->id }})" @endunless wire:key="online-{{ $pjOnline->id }}"
                            class="w-full flex items-center gap-2 p-1.5 rounded-lg text-left {{ $soyYo ? 'cursor-default' : 'hover:bg-white/5' }}">
                            <span class="relative shrink-0">
                                <img src="{{ asset('storage/' . $foto($pjOnline)) }}" alt="" class="w-9 h-9 rounded-full object-cover">
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-400 border border-black"></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold {{ $colorNombre($pjOnline) }}">{{ $pjOnline->nombre }}{{ $soyYo ? ' (vos)' : '' }}</span>
                                <span class="block text-[11px] text-yellow-300">Nivel {{ $pjOnline->nivel }}</span>
                            </span>
                            @unless ($soyYo)<i class="fa-regular fa-envelope text-gray-400 text-sm"></i>@endunless
                        </button>
                    @empty
                        <p class="px-1 py-2 text-xs text-gray-400 italic">No hay nadie conectado.</p>
                    @endforelse
                    </div>
                    <p class="mt-2 text-center text-[10px] text-gray-400">Tocá a un jugador para escribirle en privado.</p>
                </div>
            </div>
        @endif

        {{-- Modal: objeto compartido --}}
        @if ($objetoVisto)
            @php
                $ov = $objetoVisto->adjunto ?? [];
                $tipoOv = $ov['tipo'] ?? '';
                $nombreParteOv = $tipoOv === 'pocion' ? 'Poción' : (\App\Support\ChatCompartir::NOMBRE_PARTE[$tipoOv] ?? ucfirst($tipoOv));
                $textoParte = ['equipo' => 'text-indigo-300', 'entrenamiento' => 'text-green-300', 'accesorio' => 'text-pink-300', 'joya' => 'text-amber-300', 'cofre' => 'text-amber-400', 'pocion' => 'text-fuchsia-300'][$tipoOv] ?? 'text-gray-300';
                $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
            @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-3" wire:click="cerrarObjeto">
                <div wire:click.stop class="relative w-full max-w-xs max-h-[85vh] overflow-auto p-4 rounded-xl border border-black text-white text-center
                            bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
                    <button type="button" wire:click="cerrarObjeto" aria-label="Cerrar"
                        class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black font-bold bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000] hover:brightness-125">&times;</button>

                    @if (! empty($ov['imagen']))
                        <img src="{{ asset($ov['imagen']) }}" alt="{{ $ov['nombre'] ?? '' }}"
                             class="mx-auto mt-2 mb-3 w-28 h-28 rounded-lg bg-black/50 border-[3px] {{ $bordeParte[$tipoOv] ?? 'border-black' }} {{ $tipoOv === 'pocion' ? 'object-contain p-2' : 'object-cover' }} shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]">
                    @endif
                    <h2 class="text-lg font-bold text-white [text-shadow:0_2px_0_#000]">{{ $ov['nombre'] ?? 'Objeto' }}</h2>
                    <p class="text-xs font-bold uppercase tracking-wide {{ $textoParte }}">{{ $nombreParteOv }}</p>
                    <p class="mt-0.5 text-xs text-gray-300">
                        {{ collect([! empty($ov['set']) ? 'Set ' . $ov['set'] : null, ! empty($ov['nivel']) ? 'Nivel ' . $ov['nivel'] : null])->filter()->implode(' · ') }}
                    </p>

                    {{-- Tipo de daño y poderes del set (al pasar el mouse o tocarlos, nombre y descripción) --}}
                    @if ($setVisto)
                        <div class="mt-3">
                            @include('livewire.partials.iconos-tipo-poderes', ['tipoIconos' => $setVisto->tipo, 'poderesIconos' => $setVisto->poderes])
                        </div>
                    @endif

                    @if (! empty($ov['stats']))
                        <div class="mt-3 font-mono rounded-lg p-2 grid grid-cols-2 gap-x-3 gap-y-1.5 border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]">
                            @foreach ($ov['stats'] as $stat => $valor)
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="w-8 h-6 shrink-0 flex items-center justify-center font-bold text-yellow-300 {{ $etiqueta3d }}">{{ $abrev[$stat] ?? strtoupper(substr($stat, 0, 3)) }}</span>
                                    <span class="font-bold text-white">+{{ $valor }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($ov['requisitos']))
                        <p class="mt-2 text-xs italic text-purple-300">
                            Requisitos: {{ collect($ov['requisitos'])->map(fn ($v, $s) => $v . ' ' . ($abrev[$s] ?? strtoupper(substr($s, 0, 3))))->implode(', ') }}
                        </p>
                    @endif

                    @if (! empty($ov['descripcion']))
                        <p class="mt-3 text-sm text-gray-200">{{ $ov['descripcion'] }}</p>
                    @endif

                    <p class="mt-3 text-[11px] text-gray-400">Compartido por {{ $objetoVisto->personaje->nombre ?? 'alguien' }} · {{ $objetoVisto->created_at->format('d/m H:i') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
