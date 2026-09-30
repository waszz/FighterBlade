@php
    $colorTipo = match (strtolower($post->tipo ?? '')) {
        'fisico'    => 'bg-red-100 text-red-700',
        'hibrido'   => 'bg-purple-100 text-purple-700',
        'elemental' => 'bg-blue-100 text-blue-700',
        default     => 'bg-gray-100 text-gray-700',
    };

    $animaciones = [
        'gif'          => 'Personaje',
        'gif_ataque'   => 'Ataque',
        'gif_critico'  => 'Crítico',
        'gif_especial' => 'Especial',
        'gif_defensa'  => 'Defensa',
        'gif_victoria' => 'Victoria',
        'gif_derrota'  => 'Derrota',
    ];

    // Lo que da el set completo equipado (la suma de sus 3 partes), igual que en el juego
    $stats = $post->statsSetCompleto();
    $ordenStats = ['fuerza', 'ataque', 'energia', 'velocidad', 'defensa', 'resistencia'];
    $maxStat = max(1, max($stats ?: [1]));

    $partes = [
        'equipo'        => 'Equipo',
        'entrenamiento' => 'Entrenamiento',
        'accesorio'     => 'Accesorio',
    ];
@endphp

<div class="p-4 md:p-6">
    {{-- Navegación --}}
    <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
        <a href="{{ route('posts.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver a personajes</a>

        <div class="flex gap-2">
            @if ($postAnterior)
                <a href="{{ route('posts.show', $postAnterior->id) }}"
                   class="bg-slate-100 py-2 px-3 rounded-lg text-gray-700 text-xs font-bold hover:bg-slate-200">
                    ← {{ $postAnterior->titulo }} <span class="text-gray-400">(Nv {{ $postAnterior->nivel }})</span>
                </a>
            @endif
            @if ($postSiguiente)
                <a href="{{ route('posts.show', $postSiguiente->id) }}"
                   class="bg-slate-100 py-2 px-3 rounded-lg text-gray-700 text-xs font-bold hover:bg-slate-200">
                    {{ $postSiguiente->titulo }} <span class="text-gray-400">(Nv {{ $postSiguiente->nivel }})</span> →
                </a>
            @endif
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Columna principal --}}
        <div class="lg:w-2/3 w-full space-y-6">

            {{-- Cabecera --}}
            <div class="flex flex-col sm:flex-row gap-5 items-center sm:items-start">
                @if ($post->imagen)
                    <img src="{{ asset('storage/' . $post->imagen) }}" alt="{{ $post->titulo }}"
                         class="w-32 h-32 object-cover rounded-2xl shadow-md shrink-0">
                @endif

                <div class="flex-1 text-center sm:text-left">
                    <h1 class="text-3xl font-bold text-gray-800">{{ $post->titulo }}</h1>

                    <div class="mt-2 flex flex-wrap gap-2 justify-center sm:justify-start text-xs font-semibold">
                        <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Nivel {{ $post->nivel }}</span>
                        <span class="px-2 py-1 rounded-full {{ $colorTipo }}">{{ ucfirst($post->tipo) }}</span>
                        @if ($post->es_enemigo)
                            <span class="px-2 py-1 rounded-full bg-gray-800 text-white">Enemigo</span>
                        @endif
                        <span class="px-2 py-1 rounded-full {{ $post->publicado ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $post->publicado ? 'Publicado' : 'Sin publicar' }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 justify-center sm:justify-start">
                        <a href="{{ route('posts.edit', $post->id) }}"
                           class="bg-blue-800 py-2 px-4 rounded-lg text-white text-xs font-bold uppercase hover:bg-blue-900">
                            Editar
                        </a>
                    </div>
                </div>
            </div>

            {{-- Animaciones --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Animaciones</h2>
                <p class="text-xs text-gray-500 mb-3">
                    Se ven como de tu lado en la pelea: todas tienen que mirar a la <b>derecha →</b>.
                    Si alguna mira al revés, tocá <b>Girar</b> debajo de esa animación.
                </p>

                @if (auth()->user()?->isAdmin())
                    @php $escala = (float) ($post->gif_escala ?: 1); @endphp
                    <div class="flex flex-wrap items-center gap-2 mb-3 text-sm">
                        <span class="text-gray-700 font-semibold">Tamaño en las peleas:</span>
                        <button wire:click="ajustarEscala(-1)" @disabled($escala <= 0.5)
                            class="w-8 h-8 rounded-md bg-slate-700 text-white font-bold hover:bg-slate-600 disabled:opacity-40">−</button>
                        <span class="min-w-[4.5rem] text-center font-bold {{ $escala == 1 ? 'text-gray-600' : 'text-amber-600' }}">
                            {{ $escala == 1 ? 'Normal' : ($escala > 1 ? '+' : '') . round(($escala - 1) * 100) . '%' }}
                        </span>
                        <button wire:click="ajustarEscala(1)" @disabled($escala >= 2)
                            class="w-8 h-8 rounded-md bg-slate-700 text-white font-bold hover:bg-slate-600 disabled:opacity-40">+</button>
                        <span class="text-xs text-gray-500">(se suma al ajuste automático que iguala a todos los personajes)</span>
                    </div>
                @endif
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach ($animaciones as $campo => $label)
                        @php $girado = $post->gifGirado($campo); @endphp
                        <div class="bg-gray-900 rounded-xl p-2 flex flex-col items-center {{ $girado ? 'ring-2 ring-amber-400' : '' }}">
                            @if ($post->$campo)
                                <img src="{{ asset('storage/' . $post->$campo) }}" alt="{{ $label }}"
                                     style="{{ $girado ? 'rotate: y 180deg' : '' }}"
                                     class="h-28 w-full object-contain" loading="lazy">
                            @else
                                <div class="h-28 w-full flex items-center justify-center text-gray-500 text-xs">Sin gif</div>
                            @endif
                            <span class="mt-1 text-xs font-semibold text-gray-200">{{ $label }}</span>
                            @if ($post->$campo && auth()->user()?->isAdmin())
                                <button wire:click="girarGif('{{ $campo }}')" wire:loading.attr="disabled"
                                    class="mt-1 w-full py-1 rounded-md text-[11px] font-bold uppercase transition
                                           {{ $girado ? 'bg-amber-500 text-black hover:bg-amber-400' : 'bg-slate-700 text-white hover:bg-slate-600' }}">
                                    ↔ {{ $girado ? 'Girado' : 'Girar' }}
                                </button>

                                {{-- Ajuste solo de esta animación: tamaño y altura en la pelea --}}
                                @php $aj = $post->ajusteGif($campo); @endphp
                                <div class="mt-1 w-full grid grid-cols-[auto_1fr_auto] items-center gap-1 text-[11px] text-gray-300">
                                    <button wire:click="ajustarAnimacion('{{ $campo }}', 'escala', -1)" class="w-6 h-6 rounded bg-slate-700 hover:bg-slate-600 font-bold">−</button>
                                    <span class="text-center {{ $aj['escala'] != 1 ? 'text-amber-300 font-bold' : '' }}">
                                        Tamaño {{ $aj['escala'] == 1 ? '' : ($aj['escala'] > 1 ? '+' : '') . round(($aj['escala'] - 1) * 100) . '%' }}
                                    </span>
                                    <button wire:click="ajustarAnimacion('{{ $campo }}', 'escala', 1)" class="w-6 h-6 rounded bg-slate-700 hover:bg-slate-600 font-bold">+</button>

                                    <button wire:click="ajustarAnimacion('{{ $campo }}', 'subir', -1)" class="w-6 h-6 rounded bg-slate-700 hover:bg-slate-600 font-bold" title="Bajar">↓</button>
                                    <span class="text-center {{ $aj['subir'] != 0 ? 'text-amber-300 font-bold' : '' }}">
                                        Altura {{ $aj['subir'] == 0 ? '' : ($aj['subir'] > 0 ? '+' : '') . $aj['subir'] . 'px' }}
                                    </span>
                                    <button wire:click="ajustarAnimacion('{{ $campo }}', 'subir', 1)" class="w-6 h-6 rounded bg-slate-700 hover:bg-slate-600 font-bold" title="Subir">↑</button>
                                </div>
                                @if ($aj['escala'] != 1 || $aj['subir'] != 0)
                                    <button wire:click="ajustarAnimacion('{{ $campo }}', 'reiniciar', 0)" class="mt-0.5 text-[10px] text-gray-400 underline hover:text-white">reiniciar</button>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Stats --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">
                    Stats del set completo <span class="text-sm font-normal text-gray-500">(total {{ array_sum($stats) }})</span>
                </h2>
                <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2">
                    @foreach ($ordenStats as $stat)
                        @php $valor = $stats[$stat] ?? 0; @endphp
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="capitalize text-gray-700">{{ $stat }}</span>
                                <span class="font-semibold text-gray-800">{{ $valor }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-full rounded-full" style="width: {{ round($valor / $maxStat * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Poderes --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Poderes</h2>
                @forelse ($post->poderes as $poder)
                    <div class="mb-2 p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-3">
                        <x-icono-poder :poder="$poder" tam="w-11 h-11" />
                        <div>
                        <p class="font-semibold text-blue-800 text-sm">{{ $poder->nombre }}</p>
                        <p class="text-gray-600 text-sm">{{ $poder->descripcion }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Sin poderes.</p>
                @endforelse
            </section>

            {{-- Partes del set --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Partes del set</h2>
                <div class="grid sm:grid-cols-3 gap-3">
                    @foreach ($partes as $parte => $label)
                        @php
                            $nombre = $post->{$parte . '_nombre'};
                            $imagen = $post->{$parte . '_imagen'};
                            $ajustes = array_filter($post->{'ajustes_manuales_' . $parte} ?? [], fn ($v) => $v > 0);
                            $requisitos = array_filter($post->{'requisitos_' . $parte} ?? []);
                        @endphp
                        <div class="p-3 rounded-lg border border-gray-200 text-center">
                            <p class="text-xs uppercase text-gray-500 font-semibold">{{ $label }}</p>
                            @if ($imagen)
                                <img src="{{ asset('storage/posts/' . $imagen) }}" alt="{{ $label }}"
                                     class="w-14 h-14 mx-auto my-2 rounded-full object-cover border border-gray-300">
                            @endif
                            <p class="text-sm font-semibold text-gray-800">{{ $nombre ?: '—' }}</p>
                            <div class="mt-1 text-xs text-green-700">
                                @forelse ($ajustes as $stat => $valor)
                                    <span class="inline-block">+{{ $valor }} {{ ucfirst($stat) }}</span>@if (! $loop->last), @endif
                                @empty
                                    <span class="text-gray-400">Sin bonus</span>
                                @endforelse
                            </div>
                            @if ($requisitos)
                                <div class="mt-1 text-[11px] text-gray-500">
                                    Requiere:
                                    @foreach ($requisitos as $stat => $valor)
                                        {{ ucfirst($stat) }} {{ $valor }}@if (! $loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- Lateral: mismo nivel --}}
        <aside class="lg:w-1/3 w-full">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <h3 class="font-semibold text-gray-800 mb-3">Otros de nivel {{ $post->nivel }}</h3>
                @forelse ($mismoNivel as $otro)
                    <a href="{{ route('posts.show', $otro->id) }}"
                       class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 transition">
                        @if ($otro->gif)
                            <img src="{{ asset('storage/' . $otro->gif) }}" alt="{{ $otro->titulo }}"
                                 class="w-12 h-12 object-contain bg-gray-900 rounded-md" loading="lazy">
                        @endif
                        <span class="text-sm font-medium text-gray-800">{{ $otro->titulo }}</span>
                        <span class="ml-auto text-xs text-gray-500">{{ ucfirst($otro->tipo) }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-500">No hay otros personajes de este nivel.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
