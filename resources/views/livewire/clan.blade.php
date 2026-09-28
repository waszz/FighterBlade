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
        <h3 class="text-xl mt-4 mb-4 text-center font-bold text-yellow-300 [text-shadow:0_2px_0_#000]">Inventario del clan</h3>
        <div wire:key="reload-{{ $reloadClan }}">
            <div class="overflow-x-auto w-full max-w-2xl">
                <div class="grid grid-cols-6 gap-6 min-w-[600px]">
                    @for ($i = 0; $i < 100; $i++) @php $item=$inventario->get($i);
                        $stats = [];
                        $requisitos = [];

                        if ($item) {
                        if (is_string($item->objeto->stats)) {
                        $stats = json_decode($item->objeto->stats, true) ?: [];
                        } elseif (is_array($item->objeto->stats)) {
                        $stats = $item->objeto->stats;
                        }

                        if (isset($item->objeto->requisitos)) {
                        if (is_string($item->objeto->requisitos)) {
                        $requisitos = json_decode($item->objeto->requisitos, true) ?: [];
                        } elseif (is_array($item->objeto->requisitos)) {
                        $requisitos = $item->objeto->requisitos;
                        }
                        }
                        }
                        @endphp

                        <div
                            class="bg-gray-700 rounded-xl p-3 flex flex-col justify-start items-center text-sm text-center h-[200px]">
                            @if($item && $item->objeto)
                            <div class="w-full flex justify-center mb-2">
                                <img src="{{ $item->objeto->tipo === 'pocion' 
                    ? asset('images/' . $item->objeto->imagen) 
                    : asset('storage/posts/' . $item->objeto->imagen) }}" alt="{{ $item->objeto->nombre }}"
                                    class="w-16 h-16 object-cover rounded-xl shadow {{ $esFundador ? 'cursor-pointer' : '' }}"
                                    @if($esFundador) wire:click="abrirModalRetirarObjeto({{ $item->id }})" @endif />
                            </div>

                            <span class="font-semibold truncate max-w-full">{{ $item->objeto->nombre }}</span>
                            <span class="text-yellow-400 font-bold text-sm">Nivel {{ $item->objeto->nivel }}</span>

                            {{-- Stats --}}
                            @if(count($stats) > 0)
                            <div class="mt-1 text-green-400 space-y-0.5 text-[11px] leading-tight">
                                @foreach($stats as $stat => $valor)
                                @if(is_numeric($valor) && $valor > 0 && !in_array($stat, ['multiplicador', 'afecta',
                                'usos_totales', 'usos_restantes']))
                                <div class="uppercase truncate">{{ ucfirst($stat) }} +{{ $valor }}</div>
                                @endif
                                @endforeach

                                @if(isset($stats['usos_restantes']) && isset($stats['usos_totales']))
                                <div class="truncate">Usos: {{ $stats['usos_restantes'] }}/{{ $stats['usos_totales'] }}
                                </div>
                                @endif
                            </div>
                            @endif

                            {{-- Requisitos --}}
                            @if(count($requisitos) > 0)
                            <div class="mt-1 text-red-400 text-[11px] leading-tight">
                                <div class="font-semibold mb-1">Requisitos:</div>
                                @foreach($requisitos as $req => $valor)
                                <div class="truncate">{{ ucfirst($req) }}: {{ $valor }}</div>
                                @endforeach
                            </div>
                            @endif
                            @else
                            <div class="flex items-center justify-center h-full text-gray-400">Vacío</div>
                            @endif
                        </div>
                        @endfor
                </div>
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
    <div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-3" wire:click.self="cerrarModalRetirarObjeto">
        <div
            class="bg-gray-900 rounded-xl shadow-xl p-4 max-w-sm w-full text-white relative transform transition-transform duration-300 ease-in-out animate-fade-in-scale border-2 border-yellow-400">
            <button wire:click="cerrarModalRetirarObjeto"
                class="absolute top-2 right-2 text-yellow-400 text-2xl font-bold hover:text-yellow-300 transition"
                aria-label="Cerrar modal">&times;</button>

            <h3 class="text-2xl font-extrabold mb-4 text-center tracking-wide truncate">{{
                $objetoSeleccionado->objeto->nombre }}</h3>

            <img src="{{ $objetoSeleccionado->objeto->tipo === 'pocion' 
        ? asset('images/' . $objetoSeleccionado->objeto->imagen) 
        : asset('storage/posts/' . $objetoSeleccionado->objeto->imagen) }}"
                alt="{{ $objetoSeleccionado->objeto->nombre }}"
                class="w-32 h-32 object-contain mx-auto mb-4 rounded-lg shadow-lg" />

            @php
            $stats = is_array($objetoSeleccionado->objeto->stats)
            ? $objetoSeleccionado->objeto->stats
            : json_decode($objetoSeleccionado->objeto->stats, true);

            $usosRestantes = $stats['usos_restantes'] ?? null;
            $usosTotales = $stats['usos_totales'] ?? null;
            @endphp

            @if($usosRestantes !== null && $usosTotales !== null)
            <div class="text-center text-green-400 font-semibold text-sm mb-4">
                Usos: {{ $usosRestantes }}/{{ $usosTotales }}
            </div>
            @endif

            <p class="mb-6 text-center text-gray-300 text-base leading-relaxed">
                ¿Quieres <span class="font-semibold text-yellow-400">retirar</span> este objeto y devolverlo a tu
                inventario personal?
            </p>

            @if($esFundador)
            <div class="mb-4">
                <label for="miembroSeleccionado" class="block mb-1 text-sm text-gray-300">Enviar a:</label>
                <select wire:model="miembroSeleccionadoId" class="w-full text-black p-2 rounded">
                    <option value="">-- Seleccionar miembro --</option>
                    @foreach($miembrosDelClan as $miembro)
                    <option value="{{ $miembro->id }}">
                        {{ $miembro->nombre }} (Nivel {{ $miembro->nivel }})
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex justify-center gap-4">

                <button wire:click="enviarObjetoAUsuario"
                    class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg font-semibold shadow-md transition text-sm">
                    Enviar
                </button>

                <button wire:click="retirarObjetoDelClan"
                    class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg font-semibold shadow-md transition text-sm">
                    Retirar
                </button>

                <button wire:click="eliminarObjetoDelClan"
                    class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg font-semibold shadow-md transition text-sm">
                    Eliminar
                </button>

                <button wire:click="cerrarModalRetirarObjeto"
                    class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded-lg font-semibold shadow-md transition text-sm">
                    Cancelar
                </button>
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