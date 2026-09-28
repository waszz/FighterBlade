<div class="p-2 text-white text-sm flex flex-col items-center">
    @php
      // Estilos 3D del juego
      $panel3d = 'border border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_8px_16px_rgba(0,0,0,0.6)]';
      $caja3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
      $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
      $input3d = 'px-3 py-1.5 rounded-lg border border-black bg-black/50 text-white placeholder-gray-500 shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)] focus:outline-none focus:ring-2 focus:ring-yellow-500 text-sm';
      // Distancia según los minutos de la exploración
      $distancia = fn ($min) => ($min <= 5 ? 'Cerca' : ($min <= 10 ? 'Lejos' : 'Muy Lejos')) . " ({$min}')";
      // Barra de scroll propia de cada tarjeta (la nativa algunos navegadores, como Brave con barras overlay, no la muestran)
      $scrollPropio = "{
            alto: 0, arriba: 0, visible: false, arrastrando: false, inicioY: 0, inicioScroll: 0,
            medir() {
                const l = this.\$refs.lista;
                this.visible = l.scrollHeight > l.clientHeight + 1;
                const pista = l.clientHeight;
                this.alto = Math.max(24, pista * l.clientHeight / l.scrollHeight);
                this.arriba = (pista - this.alto) * (l.scrollTop / Math.max(1, l.scrollHeight - l.clientHeight));
            },
            empezar(e) { this.arrastrando = true; this.inicioY = e.clientY; this.inicioScroll = this.\$refs.lista.scrollTop; e.preventDefault(); },
            mover(e) {
                if (!this.arrastrando) return;
                const l = this.\$refs.lista;
                l.scrollTop = this.inicioScroll + (e.clientY - this.inicioY) * (l.scrollHeight - l.clientHeight) / Math.max(1, l.clientHeight - this.alto);
            },
            clickPista(e) {
                const l = this.\$refs.lista, r = e.currentTarget.getBoundingClientRect();
                l.scrollTop = ((e.clientY - r.top) / r.height) * l.scrollHeight - l.clientHeight / 2;
            }
        }";
    @endphp

    <h2 class="text-2xl font-bold uppercase mb-2 text-yellow-300 text-center [text-shadow:0_2px_0_#000]">Mis Drops</h2>

    {{-- Buscador --}}
    <div class="mb-4 p-2 rounded-xl w-full max-w-xs {{ $panel3d }}">
        <input type="text" placeholder="Filtrar drops" wire:model.live.debounce.300ms="busqueda" class="{{ $input3d }} w-full" />
    </div>

    @if (empty($grupos))
        <p class="text-center text-gray-300 italic text-xs [text-shadow:0_1px_0_#000]">
            {{ $busqueda === '' ? 'Todavía no conseguiste drops explorando.' : 'No se encontraron drops.' }}
        </p>
    @endif

    {{-- Una tarjeta por zona, en grilla --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 w-full max-w-5xl items-start">
        @foreach ($grupos as $claveCiudad => $g)
            @php $nombreCiudad = $g['ciudad']->nombre ?? 'Ciudad desconocida'; @endphp
            <div wire:key="drops-ciudad-{{ $claveCiudad }}" class="rounded-xl overflow-hidden {{ $panel3d }}">
                {{-- Encabezado: miniatura de la zona + nombre --}}
                <div class="flex items-center justify-center gap-2 px-3 py-2 rounded-none {{ $caja3d }}">
                    @if ($g['ciudad']?->gif)
                        <img src="{{ asset('storage/posts/' . $g['ciudad']->gif) }}" alt="" class="w-7 h-7 shrink-0 rounded object-cover border border-black" loading="lazy" />
                    @endif
                    <h3 class="font-bold uppercase text-yellow-300 truncate [text-shadow:0_2px_0_#000]"
                        title="{{ $g['total'] }} {{ $g['total'] === 1 ? 'drop' : 'drops' }}{{ $g['ciudad'] ? ' · Nivel ' . $g['ciudad']->nivel : '' }}">{{ $nombreCiudad }}</h3>
                </div>

                {{-- Lista con su propio scroll --}}
                <div class="relative" wire:ignore.self x-data="{{ $scrollPropio }}"
                     x-init="medir(); new ResizeObserver(() => medir()).observe($refs.lista); new MutationObserver(() => $nextTick(() => medir())).observe($refs.lista, { childList: true, subtree: true })"
                     @mousemove.window="mover($event)" @mouseup.window="arrastrando = false">
                    <div x-ref="lista" @scroll="medir()" class="max-h-[340px] overflow-y-auto lista-drops pl-2 pr-4 py-1">
                        @foreach ($g['items'] as $clave => $i)
                            {{-- Borde del retrato con el color de la ranura: Equipo índigo, Entrenamiento verde, Accesorio rosa --}}
                            @php $borde = ['Equipo' => 'border-indigo-500', 'Entrenamiento' => 'border-green-500', 'Accesorio' => 'border-pink-500'][$i['tipo']] ?? 'border-black'; @endphp
                            <div wire:key="drop-{{ $claveCiudad }}-{{ md5($clave) }}" class="flex items-center gap-3 py-1.5"
                                 title="{{ $i['tipo'] }} · Enemigo: {{ $i['enemigo'] }} · {{ $i['veces'] }} {{ $i['veces'] === 1 ? 'vez' : 'veces' }} · Última: {{ \Illuminate\Support\Carbon::parse($i['ultima'])->format('d/m/Y H:i') }}">
                                @php $retrato = $i['enemigoImagen'] ?? $i['imagen']; @endphp
                                @if ($retrato)
                                    <img src="{{ asset($retrato) }}" alt="{{ $i['enemigo'] }}" class="w-11 h-11 shrink-0 rounded-full object-cover bg-black/50 border-[3px] {{ $borde }} shadow-[0_2px_0_#000]" loading="lazy" />
                                @else
                                    <div class="w-11 h-11 shrink-0 rounded-full bg-black/50 border-[3px] {{ $borde }}"></div>
                                @endif
                                <div class="min-w-0 leading-tight">
                                    <span class="block truncate text-xs font-bold text-yellow-300">{{ $nombreCiudad }}</span>
                                    <span class="block truncate text-xs font-bold text-white [text-shadow:0_1px_0_#000]">{{ $i['nombre'] }}</span>
                                    <span class="flex flex-wrap gap-1 mt-0.5">
                                        @forelse ($i['minutos'] as $min)
                                            <span class="px-1.5 rounded text-[10px] font-bold text-orange-300 {{ $etiqueta3d }}">{{ $distancia($min) }}</span>
                                        @empty
                                            <span class="text-[10px] text-gray-500">—</span>
                                        @endforelse
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Barra de scroll propia --}}
                    <div x-show="visible" x-cloak @click.self="clickPista($event)"
                         class="absolute top-1 bottom-1 right-1 w-2 rounded bg-black/40 border border-black/60">
                        <div @mousedown="empezar($event)" :style="`height:${alto}px; transform:translateY(${arriba}px)`"
                             class="w-full rounded bg-[#3d7fd6] hover:bg-[#5b9bf0] cursor-pointer shadow-[0_0_4px_rgba(61,127,214,0.8)]"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
