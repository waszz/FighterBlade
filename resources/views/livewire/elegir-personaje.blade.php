<div class="min-h-screen bg-gray-900 text-white p-6">
    <h1 class="text-4xl font-bold text-yellow-400 mb-8 text-center">Tu Personaje</h1>

    <div class="container mx-auto p-6">

    {{-- Mostrar personajes base si no tiene ninguno o si es admin --}}
@if ($personajesUsuario->isEmpty() || auth()->user()->isAdmin())
    <p class="mb-6 text-gray-400 text-center text-lg">
        @if ($personajesUsuario->isEmpty())
            No tenés personajes creados aún. Elegí uno para comenzar tu aventura:
        @else
            Como administrador, podés crear más personajes base:
        @endif
    </p>

    @php
        $panel3d = 'bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]';
        $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
        $botonElegir = 'uppercase font-bold text-black rounded border border-black bg-gradient-to-b from-yellow-300 to-yellow-600 shadow-[inset_1px_1px_0_rgba(255,255,255,0.5),inset_-1px_-1px_0_rgba(0,0,0,0.4),0_3px_0_#000] hover:brightness-110 active:translate-y-[3px] active:shadow-none transition-all';
        $colorTipo = ['fisico' => 'text-red-400', 'elemental' => 'text-blue-400', 'hibrido' => 'text-purple-400'];
    @endphp

    {{-- Personajes iniciales (tarjetas 3D: al tocarlas se abre el modal con lo que tiene cada uno) --}}
    <div class="max-w-4xl mx-auto px-2">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 mb-6">
            @foreach ($personajesBase as $personajeBase)
                <div wire:click="verInicial({{ $personajeBase->id }})"
                     class="{{ $panel3d }} cursor-pointer rounded-xl border border-yellow-600 p-2 text-center text-sm
                            transition-transform duration-200 hover:-translate-y-1 hover:border-yellow-400">
                    <div class="h-32 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
                        @if ($personajeBase->gif)
                            <img src="{{ asset('storage/' . $personajeBase->gif) }}" alt="{{ $personajeBase->titulo }}" loading="lazy"
                                 style="{{ \App\Models\Post::estiloGif($personajeBase->gif, 0.85) }}" class="block max-w-none">
                        @endif
                    </div>

                    <h3 class="mt-2 text-yellow-300 font-bold truncate [text-shadow:0_2px_0_#000]">{{ $personajeBase->titulo }}</h3>
                    <p class="text-xs font-semibold {{ $colorTipo[$personajeBase->tipo] ?? 'text-gray-400' }}"><x-icono-tipo :tipo="$personajeBase->tipo" tam="w-5 h-5" :con-nombre="true" /></p>

                    <button wire:click.stop="seleccionarPersonajeBase({{ $personajeBase->id }})"
                            class="{{ $botonElegir }} mt-2 w-full py-1 text-xs">
                        Elegir
                    </button>
                </div>
            @endforeach
        </div>
        <p class="text-center text-xs text-gray-500">Tocá un personaje para ver sus stats y poderes.</p>
    </div>

    {{-- Modal 3D del personaje inicial --}}
    @if ($inicialModalId && ($inicial = $personajesBase->get($inicialModalId)))
        @php
            $abrev = ['fuerza' => 'FUE', 'resistencia' => 'RES', 'ataque' => 'ATA', 'defensa' => 'DEF', 'velocidad' => 'VEL', 'energia' => 'ENE'];
        @endphp
        <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="cerrarInicial">
            <div class="relative w-full max-w-sm max-h-[90vh] overflow-auto p-4 rounded-xl border border-black text-white
                        bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                        shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]"
                 wire:click.stop>

                <button wire:click="cerrarInicial"
                        class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                               bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                               hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

                <div class="mx-auto mb-3 h-40 w-48 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
                    <img src="{{ asset('storage/' . $inicial->gif) }}" alt="{{ $inicial->titulo }}"
                         style="{{ \App\Models\Post::estiloGif($inicial->gif) }}" class="block max-w-none">
                </div>

                <h2 class="text-2xl font-bold text-center [text-shadow:0_2px_0_#000]">{{ $inicial->titulo }}</h2>
                <p class="text-center mt-1 mb-3 flex justify-center gap-2 text-xs font-bold">
                    <span class="px-2 py-0.5 rounded-full {{ $etiqueta3d }} text-yellow-300">Nivel {{ $inicial->nivel }}</span>
                    <span class="px-2 py-0.5 rounded-full {{ $etiqueta3d }} {{ $colorTipo[$inicial->tipo] ?? 'text-gray-300' }}"><x-icono-tipo :tipo="$inicial->tipo" tam="w-4 h-4" :con-nombre="true" /></span>
                </p>

                {{-- Stats (mismo estilo que el panel de atributos) --}}
                <div class="font-mono rounded-lg border border-black p-2 mb-3 grid grid-cols-2 gap-x-3 gap-y-1.5 bg-gradient-to-b from-[#2a3240] to-[#10141b]
                            shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
                    @foreach (($inicial->stats ?? []) as $stat => $valor)
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-9 h-6 flex items-center justify-center font-bold text-yellow-300 {{ $etiqueta3d }}">{{ $abrev[$stat] ?? strtoupper(substr($stat, 0, 3)) }}</span>
                            <span class="font-bold">+{{ $valor }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Poderes --}}
                @if ($inicial->poderes->isNotEmpty())
                    <div class="rounded-lg border border-black p-2 mb-4 bg-gradient-to-b from-[#2a3240] to-[#10141b]
                                shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
                        <h4 class="text-yellow-400 text-center font-semibold mb-1 text-sm [text-shadow:0_1px_0_#000]">🧬 Poderes</h4>
                        <ul class="space-y-1 text-[12px] leading-snug">
                            @foreach ($inicial->poderes as $poder)
                                <li class="flex items-center gap-2"><x-icono-poder :poder="$poder" tam="w-8 h-8" /><span><b class="text-sky-300">{{ $poder->nombre }}</b>: <span class="text-gray-200">{{ $poder->descripcion }}</span></span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button wire:click="seleccionarPersonajeBase({{ $inicial->id }})" class="{{ $botonElegir }} w-full py-2 text-sm">
                    Elegir a {{ $inicial->titulo }}
                </button>
            </div>
        </div>
    @endif
