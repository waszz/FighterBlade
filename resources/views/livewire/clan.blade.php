<div>
    @if (session()->has('mensaje'))
    <div class="bg-green-600 text-white text-center p-3 rounded mb-4">
        {{ session('mensaje') }}
    </div>
    @endif

    @php
      // Estilos 3D del juego
      $panel3d = 'border border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.6)]';
      $caja3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
      $boton3d = 'px-4 py-2 rounded-lg border border-black text-white text-sm font-bold bg-gradient-to-b shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100';
      $input3d = 'w-full p-2 rounded-lg border border-black bg-black/50 text-white placeholder-gray-500 shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)] focus:outline-none focus:ring-2 focus:ring-yellow-500 disabled:opacity-50';
    @endphp
    @if($clan)
    {{-- Información del clan (panel 3D) --}}
    <div class="p-4 rounded-xl text-white max-w-2xl mx-auto flex flex-col items-center {{ $panel3d }}">
        <h2 class="text-2xl text-yellow-400 font-bold mb-3 text-center truncate [text-shadow:0_2px_0_#000]">
            {{ $clan->nombre ?? 'Sin nombre' }}
            <span class="ml-1 align-middle inline-block px-2 py-0.5 rounded-full text-xs text-sky-300 {{ $caja3d }}">[{{ $clan->tag }}]</span>
        </h2>
        <div class="w-56 h-56 mb-3 rounded-xl overflow-hidden border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
            <img src="{{ asset('storage/' . $clan->imagen) }}" alt="Imagen Clan" class="w-full h-full object-cover" />
        </div>

        {{-- Miembros y prestigio --}}
        <div class="grid grid-cols-2 gap-2 w-full max-w-xs mb-4 text-sm font-bold">
            <div class="py-1.5 rounded-lg text-center {{ $caja3d }}">
                <span class="text-gray-300">Miembros</span> <span class="text-white">{{ $miembrosCantidad }}/5</span>
            </div>
            <div class="py-1.5 rounded-lg text-center {{ $caja3d }}">
                <span class="text-gray-300">Prestigio</span> <span class="text-yellow-400">{{ number_format($clan->prestigio) }}</span>
            </div>
        </div>

        {{-- Lista de miembros --}}
        <div class="w-full mb-4">
            <h3 class="text-lg font-semibold text-center text-yellow-300 mb-2 [text-shadow:0_2px_0_#000]">Miembros del clan</h3>
            <ul class="rounded-lg px-3 py-1 text-sm max-h-48 overflow-y-auto {{ $caja3d }}">
                {{-- Fundador al inicio --}}
                @if($fundador)
                <li class="flex justify-between items-center py-2 border-b border-white/15 text-yellow-400 font-semibold">
                    <span class="truncate">👑 {{ $fundador->nombre }} <span class="text-xs text-gray-300">(Fundador)</span></span>
                    <span class="text-yellow-300">Nivel {{ $fundador->nivel }}</span>
                </li>
                @endif

                {{-- Otros miembros --}}
                @forelse ($miembros as $miembro)
                <li class="flex justify-between items-center py-2 text-gray-200 {{ $loop->last ? '' : 'border-b border-white/15' }}">
                    <span class="truncate">{{ $miembro->nombre }}</span>
                    <span class="text-yellow-400 font-bold">Nivel {{ $miembro->nivel }}</span>
                </li>
                @empty
                <li class="text-center py-2 text-gray-400">Sin miembros aún.</li>
                @endforelse
            </ul>
        </div>

        {{-- Botones --}}
        <div class="flex flex-wrap justify-center gap-2 mb-2">
            @if($esFundador)
            <button wire:click="abrirModalSolicitudes" class="{{ $boton3d }} from-[#2f5470] to-[#0a1a26]">Gestionar miembros</button>
            @endif
            @if($clan && auth()->id() !== $clan->fundador_id)
            <button wire:click="abrirModalConfirmarSalir" class="{{ $boton3d }} from-red-500 to-red-800">Salir del clan</button>
            @endif
            @if($clan && auth()->id() === $clan->fundador_id)
            <button wire:click="abrirModalConfirmarEliminarClan" class="{{ $boton3d }} from-red-600 to-red-900">Eliminar clan</button>
            @endif
        </div>
        <h3 class="text-xl mt-4 mb-1 text-center font-bold text-yellow-300 [text-shadow:0_2px_0_#000]">Inventario del clan</h3>
        <p class="text-xs text-gray-400 mb-3 text-center">{{ $inventario->count() }}/100 lugares{{ $esFundador ? ' · tocá un objeto para enviarlo, retirarlo o eliminarlo' : '' }}</p>
        {{-- Mismos casilleros que el inventario del personaje: en el celular redondos de a 5 por fila, en pantallas
             grandes cuadrados de 100 px. El borde dice qué es (Equipo índigo, Entrenamiento verde, Accesorio rosa, poción) --}}
        <div wire:key="reload-{{ $reloadClan }}" class="w-full">
            @php
            $abreviaturasSlot = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'velocidad' => 'VEL', 'resistencia' => 'RES', 'defensa' => 'DEF', 'energia' => 'ENE'];
            $casilla = 'relative rounded-full sm:rounded-md border-[3px] sm:border-2 w-full aspect-square sm:w-[100px] sm:h-[100px] sm:aspect-auto flex flex-col items-center justify-center p-1 select-none';
            @endphp
            <div class="grid grid-cols-5 gap-2 sm:flex sm:flex-wrap sm:justify-center sm:gap-3">
                @foreach ($inventario->filter(fn ($item) => $item->objeto) as $item)
                    @php
                    $objeto = $item->objeto;
                    $esPocion = $objeto->tipo === 'pocion';
                    [$borde, $texto] = match ($objeto->tipo) {
                        'equipo' => ['border-indigo-500', 'text-indigo-400'],
                        'entrenamiento' => ['border-green-500', 'text-green-400'],
                        'accesorio' => ['border-pink-500', 'text-pink-400'],
                        'pocion' => ['border-pink-500 sm:border-black', 'text-pink-400'],
                        default => ['border-black', 'text-gray-400'],
                    };
                    $statsItem = is_array($objeto->stats) ? $objeto->stats : (json_decode($objeto->stats ?? '[]', true) ?: []);
                    $tooltip = collect($statsItem)->except(['multiplicador', 'afecta', 'usos_totales', 'usos_restantes'])
                        ->filter(fn ($v) => is_numeric($v) && $v > 0)
                        ->map(fn ($v, $s) => ($abreviaturasSlot[strtolower($s)] ?? strtoupper(substr($s, 0, 3))) . ' +' . $v)->implode(', ');
                    @endphp
                    <div wire:key="clan-slot-{{ $item->id }}"
                         @if ($esFundador) wire:click="abrirModalRetirarObjeto({{ $item->id }})" @endif
                         title="{{ $objeto->nombre }} — Nivel {{ $objeto->nivel }} — {{ ucfirst($objeto->tipo) }}{{ $tooltip ? ' — ' . $tooltip : '' }}"
                         class="{{ $casilla }} {{ $borde }} bg-gradient-to-b from-[#34405a] to-[#10151d] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] {{ $esFundador ? 'cursor-pointer hover:brightness-125 hover:ring-2 hover:ring-indigo-400' : '' }} transition">
                        @if ($esPocion)
                            <img src="{{ asset('images/' . $objeto->imagen) }}" alt="{{ $objeto->nombre }}"
                                 class="w-4/5 h-4/5 sm:w-12 sm:h-12 object-contain" />
                        @else
                            <img src="{{ asset('storage/posts/' . $objeto->imagen) }}" alt="{{ $objeto->nombre }}"
                                 class="w-full h-full sm:w-12 sm:h-12 object-cover rounded-full sm:rounded" />
                        @endif
                        <p class="hidden sm:block text-white text-[9px] font-semibold truncate w-full text-center px-1 mt-0.5">{{ $objeto->nombre }}</p>
                        @if ($esPocion && isset($statsItem['usos_restantes'], $statsItem['usos_totales']))
                            <p class="absolute -bottom-1 right-0 sm:static px-1 rounded sm:px-0 bg-black/80 sm:bg-transparent text-pink-400 text-[8px] leading-none">{{ $statsItem['usos_restantes'] }}/{{ $statsItem['usos_totales'] }}</p>
                        @else
                            <p class="hidden sm:block {{ $texto }} text-[8px] leading-none uppercase">{{ $objeto->tipo }} · Nv {{ $objeto->nivel }}</p>
                        @endif
                    </div>
                @endforeach

                {{-- Espacios vacíos --}}
                @for ($i = $inventario->filter(fn ($item) => $item->objeto)->count(); $i < 100; $i++)
                    <div class="{{ $casilla }} border-black bg-[#0a0e14] shadow-[inset_0_3px_6px_rgba(0,0,0,0.9),inset_0_-1px_0_rgba(255,255,255,0.08)] text-gray-700 sm:text-gray-600 text-2xl sm:text-3xl">+</div>
                @endfor
            </div>
        </div>


    </div>
    @else
    {{-- Sin clan: panel 3D con el botón para fundar --}}
    <div class="p-6 rounded-xl text-white max-w-md mx-auto text-center {{ $panel3d }}">
        <h2 class="text-2xl font-bold text-yellow-400 mb-1 [text-shadow:0_2px_0_#000]">Clan</h2>
        <p class="text-sm text-gray-300 mb-4">Todavía no pertenecés a ningún clan. Fundá el tuyo y juntá hasta 5 miembros.</p>
        <button wire:click="abrirModalFundarClan" class="{{ $boton3d }} from-emerald-500 to-emerald-800">
            Fundar clan
        </button>
    </div>

    @if($modalFundarClan)
    <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="$set('modalFundarClan', false)">
        <div class="relative w-full max-w-md p-5 rounded-xl text-white {{ $panel3d }}" wire:click.stop>
            <button type="button" wire:click="$set('modalFundarClan', false)"
                    class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                           bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                           hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

            <h2 class="text-xl font-bold mb-4 text-center text-yellow-400 [text-shadow:0_2px_0_#000]">Fundar clan</h2>
            @if($mensajeRequisitos)
            <p class="mb-3 text-center text-yellow-300 font-semibold text-sm">
                {{ $mensajeRequisitos }}
            </p>
            @endif

            <form wire:submit.prevent="crearClan" enctype="multipart/form-data" class="space-y-3">
                <label class="block text-sm font-semibold">Nombre del clan
                    <input type="text" wire:model.defer="nombre" maxlength="50" class="mt-1 {{ $input3d }}"
                        @unless($puedeFundar) disabled @endunless />
                </label>

                <label class="block text-sm font-semibold">Tag del clan (máx. 3 caracteres)
                    <input type="text" wire:model.defer="tag" maxlength="3" class="mt-1 uppercase {{ $input3d }}"
                        @unless($puedeFundar) disabled @endunless />
                </label>
                @error('tag') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror

                <label class="block text-sm font-semibold">Imagen del clan
                    <input type="file" wire:model="imagenClan" accept="image/*" class="mt-1 block w-full text-xs text-gray-300 file:mr-2 file:px-3 file:py-1 file:rounded file:border file:border-black file:bg-[#2f5470] file:text-white"
                        @unless($puedeFundar) disabled @endunless />
                </label>
                @error('imagenClan') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('modalFundarClan', false)" class="{{ $boton3d }} from-gray-500 to-gray-800">
                        Cancelar
                    </button>
                    <button type="submit" class="{{ $boton3d }} from-emerald-500 to-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed"
                        @unless($puedeFundar) disabled @endunless>
                        Crear clan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @endif

    @if ($modalRetirarObjeto && $objetoSeleccionado)
    @php
    $objModal = $objetoSeleccionado->objeto;
    $statsModal = is_array($objModal->stats) ? $objModal->stats : (json_decode($objModal->stats ?? '[]', true) ?: []);
    $usosRestantes = $statsModal['usos_restantes'] ?? null;
    $usosTotales = $statsModal['usos_totales'] ?? null;
    $statsVisibles = collect($statsModal)->except(['multiplicador', 'afecta', 'usos_totales', 'usos_restantes'])->filter(fn ($v) => is_numeric($v) && $v > 0);
    $abrevModal = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'velocidad' => 'VEL', 'resistencia' => 'RES', 'defensa' => 'DEF', 'energia' => 'ENE'];
    @endphp
    <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click.self="cerrarModalRetirarObjeto">
        <div class="relative w-full max-w-sm p-5 rounded-xl text-white animate-fade-in-scale {{ $panel3d }}" wire:click.stop>
            <button type="button" wire:click="cerrarModalRetirarObjeto" aria-label="Cerrar"
                    class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                           bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                           hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

            <h3 class="text-xl font-bold mb-1 text-center text-yellow-400 truncate px-6 [text-shadow:0_2px_0_#000]">{{ $objModal->nombre }}</h3>
            <p class="text-center text-xs uppercase text-gray-300 mb-3">{{ $objModal->tipo }} · Nivel {{ $objModal->nivel }}</p>

            <div class="w-28 h-28 mx-auto mb-3 rounded-xl overflow-hidden border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)] flex items-center justify-center">
                <img src="{{ $objModal->tipo === 'pocion' ? asset('images/' . $objModal->imagen) : asset('storage/posts/' . $objModal->imagen) }}"
                     alt="{{ $objModal->nombre }}" class="{{ $objModal->tipo === 'pocion' ? 'w-4/5 h-4/5 object-contain' : 'w-full h-full object-cover' }}" />
            </div>

            @if ($statsVisibles->isNotEmpty())
            <div class="flex flex-wrap justify-center gap-1 mb-3">
                @foreach ($statsVisibles as $stat => $valor)
                <span class="px-2 py-0.5 rounded text-xs font-bold text-green-400 {{ $caja3d }}">{{ $abrevModal[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }} +{{ $valor }}</span>
                @endforeach
            </div>
            @endif

            @if ($usosRestantes !== null && $usosTotales !== null)
            <p class="text-center text-pink-400 font-semibold text-sm mb-3">Usos: {{ $usosRestantes }}/{{ $usosTotales }}</p>
            @endif

            @if ($esFundador)
            <label class="block mb-3 text-sm font-semibold">Enviar a
                <select wire:model="miembroSeleccionadoId" class="mt-1 {{ $input3d }}">
                    <option value="">-- Seleccionar miembro --</option>
                    @foreach ($miembrosDelClan as $miembro)
                    <option value="{{ $miembro->id }}">{{ $miembro->nombre }} (Nivel {{ $miembro->nivel }})</option>
                    @endforeach
                </select>
            </label>
            @endif

            <div class="grid grid-cols-2 gap-2">
                <button wire:click="enviarObjetoAUsuario" wire:loading.attr="disabled" class="{{ $boton3d }} from-emerald-500 to-emerald-800">Enviar</button>
                <button wire:click="retirarObjetoDelClan" wire:loading.attr="disabled" class="{{ $boton3d }} from-[#2f5470] to-[#0a1a26]">Retirar a mi inventario</button>
                <button wire:click="eliminarObjetoDelClan" wire:loading.attr="disabled" class="{{ $boton3d }} from-red-600 to-red-900">Eliminar</button>
                <button wire:click="cerrarModalRetirarObjeto" class="{{ $boton3d }} from-gray-500 to-gray-800">Cancelar</button>
            </div>
        </div>
    </div>
    @endif

    <style>
        @keyframes fadeInScale {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-in-scale {
            animation: fadeInScale 0.3s ease forwards;
        }
    </style>

    @if($modalSolicitudes)
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4" wire:click.self="$set('modalSolicitudes', false)">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-lg text-white shadow-2xl">
            @foreach($miembrosDelClan as $miembro)
            <li class="py-3 flex justify-between items-center">
                <div>
                    <span class="font-semibold text-white">
                        {{ $miembro->user->personaje->nombre ?? 'Sin personaje' }} - Nivel {{
                        $miembro->user->personaje->nivel ?? 'N/A' }}
                    </span>
                </div>
                @if($esFundador && $miembro->user_id !== auth()->id())
                <button wire:click="expulsarMiembro({{ $miembro->id }})"
                    class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm">
                    Expulsar
                </button>
                @endif
            </li>
            @endforeach


            <h2 class="text-2xl font-bold mb-4 text-center">Solicitudes pendientes</h2>

            @if($solicitudesPendientes->count())
            <ul class="divide-y divide-gray-700 max-h-96 overflow-auto">
                @foreach($solicitudesPendientes as $solicitud)
                <li class="py-3 flex justify-between items-center">
                    <div>
                        <span class="font-semibold text-yellow-300">
                            {{ $solicitud->usuario->personaje->nombre ?? 'Sin personaje' }} - Nivel {{
                            $solicitud->usuario->personaje->nivel ?? 'N/A' }}
                        </span>
                        @if(!$solicitud->usuario->personaje)
                        <span class="text-sm text-gray-400">(Sin personaje)</span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="aceptarSolicitud({{ $solicitud->id }})"
                            class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-sm">Aceptar</button>
                        <button wire:click="rechazarSolicitud({{ $solicitud->id }})"
                            class="bg-red-700 hover:bg-red-800 px-3 py-1 rounded text-sm">Rechazar</button>
                    </div>
                </li>
                @endforeach
            </ul>
            @else
            <p class="text-center text-gray-400">No hay solicitudes pendientes.</p>
            @endif

            <div class="mt-6 text-center">
                <button wire:click="$set('modalSolicitudes', false)"
                    class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">Cerrar</button>
            </div>
        </div>
    </div>
    @endif

    @if($modalConfirmarSalir)
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4" wire:click.self="$set('modalConfirmarSalir', false)">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-md text-white shadow-2xl">
            <h2 class="text-xl font-bold mb-4 text-center">Confirmar salida</h2>
            <p class="mb-6 text-center">¿Estás seguro que querés salir del clan?</p>
            <div class="flex justify-center gap-4">
                <button wire:click="confirmarSalirDelClan" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">Sí,
                    salir</button>
                <button wire:click="$set('modalConfirmarSalir', false)"
                    class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">Cancelar</button>
            </div>
        </div>
    </div>
    @endif

    @if($modalConfirmarEliminarClan)
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4" wire:click.self="$set('modalConfirmarEliminarClan', false)">
        <div class="bg-gray-900 rounded-xl p-6 w-full max-w-md text-white shadow-2xl">
            <h2 class="text-xl font-bold mb-4 text-center">Confirmar eliminación</h2>
            <p class="mb-6 text-center">¿Estás seguro que querés eliminar el clan? Esta acción no se puede deshacer.</p>
            <div class="flex justify-center gap-4">
                <button wire:click="eliminarClan" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">Sí,
                    eliminar</button>
                <button wire:click="$set('modalConfirmarEliminarClan', false)"
                    class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">Cancelar</button>
            </div>
        </div>
    </div>
    @endif


</div>