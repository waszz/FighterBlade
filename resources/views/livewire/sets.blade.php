<div class="p-2 text-white text-sm flex flex-col items-center">
    @php
      // Estilos 3D del juego
      $panel3d = 'border border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_8px_16px_rgba(0,0,0,0.6)]';
      $caja3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
      $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
      $input3d = 'px-3 py-1.5 rounded-lg border border-black bg-black/50 text-white placeholder-gray-500 shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)] focus:outline-none focus:ring-2 focus:ring-green-500 text-sm';
      $abreviaturas = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'velocidad' => 'VEL', 'resistencia' => 'RES', 'defensa' => 'DEF', 'energia' => 'ENE'];
    @endphp

    <h2 class="text-2xl font-bold uppercase mb-3 text-green-400 text-center [text-shadow:0_2px_0_#000]">
        Personajes
    </h2>

    {{-- Filtros --}}
    <div class="flex flex-col md:flex-row items-center justify-center gap-2 mb-4 p-3 rounded-xl max-w-lg w-full {{ $panel3d }}">
        <input type="text" placeholder="Nombre..." wire:model="busquedaTitulo" class="{{ $input3d }} w-full md:w-auto md:flex-1" />

        <select wire:model="nivelSeleccionado" class="{{ $input3d }} w-full md:w-auto">
            <option value="todos">Todos los niveles</option>
            @foreach ($nivelesDisponibles as $nivel)
            <option value="{{ $nivel }}">Nivel {{ $nivel }}</option>
            @endforeach
        </select>

        <button wire:click="buscar"
            class="w-full md:w-auto px-6 py-1.5 rounded-lg border border-black text-white font-bold text-sm bg-gradient-to-b from-emerald-500 to-emerald-800
                   shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100">
            Buscar
        </button>
    </div>

    @if ($personajes->isEmpty())
    <p class="text-center text-gray-300 italic text-xs max-w-md [text-shadow:0_1px_0_#000]">
        @if (empty($busquedaTitulo) && $nivelSeleccionado === 'todos')
        Buscá por nombre o elegí un nivel.
        @else
        No se encontraron personajes.
        @endif
    </p>
    @else
    <div class="grid gap-4 max-w-6xl w-full px-2 [grid-template-columns:repeat(auto-fit,minmax(260px,1fr))]">
        @foreach ($personajes as $personaje)
        <div wire:key="set-{{ $personaje->id }}" class="p-3 rounded-xl flex flex-col items-center gap-2 text-xs {{ $panel3d }}">

            {{-- Nombre y nivel --}}
            <div class="w-full text-center">
                <h3 class="font-bold text-base text-white truncate [text-shadow:0_2px_0_#000]">{{ $personaje->titulo }}</h3>
                <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px] font-bold text-yellow-300 {{ $etiqueta3d }}" title="Nivel requerido para equipar">
                    Nivel {{ $personaje->nivel }}
                </span>
            </div>

            {{-- Gif (mismo tamaño que en la pelea) --}}
            <div class="w-full h-36 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
                @if ($personaje->gif)
                <img src="{{ asset('storage/' . $personaje->gif) }}" alt="{{ $personaje->titulo }}"
                    style="{{ \App\Models\Post::estiloGif($personaje->gif, 0.8) }}" class="block max-w-none" loading="lazy" />
                @endif
            </div>

            {{-- Tipo de daño y poderes: solo iconos (el nombre aparece al pasar el mouse o tocar) --}}
            <div class="flex flex-wrap justify-center gap-1.5">
                @if ($personaje->tipo)
                <x-icono-tipo :tipo="$personaje->tipo" tam="w-9 h-9" class="cursor-pointer" />
                @endif
                @foreach ($personaje->poderes as $poder)
                <x-icono-poder :poder="$poder" tam="w-9 h-9" class="cursor-pointer" />
                @endforeach
            </div>

            {{-- Partes (como en el Mercado y el Inventario): borde del color de la parte, stats en recuadros y requisitos.
                 Tocando una se abre su tarjeta (como la del Inventario) --}}
            <div class="w-full grid grid-cols-3 gap-1.5">
                @foreach (['equipo' => 'Equipo', 'entrenamiento' => 'Entrenamiento', 'accesorio' => 'Accesorio'] as $tipoParte => $label)
                @php
                $imagenParte = $personaje->{$tipoParte . '_imagen'} ?? null;
                $nombreParte = $personaje->{$tipoParte . '_nombre'} ?: ($label . ' de ' . $personaje->titulo);
                $ajustes = array_filter((array) ($personaje->{'ajustes_manuales_' . $tipoParte} ?? []), fn ($v) => $v > 0);
                $requisitos = $personaje->{'requisitos_' . $tipoParte} ?? [];
                $requisitos = array_filter(is_string($requisitos) ? (json_decode($requisitos, true) ?: []) : (array) $requisitos, fn ($v) => $v > 0);
                [$bordeParte, $textoParte] = ['equipo' => ['border-indigo-500', 'text-indigo-300'], 'entrenamiento' => ['border-green-500', 'text-green-300'], 'accesorio' => ['border-pink-500', 'text-pink-300']][$tipoParte];
                @endphp
                <div x-data="{ ver: false }" class="flex flex-col items-center gap-1 p-1.5 rounded-lg cursor-pointer hover:brightness-125 transition {{ $caja3d }}"
                     x-on:click="ver = true" title="Ver {{ $label }}">
                    @if ($imagenParte)
                    <img src="{{ asset('storage/posts/' . $imagenParte) }}" alt="{{ $label }}" loading="lazy"
                        class="w-11 h-11 rounded-md object-cover bg-black/50 border-2 {{ $bordeParte }} shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]" />
                    @else
                    <div class="w-11 h-11 rounded-md bg-black/50 border-2 {{ $bordeParte }}"></div>
                    @endif
                    <span class="text-[9px] font-bold uppercase tracking-tight leading-none {{ $textoParte }} select-none">{{ $label }}</span>
                    <div class="flex flex-wrap justify-center gap-0.5">
                        @foreach ($ajustes as $stat => $valor)
                        <span class="border border-black bg-gradient-to-b from-green-600 to-green-900 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] text-white text-[10px] font-bold px-1 rounded whitespace-nowrap">
                            {{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }} +{{ $valor }}
                        </span>
                        @endforeach
                    </div>
                    @if ($requisitos)
                    <span class="text-[9px] text-purple-400 italic leading-tight text-center" title="Requisito para equipar">
                        Requisitos: {{ collect($requisitos)->map(fn ($v, $s) => $v . ' ' . ($abreviaturas[strtolower($s)] ?? strtoupper(substr($s, 0, 3))))->join(', ') }}
                    </span>
                    @endif

                    {{-- Tarjeta de la parte (misma que en el Inventario) --}}
                    <template x-teleport="body">
                    <div x-show="ver" x-cloak x-transition.opacity x-on:click.self="ver = false" x-on:keydown.escape.window="ver = false"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-3">
                        <div class="w-full max-w-[220px] text-white bg-gradient-to-b from-[#232c3a] to-[#0c0f14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000] border-2 border-black rounded-xl p-3 relative">
                            <button type="button" x-on:click="ver = false" class="absolute top-1 right-2 text-gray-400 hover:text-white text-2xl leading-none transition">&times;</button>
                            <div class="flex flex-col items-center text-center">
                                <p class="text-sm font-bold text-white mb-1 px-4 truncate max-w-full">{{ $nombreParte }}</p>
                                <p class="text-[11px] font-bold uppercase mb-2 {{ $textoParte }}">{{ $label }} · Nivel {{ $personaje->nivel }}</p>
                                @if ($imagenParte)
                                <img src="{{ asset('storage/posts/' . $imagenParte) }}" alt="{{ $nombreParte }}"
                                     class="w-20 h-20 object-cover rounded-lg mb-2 bg-black/50 border-2 {{ $bordeParte }} shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_3px_0_#000]" />
                                @endif
                                @if ($personaje->tipo)
                                <div class="mb-1 flex justify-center"><x-icono-tipo :tipo="$personaje->tipo" tam="w-8 h-8" /></div>
                                @endif
                                <div class="w-full grid grid-cols-2 gap-x-2 gap-y-1.5">
                                    @forelse ($ajustes as $stat => $valor)
                                    <p class="text-green-400 text-xs font-semibold px-1 py-0.5 rounded {{ $etiqueta3d }}">{{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }} +{{ $valor }}</p>
                                    @empty
                                    <p class="text-gray-500 text-xs italic col-span-2">Sin stats</p>
                                    @endforelse
                                </div>
                                <div class="w-full mt-2 pt-2 border-t border-white/10 text-xs text-left">
                                    <p class="text-gray-400 font-semibold mb-1">Requiere:</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded border border-black font-bold text-yellow-300 bg-black/40">Nivel {{ $personaje->nivel }}</span>
                                        @foreach ($requisitos as $stat => $valor)
                                        <span class="px-1.5 py-0.5 rounded border border-black font-bold text-purple-300 bg-black/40">{{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }} {{ $valor }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </template>
                </div>
                @endforeach
            </div>

            {{-- Stats: etiqueta 3D + número (como el panel lateral) --}}
            @if (isset($statsConColorPorPersonaje[$personaje->id]) && is_array($statsConColorPorPersonaje[$personaje->id]))
            <div class="w-full grid grid-cols-2 gap-x-3 gap-y-1.5 p-2 rounded-lg font-mono {{ $caja3d }}">
                @foreach ($statsConColorPorPersonaje[$personaje->id] as $statData)
                <div class="flex items-center gap-2 select-none">
                    <div class="w-8 h-6 shrink-0 flex items-center justify-center text-[11px] font-bold text-yellow-300 tracking-wide {{ $etiqueta3d }}">
                        {{ $abreviaturas[strtolower($statData['stat'])] ?? strtoupper(substr($statData['stat'], 0, 3)) }}
                    </div>
                    <span class="font-bold text-white text-sm">+{{ $statData['valor'] }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