@endif

       @if ($personajesUsuario->isNotEmpty())
    <p class="mb-6 text-gray-400 text-center text-lg">Tus personajes creados:</p>

    <div class="flex flex-wrap justify-center gap-6 mb-12">
        @foreach ($personajesUsuario as $personaje)
            <div class="bg-gray-900 border border-yellow-500 p-2 w-72 rounded-lg shadow text-center hover:bg-gray-900 transition transform hover:scale-105 duration-200 relative text-sm">
                @php
                    $imagenFinal = $personaje->imagen;
                    if ($personaje->equipo && $personaje->entrenamiento && $personaje->accesorio) {
                        $origenEquipo = $personaje->equipo->origen_post_id ?? null;
                        $origenEntrenamiento = $personaje->entrenamiento->origen_post_id ?? null;
                        $origenAccesorio = $personaje->accesorio->origen_post_id ?? null;
                        if ($origenEquipo && $origenEquipo === $origenEntrenamiento && $origenEquipo === $origenAccesorio) {
                            $postOrigen = \App\Models\Post::find($origenEquipo);
                            if ($postOrigen && $postOrigen->imagen) {
                                $imagenFinal = $postOrigen->imagen;
                            }
                        }
                    }
                @endphp

                <div class="flex items-center justify-center mb-3 h-40 w-full">
                    @if ($imagenFinal)
                        <img src="{{ asset('storage/' . $imagenFinal) }}" alt="{{ $personaje->nombre }}"
                             class="h-40 w-full object-contain bg-gray-900 rounded-lg shadow-md p-2" />
                    @else
                        <div class="h-full w-full bg-gray-800 rounded flex items-center justify-center text-gray-400">
                            Sin imagen
                        </div>
                    @endif
                </div>

                <h3 class="text-yellow-300 font-bold">{{ $personaje->nombre }}</h3>
                <p class="text-gray-400 text-sm mb-1 capitalize">{{ ucfirst($personaje->tipo) }}</p>

                <!-- Botón ojito justo debajo del tipo -->
                <button wire:click="verPostOriginal({{ $personaje->id }})"
                        class="text-gray-400 hover:text-yellow-400 transition mb-3 mx-auto flex justify-center"
                        title="Ver personaje original" aria-label="Ver personaje original">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>

                <p class="text-gray-300 mb-1">Nivel: {{ $personaje->nivel }}</p>

                <button wire:click="confirmarSeleccion({{ $personaje->id }})"
                        class="uppercase bg-gradient-to-r from-red-600 to-yellow-500 hover:from-yellow-500 hover:to-red-600
                               text-white font-bold py-2 px-6 rounded-lg text-sm shadow-[0_0_15px_rgba(255,69,0,0.8)]
                               transition transform hover:scale-105 animate-fade-in delay-300">
                    Entrar
                </button>
            </div>
        @endforeach
    </div>
