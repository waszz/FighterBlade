<div class="relative h-screen overflow-hidden">
    {{-- Fondo con imagen y capa oscura --}}
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/-juego.jpg') }}');"></div>
    <div class="absolute inset-0 bg-black bg-opacity-40"></div>

    {{-- Layout principal --}}
    <div class="relative flex flex-col lg:flex-row h-full text-white">

        {{-- Sidebar personaje (izquierda) --}}
    <aside class="sidebar-pj w-full md:w-56 shrink-0 p-1 flex flex-col items-center space-y-1 [&>*]:shrink-0 min-h-0 max-h-full overflow-y-auto overflow-x-hidden text-xs select-none text-white border-x-2 border-[#3d7fd6] shadow-[inset_0_0_12px_rgba(0,0,0,0.45)]"
        style="background-image: url('{{ asset('images/-juego.jpg') }}'); background-size: cover; background-position: 77% center; background-color: #0c202e;">
            {{-- Nombre estilo retro --}}
            <div class="w-full bg-gradient-to-b from-neutral-800 to-black border-2 border-black rounded px-1 py-0.5 font-mono shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">
                <h2 class="text-xs font-bold uppercase truncate w-full text-center text-red-500 tracking-wide"><span class="{{ $personaje->claseNombre() }}">{{ $personaje->nombre ?? 'Sin nombre' }}</span></h2>
            </div>

            {{-- Logout (y, para admins, el acceso a la administración: la barra de arriba no se muestra en el juego) --}}
            <div class="w-full flex justify-center gap-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="font-mono text-[11px] font-bold uppercase tracking-wide text-sky-300 hover:text-sky-200 underline underline-offset-2">
                        Logout
                    </button>
                </form>
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('posts.index') }}" class="font-mono text-[11px] font-bold uppercase tracking-wide text-amber-300 hover:text-amber-200 underline underline-offset-2">Admin</a>
                @endif
            </div>

            {{-- Contenedor relativo para ciudad + personaje --}}
            <div class="relative w-full max-w-[200px] mx-auto">
                @php
                $gifCiudad = $ciudadActual?->gif ?? null;
                @endphp
                @if($gifCiudad)
                <img src="{{ asset('storage/posts/' . $gifCiudad) }}" alt="Ciudad Actual" class="w-full h-40 rounded object-cover cursor-pointer"
                wire:click="$set('seccion', 'inventario')" />
                
                @endif

                @php
                $equipo = $personaje->equipo;
                $entrenamiento = $personaje->entrenamiento;
                $accesorio = $personaje->accesorio;
                $mostrarGifCompleto = false;
                $gif = null;
                if ($equipo && $entrenamiento && $accesorio &&
                    $equipo->origen_post_id === $entrenamiento->origen_post_id &&
                    $equipo->origen_post_id === $accesorio->origen_post_id) {
                    $mostrarGifCompleto = true;
                    $gif = \App\Models\Post::find($equipo->origen_post_id)?->gif;
                }
                if (!$mostrarGifCompleto && $personaje->post?->gif) $gif = $personaje->post->gif;
                @endphp

                @if($gif)
                {{-- Misma escala que en las peleas (con el ajuste de tamaño del set), un poco más chico para el recuadro --}}
                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 cursor-pointer" wire:click="$set('seccion', 'inventario')">
                    <img src="{{ asset('storage/' . $gif) }}" alt="Personaje"
                        style="{{ \App\Models\Post::estiloGif($gif, 0.8) }}" class="block max-w-none {{ $personaje->claseAura() }}" />
                </div>
                @endif
            </div>

            {{-- Nivel --}}
            <div class="px-4 py-0.5 rounded-full border-2 border-[#3d7fd6] bg-[#0a1628] font-mono font-bold text-sm text-sky-300 tracking-wide select-none shadow-[0_2px_4px_rgba(0,0,0,0.6)]">
                Nv. {{ $personaje->nivel ?? 1 }}
            </div>

            {{-- Oro y Diamante --}}
            <div class="w-full grid grid-cols-2 gap-1 font-mono text-sm select-none">
                <div wire:click="abrirModalGuardarOro" class="flex items-center justify-center gap-1.5 px-1 py-1 rounded border-2 border-[#16203a] bg-black/60 shadow-[inset_0_0_6px_rgba(0,0,0,0.6),0_0_0_1px_rgba(90,130,200,0.35)] cursor-pointer hover:brightness-125">
                    <img src="{{ asset('images/oro.png') }}" class="h-3.5" />
                    <span class="text-yellow-400 font-semibold truncate">{{ number_format($personaje->oro, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-center gap-1.5 px-1 py-1 rounded border-2 border-[#16203a] bg-black/60 shadow-[inset_0_0_6px_rgba(0,0,0,0.6),0_0_0_1px_rgba(90,130,200,0.35)]">
                    <img src="{{ asset('images/diamante.png') }}" class="h-4" />
                    <span class="font-bold truncate bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">{{ number_format($personaje->diamante, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Explorando: cuenta regresiva y cancelar. Al llegar a 0 avisa a la Ciudad para que aparezca el enemigo --}}
            @php
                $finExplorando = $personaje->fin_exploracion && $personaje->exploracion_duracion > 0
                    ? \Carbon\Carbon::parse($personaje->fin_exploracion) : null;
                $segundosExplorando = $finExplorando ? max(0, $finExplorando->timestamp - now()->timestamp) : 0;
            @endphp
            @if ($finExplorando)
                <div wire:key="explorando-{{ $finExplorando->timestamp }}"
                     x-data="{ fin: Date.now() / 1000 + {{ $segundosExplorando }}, s: {{ $segundosExplorando }}, avisado: false }"
                     x-init="const t = setInterval(() => {
                                 s = Math.max(0, Math.ceil(fin - Date.now() / 1000));
                                 if (s <= 0 && !avisado) {
                                     avisado = true; clearInterval(t);
                                     setTimeout(() => $wire.recargarPersonaje(), 1200);
                                 }
                             }, 250)"
                     class="w-full p-2 rounded-lg border border-black text-center font-mono
                            bg-gradient-to-b from-[#10283a] to-[#050d14]
                            shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]">
                    <p class="text-xs font-bold text-sky-300 uppercase tracking-wide">🧭 Explorando</p>
                    <p class="text-lg font-bold text-white" x-show="s > 0"
                       x-text="String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')">
                        {{ gmdate('i:s', $segundosExplorando) }}
                    </p>
                    <p class="text-sm font-bold text-yellow-300" x-show="s <= 0" x-cloak>¡Encontraste un enemigo!</p>
                    <button wire:click="cancelarExploracion" wire:loading.attr="disabled" x-show="s > 0"
                            class="mt-1 w-full py-1 rounded border border-black text-xs font-bold text-white
                                   bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                                   hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">
                        Cancelar exploración
                    </button>
                </div>
            @endif

            {{-- Recuperándose después de una pelea: cuenta regresiva y recuperar con oro --}}
            @php
                $finRecuperacion = $personaje->fin_exploracion && ! $personaje->exploracion_duracion
                    ? \Carbon\Carbon::parse($personaje->fin_exploracion) : null;
                $segundosRecuperacion = $finRecuperacion ? max(0, $finRecuperacion->timestamp - now()->timestamp) : 0;
                $costoRecuperacion = $personaje->nivel * 20;
            @endphp
            @if ($segundosRecuperacion > 0)
                {{-- La cuenta se calcula contra la hora de fin; al llegar a 0 se oculta sola y el panel se actualiza --}}
                <div wire:key="recuperacion-{{ $finRecuperacion->timestamp }}"
                     x-data="{ fin: Date.now() / 1000 + {{ $segundosRecuperacion }}, s: {{ $segundosRecuperacion }} }"
                     x-show="s > 0"
                     x-init="const t = setInterval(() => {
                                 s = Math.max(0, Math.ceil(fin - Date.now() / 1000));
                                 if (s <= 0) { clearInterval(t); setTimeout(() => $wire.recargarPersonaje(), 1000); }
                             }, 250)"
                     class="w-full p-2 rounded-lg border border-black text-center font-mono
                            bg-gradient-to-b from-[#3a2a10] to-[#120c04]
                            shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]">
                    <p class="text-xs font-bold text-yellow-300 uppercase tracking-wide">⏳ Recuperando</p>
                    <p class="text-lg font-bold text-white"
                       x-text="String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')">
                        {{ gmdate('i:s', $segundosRecuperacion) }}
                    </p>
                    <button wire:click="recuperarConOro" wire:loading.attr="disabled"
                            @disabled($personaje->oro < $costoRecuperacion)
                            class="mt-1 w-full flex items-center justify-center gap-1 py-1 rounded border border-black text-xs font-bold text-black
                                   bg-gradient-to-b from-yellow-300 to-yellow-600 shadow-[inset_1px_1px_0_rgba(255,255,255,0.5),0_2px_0_#000]
                                   hover:brightness-110 active:translate-y-[2px] active:shadow-none transition-all disabled:opacity-50">
                        Recuperar ya ·
                        <img src="{{ asset('images/oro.png') }}" alt="" class="h-3.5 w-3.5">
                        {{ number_format($costoRecuperacion, 0, ',', '.') }}
                    </button>
                </div>
            @endif

            {{-- Recuadro único: Stats y EXP --}}
            <div class="w-full mt-1 font-mono border-2 border-[#16203a] rounded p-1.5 shadow-[inset_0_0_10px_rgba(0,0,0,0.55),0_0_0_1px_rgba(90,130,200,0.35)] space-y-1.5"
                style="background-color: rgba(0,0,0,0.6); background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' font-family='monospace' font-size='9' fill='%23ffffff' fill-opacity='0.09'%3E%3Ctext x='4' y='12'%3E10%3C/text%3E%3Ctext x='58' y='20'%3E18%3C/text%3E%3Ctext x='96' y='10'%3E01%3C/text%3E%3Ctext x='24' y='40'%3E71%3C/text%3E%3Ctext x='80' y='46'%3E10%3C/text%3E%3Ctext x='6' y='66'%3E48%3C/text%3E%3Ctext x='50' y='72'%3E01%3C/text%3E%3Ctext x='100' y='78'%3E78%3C/text%3E%3Ctext x='30' y='98'%3E14%3C/text%3E%3Ctext x='76' y='106'%3E01%3C/text%3E%3Ctext x='4' y='116'%3E70%3C/text%3E%3C/svg%3E&quot;); background-size: 120px 120px;">

                {{-- Asignar stats --}}
                @livewire('asignar-stats', ['personaje' => $personaje], key($personaje->id))

                <div class="px-2 space-y-1.5">
                    {{-- Barra EXP --}}
                    <x-barra-exp :personaje="$personaje" />
                </div>
            </div>

            {{-- Recargar + tipo de daño y poderes (los mismos que en el inventario / la pelea). El nombre aparece al pasar el mouse o tocar --}}
            @php
                $postPoderesPj = $personaje->postDeCombate();
                // Mismo tipo que usa la pelea: el del set completo equipado o, si no, el del set base
                $tipoDanioPj = $postPoderesPj?->tipo ?? $personaje->post?->tipo;
                $poderesPj = $postPoderesPj?->poderes ?? collect();
            @endphp
            <div class="w-full flex flex-wrap items-center justify-center gap-1 mt-1">
                @if ($tipoDanioPj)
                    <x-icono-tipo :tipo="$tipoDanioPj" tam="w-10 h-10" class="cursor-pointer" />
                @endif
                @foreach ($poderesPj as $poder)
                    <x-icono-poder :poder="$poder" tam="w-10 h-10" class="cursor-pointer" />
                @endforeach
                @if ($personaje->joya)
                    <x-icono-joya :joya="$personaje->joya" tam="w-10 h-10" class="cursor-pointer" />
                @endif
            </div>
            <div class="flex justify-center mt-1">
                <button type="button" onclick="location.reload()" title="Refrescar página" aria-label="Refrescar página"
                    class="w-7 h-7 flex items-center justify-center bg-red-700 hover:bg-red-800 rounded-full text-white">
                    <i class="fa-solid fa-arrow-rotate-right text-xs"></i>
                </button>
            </div>

            {{-- Botón admin --}}
            @auth
                @if(auth()->user()->is_admin)
                    @livewire('editar-personaje-modal', ['personaje' => $personaje])
                @endif
            @endauth
        </aside>

        {{-- Contenedor columna principal: Nav y sección principal --}}
        <div class="flex flex-col flex-grow overflow-hidden">
            {{-- Nav arriba con fondo de la landing --}}
            <nav class="relative flex flex-col gap-2 p-2 sm:p-3 shadow-md border border-gray-700 w-full rounded-md text-xs sm:text-sm bg-cover bg-center"
                style="background-image: url('{{ asset('images/fondo.jpg') }}');">
                <div class="absolute inset-0 bg-black bg-opacity-80 rounded-md"></div>
                <div class="relative z-10 flex flex-col gap-2">
                {{-- Fila 1 --}}
                <div class="flex flex-wrap justify-center items-center gap-1 sm:gap-2">
                    @php
                    $botonesFila1 = [
                        ['seccion' => 'inicio', 'nombre' => 'Ciudad'],
                        ['seccion' => 'inventario', 'nombre' => 'Inventario'],
                        ['seccion' => 'ranking', 'nombre' => 'Ranking'],
                        ['seccion' => 'clan', 'nombre' => 'Clan'],
                        ['seccion' => 'mercado', 'nombre' => 'Mercado'],
                        ['seccion' => 'casino', 'nombre' => 'Casino'],
                        ['seccion' => 'extra', 'nombre' => 'Extras'],
                
                    ];
                    @endphp

                    @foreach ($botonesFila1 as $boton)
                        @php
                        $claseBoton = match($boton['seccion']) {
                            'extra' => 'border-yellow-600 text-yellow-400 from-gray-700 to-gray-950',
                            'casino' => 'border-yellow-500 text-yellow-300 from-red-800 to-gray-900',
                            default => 'border-black text-yellow-400 from-neutral-700 to-black',
                        };
                        @endphp
                        <button wire:click="cambiarSeccion('{{ $boton['seccion'] }}')"
                            class="px-3 py-1 rounded-md font-semibold uppercase transition-all duration-100 border-2 bg-gradient-to-b hover:brightness-125 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] {{ $claseBoton }}">
                            {{ $boton['nombre'] }}
                        </button>
                    @endforeach
                </div>

                {{-- Fila 2 --}}
                <div class="flex flex-wrap justify-center items-center gap-1 sm:gap-2">
                    @php
                    $botonesFila2 = [
                        ['seccion' => 'viajar', 'nombre' => 'Viajar'],
                        ['seccion' => 'galeria', 'nombre' => 'Mis Personajes'],
                        ['seccion' => 'peleas', 'nombre' => 'Mis Peleas'],
                        ['seccion' => 'drops', 'nombre' => 'Mis Drops'],
                        // Personajes y Poderes van en la ciudad, debajo de los usuarios de la zona
                    ];
                    @endphp

                    @foreach ($botonesFila2 as $boton)
                        @php
                        $claseBoton = match($boton['seccion']) {
                            'sets' => 'border-green-500 text-green-300 from-green-800 to-gray-900',
                            'poderes' => 'border-purple-500 text-purple-300 from-purple-800 to-gray-900',
                            'viajar' => 'border-cyan-500 text-cyan-300 from-cyan-800 to-gray-900',
                            'caza' => 'border-emerald-500 text-emerald-300 from-emerald-800 to-gray-900',
                            default => 'border-black text-yellow-400 from-neutral-700 to-black',
                        };
                        @endphp
                        <button wire:click="cambiarSeccion('{{ $boton['seccion'] }}')"
                            class="px-3 py-1 rounded-md font-semibold uppercase transition-all duration-100 border-2 bg-gradient-to-b hover:brightness-125 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] {{ $claseBoton }}">
                            {{ $boton['nombre'] }}
                        </button>
                    @endforeach
                </div>
                </div>
            </nav>

            {{-- Sección principal --}}
            <main class="flex-grow min-h-0 overflow-auto p-2 bg-gray-800/50">
                @if ($seccion === 'inicio')
                    @livewire('explorar', ['personajeId' => $personaje->id, 'ciudadId' => $ciudadActual->id], key('inicio-'.$reloadCounters['inicio']))
                @elseif ($seccion === 'viajar')
                    @livewire('viajar', ['personaje' => $personaje], key('viajar-'.$reloadCounters['viajar']))
                @elseif ($seccion === 'inventario')
                    <livewire:inventario :personajeId="$personaje->id" wire:key="inventario-{{ $personaje->id }}-{{ $reloadCounters['inventario'] }}" />
                @elseif ($seccion === 'mercado')
                    @livewire('mercado', ['personaje' => $personaje], key('mercado-'.$reloadCounters['mercado']))
                @elseif ($seccion === 'ranking')
                    @livewire('ranking', key('ranking-'.$reloadCounters['ranking']))
                @elseif ($seccion === 'clan')
                    <livewire:clan />
                @elseif ($seccion === 'extra')
                    <livewire:extra :personaje="$personaje" :key="'extra-' . $reloadCounters['extra']" />
                @elseif ($seccion === 'sets')
                    @livewire('sets', key('sets-'.$reloadCounters['sets']))
                @elseif ($seccion === 'poderes')
                    @livewire('poderes', key('poderes-' . $reloadCounters['poderes']))
                    @elseif ($seccion === 'galeria')
                @livewire('galeria-personajes', ['personajeId' => $personaje->id], key('galeria-'.$reloadCounters['galeria']))
                @elseif ($seccion === 'casino')
                @livewire('casino', ['personaje' => $personaje], key('casino-'.$reloadCounters['casino']))
                @elseif ($seccion === 'caza')
                @livewire('caza', ['personaje' => $personaje], key('caza-'.$reloadCounters['caza']))
                @elseif ($seccion === 'misiones')
                @livewire('misiones', ['personaje' => $personaje], key('misiones-'.($reloadCounters['misiones'] ?? 0)))
                @elseif ($seccion === 'torre')
                @livewire('torre', ['personaje' => $personaje], key('torre-'.($reloadCounters['torre'] ?? 0)))
                @elseif ($seccion === 'drops')
                @livewire('mis-drops', ['personajeId' => $personaje->id], key('drops-'.($reloadCounters['drops'] ?? 0)))
                @elseif ($seccion === 'peleas')
                @livewire('mis-peleas', ['personajeId' => $personaje->id], key('peleas-'.$reloadCounters['peleas']))
                @endif
            </main>
        </div>

        {{-- Chat lateral derecho (solo PC) --}}
        <div class="w-[380px] h-full hidden lg:flex flex-col bg-[#0c202e]">
            @livewire('chat-sidebar')
        </div>
    </div>

    {{-- Modal banco de oro --}}
    @if($modalGuardarOro)
    <div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-900 p-4 rounded-xl shadow-lg w-64 space-y-3 text-center">
            <h2 class="text-lg font-bold text-yellow-400">Banco de Oro</h2>
            <p class="text-sm text-gray-300">Oro: <span class="text-yellow-500">{{ number_format($personaje->oro) }}</span></p>
            <p class="text-sm text-gray-400">Guardado: <span class="text-yellow-500">{{ number_format($oroGuardado) }}</span></p>
            <input type="number" wire:model.defer="montoOro" min="1" placeholder="Cantidad"
                class="w-full py-1 px-2 rounded bg-gray-800 text-white border border-gray-600 text-sm" />
            <div class="flex justify-center gap-2">
                <button wire:click="guardarOro" class="px-3 py-1 bg-yellow-400 text-black rounded hover:bg-yellow-500 text-sm">Guardar</button>
                <button wire:click="retirarOro" class="px-3 py-1 bg-yellow-500 text-black rounded hover:bg-yellow-600 text-sm">Retirar</button>
            </div>
            <button wire:click="$set('modalGuardarOro', false)" class="w-full py-1 bg-gray-700 hover:bg-gray-800 text-gray-300 rounded text-sm">Cerrar</button>
        </div>
    </div>
    @endif

    {{-- Script recarga --}}
    @push('scripts')
    <script>
        window.addEventListener('recargar-pagina', () => location.reload());
    </script>
    @endpush
</div>
