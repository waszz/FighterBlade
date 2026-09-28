<div class="text-white">

    <h2
        class="text-4xl md:text-5xl font-extrabold text-center mb-6 uppercase tracking-widest text-gradient bg-clip-text text-transparent bg-gradient-to-r from-yellow-600 via-yellow-500 to-red-500 drop-shadow-lg">
        Mercado
    </h2>
    <p class="text-lg text-white text-center mb-6 font-semibold drop-shadow-md">
        Explora objetos en venta
    </p>
    @if ($esAdmin)
    <div class="flex justify-center mb-4">
        <button wire:click="forzarActualizacion" class="bg-gradient-to-b from-red-500 to-red-800 px-3 py-1 rounded text-white font-bold text-center text-sm border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0">
            🔄 Actualizar Mercado
        </button>
    </div>
    @endif

    @php
    $abreviaturas = [
    'fuerza' => 'FUE',
    'ataque' => 'ATQ',
    'velocidad' => 'VEL',
    'resistencia' => 'RES',
    'defensa' => 'DEF',
    'energia' => 'ENE',
    ];
    @endphp
    <div wire:key="mercado-{{ $reloadMercado }}">
        <div class="flex flex-wrap gap-6 p-4 mb-6">
            @foreach($postsDisponibles as $post)
            @php
            // Algunos campos vienen casteados como array desde el modelo y otros como string JSON
            $decodificar = fn($valor) => is_array($valor) ? $valor : (json_decode($valor ?? '{}', true) ?: []);

            $ajustesEquipo = $decodificar($post->ajustes_manuales_equipo);
            $ajustesEntrenamiento = $decodificar($post->ajustes_manuales_entrenamiento);
            $ajustesAccesorio = $decodificar($post->ajustes_manuales_accesorio);

            $requisitosEquipo = $decodificar($post->requisitos_equipo);
            $requisitosEntrenamiento = $decodificar($post->requisitos_entrenamiento);
            $requisitosAccesorio = $decodificar($post->requisitos_accesorio);

            $poderes = $post->poderes ?? collect();

            // Ajusta según tu estructura real para stats del personaje
            $statsPersonaje = is_string($post->stats_personaje) ? json_decode($post->stats_personaje, true) :
            ($post->stats_personaje ?? []);

            $items = [
            [
            'tipo' => 'Equipo',
            'imagen' => $post->equipo_imagen,
            'ajustes' => $ajustesEquipo,
            'requisitos' => $requisitosEquipo,
            ],
            [
            'tipo' => 'Entrenamiento',
            'imagen' => $post->entrenamiento_imagen,
            'ajustes' => $ajustesEntrenamiento,
            'requisitos' => $requisitosEntrenamiento,
            ],
            [
            'tipo' => 'Accesorio',
            'imagen' => $post->accesorio_imagen,
            'ajustes' => $ajustesAccesorio,
            'requisitos' => $requisitosAccesorio,
            ],
            ];
            @endphp

            <div class="flex-shrink-0 w-60 bg-gradient-to-b from-[#1c2533] to-[#0a0e14] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] p-3 text-white transition rounded-lg">
                {{-- Imagen principal --}}
                {{-- Misma escala que en las peleas (según el alto del personaje en el gif): todos quedan del mismo tamaño --}}
                <div class="h-24 mb-2 flex items-end justify-center overflow-hidden">
                    <img src="{{ asset('storage/' . $post->gif) }}" alt="{{ $post->nombre }}" loading="lazy"
                        style="{{ \App\Models\Post::estiloGif($post->gif, 0.65) }}" class="block max-w-none">
                </div>

                {{-- Nombre y nivel --}}
                <p class="text-center font-bold text-lg text-yellow-400 truncate">{{ $post->nombre }}</p>
                <p class="text-center text-sm text-white mb-0.5">Nivel {{ $post->nivel ?? 1 }}</p>
                {{-- Tipo de daño y poderes: solo iconos, al pasar el mouse o tocarlos muestran nombre y descripción --}}
                <div class="flex flex-wrap items-center justify-center gap-1.5 mb-3">
                    @if ($post->tipo)
                        <x-icono-tipo :tipo="$post->tipo" tam="w-8 h-8" class="cursor-pointer" />
                    @endif
                    @foreach ($poderes as $poder)
                        <x-icono-poder :poder="$poder" tam="w-8 h-8" class="cursor-pointer" />
                    @endforeach
                </div>

                <div class="flex justify-center gap-2 mb-3">
                    @foreach ($items as $item)
                    {{-- Icono de la parte: cuadrado 3D con el borde y el texto del color de su ranura (como en el inventario y Mis Drops) --}}
                    @php [$bordeParte, $textoParte] = ['Equipo' => ['border-indigo-500', 'text-indigo-300'], 'Entrenamiento' => ['border-green-500', 'text-green-300'], 'Accesorio' => ['border-pink-500', 'text-pink-300']][$item['tipo']]; @endphp
                    <div class="text-center w-[4.5rem]">
                        <img src="{{ asset('storage/posts/' . $item['imagen']) }}" alt="{{ $item['tipo'] }}" loading="lazy"
                            class="w-12 h-12 rounded-md object-cover mx-auto mb-1 bg-black/50 border-2 {{ $bordeParte }} shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">

                        {{-- Texto con tipo debajo de la imagen --}}
                        <p class="text-[10px] font-bold {{ $textoParte }} mb-1.5 select-none">
                            {{ $item['tipo'] }}
                        </p>

                        {{-- Ajustes Manuales --}}
                        @if(!empty($item['ajustes']))
                        <div class="mb-2 flex flex-wrap justify-center gap-1 text-[10px] font-semibold text-green-600">
                            @foreach($item['ajustes'] as $stat => $valor)
                            @if($valor > 0)
                            <span class="border border-black bg-gradient-to-b from-green-600 to-green-900 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] text-white px-2 py-0.5 rounded">
                                {{ $abreviaturas[$stat] ?? strtoupper($stat) }} +{{ $valor }}
                            </span>
                            @endif
                            @endforeach
                        </div>
                        @endif

                        {{-- Requisitos --}}
                        @if(!empty($item['requisitos']))
                        <div class="text-purple-400 italic text-[10px] mt-2">
                            Requisitos:
                            @foreach($item['requisitos'] as $stat => $valor)
                            <span>
                                {{ $valor }} {{ $abreviaturas[$stat] ?? strtoupper($stat) }}@if(!$loop->last), @endif
                            </span>
                            @endforeach
                        </div>
                        @else
                        <div class="text-purple-400 italic text-[10px] mt-2">Sin requisitos</div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @php
                    $nivel = $post->nivel ?? 1;
                    $costo = $nivel === 5 ? 250 : 250 + ($nivel - 5) * 20;
                @endphp


                {{-- Botón Comprar --}}
                <button wire:click="comprarPersonaje({{ $post->id }})" wire:loading.attr="disabled"
                    class="w-full bg-gradient-to-b from-blue-500 to-blue-800 text-white font-bold text-xs py-1.5 rounded border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="comprarPersonaje({{ $post->id }})">
                      
                        <span>Comprar por <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-4 w-4 align-[-0.2em]"> {{ $costo }} </span>
                    </span>
                    <span wire:loading wire:target="comprarPersonaje({{ $post->id }})">
                        Comprando...
                    </span>
                </button>
            </div>
            @endforeach
        </div>
        @if (session()->has('mensaje'))
        <div class="fixed top-4 right-4 z-[100] max-w-xs w-full" x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)" x-show="show"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-8">
            <div class="bg-green-600 border-l-4 border-green-300 text-white px-4 py-3 rounded-lg shadow-2xl flex items-center gap-3 w-full">
                <i class="fas fa-check-circle text-xl"></i>
                <span class="text-sm font-semibold">
                    {{ session('mensaje') }}
                </span>
            </div>
        </div>
        @endif

    </div>


    <h3 class="text-2xl text-center font-bold text-white mt-10 mb-4">Mercado de Pociones</h3>
    <livewire:mercado-pociones :personajeId="$personaje->id" />

    <h3 class="text-2xl text-center font-bold text-white mt-10 mb-4">Objetos de la Semana</h3>

    <div wire:key="partesRandom-{{ $reloadMercado }}" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 px-4">
        @foreach($partesRandom as $index => $parte)

        {{-- @dd($parte) --}}
        @php
        $tipo = $parte['tipo'];
        $imagen = $parte['imagen'];
        $nombre = $parte['nombre'];
        $ajustes = is_string($parte['ajustes']) ? json_decode($parte['ajustes'], true) : ($parte['ajustes'] ?? []);
        $nivel = $parte['nivel'] ?? 1;
        $costo = 50 * $nivel;
        $estilo = $parte['estilo'] ?? null;
        // Set de origen (tipo de daño y poderes): el guardado en la oferta o, en las viejas, buscado por nombre
        $postParte = ! empty($parte['origen_post_id'])
            ? \App\Models\Post::conRivales()->with('poderes')->find($parte['origen_post_id'])
            : \App\Models\Post::with('poderes')->where($tipo . '_nombre', $nombre)->where('nivel', $nivel)->first();
        $estilo = $postParte?->tipo ?? $estilo;
        $requisitos = [];

        if ($tipo === 'equipo' && isset($parte['requisitos_equipo'])) {
        $requisitos = is_string($parte['requisitos_equipo']) ? json_decode($parte['requisitos_equipo'], true) :
        $parte['requisitos_equipo'];
        } elseif ($tipo === 'entrenamiento' && isset($parte['requisitos_entrenamiento'])) {
        $requisitos = is_string($parte['requisitos_entrenamiento']) ? json_decode($parte['requisitos_entrenamiento'],
        true) : $parte['requisitos_entrenamiento'];
        } elseif ($tipo === 'accesorio' && isset($parte['requisitos_accesorio'])) {
        $requisitos = is_string($parte['requisitos_accesorio']) ? json_decode($parte['requisitos_accesorio'], true) :
        $parte['requisitos_accesorio'];
        }
        $abreviaturas = [
        'fuerza' => 'FUE',
        'ataque' => 'ATQ',
        'velocidad' => 'VEL',
        'resistencia' => 'RES',
        'defensa' => 'DEF',
        'energia' => 'ENE',
        ];
        @endphp

        @if($nombre && $imagen)
        <div class="bg-gradient-to-b from-[#2a3240] to-[#10141b] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] p-3 rounded-lg text-center text-sm text-white">
            @php [$bordeParte, $textoParte] = ['equipo' => ['border-indigo-500', 'text-indigo-300'], 'entrenamiento' => ['border-green-500', 'text-green-300'], 'accesorio' => ['border-pink-500', 'text-pink-300']][$tipo] ?? ['border-black', 'text-gray-300']; @endphp
            <img src="{{ asset('storage/posts/' . $imagen) }}" alt="{{ $nombre }}" loading="lazy"
                class="w-16 h-16 mx-auto object-cover rounded-md mb-2 bg-black/50 border-2 {{ $bordeParte }} shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
            <p class="text-xs font-bold uppercase mb-1 {{ $textoParte }}">{{ ucfirst($tipo) }}</p>
            <p class="font-bold text-white truncate">{{ $nombre }}</p>
            <p class="text-xs text-white mb-1">Nivel: {{ $nivel }}</p>
            @include('livewire.partials.iconos-tipo-poderes', ['tipoIconos' => $estilo, 'poderesIconos' => $postParte?->poderes])


            {{-- Stats desde ajustes --}}
            @if(!empty($ajustes))
            <div class="flex flex-wrap justify-center gap-1 text-xs mb-1">
                @foreach($ajustes as $stat => $valor)
                @if($valor > 0)
                <span class="border border-black bg-gradient-to-b from-green-600 to-green-900 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] text-white px-2 rounded">
                    {{ $abreviaturas[$stat] ?? strtoupper($stat) }} +{{ $valor }}
                </span>
                @endif
                @endforeach
            </div>
            @endif

            {{-- Requisitos según tipo --}}
            @if (!empty($requisitos))
            <div class="mb-1 text-xs text-purple-400 italic">
                Requisitos:
                @foreach ($requisitos as $stat => $valor)
                <span>
                    {{ $valor }} {{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }}
                    @if (!$loop->last), @endif
                </span>
                @endforeach
            </div>
            @endif

            {{-- Costo --}}
  <p class="text-xs text-yellow-400 mb-2 mt-2 flex items-center gap-1 justify-center">
  <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-4 h-4 inline-block" />
  {{ number_format($costo, 0, ',', '.') }}
