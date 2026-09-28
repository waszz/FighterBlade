<div class="p-3 text-white">
@php
  $panel3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]';
  $boton3d = 'px-4 py-1.5 rounded-lg border border-black text-white text-sm font-bold bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100';
@endphp

@if ($repeticion)
  {{-- Repetición de la pelea: la misma pantalla de rondas que la Ciudad --}}
  <div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between gap-2 mb-3">
      <button wire:click="volverALista" class="{{ $boton3d }}">← Mis peleas</button>
      <p class="text-xs text-gray-300 [text-shadow:0_1px_0_#000]">{{ $repeticion['pelea']->realizada_en?->diffForHumans() }}</p>
    </div>
    @php
      $datosRep = $repeticion['pelea']->datos_combate ?? [];
      $ganoRep = $repeticion['pelea']->resultado === 'victoria';
      // Verde si ganó, gris si empató, rojo si perdió
      $colorRep = match ($repeticion['pelea']->resultado) { 'victoria' => 'bg-gradient-to-b from-emerald-500 to-emerald-800', 'empate' => 'bg-gradient-to-b from-gray-400 to-gray-700', default => 'bg-gradient-to-b from-red-500 to-red-800' };
      $fondoRep = $repeticion['escenarioMision'] ?? $repeticion['ciudadActual']?->gif;
      $gifPjRep = $datosRep['gif_personaje'] ?? null;
      $gifEnRep = $datosRep['gif_enemigo'] ?? null;
      // Los poderes que tenía cada uno en esa pelea (guardados por nombre); se buscan para mostrar el icono y la descripción
      $poderesPorNombre = fn ($nombres) => collect($nombres ?? [])->map(fn ($n) => \App\Models\Poder::where('nombre', $n)->first() ?? ['nombre' => $n]);
      $poderesRepPj = $poderesPorNombre($datosRep['poderes_personaje'] ?? []);
      $poderesRepEn = $poderesPorNombre($datosRep['poderes_enemigo'] ?? []);
      $nombrePjRep = $datosRep['nombre_personaje'] ?? $repeticion['personaje']->nombre;
      $nombreEnRep = $datosRep['nombre_enemigo'] ?? ($repeticion['enemigo']->titulo ?? $repeticion['enemigo']->nombre ?? 'Enemigo');
    @endphp

    {{-- Encabezado: quién contra quién y resultado --}}
    <h2 class="text-center text-xl font-bold mb-2 [text-shadow:0_2px_0_#000]">
      <span class="text-yellow-300">{{ $datosRep['nombre_personaje'] ?? $repeticion['personaje']->nombre }}</span>
      <span class="text-gray-300 mx-1">vs</span>
      <span class="text-yellow-300">{{ $datosRep['nombre_enemigo'] ?? ($repeticion['enemigo']->titulo ?? $repeticion['enemigo']->nombre ?? 'Enemigo') }}</span>
      <span class="ml-2 inline-block align-middle px-2 py-0.5 rounded-full border border-black text-xs font-extrabold uppercase shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]
                   {{ $colorRep }}">{{ ucfirst($repeticion['pelea']->resultado) }}</span>
    </h2>

    {{-- Escenario de la pelea, como en la Ciudad: los dos enfrentados con el VS y los poderes a los costados --}}
    <div class="w-full flex items-center justify-center gap-3 mb-4">
    <aside class="hidden md:flex w-36 shrink-0 flex-col items-end gap-1.5 text-right text-white">
      <p class="text-sm font-semibold leading-tight [text-shadow:0_2px_0_#000]">Poderes de<br><span class="text-yellow-400">{{ $nombrePjRep }}</span></p>
      <div class="flex flex-wrap justify-end gap-1.5">
        @forelse ($poderesRepPj as $poder)
          <x-icono-poder :poder="$poder" tam="w-10 h-10" class="cursor-pointer" />
        @empty
          <span class="text-xs italic text-gray-400">Sin poderes</span>
        @endforelse
      </div>
    </aside>
    <div class="relative w-full max-w-[650px] min-h-[240px] rounded-xl overflow-hidden border-2 border-gray-900 shadow-lg select-none">
      @if ($fondoRep)
        <img src="{{ asset('storage/posts/' . $fondoRep) }}" alt="" class="absolute inset-0 w-full h-full object-cover object-bottom">
      @else
        <div class="absolute inset-0 bg-gray-800"></div>
      @endif
      @if ($gifPjRep)
        <div class="absolute bottom-1 left-[8%] z-10">
          <img src="{{ asset('storage/' . $gifPjRep) }}" alt="" style="{{ \App\Models\Post::estiloGif($gifPjRep) }}" class="block max-w-none">
        </div>
      @endif
      <div class="absolute top-[62%] left-1/2 -translate-x-1/2 -translate-y-1/2 z-10">
        <span class="text-4xl font-extrabold italic tracking-wider text-white [text-shadow:2px_2px_0_#000,-1px_-1px_0_#000] inline-block -rotate-6">VS</span>
      </div>
      @if ($gifEnRep)
        <div class="absolute bottom-1 right-[8%] z-10 scale-x-[-1]">
          <img src="{{ asset('storage/' . $gifEnRep) }}" alt="" style="{{ \App\Models\Post::estiloGif($gifEnRep) }}" class="block max-w-none">
        </div>
      @endif
    </div>
    <aside class="hidden md:flex w-36 shrink-0 flex-col items-start gap-1.5 text-left text-white">
      <p class="text-sm font-semibold leading-tight [text-shadow:0_2px_0_#000]">Poderes de<br><span class="text-yellow-400">{{ $nombreEnRep }}</span></p>
      <div class="flex flex-wrap justify-start gap-1.5">
        @forelse ($poderesRepEn as $poder)
          <x-icono-poder :poder="$poder" tam="w-10 h-10" class="cursor-pointer" />
        @empty
          <span class="text-xs italic text-gray-400">Sin poderes</span>
        @endforelse
      </div>
    </aside>
    </div>{{-- fin fila escenario + poderes --}}

    @if ($repeticion['conRondas'])
    {{-- Rondas, poderes y resultado: la misma pantalla que la Ciudad --}}
    <div class="flex flex-col items-center">
      @include('livewire.partials.resultado-pelea', $repeticion)
    </div>
    @else
    {{-- Peleas viejas: no se guardaron las rondas, se muestra el resumen --}}
    <div class="max-w-md mx-auto p-4 rounded-xl text-center space-y-2 {{ $panel3d }}">
      <p class="text-xs text-amber-300">Esta pelea es de antes de que se guardaran las rondas: solo queda el resumen.</p>
      <p><b class="text-white">{{ $datosRep['nombre_personaje'] ?? 'Personaje' }}</b> hizo <span class="text-red-400 font-bold">{{ number_format($datosRep['danio_personaje'] ?? 0) }}</span> de daño</p>
      <p><b class="text-white">{{ $datosRep['nombre_enemigo'] ?? 'Enemigo' }}</b> hizo <span class="text-red-400 font-bold">{{ number_format($datosRep['danio_enemigo'] ?? 0) }}</span> de daño</p>
      <div class="flex justify-center gap-4 pt-1 font-bold">
        <span class="text-green-400">EXP +{{ number_format($repeticion['pelea']->exp_ganada ?? 0) }}</span>
        <span class="flex items-center gap-1 text-yellow-400"><img src="{{ asset('images/oro.png') }}" alt="" class="w-4 h-4">+{{ number_format($repeticion['pelea']->oro_ganado ?? 0) }}</span>
        @if (! empty($datosRep['diamante']))
          <span class="flex items-center gap-1 text-cyan-300"><img src="{{ asset('images/diamante.png') }}" alt="" class="w-4 h-4">+{{ number_format($datosRep['diamante']) }}</span>
        @endif
      </div>
    </div>
    @endif
  </div>
