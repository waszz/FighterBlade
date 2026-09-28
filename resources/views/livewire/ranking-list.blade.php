<div>
    @if($ranking && count($ranking) > 0)
    <div>
        @foreach($ranking as $index => $item)
        @if($tipo === 'Clanes')
        {{-- Fila de clan: sin tarjeta, línea abajo; imagen y en el medio nombre, tag y prestigio --}}
        <div wire:click="abrirModalIngreso({{ $item->id }})"
             class="flex items-center gap-2 px-1 py-1.5 cursor-pointer transition hover:bg-white/5
                    {{ $loop->last ? '' : 'border-b border-white/20' }}
                    {{ optional($clanActual)->id === $item->id ? 'font-bold' : '' }}">
            @if($item->imagen)
            <img src="{{ asset('storage/' . $item->imagen) }}" alt="{{ $item->nombre }}" class="w-10 h-10 shrink-0 rounded-full object-cover" />
            @endif
            <div class="flex-1 min-w-0 text-center">
                <p class="text-[11px] leading-tight font-semibold text-white break-words">{{ $item->nombre }} <span class="text-yellow-400 font-medium">[{{ $item->tag }}]</span></p>
                <p class="text-xs font-semibold text-yellow-300">Prestigio {{ number_format($item->prestigio) }}</p>
            </div>
        </div>
        @else
        @php
        $equipo = $item->equipo;
        $entrenamiento = $item->entrenamiento;
        $accesorio = $item->accesorio;
        $mostrarImagenCompleta = false;
        $imagen = null;

        if ($equipo && $entrenamiento && $accesorio) {
        if (
        $equipo->origen_post_id &&
        $equipo->origen_post_id === $entrenamiento->origen_post_id &&
        $equipo->origen_post_id === $accesorio->origen_post_id
        ) {
        $mostrarImagenCompleta = true;
        $imagen = \App\Models\Post::find($equipo->origen_post_id)?->imagen ?? null;
        }
        }

        if (!$mostrarImagenCompleta && $item->post?->imagen) {
        $imagen = $item->post->imagen;
        }
        @endphp

        {{-- Fila de personaje: sin tarjeta, línea abajo; foto, foto del clan y en el medio nombre y nivel --}}
        @php $esYoRanking = optional($personajeActual)?->id === $item->id; @endphp
        <div wire:click="mostrarModalPersonaje({{ $item->id }})"
             class="flex items-center gap-2 px-1 py-1.5 cursor-pointer transition hover:bg-white/5
                    {{ $loop->last ? '' : 'border-b border-white/20' }}
                    {{ $esYoRanking ? 'font-bold' : '' }}
                    {{ $item instanceof \App\Models\Personaje && $item->claseRanking() ? 'rounded-lg border ' . $item->claseRanking() : '' }}">
            @if($imagen)
            <img src="{{ asset('storage/' . $imagen) }}" alt="Imagen de {{ $item->nombre }}" class="w-10 h-10 shrink-0 rounded-full object-cover" />
            @endif
            @if($item->clan)
            <img src="{{ asset('storage/' . $item->clan->imagen) }}" alt="Clan {{ $item->clan->nombre }}" title="{{ $item->clan->nombre }}"
                 class="w-6 h-6 shrink-0 rounded-full object-cover border border-yellow-400" />
            @endif

            <div class="flex-1 min-w-0 text-center">
                <p class="text-sm font-bold truncate {{ $esYoRanking ? 'text-yellow-300' : 'text-white' }}">
                    <span class="{{ $item instanceof \App\Models\Personaje ? $item->claseNombre() : '' }}">{{ $item->nombre }}</span>
                </p>
                <p class="text-xs">
                    @if($tipo === 'Nivel')
                    <span class="text-yellow-400 font-bold">Nivel {{ $item->nivel }}</span>
                    @elseif($tipo === 'Campeones')
                    {{-- Cuándo llegó al nivel 100 (se lee de la tabla: Livewire relee los personajes y pierde la columna extra) --}}
                    @php
                        $fechasCampeones ??= \Illuminate\Support\Facades\DB::table('campeones')->pluck('alcanzado_en', 'personaje_id');
                        $fechaCampeon = \Carbon\Carbon::parse($fechasCampeones[$item->id] ?? now());
                    @endphp
                    <span class="text-yellow-300 font-bold" title="{{ $fechaCampeon->format('d/m/Y H:i') }}">👑 {{ $fechaCampeon->locale('es')->isoFormat('D MMM YYYY') }}</span>
                    @elseif($tipo === 'PvP')
                    <span class="text-green-400 font-semibold">{{ $item->pvp_ganadas ?? 0 }}</span>
                    <span class="text-white">/</span>
                    <span class="text-red-400 font-semibold">{{ $item->pvp_perdidas ?? 0 }}</span>
                    @else
                    <span class="text-green-400 font-semibold">{{ $item->pve_ganadas ?? 0 }}</span>
                    <span class="text-white">/</span>
                    <span class="text-red-400 font-semibold">{{ $item->pve_perdidas ?? 0 }}</span>
                    @endif
                </p>
            </div>
        </div>
        @endif
        @endforeach
    </div>
    @else
    <p class="text-center text-gray-400 italic">{{ $tipo === 'Campeones' ? 'Todavía nadie llegó al nivel 100.' : 'No hay datos para esta categoría.' }}</p>
    @endif

    @if($mostrarModal)

    @php
      // Estilos 3D del juego
      $modal3d = 'relative w-full max-w-xs max-h-[85vh] overflow-auto p-4 rounded-xl border border-black text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]';
      $caja3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
      $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
      $cerrar3d = 'absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000] hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all';
    @endphp

    @if($tipo === 'Clanes' && $clanSeleccionado)
    {{-- Modal Clan (3D) --}}
    <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="$set('mostrarModal', false)">
        <div class="{{ $modal3d }}" wire:click.stop>
            <button wire:click="$set('mostrarModal', false)" class="{{ $cerrar3d }}">&times;</button>

            @if($clanSeleccionado->imagen)
            <div class="mx-auto mb-3 w-24 h-24 rounded-xl overflow-hidden border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
                <img src="{{ asset('storage/' . $clanSeleccionado->imagen) }}" alt="Clan" class="w-full h-full object-cover">
            </div>
            @endif

            <h2 class="text-xl font-bold text-center mb-1 truncate [text-shadow:0_2px_0_#000]">{{ $clanSeleccionado->nombre }}</h2>
            <p class="text-center mb-3">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold text-sky-300 {{ $etiqueta3d }}">[{{ $clanSeleccionado->tag }}]</span>
            </p>

            <div class="py-2 mb-4 rounded-lg text-center text-sm font-bold {{ $caja3d }}">
                <span class="text-gray-300">Prestigio</span> <span class="text-yellow-300">{{ number_format($clanSeleccionado->prestigio) }}</span>
            </div>

            <button wire:click="enviarSolicitudIngreso"
                class="w-full py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-yellow-500 to-yellow-700
                       shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">
                Enviar solicitud
            </button>
        </div>
    </div>

    @elseif($tipo !== 'Clanes' && $personajeSeleccionadoModal)
    @php
        $abreviaturas = ['fuerza' => 'FUE', 'resistencia' => 'RES', 'ataque' => 'ATA', 'defensa' => 'DEF', 'velocidad' => 'VEL', 'energia' => 'ENE'];

        $nivel = $personajeSeleccionadoModal->nivel ?? 1;

        $postModalRanking = $personajeSeleccionadoModal->postDeCombate();
        $gifModalRanking = $gifPersonajeEquipado ?? $postModalRanking?->gif ?? $personajeSeleccionadoModal->post?->gif;
        $gifZonaRanking = $personajeSeleccionadoModal->ciudadActual?->gif;
        $esMiPersonajeRanking = $personajeSeleccionadoModal->user_id === auth()->id();
    @endphp

    {{-- Modal Personaje (3D, igual al de la Ciudad) --}}
    <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="$set('mostrarModal', false)">
        <div class="{{ $modal3d }}" wire:click.stop>
            <button wire:click="$set('mostrarModal', false)" class="{{ $cerrar3d }}">&times;</button>

            {{-- Personaje sobre su zona --}}
            <div class="relative mx-auto mb-3 h-32 w-48 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
                @if ($gifZonaRanking)
                    <img src="{{ asset('storage/posts/' . $gifZonaRanking) }}" alt="Zona" class="absolute inset-0 w-full h-full object-cover object-bottom">
                @endif
                @if ($gifModalRanking)
                    <img src="{{ asset('storage/' . $gifModalRanking) }}" alt="Personaje"
                         style="{{ \App\Models\Post::estiloGif($gifModalRanking, 0.8) }}" class="relative z-10 block max-w-none scale-x-[-1] {{ $personajeSeleccionadoModal->claseAura() }}">
                @endif
            </div>

            {{-- Nombre y nivel --}}
            <h2 class="text-xl font-bold text-center mb-1 truncate [text-shadow:0_2px_0_#000]"><span class="{{ $personajeSeleccionadoModal->claseNombre() }}">{{ $personajeSeleccionadoModal->nombre }}</span></h2>
            <p class="text-center mb-3">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold text-yellow-300 {{ $etiqueta3d }}">Nivel {{ $nivel }}</span>
            </p>

            {{-- Experiencia --}}
            <x-barra-exp :personaje="$personajeSeleccionadoModal" class="mb-3" />

            {{-- Oro, diamantes y stats: solo del personaje propio --}}
            @if ($esMiPersonajeRanking)
            <div class="grid grid-cols-2 gap-2 mb-3 text-sm font-bold">
                <div class="flex items-center justify-center gap-1.5 py-1 rounded-lg {{ $caja3d }}">
                    <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-4 h-4">
                    <span class="text-yellow-300">{{ number_format($personajeSeleccionadoModal->oro ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-center gap-1.5 py-1 rounded-lg {{ $caja3d }}">
                    <img src="{{ asset('images/diamante.png') }}" alt="Esmeralda" class="w-4 h-4">
                    <span class="text-cyan-300">{{ number_format($personajeSeleccionadoModal->diamante ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Stats: etiqueta 3D + número --}}
            <div class="font-mono rounded-lg p-2 mb-3 grid grid-cols-2 gap-x-3 gap-y-1.5 {{ $caja3d }}">
                @foreach ($personajeSeleccionadoModalStats ?? [] as $statName => $value)
                <div class="flex items-center gap-2 select-none text-sm">
                    <div class="w-8 h-6 shrink-0 flex items-center justify-center font-bold text-yellow-300 tracking-wide {{ $etiqueta3d }}">{{ $abreviaturas[$statName] ?? strtoupper(substr($statName, 0, 3)) }}</div>
                    <span class="font-bold text-white">+{{ $value }}</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Tipo de daño y poderes: solo iconos --}}
            @if ($postModalRanking || $personajeSeleccionadoModal->joya)
            <div class="rounded-lg p-2 mb-3 {{ $caja3d }}">
                <div class="flex flex-wrap justify-center gap-2">
                    @if ($postModalRanking?->tipo)
                        <x-icono-tipo :tipo="$postModalRanking->tipo" tam="w-10 h-10" class="cursor-pointer" />
                    @endif
                    @foreach ($postModalRanking?->poderes ?? [] as $poder)
                        <x-icono-poder :poder="$poder" tam="w-10 h-10" class="cursor-pointer" />
                    @endforeach
                    @if ($personajeSeleccionadoModal->joya)
                        <x-icono-joya :joya="$personajeSeleccionadoModal->joya" tam="w-10 h-10" class="cursor-pointer" />
                    @endif
                </div>
            </div>
            @endif

            {{-- Atacar (no a uno mismo) --}}
            @if (! $esMiPersonajeRanking && $personajeActual)
            <a href="{{ route('atacar.personaje', ['personajeId' => $personajeActual->id, 'objetivoId' => $personajeSeleccionadoModal->id]) }}"
               class="block w-full text-center py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-red-500 to-red-800
                      shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">
                Atacar
            </a>
            @endif
        </div>
    </div>
    @endif
        @endif

</div>