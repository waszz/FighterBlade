{{-- fixed inset-0: ocupa exactamente la ventana visible (100dvh en algunos iPhone quedaba más corto y dejaba una franja abajo) --}}
<div class="fixed inset-0 overflow-hidden" x-data="{ perfil: false, chat: false, pelea: false }" x-init="$watch('chat', () => window.dispatchEvent(new CustomEvent('chat-visto')))" @cerrar-chat.window="chat = false" @modo-pelea.window="pelea = $event.detail.activo">
    {{-- Una sola pestaña por navegador: las pestañas comparten la sesión (el servidor no las distingue), así que se avisan
         entre ellas; al abrir el juego en otra pestaña, esta queda bloqueada. "Jugar acá" la recarga y bloquea a la otra --}}
    <div wire:ignore x-data="{ otraPestana: false }"
         x-init="if ('BroadcastChannel' in window) {
                     const canal = new BroadcastChannel('fighterblade-juego');
                     canal.onmessage = (e) => { if (e.data === 'abierta') otraPestana = true; };
                     canal.postMessage('abierta');
                 }"
         x-show="otraPestana" x-cloak
         class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/90 px-4">
        <div class="w-full max-w-xs p-5 rounded-xl border border-black text-center text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
            <p class="text-3xl mb-2">⚠️</p>
            <h2 class="text-lg font-bold text-yellow-300 mb-1 [text-shadow:0_2px_0_#000]">Te conectaste desde otro lugar</h2>
            <p class="text-sm text-gray-300 mb-4">Abriste el juego en otra pestaña de este navegador. Solo se puede jugar en una a la vez.</p>
            <button type="button" @click="location.reload()"
                class="w-full py-2 rounded-lg border border-black font-bold bg-gradient-to-b from-green-500 to-green-800
                       shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">
                Jugar acá
            </button>
        </div>
    </div>

    {{-- Tutorial: se abre con los botones "?" y solo la primera vez a los personajes nuevos --}}
    <x-tutorial :personaje="$personaje" />

    {{-- Fondo con imagen y capa oscura --}}
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/-juego.jpg') }}');"></div>
    <div class="absolute inset-0 bg-black bg-opacity-40"></div>

    {{-- Layout principal --}}
    <div class="relative flex flex-col lg:flex-row h-full text-white">

        {{-- Sidebar personaje (izquierda) --}}
    {{-- Fondo oscuro detrás de los paneles del celular (perfil / chat) --}}
    <div x-show="perfil || chat" x-cloak x-transition.opacity @click="perfil = false; chat = false"
         class="lg:hidden fixed inset-0 z-40 bg-black/70"></div>

    {{-- En PC fijo a la izquierda; en celular es un panel que se abre con el botón de perfil --}}
    <aside :class="perfil ? '!flex fixed inset-y-0 left-0 z-50 w-64' : ''"
        class="sidebar-pj hidden lg:flex lg:w-56 shrink-0 p-1 flex-col items-center space-y-1 [&>*]:shrink-0 min-h-0 max-h-full overflow-y-auto overflow-x-hidden text-xs select-none text-white border-x-2 border-[#3d7fd6] shadow-[inset_0_0_12px_rgba(0,0,0,0.45)]"
        style="background-image: url('{{ asset('images/-juego.jpg') }}'); background-size: cover; background-position: 77% center; background-color: #0c202e;">
            {{-- Cerrar (solo celular) --}}
            <button type="button" @click="perfil = false" aria-label="Cerrar"
                class="lg:hidden self-end w-7 h-7 flex items-center justify-center rounded-full border-2 border-black bg-gradient-to-b from-red-600 to-red-900 text-white text-sm font-bold shadow-[0_2px_0_#000]">&times;</button>

            {{-- Nombre estilo retro --}}
            <div data-tope-gif class="relative z-20 w-full bg-gradient-to-b from-neutral-800 to-black border-2 border-black rounded px-1 py-0.5 font-mono shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">
                <h2 class="text-xs font-bold uppercase truncate w-full text-center text-red-500 tracking-wide"><span class="{{ $personaje->claseNombre() }}">{{ $personaje->nombre ?? 'Sin nombre' }}</span></h2>
            </div>

            {{-- Logout (y, para admins, el acceso a la administración: la barra de arriba no se muestra en el juego) --}}
            <div class="relative z-20 w-full flex justify-center gap-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-1.5 rounded bg-black/60 font-mono text-[11px] font-bold uppercase tracking-wide text-sky-300 hover:text-sky-200 underline underline-offset-2">
                        Logout
                    </button>
                </form>
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('posts.index') }}" class="px-1.5 rounded bg-black/60 font-mono text-[11px] font-bold uppercase tracking-wide text-amber-300 hover:text-amber-200 underline underline-offset-2">Admin</a>
                @endif
                {{-- Inventario lleno: aviso (lleva al inventario). Las partes, pociones y cofres que se ganan no entran --}}
                @if ($personaje->lugaresLibres() <= 0)
                    <button type="button" wire:click="$set('seccion', 'inventario')" title="Inventario lleno: lo que ganes no va a entrar"
                        class="px-1.5 rounded border border-black bg-gradient-to-b from-red-500 to-red-800 font-mono text-[11px] font-bold uppercase tracking-wide text-white animate-pulse shadow-[0_2px_0_#000]">
                        🎒 Lleno
                    </button>
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
                {{-- Misma escala que en las peleas (con el ajuste de tamaño del set); si es más alto que el recuadro de la
                     ciudad se achica para entrar (los sets grandes salían por arriba y tapaban el Logout) --}}
                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 cursor-pointer" wire:click="$set('seccion', 'inventario')"
                     x-data="{
                         // La escala queda en el estado de Alpine (no en un estilo puesto a mano): cuando Livewire redibuja el
                         // panel la vuelve a aplicar (antes la borraba y el personaje se agrandaba y achicaba)
                         k: 1,
                         ajustar() {
                             const caja = $el.parentElement, img = $refs.img;
                             if (! caja.clientHeight || ! img.complete) return;
                             // Los sets grandes pueden salir por arriba del recuadro de la ciudad y pasar por detrás del Logout y
                             // del nombre (que quedan adelante); recién si la cabeza pasaría el nombre se achica desde abajo
                             // (los pies quedan donde están). Se mide con la escala puesta (dividiendo por ella): así no parpadea
                             const c = caja.getBoundingClientRect(), r = img.getBoundingClientRect(), base = $refs.escala.getBoundingClientRect().bottom;
                             if (! r.height) return;
                             const tope = $el.closest('aside')?.querySelector('[data-tope-gif]')?.getBoundingClientRect().top ?? c.top;
                             const alto = (base - r.top) / this.k, ancho = r.width / this.k;
                             this.k = Math.min(1, (base - tope - 2) / alto, (c.width + 40) / ancho);
                         }
                     }"
                     x-init="$nextTick(() => ajustar()); $refs.img.addEventListener('load', () => ajustar());
                             new ResizeObserver(() => ajustar()).observe($el.parentElement)">
                    <div x-ref="escala" wire:ignore.self style="transform-origin: bottom center" :style="'transform-origin: bottom center; transform: scale(' + k + ')'">
                        <img x-ref="img" src="{{ asset('storage/' . $gif) }}" alt="Personaje"
                            style="{{ \App\Models\Post::estiloGif($gif, 1) }}" class="block max-w-none {{ $personaje->claseAura() }}" />
                    </div>
                </div>
                @endif
            </div>

            {{-- Nivel --}}
            <div class="px-4 py-0.5 rounded-full border-2 border-[#3d7fd6] bg-[#0a1628] font-mono font-bold text-sm text-sky-300 tracking-wide select-none shadow-[0_2px_4px_rgba(0,0,0,0.6)]">
                <span class="{{ $personaje->claseNivel() }}">Nv. {{ $personaje->nivel ?? 1 }}</span>
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
            {{-- Contenedores fijos (display: contents) para los paneles que aparecen y desaparecen: si el @if quedara suelto,
                 Livewire empareja mal los elementos al redibujar y pisa el componente de stats de abajo (se traba el juego) --}}
            <div wire:key="panel-explorando" class="contents">
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
            </div>

            {{-- Cazando: cuenta regresiva del rastreo de la presa. Al terminar, el botón lleva a la Caza para enfrentarla --}}
            @php
                $cazaRastreo = \App\Models\Caza::activaDe($personaje->id);
                $cazaRastreo = $cazaRastreo?->estado === 'rastreando' ? $cazaRastreo : null;
                $segundosCaza = $cazaRastreo ? max(0, $cazaRastreo->fin_rastreo->timestamp - now()->timestamp) : 0;
                $presaRastreo = $cazaRastreo ? \App\Models\Post::conRivales()->find($cazaRastreo->post_id) : null;
            @endphp
            <div wire:key="panel-cazando" class="contents">
            @if ($cazaRastreo)
                <div wire:key="cazando-{{ $cazaRastreo->id }}-{{ $cazaRastreo->fin_rastreo->timestamp }}"
                     x-data="{ fin: Date.now() / 1000 + {{ $segundosCaza }}, s: {{ $segundosCaza }} }"
                     x-init="if (s > 0) { const t = setInterval(() => { s = Math.max(0, Math.ceil(fin - Date.now() / 1000)); if (s <= 0) clearInterval(t); }, 250) }"
                     class="w-full p-2 rounded-lg border border-black text-center font-mono
                            bg-gradient-to-b from-[#123320] to-[#04140a]
                            shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]">
                    <p class="text-xs font-bold text-emerald-300 uppercase tracking-wide">🎯 Cazando</p>
                    @if ($presaRastreo)
                        <p class="text-[11px] font-bold truncate {{ $cazaRastreo->rareza === 'legendaria' ? 'text-amber-300' : ($cazaRastreo->rareza === 'rara' ? 'text-sky-300' : 'text-gray-300') }}">
                            {{ $cazaRastreo->rarezaInfo()['nombre'] }}: {{ $presaRastreo->titulo }}
                        </p>
                    @endif
                    <p class="text-lg font-bold text-white" x-show="s > 0"
                       x-text="(s >= 3600 ? Math.floor(s / 3600) + ':' : '') + String(Math.floor(s % 3600 / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')">
                        {{ $segundosCaza >= 3600 ? gmdate('G:i:s', $segundosCaza) : gmdate('i:s', $segundosCaza) }}
                    </p>
                    <p class="text-sm font-bold text-yellow-300" x-show="s <= 0" @if ($segundosCaza > 0) x-cloak @endif>¡Encontraste la presa!</p>
                    <button wire:click="cambiarSeccion('caza')" x-show="s <= 0" @if ($segundosCaza > 0) x-cloak @endif
                            class="mt-1 w-full py-1 rounded border border-black text-xs font-bold text-white
                                   bg-gradient-to-b from-emerald-500 to-emerald-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                                   hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">
                        Ir a enfrentarla
                    </button>
                </div>
            @endif
            </div>

            {{-- Recuperándose después de una pelea: cuenta regresiva y recuperar con oro --}}
            @php
                // (también mientras explora, si lo atacaron en PvP: ver Personaje::segundosRecuperacion)
                $segundosRecuperacion = $personaje->segundosRecuperacion();
                $finRecuperacion = $segundosRecuperacion > 0 ? now()->addSeconds($segundosRecuperacion) : null;
                $costoRecuperacion = $personaje->nivel * 20;
            @endphp
            <div wire:key="panel-recuperacion" class="contents">
            @if ($segundosRecuperacion > 0)
                {{-- La cuenta se calcula contra la hora de fin; al llegar a 0 se oculta sola y el panel se actualiza --}}
                <div wire:key="recuperacion-{{ $finRecuperacion->timestamp }}"
                     x-data="{ fin: Date.now() / 1000 + {{ $segundosRecuperacion }}, s: {{ $segundosRecuperacion }} }"
                     x-show="s > 0"
                     x-init="const t = setInterval(() => {
                                 s = Math.max(0, Math.ceil(fin - Date.now() / 1000));
                                 if (s <= 0) { clearInterval(t); setTimeout(() => { $wire.recargarPersonaje(); Livewire.dispatch('recuperacionTerminada'); }, 1000); }
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
            </div>

            {{-- Recuadro único: Stats y EXP --}}
            <div wire:key="recuadro-stats" class="w-full mt-1 font-mono border-2 border-[#16203a] rounded p-1.5 shadow-[inset_0_0_10px_rgba(0,0,0,0.55),0_0_0_1px_rgba(90,130,200,0.35)] space-y-1.5"
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
                @if ($personaje->objeto_consumible)
                    <x-icono-pocion :pocion="$personaje->objeto_consumible" tam="w-10 h-10" class="cursor-pointer" />
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
        <div class="flex flex-col flex-grow min-h-0 overflow-hidden">
            @php
                // Botones del celular: redondos, a los costados (izquierda / derecha)
                $botonesMovilIzq = [
                    ['seccion' => 'inventario', 'nombre' => 'Inventario', 'icono' => 'fa-bag-shopping'],
                    ['seccion' => 'viajar', 'nombre' => 'Viajar', 'icono' => 'fa-plane'],
                    ['seccion' => 'mercado', 'nombre' => 'Mercado', 'icono' => 'fa-store'],
                    ['seccion' => 'extra', 'nombre' => 'Extras', 'icono' => 'fa-star'],
                ];
                $botonesMovilDer = [
                    ['seccion' => 'ranking', 'nombre' => 'Ranking', 'icono' => 'fa-trophy'],
                    ['seccion' => 'clan', 'nombre' => 'Clan', 'icono' => 'fa-shield-halved'],
                    ['seccion' => 'casino', 'nombre' => 'Casino', 'icono' => 'fa-dice'],
                    ['seccion' => 'peleas', 'nombre' => 'Mis Peleas', 'icono' => 'fa-hand-fist'],
                    ['seccion' => 'drops', 'nombre' => 'Mis Drops', 'icono' => 'fa-gem'],
                    ['seccion' => 'galeria', 'nombre' => 'Mis Personajes', 'icono' => 'fa-users'],
                    ['seccion' => 'calendario', 'nombre' => 'Calendario', 'icono' => 'fa-calendar-days'],
                ];
                // En el inventario se necesita el ancho: quedan solo Extras y Ranking (la Ciudad está en la barra de arriba)
                if ($seccion === 'inventario') {
                    $botonesMovilIzq = array_values(array_filter($botonesMovilIzq, fn ($b) => $b['seccion'] === 'extra'));
                    $botonesMovilDer = array_values(array_filter($botonesMovilDer, fn ($b) => $b['seccion'] === 'ranking'));
                }
                $botonRedondo = 'flex items-center justify-center rounded-full border-2 border-black text-white bg-gradient-to-b shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] active:translate-y-[2px] transition-all';
                $botonCuadrado = 'w-11 h-11 shrink-0 flex items-center justify-center rounded-lg border-2 border-black text-white text-lg bg-gradient-to-b from-green-500 to-green-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.4),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] active:translate-y-[2px] transition-all';
            @endphp

            {{-- Barra superior (solo celular): Ciudad · nombre, nivel, oro y esmeraldas · perfil --}}
            <div class="lg:hidden relative z-30 flex items-center gap-2 px-2 py-1.5 border-b-2 border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[0_3px_0_#000]">
                <button type="button" wire:click="cambiarSeccion('inicio')" aria-label="Ciudad" class="{{ $botonCuadrado }}">
                    <i class="fa-solid fa-house"></i>
                </button>
                <div class="flex-1 min-w-0 text-center font-mono leading-tight">
                    <p class="truncate text-sm font-bold"><span class="{{ $personaje->claseNombre() }}">{{ $personaje->nombre }}</span> <span class="{{ $personaje->claseNivel() ?: 'text-sky-300' }}">Nv. {{ $personaje->nivel ?? 1 }}</span></p>
                    <p class="flex items-center justify-center gap-3 text-xs font-bold">
                        <span class="flex items-center gap-1 text-yellow-400"><img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-3.5">{{ number_format($personaje->oro, 0, ',', '.') }}</span>
                        <span class="flex items-center gap-1"><img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="h-3.5"><span class="num-esmeralda">{{ number_format($personaje->diamante, 0, ',', '.') }}</span></span>
                    </p>
                </div>
                {{-- Notificaciones del juego y transacciones --}}
                <livewire:avisos-juego :personaje-id="$personaje->id" estilo="celular" :key="'avisos-cel-' . $personaje->id" />
                <button type="button" @click="$dispatch('abrir-tutorial')" title="Cómo jugar" aria-label="Cómo jugar"
                    class="w-9 h-9 text-lg shrink-0 flex items-center justify-center rounded-full border-2 border-black text-white font-extrabold bg-gradient-to-b from-sky-500 to-sky-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.4),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[2px] transition-all">?</button>
                <button type="button" @click="perfil = true" aria-label="Mi personaje" class="{{ $botonCuadrado }}">
                    <i class="fa-solid fa-user"></i>
                </button>
            </div>

            {{-- Botones redondos a los costados (solo celular); la sección abierta queda en verde --}}
            @foreach (['left-1' => $botonesMovilIzq, 'right-1' => $botonesMovilDer] as $lado => $botonesLado)
                {{-- En una pelea (y su resultado) no se muestran: tapaban el texto de las rondas --}}
                <div x-show="! pelea" class="lg:hidden fixed {{ $lado }} top-[4.25rem] z-30 flex flex-col gap-2">
                    @foreach ($botonesLado as $boton)
                        <button type="button" wire:click="cambiarSeccion('{{ $boton['seccion'] }}')" title="{{ $boton['nombre'] }}" aria-label="{{ $boton['nombre'] }}"
                            class="{{ $botonRedondo }} w-11 h-11 text-lg {{ $seccion === $boton['seccion'] ? 'from-green-500 to-green-800' : 'from-red-700 to-red-950' }}">
                            <i class="fa-solid {{ $boton['icono'] }}"></i>
                        </button>
                    @endforeach
                </div>
            @endforeach

            {{-- Nav arriba con fondo de la landing --}}
            <nav class="relative hidden lg:flex flex-col gap-2 p-2 sm:p-3 shadow-md border border-gray-700 w-full rounded-md text-xs sm:text-sm bg-cover bg-center"
                style="background-image: url('{{ asset('images/fondo.jpg') }}');">
                <div class="absolute inset-0 bg-black bg-opacity-80 rounded-md"></div>
                <div class="absolute top-2 right-2 z-20">
                    <button type="button" @click="$dispatch('abrir-tutorial')" title="Cómo jugar" aria-label="Cómo jugar"
                    class="w-9 h-9 text-lg shrink-0 flex items-center justify-center rounded-full border-2 border-black text-white font-extrabold bg-gradient-to-b from-sky-500 to-sky-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.4),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[2px] transition-all">?</button>
                </div>
                <div class="relative z-10 flex flex-col gap-2 pr-11">
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
                        ['seccion' => 'calendario', 'nombre' => 'Calendario'],
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
                    {{-- Notificaciones del juego y transacciones, al lado de Mis Drops --}}
                    <livewire:avisos-juego :personaje-id="$personaje->id" estilo="pc" :key="'avisos-pc-' . $personaje->id" />
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
                @elseif ($seccion === 'calendario')
                @livewire('calendario', key('calendario-'.($reloadCounters['calendario'] ?? 0)))
                @elseif ($seccion === 'mazmorra')
                @livewire('mazmorra', ['personaje' => $personaje], key('mazmorra-'.($reloadCounters['mazmorra'] ?? 0)))
                @elseif ($seccion === 'entrenar')
                @livewire('entrenar', ['personaje' => $personaje], key('entrenar-'.($reloadCounters['entrenar'] ?? 0)))
                @elseif ($seccion === 'drops')
                @livewire('mis-drops', ['personajeId' => $personaje->id], key('drops-'.($reloadCounters['drops'] ?? 0)))
                @elseif ($seccion === 'peleas')
                @livewire('mis-peleas', ['personajeId' => $personaje->id, 'peleaCompartidaId' => $peleaCompartidaId], key('peleas-'.$reloadCounters['peleas']))
                @endif
            </main>

            {{-- Barra inferior (solo celular): perfil · chat --}}
            <div x-show="! pelea" class="lg:hidden relative z-30 flex items-center justify-between px-3 py-1.5 border-t-2 border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14]">
                <button type="button" @click="perfil = true" aria-label="Mi personaje" class="{{ $botonRedondo }} w-9 h-9 text-sm from-[#2f5470] to-[#0a1a26]">
                    <i class="fa-solid fa-user"></i>
                </button>
                <button type="button" @click="chat = true" aria-label="Chat" class="relative {{ $botonRedondo }} w-9 h-9 text-sm from-[#2f5470] to-[#0a1a26]">
                    <i class="fa-solid fa-comments"></i>
                    {{-- Punto rojo: mensajes privados sin leer o mensajes nuevos en el chat general --}}
                    @livewire('aviso-chat', ['personajeId' => $personaje->id], key('aviso-chat-' . $personaje->id))
                </button>
            </div>
        </div>

        {{-- Chat lateral derecho (solo PC) --}}
        <div :class="chat ? '!flex fixed inset-0 !h-auto z-50 !w-full' : ''" class="w-[380px] h-full hidden lg:flex flex-col bg-[#0c202e]">
            <button type="button" @click="chat = false" aria-label="Cerrar chat"
                class="lg:hidden self-end m-2 w-8 h-8 shrink-0 flex items-center justify-center rounded-full border-2 border-black bg-gradient-to-b from-red-600 to-red-900 text-white font-bold shadow-[0_2px_0_#000]">&times;</button>
            @livewire('chat-sidebar', ['personajeId' => $personaje->id])
        </div>
    </div>

    {{-- Modal banco de oro --}}
    @if($modalGuardarOro)
    {{-- Modal 3D (mismo estilo que los modales de jugador) --}}
    <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="$set('modalGuardarOro', false)">
        <div wire:click.stop
             class="relative w-full max-w-xs p-4 rounded-xl border border-black text-white text-center space-y-3
                    bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
            <button type="button" wire:click="$set('modalGuardarOro', false)" aria-label="Cerrar"
                class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                       bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                       hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

            <h2 class="flex items-center justify-center gap-2 text-xl font-bold text-yellow-300 [text-shadow:0_2px_0_#000]">
                <img src="{{ asset('images/oro.png') }}" alt="" class="h-5 w-5"> Banco de Oro
            </h2>

            {{-- Oro encima y guardado --}}
            <div class="grid grid-cols-2 gap-2 text-sm font-bold">
                @foreach (['En mano' => $personaje->oro, 'Guardado' => $oroGuardado] as $etiquetaOro => $cantidadOro)
                    <div class="py-1.5 rounded-lg border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b]
                                shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400">{{ $etiquetaOro }}</p>
                        <p class="flex items-center justify-center gap-1 text-yellow-300">
                            <img src="{{ asset('images/oro.png') }}" alt="" class="h-4 w-4">{{ number_format($cantidadOro ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>

            <input type="number" wire:model.defer="montoOro" min="1" placeholder="Cantidad"
                class="w-full px-3 py-1.5 rounded-lg border border-black bg-black/50 text-white text-center placeholder-gray-500
                       shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)] focus:outline-none focus:ring-2 focus:ring-yellow-500 text-sm" />

            <div class="grid grid-cols-2 gap-2">
                <button wire:click="guardarOro" wire:loading.attr="disabled"
                    class="py-1.5 rounded-lg border border-black font-bold text-sm text-white bg-gradient-to-b from-green-500 to-green-800
                           shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]
                           hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all disabled:opacity-50">
                    Guardar
                </button>
                <button wire:click="retirarOro" wire:loading.attr="disabled"
                    class="py-1.5 rounded-lg border border-black font-bold text-sm text-black bg-gradient-to-b from-yellow-300 to-yellow-600
                           shadow-[inset_1px_1px_0_rgba(255,255,255,0.5),inset_-1px_-1px_0_rgba(0,0,0,0.4),0_3px_0_#000]
                           hover:brightness-110 active:translate-y-[3px] active:shadow-none transition-all disabled:opacity-50">
                    Retirar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Duelos e intercambios: avisos y ventana de intercambio --}}
    <livewire:desafios :personajeId="$personaje->id" :key="'desafios-' . $personaje->id" />

    {{-- Perfil de un jugador tocado en los Conectados del chat (el mismo modal del Ranking) --}}
    <livewire:ranking-list :ranking="collect()" tipo="Nivel" :personaje="$personaje" :solo-modal="true" key="perfil-jugador-chat" />

    {{-- Script recarga --}}
    @push('scripts')
    <script>
        window.addEventListener('recargar-pagina', () => location.reload());
    </script>
    @endpush
</div>