@else
  <div class="max-w-xl mx-auto">
    <h2 class="text-xl font-bold text-yellow-400 mb-3 text-center [text-shadow:0_2px_0_#000]">Últimas 20 peleas</h2>

    <div class="space-y-2">
      @forelse($peleas as $pelea)
        @php
          $datosCombate = is_string($pelea->datos_combate)
              ? json_decode($pelea->datos_combate, true)
              : ($pelea->datos_combate ?? []);

          $gifPersonaje = $datosCombate['gif_personaje'] ?? null;
          $gifEnemigo = $datosCombate['gif_enemigo'] ?? null;

          $nombrePersonaje = $datosCombate['nombre_personaje'] ?? ($pelea->personaje->nombre ?? 'Personaje');
          $nombreEnemigo = $datosCombate['nombre_enemigo'] ?? ($pelea->enemigo->titulo ?? 'Enemigo');
          $gano = $pelea->resultado === 'victoria';
          $colorResultado = match ($pelea->resultado) { 'victoria' => 'bg-gradient-to-b from-emerald-500 to-emerald-800', 'empate' => 'bg-gradient-to-b from-gray-400 to-gray-700', default => 'bg-gradient-to-b from-red-500 to-red-800' };
        @endphp

        {{-- Tarjeta 3D de la pelea --}}
        <div wire:key="pelea-{{ $pelea->id }}" class="p-2 rounded-xl flex items-center gap-2 text-sm {{ $panel3d }}">
          {{-- Personaje --}}
          <div class="flex flex-col items-center w-20 shrink-0">
            <div class="w-14 h-14 rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_3px_8px_rgba(0,0,0,0.9)] flex items-end justify-center overflow-hidden">
              @if($gifPersonaje)
                <img src="{{ asset('storage/' . $gifPersonaje) }}" alt="Personaje" class="max-w-full max-h-full object-contain" loading="lazy" />
              @endif
            </div>
            <span class="mt-1 font-semibold text-yellow-300 text-xs text-center truncate w-full" title="{{ $nombrePersonaje }}">{{ $nombrePersonaje }}</span>
          </div>

          {{-- Resultado --}}
          <div class="flex-1 min-w-0 text-center">
            <span class="inline-block px-2 py-0.5 rounded-full border border-black text-xs font-extrabold uppercase shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]
                         {{ $colorResultado }}">
              {{ ucfirst($pelea->resultado) }}
            </span>
            <p class="mt-1 text-gray-300 text-xs flex justify-center items-center gap-2">
              <span>EXP <span class="text-green-400 font-semibold">+{{ number_format($pelea->exp_ganada) }}</span></span>
              <span class="flex items-center gap-1 text-yellow-400 font-semibold">
                <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-4 h-4" />+{{ number_format($pelea->oro_ganado) }}
              </span>
            </p>
            <p class="text-gray-400 text-[11px] mt-0.5">{{ $pelea->realizada_en->diffForHumans() }}</p>
            <div class="mt-1.5 flex justify-center gap-1.5">
              <button wire:click="verPelea({{ $pelea->id }})" class="{{ $boton3d }} !py-1 !text-xs">Ver</button>
              <button wire:click="compartirPelea({{ $pelea->id }})" wire:loading.attr="disabled" title="Compartir en el chat" class="{{ $boton3d }} !py-1 !text-xs">💬 Compartir</button>
            </div>
          </div>

          {{-- Enemigo --}}
          <div class="flex flex-col items-center w-20 shrink-0">
            <div class="w-14 h-14 rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_3px_8px_rgba(0,0,0,0.9)] flex items-end justify-center overflow-hidden">
              @if($gifEnemigo)
                <img src="{{ asset('storage/' . $gifEnemigo) }}" alt="Enemigo" class="max-w-full max-h-full object-contain scale-x-[-1]" loading="lazy" />
              @endif
            </div>
            <span class="mt-1 font-semibold text-yellow-300 text-xs text-center truncate w-full" title="{{ $nombreEnemigo }}">{{ $nombreEnemigo }}</span>
          </div>
        </div>
      @empty
        <p class="text-gray-400 text-center text-sm">Aún no tienes peleas registradas.</p>
      @endforelse
    </div>
  </div>