</p>
            {{-- Botón Comprar --}}
            <button wire:click="comprarParteAleatoria({{ $index }})"
                class="bg-gradient-to-b from-green-500 to-green-800 text-white font-bold border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0 py-1 px-3 rounded-lg text-xs w-full">
                Comprar
            </button>
        </div>
        @endif
        @endforeach
    </div>

    @if($objetosEnVenta->isEmpty())
    <p class="mt-4 text-sm text-gray-400 text-center">No hay objetos en venta por ahora.</p>
    @else
    @php
    // Filtramos para quitar la Poción de Recuperación de la lista general
    $objetosEnVentaSinPocion = $objetosEnVenta->reject(function($obj) {
    return strtolower($obj->nombre) === 'poción de recuperación';
    });
    @endphp

    <h3 class="text-xl text-center font-bold text-white mt-8 mb-3">Vendidos por jugadores</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 px-4">
        @foreach($objetosEnVentaSinPocion as $objeto)
        @php
        $stats = is_string($objeto->stats) ? json_decode($objeto->stats, true) : ($objeto->stats ?? []);
        $requisitos = [];
        if ($objeto->tipo === 'equipo') {
        $requisitos = is_string($objeto->requisitos_equipo) ? json_decode($objeto->requisitos_equipo, true) :
        ($objeto->requisitos_equipo ?? []);
        } elseif ($objeto->tipo === 'entrenamiento') {
        $requisitos = is_string($objeto->requisitos_entrenamiento) ? json_decode($objeto->requisitos_entrenamiento,
        true) : ($objeto->requisitos_entrenamiento ?? []);
        } elseif ($objeto->tipo === 'accesorio') {
        $requisitos = is_string($objeto->requisitos_accesorio) ? json_decode($objeto->requisitos_accesorio, true) :
        ($objeto->requisitos_accesorio ?? []);
        }

        $abreviaturas = [
        'fuerza' => 'FUE',
        'ataque' => 'ATQ',
        'velocidad' => 'VEL',
        'resistencia' => 'RES',
        'defensa' => 'DEF',
        'energia' => 'ENE',
        ];

        $nombreMinuscula = strtolower($objeto->nombre);
        $esPocion = str_contains($nombreMinuscula, 'poción');
        $rutaImagen = $esPocion
        ? asset('images/' . $objeto->imagen)
        : asset('storage/posts/' . $objeto->imagen);
        @endphp

        <div class="bg-gradient-to-b from-[#2a3240] to-[#10141b] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] p-3 rounded-lg text-center text-sm text-white">
            @php [$bordeParte, $textoParte] = ['equipo' => ['border-indigo-500', 'text-indigo-300'], 'entrenamiento' => ['border-green-500', 'text-green-300'], 'accesorio' => ['border-pink-500', 'text-pink-300']][$objeto->tipo] ?? ['border-black', 'text-gray-300']; @endphp
            <img src="{{ $rutaImagen }}" alt="{{ $objeto->nombre }}" loading="lazy"
                class="mx-auto w-16 h-16 mb-2 rounded-md {{ $esPocion ? 'object-contain' : 'object-cover bg-black/50 border-2 ' . $bordeParte . ' shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]' }}">
            <p class="text-xs font-bold uppercase mb-1 {{ $textoParte }}">{{ ucfirst($objeto->tipo) }}</p>
            <p class="font-bold text-white truncate">{{ $objeto->nombre ?? 'Objeto Misterioso' }}</p>
            <p class="text-xs text-white mb-1">Nivel: {{ $objeto->nivel ?? 1 }}</p>

            {{-- SOLO PARA POCIONES: mostrar usos y qué afecta --}}
            @if($esPocion)
            @php
            $usosTotales = $stats['usos_totales'] ?? 1;
            $usosRestantes = $stats['usos_restantes'] ?? $usosTotales;
            @endphp

            <p class="text-xs text-pink-400 mb-1">Usos: {{ $usosRestantes }}/{{ $usosTotales }}</p>

            @endif

            @if (!empty($stats))
            @php
            // Mostrar campo "afecta" si existe y es string
            $afectaTexto = $stats['afecta'] ?? null;
            if (is_array($afectaTexto)) {
            $afectaTexto = implode(', ', $afectaTexto);
            }
            if ($afectaTexto === 'drop_partes') {
            $afectaTexto = '100% de drop de partes';
            }
            @endphp

            @if ($afectaTexto)
            <p class="text-xs text-blue-300 mb-1">Afecta: {{ ucfirst($afectaTexto) }}</p>
            @endif
            @endif


            {{-- Tipo de daño y poderes del set de la parte (no en pociones) --}}
            @if (!$esPocion)
            @include('livewire.partials.iconos-tipo-poderes', ['tipoIconos' => $objeto->post?->tipo ?? $objeto->estilo, 'poderesIconos' => $objeto->post?->poderes])
            @endif

            {{-- Stats SOLO si no es poción --}}
            @if (!empty($stats) && !$esPocion)
            <div class="flex flex-wrap justify-center gap-1 text-xs mb-1">
                @foreach ($stats as $stat => $valor)
                @if($valor > 0)
                <span class="border border-black bg-gradient-to-b from-green-600 to-green-900 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000] text-white px-2 rounded">
                    {{ ($abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3))) }} +{{ $valor }}
                </span>
                @endif
                @endforeach
            </div>
            @endif

            {{-- Requisitos --}}
            @if (!empty($requisitos))
            <div class="mb-1 text-xs text-purple-400 italic">
                Requisitos:
                @foreach ($requisitos as $stat => $valor)
                <span>
                    {{ $valor }} {{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }}
                    @if (!$loop->last), @endif
                </span>
                @endforeach
            </div>
            @endif

<p class="text-xs text-yellow-400 mb-2 mt-2 flex items-center gap-1 justify-center">
  <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-4 h-4 inline-block" />
  {{ number_format($objeto->precio_venta ?? 250, 0, ',', '.') }}
</p>
            <p class="text-xs text-gray-500 mb-2 truncate">Vendedor: {{ $objeto->personaje->nombre ?? 'Desconocido' }}
            </p>

            @if($objeto->personaje_id === $personaje->id)
            <button disabled class="mt-1 bg-gradient-to-b from-gray-500 to-gray-700 border border-black text-white py-1 px-2 rounded-lg text-xs cursor-not-allowed w-full opacity-70 shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6)]">
                Tu objeto
            </button>
            @else
            <button wire:click="comprarObjeto({{ $objeto->id }})"
                class="mt-1 bg-gradient-to-b from-green-500 to-green-800 text-white font-bold border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0 py-1 px-2 rounded-lg text-xs w-full">
                Comprar
            </button>
            @endif
        </div>
        @endforeach
    </div>
    @endif

</div>