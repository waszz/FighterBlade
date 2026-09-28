<div class="min-h-screen py-6 px-2 bg-gray-900 flex flex-col items-center relative text-white"
    style="background-image: url('{{ asset('images/fondo.jpg') }}'); background-size: cover; background-position: center;">
    <div class="absolute inset-0 bg-black bg-opacity-80 z-0"></div>

    {{-- Más ancho que el resto para que el ranking quede bien al costado --}}
    <div class="relative z-10 flex flex-col lg:flex-row items-start justify-between max-w-[1700px] w-full gap-6 lg:px-4">
        <div class="flex-1 flex flex-col items-center">
            <img src="{{ asset('images/logo-pelea.png') }}" alt="Logo"
                class="w-60 md:w-40 lg:w-72 mb-3 drop-shadow-[0_0_15px_rgba(255,0,0,0.7)] animate-fade-in" />

            <h1 class="text-3xl md:text-4xl font-extrabold mb-3 drop-shadow-lg animate-fade-in">
                ¡Prepárate para la batalla definitiva!
            </h1>

            <p class="text-base md:text-lg max-w-md text-yellow-300 mb-6 drop-shadow-md animate-fade-in delay-150">
                Elige tu luchador, desafía a tus rivales y domina el ranking.
            </p>

            @php
                // ¡Jugar ahora!: al juego con tu personaje, a elegir uno, o a iniciar sesión
                $urlJugar = ! auth()->check()
                    ? route('login')
                    : ($personajeUsuario ? route('juego.mostrar', ['personajeId' => $personajeUsuario->id]) : route('personajes.elegir'));
            @endphp
            <a href="{{ $urlJugar }}" class="uppercase bg-gradient-to-r from-red-600 to-yellow-500 hover:from-yellow-500 hover:to-red-600
                text-white font-bold py-4 px-12 rounded-lg text-lg shadow-[0_0_15px_rgba(255,69,0,0.8)]
                transition transform hover:scale-105 animate-fade-in delay-300">
                ¡Jugar Ahora!
            </a>

            {{-- Tu personaje se muestra al costado, arriba del ranking --}}

            <h2 class="text-xl font-bold text-white my-8 animate-fade-in delay-700">Personajes Iniciales</h2>

            <div class="flex flex-col lg:flex-row w-full gap-6 animate-fade-in delay-700">
               <div class="w-full grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 place-items-center">
    @php
        $abrevInicial = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'energia' => 'ENE', 'velocidad' => 'VEL', 'defensa' => 'DEF', 'resistencia' => 'RES'];
        $colorTipoInicial = ['fisico' => 'text-red-400', 'elemental' => 'text-blue-400', 'hibrido' => 'text-purple-400'];
    @endphp
    @foreach ($posts->take(5) as $post)
    {{-- Tarjeta 3D: gif animado + stats --}}
    <div class="w-full max-w-[190px] p-2 rounded-xl border border-yellow-600 text-center
                bg-gradient-to-b from-[#1c2533]/95 to-[#0a0e14]/95 transition-transform duration-200 hover:-translate-y-1
                shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_8px_16px_rgba(0,0,0,0.7)]">
        <div class="h-36 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
            @if ($post->gif)
                <img src="{{ asset('storage/' . $post->gif) }}" alt="{{ $post->titulo }}" loading="lazy"
                     style="{{ \App\Models\Post::estiloGif($post->gif, 0.9) }}" class="block max-w-none">
            @endif
        </div>

        <h3 class="mt-2 font-bold text-yellow-300 truncate [text-shadow:0_2px_0_#000]">{{ $post->titulo }}</h3>
        <p class="text-xs font-semibold {{ $colorTipoInicial[$post->tipo] ?? 'text-gray-400' }}"><x-icono-tipo :tipo="$post->tipo" tam="w-5 h-5" :con-nombre="true" /></p>

        {{-- Stats con etiquetas 3D (como el panel de atributos) --}}
        <div class="mt-2 grid grid-cols-2 gap-1 font-mono text-xs">
            @foreach ($abrevInicial as $stat => $abrev)
                <div class="flex items-center gap-1">
                    <span class="w-8 h-5 flex items-center justify-center font-bold text-yellow-300 border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">{{ $abrev }}</span>
                    <span class="font-bold text-white">+{{ $post->stats[$stat] ?? 0 }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>


            </div>
        </div>

        {{-- Ranking: al costado, arriba y fijo en su lugar (en celular queda debajo) --}}
        <aside class="w-full lg:w-96 shrink-0 space-y-5 animate-fade-in delay-300">
            {{-- Iniciar sesión (solo si todavía no entró) --}}
            @guest
            <form method="POST" action="{{ route('login') }}" novalidate
                  class="p-5 rounded-xl border border-yellow-600 bg-gradient-to-b from-[#1c2533]/95 to-[#0a0e14]/95 space-y-3
                         shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_8px_16px_rgba(0,0,0,0.7)]">
                @csrf
                <h3 class="text-2xl font-bold text-yellow-400 text-center [text-shadow:0_2px_0_#000]">🔑 Iniciar sesión</h3>

                <div>
                    <label for="home-email" class="block text-sm font-semibold text-gray-300 mb-1">Email</label>
                    <input id="home-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                           class="w-full rounded-lg border border-black bg-black/50 px-3 py-2 text-white placeholder-gray-500
                                  shadow-[inset_0_2px_6px_rgba(0,0,0,0.8)] focus:outline-none focus:ring-2 focus:ring-yellow-500"
                           placeholder="tu@email.com">
                </div>

                <div>
                    <label for="home-password" class="block text-sm font-semibold text-gray-300 mb-1">Contraseña</label>
                    <input id="home-password" type="password" name="password" required autocomplete="current-password"
                           class="w-full rounded-lg border border-black bg-black/50 px-3 py-2 text-white placeholder-gray-500
                                  shadow-[inset_0_2px_6px_rgba(0,0,0,0.8)] focus:outline-none focus:ring-2 focus:ring-yellow-500"
                           placeholder="••••••••">
                </div>

                @if ($errors->any())
                    <p class="text-sm text-red-400 font-semibold">{{ $errors->first() }}</p>
                @endif

                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input type="checkbox" name="remember" class="rounded border-gray-500 bg-black/50 text-yellow-500 focus:ring-yellow-500">
                    Recordarme
                </label>

                <button type="submit"
                        class="w-full py-2 rounded-lg border border-black font-bold uppercase text-white
                               bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]
                               hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">
                    Entrar
                </button>

                <div class="flex justify-between text-xs">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-gray-400 underline hover:text-white">¿Olvidaste tu contraseña?</a>
                    @endif
                    <a href="{{ route('register') }}" class="text-yellow-400 underline hover:text-yellow-300">Crear cuenta</a>
                </div>
            </form>
            @endguest

            {{-- Tu personaje (con la sesión iniciada) --}}
            @auth
            <div class="p-5 rounded-xl border border-yellow-600 bg-gradient-to-b from-[#1c2533]/95 to-[#0a0e14]/95 text-center
                        shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_8px_16px_rgba(0,0,0,0.7)]">
                <h3 class="text-2xl font-bold text-yellow-400 [text-shadow:0_2px_0_#000]">⚔️ Tu personaje</h3>

                @if ($personajeUsuario)
                    @php
                        $setMio = $personajeUsuario->postDeCombate();
                        $gifMio = $setMio?->gif ?? $personajeUsuario->gif;
                        $statsMios = $personajeUsuario->statsDeCombate();
                        $abrevMio = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'energia' => 'ENE', 'velocidad' => 'VEL', 'defensa' => 'DEF', 'resistencia' => 'RES'];
                    @endphp
                    <div class="mt-3 mx-auto h-40 w-52 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
                        @if ($gifMio)
                            <img src="{{ asset('storage/' . $gifMio) }}" alt="{{ $personajeUsuario->nombre }}"
                                 style="{{ \App\Models\Post::estiloGif($gifMio) }}" class="block max-w-none">
                        @endif
                    </div>

                    <p class="mt-2 text-xl font-bold [text-shadow:0_2px_0_#000]">{{ $personajeUsuario->nombre }}</p>
                    <p class="mt-1 flex justify-center gap-2 text-xs font-bold">
                        <span class="px-2 py-0.5 rounded-full border border-black text-yellow-300 bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]">Nivel {{ $personajeUsuario->nivel }}</span>
                        @if ($setMio)
                            <span class="px-2 py-0.5 rounded-full border border-black text-sky-300 bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]">{{ $setMio->titulo }}</span>
                        @endif
                    </p>

                    <div class="mt-3 grid grid-cols-3 gap-1.5 font-mono text-xs">
                        @foreach ($abrevMio as $stat => $abrev)
                            <div class="flex items-center justify-center gap-1">
                                <span class="w-8 h-5 flex items-center justify-center font-bold text-yellow-300 border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">{{ $abrev }}</span>
                                <span class="font-bold text-white">+{{ $statsMios[$stat] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 flex justify-center gap-4 text-sm font-bold">
                        <span class="flex items-center gap-1 text-yellow-300"><img src="{{ asset('images/oro.png') }}" alt="" class="h-4 w-4">{{ number_format($personajeUsuario->oro ?? 0, 0, ',', '.') }}</span>
                        <span class="flex items-center gap-1 text-cyan-300"><img src="{{ asset('images/diamante.png') }}" alt="" class="h-4 w-4">{{ number_format($personajeUsuario->diamante ?? 0, 0, ',', '.') }}</span>
                    </div>

                    <a href="{{ route('juego.mostrar', ['personajeId' => $personajeUsuario->id]) }}"
                       class="mt-4 block w-full py-2 rounded-lg border border-black font-bold uppercase text-white
                              bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]
                              hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">
                        Entrar
                    </a>
                @else
                    <p class="mt-3 text-gray-300">Todavía no creaste ningún personaje.</p>
                    <a href="{{ route('personajes.elegir') }}"
                       class="mt-4 block w-full py-2 rounded-lg border border-black font-bold uppercase text-black
                              bg-gradient-to-b from-yellow-300 to-yellow-600 shadow-[inset_1px_1px_0_rgba(255,255,255,0.5),0_3px_0_#000]
                              hover:brightness-110 active:translate-y-[3px] active:shadow-none transition-all">
                        Crear personaje
                    </a>
                @endif
            </div>
            @endauth

            <div class="p-5 rounded-xl border border-yellow-600 bg-gradient-to-b from-[#1c2533]/95 to-[#0a0e14]/95
                        shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_8px_16px_rgba(0,0,0,0.7)]">
                <h3 class="text-2xl font-bold mb-4 text-yellow-400 text-center [text-shadow:0_2px_0_#000]">🏆 Ranking</h3>
                <ol class="space-y-2">
                    @foreach($ranking as $index => $Personaje)
                    @php
                        $medalla = match ($index) { 0 => 'from-yellow-400 to-yellow-700 text-black', 1 => 'from-gray-200 to-gray-500 text-black', 2 => 'from-orange-400 to-orange-700 text-black', default => 'from-[#2f5470] to-[#0a1a26] text-white' };
                    @endphp
                    {{-- Efectos de ranking y de nombre comprados en Extras --}}
                    <li class="flex items-center justify-between gap-2 p-2 rounded-lg border border-black text-white
                               bg-gradient-to-b from-[#2a3240] to-[#10141b] hover:brightness-125 transition
                               shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]
                               {{ $Personaje->claseRanking() }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-8 h-8 shrink-0 flex items-center justify-center rounded-full border border-black font-extrabold text-sm bg-gradient-to-b {{ $medalla }} shadow-[inset_1px_1px_0_rgba(255,255,255,0.5),0_2px_0_#000]">{{ $index + 1 }}</span>
                            <img src="{{ asset('storage/' . ($Personaje->imagen_final ?? 'default.png')) }}"
                                alt="{{ $Personaje->nombre }}"
                                class="w-11 h-11 shrink-0 rounded-lg object-cover border-2 border-yellow-400">
                            <span class="truncate font-semibold text-base {{ $Personaje->claseNombre() }}">{{ $Personaje->nombre }}</span>
                        </div>
                        <span class="shrink-0 text-yellow-300 font-bold">Nv {{ $Personaje->nivel }}</span>
                    </li>
                    @endforeach
                </ol>
            </div>
        </aside>
    </div>

    <style>
        @keyframes fade-in {
            0% {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.6s ease-out forwards;
        }

        .delay-150 {
            animation-delay: 0.15s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        .delay-500 {
            animation-delay: 0.5s;
        }

        .delay-600 {
            animation-delay: 0.6s;
        }

        .delay-700 {
            animation-delay: 0.7s;
        }
    </style>
</div>