@endif

@if($mostrarModal && $peleaSeleccionada)
    <div class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 p-3 overflow-auto" wire:click="cerrarModal">
    <div class="bg-gray-900 border border-yellow-400 rounded-lg shadow-xl max-w-3xl w-full max-h-[85vh] p-4 relative overflow-auto text-sm" @click.stop>
            <button wire:click="cerrarModal"
                class="absolute top-2 right-2 text-white text-xl font-bold hover:text-yellow-400">&times;</button>

            <h3 class="text-lg font-bold mb-4 text-yellow-400 text-center">Detalle de la pelea</h3>

            @php
                $datosCombate = $peleaSeleccionada->datos_combate ?? [];

                // Gifs que se usaron en la pelea
                $gifMostrar = $datosCombate['gif_personaje'] ?? null;
                $gifEnemigo = $datosCombate['gif_enemigo'] ?? null;

                // Nombres para mostrar
                $nombrePersonaje = $datosCombate['nombre_personaje'] ?? $peleaSeleccionada->personaje->nombre ?? 'Personaje';
                $nombreEnemigo = $datosCombate['nombre_enemigo'] ?? $peleaSeleccionada->enemigo->titulo ?? 'Enemigo';

                // Poderes personaje
                $poderesPersonaje = collect($datosCombate['poderes_personaje'] ?? [])
                    ->map(fn($nombre) => (object)['nombre' => strtoupper($nombre)]);
                $poderPrincipalPersonaje = $poderesPersonaje->first()->nombre ?? null;

                // Poderes enemigo
                $poderesEnemigo = collect($datosCombate['poderes_enemigo'] ?? [])
                    ->map(fn($nombre) => (object)['nombre' => strtoupper($nombre)]);
                $poderPrincipalEnemigo = $poderesEnemigo->first()->nombre ?? null;
            @endphp

            {{-- Contenedor ciudad + GIFs --}}
            <div
                class="relative border border-gray-700 rounded-lg shadow-md overflow-hidden select-none w-full max-w-2xl min-h-[200px] mx-auto mb-5">

                {{-- Fondo de ciudad --}}
                @php
                    $gifCiudadPersonaje = $peleaSeleccionada->personaje->ciudadActual?->gif ?? null;
                @endphp
                @if($gifCiudadPersonaje)
                    <img src="{{ asset('storage/posts/' . $gifCiudadPersonaje) }}" alt="Ciudad personaje"
                        class="absolute inset-0 w-full h-full object-cover object-bottom z-0" />
                @else
                    <div class="absolute inset-0 bg-gray-700"></div>
                @endif

                {{-- Personaje --}}
                @if($gifMostrar)
                    <div class="absolute bottom-2 left-[25%] z-10">
                        <img src="{{ asset('storage/' . $gifMostrar) }}" alt="GIF personaje"
                            class="max-h-28 md:max-h-32 object-contain {{ $peleaSeleccionada->personaje->orientacion_gif === 'derecha' ? 'scale-x-[-1]' : '' }}" />
                    </div>
                @endif

                {{-- Enemigo --}}
                @if($gifEnemigo)
                    <div class="absolute bottom-2 right-[25%] z-10 scale-x-[-1]">
                        <img src="{{ asset('storage/' . $gifEnemigo) }}" alt="GIF enemigo"
                            class="max-h-28 md:max-h-32 object-contain" />
                    </div>
                @endif

            </div>

            <div class="mb-4 flex justify-center gap-6 text-white max-w-xl mx-auto text-xs md:text-sm">
                {{-- Poderes personaje --}}
                <div class="bg-blue-900 bg-opacity-80 rounded-lg p-3 shadow-md w-1/2">
                    <h4 class="font-semibold mb-1 text-blue-300 text-center leading-tight">
                        Poderes de<br>
                        <span class="block text-yellow-400">{{ $nombrePersonaje }}</span>
                    </h4>

                    @if($poderesPersonaje->isNotEmpty())
                        <div class="flex flex-wrap justify-center gap-1.5">
                            @foreach($poderesPersonaje as $poder)
                                <x-icono-poder :poder="$poder" tam="w-9 h-9" />
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-400 text-center italic">Sin poderes activos</p>
                    @endif
                </div>

                {{-- Poderes enemigo --}}
                <div class="bg-red-900 bg-opacity-80 rounded-lg p-3 shadow-md w-1/2">
                    <h4 class="font-semibold mb-1 text-red-300 text-center leading-tight">
                        Poderes de<br>
                        <span class="block text-yellow-400">{{ $nombreEnemigo }}</span>
                    </h4>

                    @if($poderesEnemigo->isNotEmpty())
                        <div class="flex flex-wrap justify-center gap-1.5">
                            @foreach($poderesEnemigo as $poder)
                                <x-icono-poder :poder="$poder" tam="w-9 h-9" />
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-400 text-center italic">Sin poderes activos</p>
                    @endif
                </div>
            </div>

            {{-- Resultados --}}
    <div class="mt-4 text-center text-white space-y-3 text-sm">
    <p>
        <strong>{{ $nombrePersonaje }}</strong> ha recibido
        <span class="text-red-400 font-semibold">{{ $datosCombate['danio_enemigo'] ?? 0 }}</span> puntos de daño
    </p>
    <p>
        <strong>{{ $nombreEnemigo }}</strong> ha recibido
        <span class="text-red-400 font-semibold">{{ $datosCombate['danio_personaje'] ?? 0 }}</span> puntos de daño
    </p>
    <p class="mt-4">
        <span class="font-bold">{{ $nombrePersonaje }}</span> ha recibido:
    </p>
    <p class="font-semibold text-green-500">
        {{ number_format($peleaSeleccionada->exp_ganada ?? 0) }}
        <span class="text-white">puntos de experiencia.</span>
    </p>

    {{-- Oro --}}
    <p class="flex justify-center items-center gap-2 text-yellow-400 font-bold">
        <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-5 h-5" />
        <span>{{ number_format($peleaSeleccionada->oro_ganado ?? 0) }}</span>
        <span class="text-white">monedas de oro.</span>
    </p>

    {{-- Diamantes (solo si ganó y hay diamantes) --}}
    @if(($peleaSeleccionada->resultado ?? '') === 'victoria' && !empty($datosCombate['diamante']) && $datosCombate['diamante'] > 0)
    <p class="flex justify-center items-center gap-1 text-cyan-400 font-bold mt-1">
        <img src="{{ asset('images/diamante.png') }}" alt="Esmeralda" class="w-6 h-6" />
        <span>{{ number_format($datosCombate['diamante']) }}</span>
        <span class="text-white">esmeraldas.</span>
    </p>
    @endif
