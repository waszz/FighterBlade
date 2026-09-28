<div class="p-2 text-white text-sm flex flex-col items-center">
    @php
      // Estilos 3D del juego
      $panel3d = 'border border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_8px_16px_rgba(0,0,0,0.6)]';
      $caja3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
      $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
      $input3d = 'px-3 py-1.5 rounded-lg border border-black bg-black/50 text-white placeholder-gray-500 shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)] focus:outline-none focus:ring-2 focus:ring-yellow-500 text-sm';
    @endphp

    <h2 class="text-2xl font-bold uppercase mb-1 text-yellow-300 text-center [text-shadow:0_2px_0_#000]">Mis Drops</h2>
    <p class="text-[11px] text-gray-300 text-center mb-3 max-w-lg [text-shadow:0_1px_0_#000]">
        Lo que conseguiste explorando en cada ciudad y los minutos de la exploración.
    </p>

    {{-- Buscador --}}
    <div class="mb-4 p-3 rounded-xl max-w-lg w-full {{ $panel3d }}">
        <input type="text" placeholder="Buscar ciudad, enemigo, objeto o minutos..." wire:model.live.debounce.300ms="busqueda" class="{{ $input3d }} w-full" />
    </div>

    @if (empty($grupos))
        <p class="text-center text-gray-300 italic text-xs [text-shadow:0_1px_0_#000]">
            {{ $busqueda === '' ? 'Todavía no conseguiste drops explorando.' : 'No se encontraron drops.' }}
        </p>
    @endif

    {{-- La lista tiene su propio scroll (el buscador queda fijo arriba). La barra la dibuja el juego:
         la nativa algunos navegadores (Brave con barras overlay) no la muestran --}}
    <div class="relative max-w-3xl w-full" wire:ignore.self
         x-data="{
            alto: 0, arriba: 0, visible: false, arrastrando: false, inicioY: 0, inicioScroll: 0,
            medir() {
                const l = this.$refs.lista;
                this.visible = l.scrollHeight > l.clientHeight + 1;
                const pista = l.clientHeight;
                this.alto = Math.max(30, pista * l.clientHeight / l.scrollHeight);
                this.arriba = (pista - this.alto) * (l.scrollTop / Math.max(1, l.scrollHeight - l.clientHeight));
            },
            empezar(e) { this.arrastrando = true; this.inicioY = e.clientY; this.inicioScroll = this.$refs.lista.scrollTop; e.preventDefault(); },
            mover(e) {
                if (!this.arrastrando) return;
                const l = this.$refs.lista;
                l.scrollTop = this.inicioScroll + (e.clientY - this.inicioY) * (l.scrollHeight - l.clientHeight) / Math.max(1, l.clientHeight - this.alto);
            },
            clickPista(e) {
                const l = this.$refs.lista, r = e.currentTarget.getBoundingClientRect();
                l.scrollTop = ((e.clientY - r.top) / r.height) * l.scrollHeight - l.clientHeight / 2;
            }
         }"
         x-init="medir(); new ResizeObserver(() => medir()).observe($refs.lista); new MutationObserver(() => $nextTick(() => medir())).observe($refs.lista, { childList: true, subtree: true })"
         @mousemove.window="mover($event)" @mouseup.window="arrastrando = false">
    <div x-ref="lista" @scroll="medir()" class="flex flex-col gap-4 w-full pl-2 pr-5 pb-2 max-h-[65vh] overflow-y-auto lista-drops">
        @foreach ($grupos as $claveCiudad => $g)
            <div wire:key="drops-ciudad-{{ $claveCiudad }}" class="rounded-xl overflow-hidden {{ $panel3d }}">
                {{-- Encabezado con la zona de fondo --}}
                <div class="relative h-16 flex items-center justify-between gap-2 px-4 bg-cover bg-center border-b border-black"
                     style="background-image: url('{{ $g['ciudad']?->gif ? asset('storage/' . $g['ciudad']->gif) : '' }}')">
                    <div class="absolute inset-0 bg-gradient-to-r from-black/85 via-black/50 to-black/85"></div>
                    <div class="relative min-w-0">
                        <h3 class="font-bold text-lg text-white truncate [text-shadow:0_2px_0_#000]">{{ $g['ciudad']->nombre ?? 'Ciudad desconocida' }}</h3>
                        @if ($g['ciudad'])
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold text-yellow-300 {{ $etiqueta3d }}">Nivel {{ $g['ciudad']->nivel }}</span>
                        @endif
                    </div>
                    <span class="relative shrink-0 px-2 py-0.5 rounded-md border border-black bg-black/60 text-xs font-bold text-gray-200" title="Drops conseguidos en esta ciudad">
                        {{ $g['total'] }} {{ $g['total'] === 1 ? 'drop' : 'drops' }}
                    </span>
                </div>

                {{-- Lista: enemigo → drop → minutos --}}
                <div class="px-3 py-1">
                    @foreach ($g['items'] as $clave => $i)
                        <div wire:key="drop-{{ $claveCiudad }}-{{ md5($clave) }}"
                             class="flex items-center gap-3 py-2 {{ $loop->last ? '' : 'border-b border-white/15' }}"
                             title="Última vez: {{ \Illuminate\Support\Carbon::parse($i['ultima'])->format('d/m/Y H:i') }}">
                            {{-- Enemigo --}}
                            <div class="flex items-center gap-2 w-1/3 min-w-0">
                                @if ($i['enemigoImagen'])
                                    <img src="{{ asset($i['enemigoImagen']) }}" alt="{{ $i['enemigo'] }}" class="w-10 h-10 shrink-0 rounded-full object-cover border border-black" loading="lazy" />
                                @else
                                    <div class="w-10 h-10 shrink-0 rounded-full border border-black bg-black/50"></div>
                                @endif
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase text-gray-400 leading-none">Enemigo</span>
                                    <span class="block truncate text-xs font-bold">{{ $i['enemigo'] }}</span>
                                </div>
                            </div>

                            <span class="text-gray-500 shrink-0">➜</span>

                            {{-- Drop (borde y tipo con el color de su ranura: Equipo índigo, Entrenamiento verde, Accesorio rosa) --}}
                            @php [$bordeParte, $textoParte] = ['Equipo' => ['border-indigo-500', 'text-indigo-400'], 'Entrenamiento' => ['border-green-500', 'text-green-400'], 'Accesorio' => ['border-pink-500', 'text-pink-400']][$i['tipo']] ?? ['border-black', 'text-gray-400']; @endphp
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                @if ($i['imagen'])
                                    <img src="{{ asset($i['imagen']) }}" alt="{{ $i['nombre'] }}" class="w-10 h-10 shrink-0 rounded-md object-cover border-2 {{ $bordeParte }} shadow-[0_2px_0_#000]" loading="lazy" />
                                @else
                                    <div class="w-10 h-10 shrink-0 rounded-md border border-black bg-black/50"></div>
                                @endif
                                <div class="min-w-0">
                                    <span class="block truncate text-xs font-bold text-yellow-200">{{ $i['nombre'] }}</span>
                                    <span class="block text-[10px] {{ $textoParte }}">{{ $i['tipo'] }}</span>
                                </div>
                            </div>

                            {{-- Minutos y veces --}}
                            <div class="shrink-0 flex items-center gap-1">
                                @forelse ($i['minutos'] as $min)
                                    <span class="px-1.5 rounded text-[10px] font-bold text-cyan-200 {{ $etiqueta3d }}">{{ $min }} min</span>
                                @empty
                                    <span class="text-[10px] text-gray-500">— min</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Barra de scroll propia --}}
    <div x-show="visible" x-cloak @click.self="clickPista($event)"
         class="absolute top-0 right-0 w-2 h-full rounded bg-black/40 border border-black/60">
        <div @mousedown="empezar($event)" :style="`height:${alto}px; transform:translateY(${arriba}px)`"
             class="w-full rounded bg-[#3d7fd6] hover:bg-[#5b9bf0] cursor-pointer shadow-[0_0_4px_rgba(61,127,214,0.8)]"></div>
    </div>
    </div>
</div>
