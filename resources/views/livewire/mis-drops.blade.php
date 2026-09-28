<div class="p-2 text-white text-sm flex flex-col items-center">
    @php
      $input3d = 'px-3 py-1 rounded-md border border-black bg-white text-gray-900 placeholder-gray-500 font-semibold shadow-[inset_0_2px_4px_rgba(0,0,0,0.35),0_2px_0_#000] focus:outline-none focus:ring-2 focus:ring-yellow-500 text-sm';
      // Tarjeta de cada zona: panel rojo oscuro translúcido
      $tarjeta = 'rounded-lg border border-red-900/80 bg-[#3a0d12]/80 shadow-[inset_1px_1px_0_rgba(255,255,255,0.12),0_4px_10px_rgba(0,0,0,0.6)]';
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

    <h2 class="text-3xl font-extrabold uppercase mb-2 text-center bg-gradient-to-b from-yellow-300 to-orange-500 bg-clip-text text-transparent [filter:drop-shadow(0_2px_0_#000)]">Mis Drops</h2>

    {{-- Buscador --}}
    <div class="mb-4 w-full max-w-xs">
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
            <div wire:key="drops-ciudad-{{ $claveCiudad }}" class="{{ $tarjeta }}">
                {{-- Encabezado: miniatura de la zona + nombre --}}
                <div class="flex items-center justify-center gap-2 px-3 py-2 border-b border-red-900/80">
                    @if ($g['ciudad']?->gif)
                        <img src="{{ asset('storage/' . $g['ciudad']->gif) }}" alt="" class="w-7 h-7 shrink-0 rounded object-cover border border-black" loading="lazy" />
                    @endif
                    <h3 class="font-extrabold uppercase text-yellow-400 truncate [text-shadow:0_2px_0_#000]"
                        title="{{ $g['total'] }} {{ $g['total'] === 1 ? 'drop' : 'drops' }}{{ $g['ciudad'] ? ' · Nivel ' . $g['ciudad']->nivel : '' }}">{{ $nombreCiudad }}</h3>
                </div>

                {{-- Lista con su propio scroll --}}
                <div class="relative" wire:ignore.self x-data="{{ $scrollPropio }}"
                     x-init="medir(); new ResizeObserver(() => medir()).observe($refs.lista); new MutationObserver(() => $nextTick(() => medir())).observe($refs.lista, { childList: true, subtree: true })"
                     @mousemove.window="mover($event)" @mouseup.window="arrastrando = false">
                    <div x-ref="lista" @scroll="medir()" class="max-h-[340px] overflow-y-auto lista-drops pl-2 pr-4 py-1">
                        @foreach ($g['items'] as $clave => $i)
                            {{-- Borde del retrato con el color de la ranura: Equipo índigo, Entrenamiento verde, Accesorio rosa --}}
                            @php $borde = ['Equipo' => 'border-indigo-500', 'Entrenamiento' => 'border-green-500', 'Accesorio' => 'border-pink-500'][$i['tipo']] ?? 'border-red-600'; @endphp
                            <div wire:key="drop-{{ $claveCiudad }}-{{ md5($clave) }}" class="flex items-center gap-3 py-1.5"
                                 title="{{ $i['tipo'] }} · Enemigo: {{ $i['enemigo'] }} · {{ $i['veces'] }} {{ $i['veces'] === 1 ? 'vez' : 'veces' }} · Última: {{ \Illuminate\Support\Carbon::parse($i['ultima'])->format('d/m/Y H:i') }}">
                                @php $retrato = $i['enemigoImagen'] ?? $i['imagen']; @endphp
                                @if ($retrato)
                                    <img src="{{ asset($retrato) }}" alt="{{ $i['enemigo'] }}" class="w-11 h-11 shrink-0 rounded-full object-cover bg-white border-[3px] {{ $borde }} shadow-[0_2px_0_#000]" loading="lazy" />
                                @else
                                    <div class="w-11 h-11 shrink-0 rounded-full bg-black/50 border-[3px] {{ $borde }}"></div>
                                @endif
                                <div class="min-w-0 leading-tight">
                                    <span class="block truncate text-xs font-bold text-yellow-400">{{ $nombreCiudad }}</span>
                                    <span class="block truncate text-xs font-bold text-white">{{ $i['nombre'] }}</span>
                                    <span class="block text-[10px] text-red-500">
                                        {{ $i['minutos'] ? implode(' · ', array_map($distancia, $i['minutos'])) : '—' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Barra de scroll propia --}}
                    <div x-show="visible" x-cloak @click.self="clickPista($event)"
                         class="absolute top-1 bottom-1 right-1 w-1.5 rounded bg-black/40">
                        <div @mousedown="empezar($event)" :style="`height:${alto}px; transform:translateY(${arriba}px)`"
                             class="w-full rounded bg-red-700 hover:bg-red-500 cursor-pointer"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