</div>



            {{-- Drop --}}
            @if(($peleaSeleccionada->resultado ?? '') === 'victoria' && !empty($datosCombate['drop']))
                <div
    class="mt-4 mx-auto w-fit bg-gray-900 bg-opacity-60 rounded-lg shadow-lg px-3 pt-2 pb-8 flex flex-col items-center text-center text-yellow-100 text-xs leading-tight space-y-1 relative">

    <div class="font-semibold mb-1">Obtuviste</div>

    <div class="flex justify-center">
        <img src="{{ $datosCombate['drop']['tipo'] === 'pocion'
            ? asset('images/' . $datosCombate['drop']['imagen'])
            : Storage::url('posts/' . $datosCombate['drop']['imagen']) }}"
            alt="{{ $datosCombate['drop']['nombre'] ?? 'Objeto' }}"
            class="w-20 h-20 object-cover rounded-md border border-yellow-300 shadow-md" />
    </div>

    {{-- Mostrar nombre solo una vez --}}
    @if(isset($datosCombate['drop']['nombre']))
        <div class="text-white font-semibold text-sm">
            {{ $datosCombate['drop']['nombre'] }}
        </div>
    @endif

    {{-- Mostrar info extra si es poción --}}
    @if($datosCombate['drop']['tipo'] === 'pocion')
        <div class="text-xs text-blue-300 mt-1 space-y-1">
            {{-- Usos --}}
            <div class="text-purple-400">
                Usos: {{ $datosCombate['drop']['stats']['usos_restantes'] ?? 1 }}/{{ $datosCombate['drop']['stats']['usos_totales'] ?? 1 }}
            </div>

            {{-- Descripción --}}
            @if(!empty($datosCombate['drop']['descripcion']))
                <div class="italic text-blue-200">
                    {{ $datosCombate['drop']['descripcion'] }}
                </div>
            @endif
        </div>
    @else
        {{-- Mostrar stats para objetos no poción --}}
        @if(!empty($datosCombate['drop']['stats']) && is_array($datosCombate['drop']['stats']))
            <ul class="text-yellow-200 text-xs my-1">
                @foreach($datosCombate['drop']['stats'] as $stat => $value)
                    @if($value)
                        <li class="capitalize leading-tight">
                            {{ str_replace('_', ' ', $stat) }} +{{ $value }}
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    @endif

    {{-- Nivel + Requisitos --}}
    <div class="mt-1 flex justify-between items-center text-[9px] w-full px-2">
        @if(isset($datosCombate['drop']['nivel']))
            <span class="bg-black bg-opacity-60 px-1 py-0.5 rounded text-white font-semibold">
                Nivel {{ $datosCombate['drop']['nivel'] }}
            </span>
        @endif

        @if(!empty($datosCombate['drop']['requisitos']) && is_array($datosCombate['drop']['requisitos']))
            <span
                class="bg-black bg-opacity-60 px-1 py-0.5 rounded text-white font-semibold flex flex-wrap gap-1 justify-center">
                @foreach($datosCombate['drop']['requisitos'] as $stat => $valor)
                    @if($valor > 0)
                        <span>{{ ucfirst($stat) }} {{ $valor }}</span>
                    @endif
                @endforeach
            </span>
        @endif
    </div>

</div>

            @endif

        </div>
    </div>
@endif

</div>