@endif


        {{-- Modal para crear personaje --}}
        @if ($mostrarFormularioNombre)
        <div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50" wire:click.self="cancelarCrearPersonaje">
            <div class="bg-gray-900 p-6 rounded-lg w-80">
                <h2 class="text-xl font-bold mb-4 text-yellow-400">Nombre para tu personaje</h2>

                <input type="text" wire:model.defer="nuevoNombre" placeholder="Escribe un nombre"
                    class="w-full p-2 rounded mb-4 text-black" />

                @error('nuevoNombre')
                <span class="text-red-500 text-sm text-center">{{ $message }}</span>
                @enderror

                <div class="flex justify-end space-x-3 mt-2">
                    <button wire:click="cancelarCrearPersonaje"
                        class="uppercase bg-gray-700 text-white font-semibold px-4 py-2 rounded-lg text-sm shadow-md hover:bg-gray-600 transition transform hover:scale-105 animate-fade-in delay-300">
                        Cancelar
                    </button>

                    <button wire:click="confirmarCrearPersonaje" class="uppercase bg-gradient-to-r from-red-600 to-yellow-500 hover:from-yellow-500 hover:to-red-600
                text-white font-bold py-2 px-6 rounded-lg text-sm shadow-[0_0_15px_rgba(255,69,0,0.8)]
                transition transform hover:scale-105 animate-fade-in delay-300">Crear</button>
                </div>
            </div>
        </div>
        @endif

        @if ($mostrarModalPost)
        <div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50"
            wire:click="cerrarModalPost">
            <div class="bg-gray-800 bg-opacity-50 border border-yellow-500 rounded-lg p-4 w-80 relative max-w-full mx-2 shadow-xl"
                wire:click.stop>
                <button wire:click="cerrarModalPost"
                    class="absolute top-2 right-3 text-white hover:text-red-400 text-xl font-bold leading-none">&times;</button>

                <h2 class="text-yellow-400 font-semibold text-lg mb-3 text-center">{{ $nombrePost }}</h2>

                @if ($gifPost)
                <div class="flex justify-center mb-3">
                    <img src="{{ asset('storage/' . $gifPost) }}" alt="GIF del personaje original"
                        class="max-h-32 md:max-h-34 object-contain bg-gray-900 bg-opacity-50 rounded shadow" />
                </div>

                {{-- Poderes del personaje --}}

                <div class="flex justify-center mt-8">
                    <div class="w-full px-2 max-w-xl">
                        <!-- limitamos ancho máximo para que no se estire mucho -->
                        <h3 class="text-lg font-bold mb-3 text-yellow-400 flex items-center justify-center gap-2">
                            🧬 Poderes del Personaje
                        </h3>

                        @if ($poderesPersonaje->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 justify-items-center">
                            @foreach ($poderesPersonaje as $poder)
                            <div
                                class="bg-gray-700 p-2 rounded-md shadow hover:brightness-125 transition w-auto max-w-xs flex items-center gap-2">
                                <x-icono-poder :poder="$poder" tam="w-10 h-10" />
                                <div>
                                <p class="text-xs font-semibold text-blue-300">{{ $poder->nombre }}</p>
                                <p class="text-xs text-gray-200 mt-0.5">{{ $poder->descripcion }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-gray-400 italic text-center text-sm">Este personaje no tiene poderes asignados.
                        </p>
                        @endif
                    </div>
                </div>

                @else
                <p class="text-center text-gray-400 mb-4">Sin gif disponible.</p>
                @endif

                @if ($statsPost && is_array($statsPost))
                <div class="text-xs text-white space-y-2 max-h-52 overflow-y-auto pr-1 mt-4">
                    @php
                    $abreviaturas = [
                    'fuerza' => 'FUE', 'ataque' => 'ATQ', 'velocidad' => 'VEL',
                    'resistencia' => 'RES', 'defensa' => 'DEF', 'energia' => 'ENE',
                    'recuperacion' => 'REC',
                    ];
                    @endphp

                    @foreach ($statsPost as $clave => $valor)
                    @php
                    $label = $abreviaturas[$clave] ?? strtoupper($clave);
                    $porcentaje = min(100, ($valor / 20) * 100); // Escala de barra básica
                    @endphp

                    <div class="flex items-center gap-2">
                        <span class="w-10 text-yellow-400 font-bold">{{ $label }}</span>
                        <div class="h-2 bg-gray-900 overflow-hidden border border-white border-opacity-30"
                            style="width: 100px;">
                            <div class="bg-yellow-400 h-2" style="width: {{ $porcentaje }}%"></div>
                        </div>
                        <span class="w-6 text-right text-gray-300">{{ $valor }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-400 text-xs mt-2 text-center">Sin stats disponibles.</p>
                @endif
            </div>
        </div>
        @endif

    </div>
</div>