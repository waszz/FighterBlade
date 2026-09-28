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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-w-6xl w-full px-2">
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

            {{-- Partes: bonus y requisitos --}}
            <div class="w-full grid grid-cols-3 gap-1.5">
                @foreach (['equipo_imagen' => 'Equipo', 'entrenamiento_imagen' => 'Entrenam.', 'accesorio_imagen' => 'Accesorio'] as $campo => $label)
                @php
                $tipoParte = str_replace('_imagen', '', $campo);
                $ajustes = $personaje->{'ajustes_manuales_' . $tipoParte} ?? [];
                $requisitos = $personaje->{'requisitos_' . $tipoParte} ?? [];
                @endphp
                <div class="flex flex-col items-center p-1.5 rounded-lg {{ $caja3d }}">
                    @if (!empty($personaje->$campo))
                    <img src="{{ asset('storage/posts/' . $personaje->$campo) }}" alt="{{ $label }}"
                        class="w-10 h-10 rounded-md object-cover border border-black shadow-[0_2px_0_#000]" loading="lazy" />
                    @else
                    <div class="w-10 h-10 rounded-md border border-black bg-black/50"></div>
                    @endif
                    <span class="mt-1 text-[9px] text-gray-300 select-none">{{ $label }}</span>
                    @foreach ($ajustes as $stat => $valor)
                    @if ($valor > 0)
                    <span class="text-[10px] font-bold text-emerald-300 leading-tight">+{{ $valor }} {{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }}</span>
                    @endif
                    @endforeach
                    @foreach ($requisitos as $stat => $valor)
                    @if ($valor > 0)
                    <span class="text-[10px] font-bold text-red-400 leading-tight" title="Requisito para equipar">Req {{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }} {{ $valor }}</span>
                    @endif
                    @endforeach
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
