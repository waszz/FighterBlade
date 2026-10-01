<div class="text-white">
 
  <h2
    class="text-4xl md:text-5xl font-extrabold text-center mb-6 uppercase tracking-widest text-gradient bg-clip-text text-transparent bg-gradient-to-r from-yellow-400 via-red-500 to-purple-600 drop-shadow-lg">
    Inventario
  </h2>
  <div wire:key="inventario-{{ $reloadInventario }}">
    @php
    $equipo = $personaje->equipo;
    $entrenamiento = $personaje->entrenamiento;
    $accesorio = $personaje->accesorio;

    $mostrarImagenCompleta = false;
    $imagen = null;
    $nombrePostEquipado = null;

    if ($equipo && $entrenamiento && $accesorio) {
    if (
    $equipo->origen_post_id &&
    $equipo->origen_post_id === $entrenamiento->origen_post_id &&
    $equipo->origen_post_id === $accesorio->origen_post_id
    ) {
    $mostrarImagenCompleta = true;
    $postEquipado = \App\Models\Post::find($equipo->origen_post_id);
    $imagen = $postEquipado?->imagen ?? null;
    $nombrePostEquipado = $postEquipado?->nombre ?? null;
    }
    }

    if (! $mostrarImagenCompleta && $personaje->post?->imagen) {
    $imagen = $personaje->post->imagen;
    }

    $tipoPersonaje = $personaje->post->tipo ?? 'Desconocido';
    // Mismo tipo que usa la pelea: el del set completo equipado o, si no, el del set base (una parte suelta no lo cambia)
    $tipoFinal = $personaje->postDeCombate()?->tipo ?? $tipoPersonaje;
    @endphp

    {{-- Info del personaje --}}
    <div
      class="flex flex-col items-center justify-center mb-4 p-4 w-full bg-gradient-to-br from-gray-800 to-gray-900 rounded-lg shadow-lg text-white">

      {{-- Nombre y clan --}}
      <p class="text-lg text-yellow-400  font-bold mb-1 truncate text-center max-w-full">
        {{ $personaje->nombre }}
        <span class="text-sm font-semibold text-gray-400 ml-2">
          {{ $clan->nombre ?? '(Sin clan)' }}
        </span>
      </p>

      {{-- Nivel y personaje equipado --}}
      <p class="text-sm font-semibold text-white mb-2">
  Nivel <span class="text-yellow-400">{{ $personaje->nivel }}</span>
  <span class="text-white px-0.5 font-semibold">
    {{ $nombrePostEquipado ?? $personaje->post->nombre ?? 'Ninguno' }}
  </span>
</p>


      {{-- Imagen --}}
      @if($imagen)
      <img src="{{ asset('storage/' . $imagen) }}" alt="Imagen de {{ $personaje->nombre }}"
        class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-md border-2 border-black shadow-md ring-1 ring-black mt-3">
      @else
      <div
        class="w-20 h-20 sm:w-24 sm:h-24 bg-gray-700 rounded-md flex items-center justify-center text-white text-xs shadow-inner mt-3">
        Sin imagen
      </div>
      @endif
      {{-- Formulario para editar la frase --}}
      <div class="mt-4 w-full text-center">
        {{-- Frase + foto del chat en una fila (la frase no ocupa todo el ancho) --}}
        <form wire:submit.prevent="actualizarFrase">
          <div class="flex flex-wrap items-center justify-center gap-2">
            <input type="text" wire:model.defer="nuevaFrase"
              class="w-60 max-w-full px-3 py-1 text-sm text-black rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500"
              placeholder="Escribí una nueva frase..." maxlength="100">
            <button type="submit"
              class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-1 rounded shadow">
              Guardar frase
            </button>
            {{-- Imagen que se ve en el chat: la de tu set (por defecto) o una subida --}}
            <button type="button" wire:click="abrirModalFotoChat"
              class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold pl-1 pr-3 py-0.5 rounded shadow">
              <img src="{{ asset('storage/' . $personaje->fotoChat()) }}" alt="" class="w-6 h-6 rounded-full object-cover border border-white/40">
              Subir imagen
            </button>
          </div>
        </form>
      </div>

      @if ($modalFotoChatAbierto)
      @php $usaImagenSubida = $personaje->foto_chat_post_id === 0 && $personaje->foto_chat_propia; @endphp
      <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="cerrarModalFotoChat">
        <div class="relative w-full max-w-xs p-5 rounded-xl border border-black text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]" wire:click.stop>
          <button wire:click="cerrarModalFotoChat"
                  class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                         bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                         hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

          <h3 class="text-lg font-bold text-center text-yellow-400 mb-1 [text-shadow:0_2px_0_#000]">Imagen del chat</h3>
          <p class="text-xs text-center text-gray-400 mb-4">La que ven los demás en tus mensajes.</p>
          @error('fotoChatSubida') <p class="mb-3 text-center text-sm text-red-400">{{ $message }}</p> @enderror

          <div class="grid grid-cols-2 gap-3">
            {{-- Por defecto: la foto de tu set --}}
            <button wire:click="elegirFotoChat(null)"
                    class="flex flex-col items-center gap-1.5 p-3 rounded-lg border transition hover:brightness-125
                           {{ ! $usaImagenSubida ? 'border-yellow-400 bg-yellow-500/10' : 'border-black bg-black/30' }}">
              <img src="{{ asset('storage/' . ($personaje->postDeCombate()?->imagen ?? $personaje->imagen)) }}" alt="" class="w-16 h-16 rounded-full object-cover">
              <span class="text-xs font-semibold leading-tight text-center">Por defecto<br><span class="font-normal text-gray-400">(tu set)</span></span>
            </button>

            {{-- Subir una imagen (se recorta cuadrada); si ya está usando una, se ve acá --}}
            <label class="flex flex-col items-center gap-1.5 p-3 rounded-lg border cursor-pointer transition hover:brightness-125
                          {{ $usaImagenSubida ? 'border-yellow-400 bg-yellow-500/10' : 'border-dashed border-indigo-400 bg-indigo-500/10' }}">
              <div class="w-16 h-16 rounded-full flex items-center justify-center overflow-hidden bg-black/40 border border-indigo-400">
                <span wire:loading wire:target="fotoChatSubida" class="text-[10px] text-indigo-200">Subiendo…</span>
                <span wire:loading.remove wire:target="fotoChatSubida" class="contents">
                  @if ($usaImagenSubida)
                    <img src="{{ asset('storage/' . $personaje->foto_chat_propia) }}" alt="" class="w-full h-full object-cover">
                  @else
                    <span class="text-2xl">📷</span>
                  @endif
                </span>
              </div>
              <span class="text-xs font-semibold leading-tight text-center">Subir imagen<br><span class="font-normal text-gray-400">{{ $usaImagenSubida ? '(cambiar)' : '(JPG, PNG, GIF)' }}</span></span>
              <input type="file" wire:model="fotoChatSubida" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden">
            </label>
          </div>
        </div>
      </div>
      @endif

    </div>


    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 my-2">

      {{-- Equipo --}}
      <div class="relative bg-[#0c0f14] border border-indigo-500/30 p-3 rounded-2xl shadow-lg flex flex-col items-center"
        style="background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 14px 14px;">

        <div class="text-indigo-400 font-bold text-xs tracking-wider uppercase mb-2">
          Equipo
        </div>

        @if(isset($personaje->equipo))
        @if($personaje->equipo->nombre)
        <h3 class="text-sm font-bold text-indigo-200 mb-1 text-center w-full max-w-xs truncate">
          {{ $personaje->equipo->nombre }}
        </h3>
        @endif
        <div class="flex flex-col items-center">
          @if($personaje->equipo->imagen)
          <img src="{{ asset('storage/posts/' . $personaje->equipo->imagen) }}" alt="Equipo"
            class="w-24 h-24 object-cover rounded-xl shadow-lg transition-transform duration-200 hover:scale-105 cursor-pointer"
            wire:click="abrirModalDesequipar('equipo')" title="Haz clic para desequipar" />
          @else
          <div
            class="w-24 h-24 bg-gray-700 rounded-xl flex items-center justify-center text-indigo-400 text-xs font-bold select-none"
            title="Sin imagen">Sin imagen</div>
          @endif

          <div class="mt-1 w-full max-w-xs border border-indigo-500 rounded-md p-1 bg-gray-900 text-white text-xs">
            @if (isset($personaje->equipo->stats) && is_array($personaje->equipo->stats) &&
            count($personaje->equipo->stats) > 0)
            @foreach ($personaje->equipo->stats as $stat => $valor)
            @if ($valor > 0)
            <p class="text-white text-center font-bold uppercase truncate">{{ ucfirst($stat) }} +{{ $valor }}</p>
            @endif
            @endforeach
            @else
            <p class="text-gray-400 italic">Sin stats</p>
            @endif

            <div class="flex justify-between items-center mt-1 text-[9px] text-gray-400">
              <span>Nivel {{ $personaje->equipo->nivel ?? 'N/A' }}</span>
              @if (!empty($personaje->equipo->requisitos_equipo))
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

              <span class="text-purple-400 italic truncate max-w-[70px]"
                title="@foreach ($personaje->equipo->requisitos_equipo as $stat => $valor) {{ $valor }} {{ $abreviaturas[$stat] ?? strtoupper(substr($stat, 0, 3)) }}@if (!$loop->last), @endif @endforeach">
                @foreach ($personaje->equipo->requisitos_equipo as $stat => $valor)
                {{ $valor }} {{ $abreviaturas[$stat] ?? strtoupper(substr($stat, 0, 3)) }}@if (!$loop->last), @endif
                @endforeach
              </span>

              @else
              <span class="text-purple-400 italic">Sin reqs</span>
              @endif
            </div>
          </div>
        </div>
        @else
        <div class="w-14 h-14 rounded-full border-2 border-dashed border-gray-600 flex items-center justify-center text-gray-500 text-2xl mb-2">+</div>
        <p class="text-gray-500 text-xs">Vacío</p>
        @endif
      </div>

      {{-- Entrenamiento --}}
      <div class="relative bg-[#0c0f14] border border-green-500/30 p-3 rounded-2xl shadow-lg flex flex-col items-center"
        style="background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 14px 14px;">

        <div class="text-green-400 font-bold text-xs tracking-wider uppercase mb-2">
          Entrenamiento
        </div>

        @if(isset($personaje->entrenamiento))
        @if($personaje->entrenamiento->nombre)
        <h3 class="text-sm font-bold text-green-200 mb-1 text-center w-full max-w-xs truncate">
          {{ $personaje->entrenamiento->nombre }}
        </h3>
        @endif
        <div class="flex flex-col items-center">
          @if($personaje->entrenamiento->imagen)
          <img src="{{ asset('storage/posts/' . $personaje->entrenamiento->imagen) }}" alt="Entrenamiento"
            class="w-24 h-24 object-cover rounded-xl shadow-lg transition-transform duration-200 hover:scale-105 cursor-pointer"
            wire:click="abrirModalDesequipar('entrenamiento')" title="Haz clic para desequipar" />
          @else
          <div
            class="w-24 h-24 bg-gray-700 rounded-xl flex items-center justify-center text-indigo-400 text-xs font-bold select-none"
            title="Sin imagen">Sin imagen</div>
          @endif

          <div class="mt-1 w-full max-w-xs border border-indigo-500 rounded-md p-1 bg-gray-900 text-white text-xs">
            @if (isset($personaje->entrenamiento->stats) && is_array($personaje->entrenamiento->stats) &&
            count($personaje->entrenamiento->stats) > 0)
            @foreach ($personaje->entrenamiento->stats as $stat => $valor)
            @if ($valor > 0)
            <p class="text-white text-center font-bold uppercase truncate">{{ ucfirst($stat) }} +{{ $valor }}</p>
            @endif
            @endforeach
            @else
            <p class="text-gray-400 italic">Sin stats</p>
            @endif

            <div class="flex justify-between items-center mt-1 text-[9px] text-gray-400">
              <span>Nivel {{ $personaje->entrenamiento->nivel ?? 'N/A' }}</span>
              @if (!empty($personaje->entrenamiento->requisitos_entrenamiento))
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

              <span class="text-purple-400 italic truncate max-w-[70px]"
                title="@foreach ($personaje->entrenamiento->requisitos_entrenamiento as $stat => $valor) {{ $valor }} {{ $abreviaturas[$stat] ?? strtoupper(substr($stat, 0, 3)) }}@if (!$loop->last), @endif @endforeach">
                @foreach ($personaje->entrenamiento->requisitos_entrenamiento as $stat => $valor)
                {{ $valor }} {{ $abreviaturas[$stat] ?? strtoupper(substr($stat, 0, 3)) }}@if (!$loop->last), @endif
                @endforeach
              </span>
              @else
              <span class="text-purple-400 italic">Sin reqs</span>
              @endif
            </div>
          </div>
        </div>
        @else
        <div class="w-14 h-14 rounded-full border-2 border-dashed border-gray-600 flex items-center justify-center text-gray-500 text-2xl mb-2">+</div>
        <p class="text-gray-500 text-xs">Vacío</p>
        @endif
      </div>

      {{-- Accesorio --}}
      <div class="relative bg-[#0c0f14] border border-pink-500/30 p-3 rounded-2xl shadow-lg flex flex-col items-center"
        style="background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 14px 14px;">

        <div class="text-pink-400 font-bold text-xs tracking-wider uppercase mb-2">
          Accesorio
        </div>

        @if(isset($personaje->accesorio))
        @if($personaje->accesorio->nombre)
        <h3 class="text-sm font-bold text-pink-200 mb-1 text-center w-full max-w-xs truncate">
          {{ $personaje->accesorio->nombre }}
        </h3>
        @endif
        <div class="flex flex-col items-center">
          @if($personaje->accesorio->imagen)
          <img src="{{ asset('storage/posts/' . $personaje->accesorio->imagen) }}" alt="Accesorio"
            class="w-24 h-24 object-cover rounded-xl shadow-lg transition-transform duration-200 hover:scale-105 cursor-pointer"
            wire:click="abrirModalDesequipar('accesorio')" title="Haz clic para desequipar" />
          @else
          <div
            class="w-24 h-24 bg-gray-700 rounded-xl flex items-center justify-center text-indigo-400 text-xs font-bold select-none"
            title="Sin imagen">Sin imagen</div>
          @endif

          <div class="mt-1 w-full max-w-xs border border-indigo-500 rounded-md p-1 bg-gray-900 text-white text-xs">
            @if (isset($personaje->accesorio->stats) && is_array($personaje->accesorio->stats) &&
            count($personaje->accesorio->stats) > 0)
            @foreach ($personaje->accesorio->stats as $stat => $valor)
            @if ($valor > 0)
            <p class="text-white text-center font-bold uppercase truncate">{{ ucfirst($stat) }} +{{ $valor }}</p>
            @endif
            @endforeach
            @else
            <p class="text-gray-400 italic">Sin stats</p>
            @endif

            <div class="flex justify-between items-center mt-1 text-[9px] text-gray-400">
              <span>Nivel {{ $personaje->accesorio->nivel ?? 'N/A' }}</span>
              @if (!empty($personaje->accesorio->requisitos_accesorio))
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

              <span class="text-purple-400 italic truncate max-w-[70px]"
                title="@foreach ($personaje->accesorio->requisitos_accesorio as $stat => $valor) {{ $valor }} {{ $abreviaturas[$stat] ?? strtoupper(substr($stat, 0, 3)) }}@if (!$loop->last), @endif @endforeach">
                @foreach ($personaje->accesorio->requisitos_accesorio as $stat => $valor)
                {{ $valor }} {{ $abreviaturas[$stat] ?? strtoupper(substr($stat, 0, 3)) }}@if (!$loop->last), @endif
                @endforeach
              </span>
              @else
              <span class="text-purple-400 italic">Sin reqs</span>
              @endif
            </div>
          </div>
        </div>
        @else
        <div class="w-14 h-14 rounded-full border-2 border-dashed border-gray-600 flex items-center justify-center text-gray-500 text-2xl mb-2">+</div>
        <p class="text-gray-500 text-xs">Vacío</p>
        @endif
      </div>
      {{-- Joya (anillo de la Torre): 2 stats --}}
      <div class="relative bg-[#0c0f14] border border-amber-500/30 p-3 rounded-2xl shadow-lg flex flex-col items-center"
        style="background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 14px 14px;">
        <div class="text-amber-400 font-bold text-xs tracking-wider uppercase mb-2">Joya</div>
        @if ($personaje->joya)
        <h3 class="text-sm font-bold text-amber-200 mb-1 text-center w-full max-w-xs truncate">{{ $personaje->joya->nombre }}</h3>
        <div class="flex flex-col items-center">
          <img src="{{ asset('storage/posts/' . $personaje->joya->imagen) }}" alt="Joya"
            class="w-24 h-24 object-contain drop-shadow-[0_4px_6px_rgba(0,0,0,0.8)] transition-transform duration-200 hover:scale-105 cursor-pointer"
            wire:click="abrirModalDesequipar('joya')" title="Haz clic para desequipar" />
          <div class="mt-1 w-full max-w-xs border border-amber-500 rounded-md p-1 bg-gray-900 text-white text-xs">
            @foreach ((array) $personaje->joya->stats as $stat => $valor)
              @if ($valor > 0)
              <p class="text-white text-center font-bold uppercase truncate">{{ ucfirst($stat) }} +{{ $valor }}</p>
              @endif
            @endforeach
            <div class="mt-1 text-center text-[9px] text-gray-400">Nivel {{ $personaje->joya->nivel }}</div>
          </div>
        </div>
        @else
        <div class="w-14 h-14 rounded-full border-2 border-dashed border-gray-600 flex items-center justify-center text-gray-500 text-2xl mb-2">+</div>
        <p class="text-gray-500 text-xs text-center">Vacío<br><span class="text-[10px] text-gray-600">Se gana en la Torre</span></p>
        @endif
      </div>
@php
    // Decodificar stats para tener los valores correctos
    $stats = $objetoEquipado ? (is_array($objetoEquipado->stats) ? $objetoEquipado->stats : json_decode($objetoEquipado->stats, true)) : [];
@endphp

{{-- Poción --}}

<div class="relative bg-[#0c0f14] border border-teal-500/30 p-3 rounded-2xl shadow-lg flex flex-col items-center"
    style="background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 14px 14px;">

    @if($objetoEquipado && $objetoEquipado->pocion && ($stats['usos_restantes'] ?? 1) > 0)
    {{-- Compartir en el chat (esquina de la tarjeta) --}}
    <button type="button" wire:click="compartirEnChat({{ $objetoEquipado->id }})" wire:loading.attr="disabled" wire:target="compartirEnChat"
      title="Compartir en el chat" aria-label="Compartir en el chat"
      class="absolute -top-3 -left-3 z-20 w-9 h-9 flex items-center justify-center rounded-full border-2 border-black text-white text-sm
             bg-gradient-to-b from-red-700 to-red-950 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_8px_rgba(0,0,0,0.7)]
             hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all disabled:opacity-50">
      <i class="fa-solid fa-share-nodes"></i>
    </button>
    @endif

    <div class="text-teal-400 font-bold text-xs tracking-wider uppercase mb-2">
        Poción
    </div>

    <div class="flex flex-col items-center space-y-2 w-full max-w-xs">
        @if($objetoEquipado && $objetoEquipado->pocion && ($stats['usos_restantes'] ?? 1) > 0)
            <img src="{{ asset('images/' . $objetoEquipado->imagen) }}" alt="{{ $objetoEquipado->nombre }}"
                 class="w-24 h-24 object-contain rounded-md mx-auto" />

            <p class="text-sm font-bold text-teal-200 text-center truncate w-full">{{ $objetoEquipado->nombre }}</p>
            <p class="text-gray-300 text-sm italic text-center w-full">
                {{-- Mismo texto que la poción en el inventario (su descripción); las viejas decían "diamantes" --}}
                @if (! empty($objetoEquipado->descripcion))
                    {{ str_ireplace(['diamantes', 'diamante'], ['esmeraldas', 'esmeralda'], $objetoEquipado->descripcion) }}
                @elseif (($stats['afecta'] ?? null) === 'drop_partes')
                    Afecta a: 100% de drop de partes
                @else
                    Afecta a: {{ ucfirst($stats['afecta'] ?? '?') }} x{{ $stats['multiplicador'] ?? '1' }}
                @endif
            </p>
            <p class="text-pink-400 text-sm text-center w-full">
                Usos: {{ $stats['usos_restantes'] ?? '?' }} / {{ $stats['usos_totales'] ?? '?' }}
            </p>

            <button wire:click="desequiparPocion"
                    class="mt-1 bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded shadow">
                Desequipar
            </button>
        @else
            <div class="w-14 h-14 rounded-full border-2 border-dashed border-gray-600 flex items-center justify-center text-gray-500 text-2xl mb-2">+</div>
            <p class="text-gray-500 text-xs">Vacío</p>
        @endif
    </div>
</div>
@if (session('mensaje'))
<div class="fixed top-4 right-4 z-[100] max-w-xs w-full"
    x-data="{ show: true }"
    x-init="setTimeout(() => show = false, 3000)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-x-8"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-8">

    <div
        class="relative px-4 py-3 rounded-lg shadow-2xl text-white w-full border-l-4
            {{ session('error') ? 'bg-red-600 border-red-300' : 'bg-green-600 border-green-300' }}">

        <div class="flex items-center gap-3 pr-5">
            <i class="fas {{ session('error') ? 'fa-times-circle' : 'fa-check-circle' }} text-xl"></i>
            <p class="text-sm font-semibold">{{ session('mensaje') }}</p>
        </div>

        <button @click="show = false"
            class="absolute top-1.5 right-2 text-white/70 hover:text-white transition text-sm">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif


<style>
@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    50% { transform: translateX(6px); }
    75% { transform: translateX(-4px); }
    100% { transform: translateX(0); }
}
.animate-shake {
    animation: shake 0.4s ease-in-out;
}
</style>

    </div>
    {{-- Nombre y oro al pie --}}
    <div
      class="mt-4 p-3 rounded-lg bg-gradient-to-br from-gray-800 to-gray-900 shadow-md text-white text-center max-w-lg mx-auto">

     <p wire:click="abrirModalGuardarOro"
   class="flex items-center justify-center gap-1 font-semibold text-yellow-400 mb-3 cursor-pointer hover:underline select-none"
   title="Click para guardar tu oro actual en el oro guardado">
   <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-5 h-5 inline-block" />
   Oro en el Banco:
   <strong>{{ number_format($oroGuardado, 0, ',', '.') }}</strong>
</p>

      @php
      $postCompleto = null;

      if (
      $equipo && $entrenamiento && $accesorio &&
      $equipo->origen_post_id &&
      $equipo->origen_post_id === $entrenamiento->origen_post_id &&
      $equipo->origen_post_id === $accesorio->origen_post_id
      ) {
      $postCompleto = \App\Models\Post::with('poderes')->find($equipo->origen_post_id);
      }

      $poderesPersonaje = $postCompleto?->poderes ?? $personaje->post?->poderes ?? collect();
      @endphp

      {{-- Tipo de daño y poderes: solo iconos en una fila (el nombre aparece al pasar el mouse o al tocar) --}}
      <div class="flex flex-wrap items-center justify-center gap-2">
        <x-icono-tipo :tipo="$tipoFinal" tam="w-14 h-14" class="cursor-pointer" />
        @foreach ($poderesPersonaje as $poder)
          <x-icono-poder :poder="$poder" tam="w-14 h-14" class="cursor-pointer" />
        @endforeach
      </div>

    </div>

    @php
    $objetosVisibles = $objetosGuardados ?? collect();

    // Las pociones también ocupan un lugar en el inventario (salvo la que está equipada)
    $pocionesEnInventario = collect($pociones ?? [])
        ->filter(fn($p) => ! $objetoEquipado || $objetoEquipado->id !== $p->id);

    $cantidadGuardados = $objetosVisibles->count() + $pocionesEnInventario->count();
    // Lugares: 100 + los comprados (hasta 200)
    $totalSlots = $personaje->capacidadInventario();
    $espaciosDisponibles = max($totalSlots - $cantidadGuardados, 0);
    $grupos = $objetosVisibles->groupBy('origen_post_id');
    $precioSlots = $personaje->precioProximosSlots();
    $sumaSlots = min(\App\Models\Personaje::SLOTS_POR_COMPRA, \App\Models\Personaje::SLOTS_MAX - $totalSlots);
    @endphp

    <div class="mt-6 max-w-7xl mx-auto p-3 sm:p-5 rounded-xl border-2 border-gray-500/70 bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]"
         x-data="{ comprarLugares: false }">

      <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
        <h3 class="text-lg font-extrabold uppercase tracking-wide text-yellow-400 [text-shadow:1px_1px_0_#000]">
          📦 Inventario ({{ $cantidadGuardados }}/{{ $totalSlots }})
        </h3>
        {{-- Comprar 3 lugares más --}}
        @if ($precioSlots !== null)
          <button type="button" x-on:click="comprarLugares = true"
            class="flex items-center gap-1.5 px-3 py-1 rounded-lg border border-black text-sm font-bold text-black bg-gradient-to-b from-yellow-300 to-yellow-600
                   shadow-[inset_1px_1px_0_rgba(255,255,255,0.5),inset_-1px_-1px_0_rgba(0,0,0,0.4),0_3px_0_#000] hover:brightness-110 active:translate-y-[3px] active:shadow-none transition-all">
            ➕ {{ $sumaSlots }} lugares ·
            <img src="{{ asset('images/oro.png') }}" alt="" class="h-4">{{ number_format($precioSlots, 0, ',', '.') }}
          </button>
        @else
          <span class="text-xs font-bold text-emerald-300">Máximo de lugares ({{ \App\Models\Personaje::SLOTS_MAX }})</span>
        @endif
      </div>

      <p class="text-xs sm:text-sm mb-3 {{ $espaciosDisponibles > 0 ? 'text-green-400' : 'text-red-400' }}">
        Espacios disponibles: <strong>{{ $espaciosDisponibles }}</strong>
        @if ($espaciosDisponibles === 0) · ¡Inventario lleno! Los objetos que caigan se pierden. @endif
      </p>

      {{-- Aviso antes de comprar lugares --}}
      @if ($precioSlots !== null)
        <div x-show="comprarLugares" x-cloak x-transition.opacity x-on:click.self="comprarLugares = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-3">
          <div class="w-full max-w-xs p-4 text-center rounded-xl border border-black text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                      shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">
            <h3 class="text-lg font-extrabold text-yellow-300 [text-shadow:0_2px_0_#000]">¿Comprar {{ $sumaSlots }} lugares?</h3>
            <p class="mt-1 text-sm text-gray-300">
              Pasás de {{ $totalSlots }} a <b class="text-white">{{ $totalSlots + $sumaSlots }}</b> lugares por
              <b class="text-yellow-400">{{ number_format($precioSlots, 0, ',', '.') }} de oro</b>.
            </p>
            <p class="mt-1 text-[11px] text-gray-400">Cada compra cuesta {{ number_format(\App\Models\Personaje::SLOTS_PRECIO_AUMENTO, 0, ',', '.') }} de oro más. Máximo {{ \App\Models\Personaje::SLOTS_MAX }} lugares.</p>
            <div class="mt-4 grid grid-cols-2 gap-2">
              <button type="button" wire:click="comprarSlots" x-on:click="comprarLugares = false" wire:loading.attr="disabled"
                      class="py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-emerald-500 to-emerald-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Comprar</button>
              <button type="button" x-on:click="comprarLugares = false"
                      class="py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">Cancelar</button>
            </div>
          </div>
        </div>
      @endif

      @if($mensajeOroObtenido)
      <div class="mt-3 text-yellow-400 text-base font-semibold text-center animate-pulse" x-data
        x-init="setTimeout(() => $wire.set('mensajeOroObtenido', null), 1000)">
        {{ $mensajeOroObtenido }}
      </div>
      @endif

      <button wire:click="abrirModalTirarObjetosSeleccionados"
        class="mt-3 mb-4 w-full max-w-xs px-3 py-1.5 rounded border border-black bg-gradient-to-b from-red-500 to-red-800 text-white text-sm font-bold shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0"
        @if(count($objetosSeleccionadosParaTirar)===0) disabled @endif>
         Tirar objetos seleccionados ({{ count($objetosSeleccionadosParaTirar) }})
      </button>

{{-- <script>
    window.addEventListener('mensaje', event => {
     
    });
</script> --}}



{{-- Las pociones se muestran más abajo, dentro de la grilla de slots --}}

@if ($modalEquiparPocionAbierto && $pocionSeleccionada)
  <div class="fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50 p-2" wire:click.self="$set('modalEquiparPocionAbierto', false)">
    <div class="flex flex-col items-center w-full max-w-[220px]">

      {{-- CARD 1: info de la poción --}}
      <div class="bg-gradient-to-b from-[#232c3a] to-[#0c0f14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000] rounded-xl border-2 border-black w-full p-3 relative">

        {{-- Compartir en el chat (esquina de la tarjeta) --}}
          <button type="button" wire:click="compartirEnChat({{ $pocionSeleccionada->id }})" wire:loading.attr="disabled" wire:target="compartirEnChat"
            title="Compartir en el chat" aria-label="Compartir en el chat"
            class="absolute -top-3 -left-3 z-20 w-9 h-9 flex items-center justify-center rounded-full border-2 border-black text-white text-sm
                   bg-gradient-to-b from-red-700 to-red-950 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_8px_rgba(0,0,0,0.7)]
                   hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all disabled:opacity-50">
            <i class="fa-solid fa-share-nodes"></i>
          </button>

        {{-- Cerrar modal --}}
        <button wire:click="$set('modalEquiparPocionAbierto', false)"
                class="absolute top-2 right-2 text-gray-400 hover:text-white transition"
                aria-label="Cerrar modal">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <div class="flex flex-col items-center text-center">
          <p class="text-sm font-bold text-white mb-2 px-4 truncate max-w-full">{{ $pocionSeleccionada->nombre }}</p>

          <img src="{{ asset('images/' . $pocionSeleccionada->imagen) }}" alt="{{ $pocionSeleccionada->nombre }}"
               class="w-20 h-20 object-contain rounded-lg shadow-lg mb-2" />

          <div class="w-full">

            <p class="text-[11px] text-pink-400">
              Usos: {{ $pocionSeleccionada->stats['usos_restantes'] ?? 'N/A' }} / {{ $pocionSeleccionada->stats['usos_totales'] ?? 'N/A' }}
            </p>

            <p class="text-gray-300 text-xs">{{ $pocionSeleccionada->descripcion }}</p>
          </div>
        </div>

        @if($pocionSeleccionada->precio_venta)
          <p class="text-red-500 font-semibold text-center text-xs mt-2">
            Esta poción está en venta y no puede ser equipada ni guardada en clan.
          </p>
        @endif

        @if($mensajeErrorEquiparPocion)
          <p class="text-red-500 font-semibold text-center text-xs mt-2">{{ $mensajeErrorEquiparPocion }}</p>
        @endif
      </div>

      {{-- CARD 2: menú de acciones (separado) --}}
      <div class="flex flex-col w-[85%] mt-2 rounded-lg overflow-hidden border border-black shadow-[0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.7)]">

        @if(!$pocionSeleccionada->precio_venta)
          <button type="button" wire:click="equiparPocion"
                  class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Equipar
          </button>

          <button type="button" wire:click="guardarEnClan({{ $pocionSeleccionada->id }})"
                  class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Guardar en Clan
          </button>
        @endif

        @if($pocionSeleccionada->precio_venta)
          <div class="p-1.5 bg-[#0c0f14] space-y-1 border-t border-black">
            <label for="nuevoPrecioPocion" class="text-yellow-400 text-center font-semibold text-[10px] block">Modificar Precio:</label>
            <input id="nuevoPrecioPocion" type="number" min="1" step="1" wire:model.defer="nuevoPrecioVenta"
                   placeholder="Nuevo precio"
                   class="text-black rounded px-2 py-0.5 w-full text-center text-xs" />
          </div>
          <button type="button" wire:click="actualizarPrecioVentaPocion({{ $pocionSeleccionada->id }})"
                  class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Actualizar
          </button>

          <button type="button" wire:click="cancelarVentaPocion({{ $pocionSeleccionada->id }})"
                  class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Quitar venta
          </button>
        @else
          <div class="p-1.5 bg-[#0c0f14] space-y-1 border-t border-black">
            <input type="number" min="1" step="1" wire:model="precioVentaPocion" placeholder="Precio de venta"
                   class="text-black rounded px-2 py-0.5 w-full text-center text-xs" />
            <p class="text-gray-300 text-[10px] text-center">Precio actual: {{ $precioVentaPocion }}</p>
          </div>
          <button type="button" wire:click="venderPocion"
                  class="w-full py-1.5 bg-gradient-to-b from-yellow-500 to-yellow-700 hover:brightness-110 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-300 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
                  Vender
          </button>
        @endif

        @if(!$pocionSeleccionada->precio_venta)
          <button type="button" wire:click="eliminarPocion"
                  class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
           Tirar poción
          </button>
        @endif
      </div>
    </div>
  </div>
@endif






      {{-- Pociones + espacios vacíos (slots del inventario).
           Celular: casilleros redondos de a 5 por fila, a todo el ancho; en pantallas grandes, cuadrados de 100 px --}}
      <div class="grid grid-cols-5 gap-2 sm:flex sm:flex-wrap sm:justify-center sm:gap-3">

        {{-- Partes ocupando slots --}}
        @php
        $abreviaturasSlot = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'velocidad' => 'VEL', 'resistencia' => 'RES', 'defensa' => 'DEF', 'energia' => 'ENE'];
        @endphp
        @foreach($objetosVisibles->where('tipo', '!=', 'pocion') as $objeto)
          @php
          $tipoPost = $objeto->post->tipo ?? 'desconocido';
          $colorClase = match(strtolower($tipoPost)) {
            'fisico' => 'text-red-500',
            'elemental' => 'text-blue-500',
            'hibrido' => 'text-purple-500',
            default => 'text-gray-400',
          };
          // Mismo color que la ranura de cada parte (Equipo índigo, Entrenamiento verde, Accesorio rosa)
          [$bordeParte, $textoParte] = match($objeto->tipo) {
            'equipo' => ['border-indigo-500', 'text-indigo-400'],
            'entrenamiento' => ['border-green-500', 'text-green-400'],
            'accesorio' => ['border-pink-500', 'text-pink-400'],
            default => ['border-black', $colorClase],
          };
          $statsSlot = is_array($objeto->stats) ? collect($objeto->stats)->filter(fn($v) => $v > 0) : collect();
          $tooltipStats = $statsSlot->map(fn($v, $s) => ($abreviaturasSlot[strtolower($s)] ?? strtoupper(substr($s, 0, 3))) . ' +' . $v)->implode(', ');
          @endphp
          <div class="relative rounded-full sm:rounded-md border-[3px] sm:border-2 {{ $bordeParte }} bg-gradient-to-b from-[#34405a] to-[#10151d] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] w-full aspect-square sm:w-[100px] sm:h-[100px] sm:aspect-auto flex flex-col items-center justify-center p-1 select-none cursor-pointer hover:brightness-125 hover:ring-2 hover:ring-indigo-400 transition"
               wire:key="slot-objeto-{{ $objeto->id }}"
               title="{{ $objeto->nombre }} — {{ ucfirst($objeto->tipo) }}{{ $tooltipStats ? ' — ' . $tooltipStats : '' }}">

            <input type="checkbox"
                   value="{{ $objeto->id }}"
                   wire:model="objetosSeleccionadosParaTirar"
                   wire:key="checkbox-{{ $objeto->id }}"
                   wire:change="$refresh"
                   @if($objeto->precio_venta) disabled @endif
                   class="absolute bottom-0 left-0 sm:bottom-auto sm:top-1 sm:left-1 w-3.5 h-3.5 z-10 text-indigo-600 bg-gray-700 border-gray-300 rounded focus:ring-indigo-500
                   @if($objeto->precio_venta) cursor-not-allowed @endif"
                   @if($objeto->precio_venta) title="No se puede tirar un objeto en venta" @endif
            />

            @if($objeto->precio_venta)
              <div class="absolute -top-1 left-1/2 -translate-x-1/2 sm:translate-x-0 sm:left-auto sm:top-1 sm:right-1 bg-yellow-500 text-black text-[7px] sm:text-[8px] font-bold px-1 rounded z-10 whitespace-nowrap">
                En venta
              </div>
            @endif

            <div class="flex flex-col items-center justify-center w-full h-full sm:h-auto @if($objeto->precio_venta) opacity-60 @endif"
                 wire:click="mostrarOpciones({{ $objeto->id }})">
              <img src="{{ asset('storage/posts/' . $objeto->imagen) }}" alt="{{ $objeto->nombre }}"
                  class="w-full h-full sm:w-12 sm:h-12 object-cover rounded-full sm:rounded hover:scale-110 transition duration-150" />

              <p class="hidden sm:block text-white text-[9px] font-semibold truncate w-full text-center px-1 mt-0.5">{{ $objeto->nombre }}</p>
              <p class="hidden sm:block {{ $textoParte }} text-[8px] leading-none uppercase">{{ $objeto->tipo }}</p>
            </div>
          </div>
        @endforeach

        {{-- Pociones ocupando slots --}}
        @foreach($pocionesEnInventario as $pocion)
          <div class="relative rounded-full sm:rounded-md border-[3px] sm:border-2 border-pink-500 sm:border-black bg-gradient-to-b from-[#34405a] to-[#10151d] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] w-full aspect-square sm:w-[100px] sm:h-[100px] sm:aspect-auto flex flex-col items-center justify-center p-1 select-none cursor-pointer hover:brightness-125 hover:ring-2 hover:ring-indigo-400 transition"
               wire:key="slot-pocion-{{ $pocion->id }}"
               title="{{ $pocion->nombre }} — {{ $pocion->descripcion }}">

            <input type="checkbox"
                   value="{{ $pocion->id }}"
                   wire:model="objetosSeleccionadosParaTirar"
                   wire:key="checkbox-{{ $pocion->id }}"
                   wire:change="$refresh"
                   @if($pocion->precio_venta) disabled @endif
                   class="absolute bottom-0 left-0 sm:bottom-auto sm:top-1 sm:left-1 w-3.5 h-3.5 z-10 text-indigo-600 bg-gray-700 border-gray-300 rounded focus:ring-indigo-500
                   @if($pocion->precio_venta) cursor-not-allowed @endif"
                   @if($pocion->precio_venta) title="No se puede tirar una poción en venta" @endif
            />

            @if($pocion->precio_venta)
              <div class="absolute -top-1 left-1/2 -translate-x-1/2 sm:translate-x-0 sm:left-auto sm:top-1 sm:right-1 bg-yellow-500 text-black text-[7px] sm:text-[8px] font-bold px-1 rounded z-10 whitespace-nowrap">
                En venta
              </div>
            @endif

            <div class="flex flex-col items-center justify-center w-full h-full sm:h-auto @if($pocion->precio_venta) opacity-60 @endif"
                 wire:click="abrirModalEquiparPocion({{ $pocion->id }})">
              <img src="{{ asset('images/' . $pocion->imagen) }}" alt="{{ $pocion->nombre }}"
                  class="w-4/5 h-4/5 sm:w-12 sm:h-12 object-contain hover:scale-110 transition duration-150" />

              <p class="hidden sm:block text-white text-[9px] font-semibold truncate w-full text-center px-1 mt-0.5">{{ $pocion->nombre }}</p>
              <p class="absolute -bottom-1 right-0 sm:static px-1 rounded sm:px-0 bg-black/80 sm:bg-transparent text-pink-400 text-[8px] leading-none">
                {{ $this->obtenerStat($pocion, 'usos_restantes') }}/{{ $this->obtenerStat($pocion, 'usos_totales') }}
              </p>
            </div>
          </div>
        @endforeach

        {{-- Espacios vacíos --}}
        @for($i = 0; $i < $espaciosDisponibles; $i++)
          <div class="rounded-full sm:rounded-md border-[3px] sm:border-2 border-black bg-[#0a0e14] shadow-[inset_0_3px_6px_rgba(0,0,0,0.9),inset_0_-1px_0_rgba(255,255,255,0.08)] flex items-center justify-center text-gray-700 sm:text-gray-600 text-2xl sm:text-3xl w-full aspect-square sm:w-[100px] sm:h-[100px] sm:aspect-auto select-none">
            +
          </div>
        @endfor
      </div>

    {{-- Admin: agregarse partes de cualquier set para probarlas --}}
    @if (auth()->user()?->isAdmin())
    <div class="mt-4 max-w-xs mx-auto">
      <button wire:click="guardarObjetoEjemplo"
        class="w-full px-4 py-2 rounded border border-black bg-gradient-to-b from-indigo-500 to-indigo-800 text-white text-sm font-bold shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100">
        Guardar nuevo objeto
      </button>
    </div>

    @if ($modalAgregarParteAbierto)
    @php $setElegido = $parteSetId ? $setsAdmin->firstWhere('id', (int) $parteSetId) : null; @endphp
    <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="cerrarModalAgregarParte">
      <div class="relative w-full max-w-sm p-5 rounded-xl border border-black text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                  shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]" wire:click.stop>
        <button wire:click="cerrarModalAgregarParte"
                class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                       bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                       hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

        <h3 class="text-lg font-bold text-center text-yellow-400 mb-1 [text-shadow:0_2px_0_#000]">Agregar partes (admin)</h3>
        <p class="text-xs text-center text-gray-400 mb-4">Para probar sets: se agregan a tu inventario como si te hubieran caído.</p>

        <label class="block text-sm font-semibold mb-1">Set</label>
        <select wire:model.live="parteSetId"
                class="w-full p-2 mb-3 rounded-lg border border-black bg-black/50 text-white shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)] focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
          <option value="">Elegí un set…</option>
          @php
            $esEspecialAdmin = fn ($s) => in_array((int) $s->es_enemigo, [\App\Models\Post::ENEMIGO_ESPECIAL, \App\Models\Post::RIVAL_MISION], true);
          @endphp
          @foreach ($setsAdmin->reject($esEspecialAdmin)->groupBy('nivel') as $nivelSet => $setsDelNivel)
            <optgroup label="Nivel {{ $nivelSet }}">
              @foreach ($setsDelNivel as $setOpcion)
                <option value="{{ $setOpcion->id }}">{{ $setOpcion->titulo }}</option>
              @endforeach
            </optgroup>
          @endforeach
          {{-- Personajes especiales: rivales de misión y enemigo de bienvenida --}}
          <optgroup label="★ Personajes especiales">
            @foreach ($setsAdmin->filter($esEspecialAdmin) as $setOpcion)
              <option value="{{ $setOpcion->id }}">{{ $setOpcion->titulo }} (Nv {{ $setOpcion->nivel }}{{ $setOpcion->es_enemigo == \App\Models\Post::ENEMIGO_ESPECIAL ? ', bienvenida' : ', misión' }})</option>
            @endforeach
          </optgroup>
        </select>

        @if ($setElegido)
        <div class="flex items-center gap-3 mb-3 p-2 rounded-lg border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_3px_0_#000]">
          <img src="{{ asset('storage/' . $setElegido->imagen) }}" alt="" class="w-12 h-12 rounded-md object-cover border border-black">
          <div class="text-sm">
            <p class="font-bold">{{ $setElegido->titulo }}</p>
            <p class="text-xs text-gray-300 flex items-center gap-1">Nivel {{ $setElegido->nivel }} · <x-icono-tipo :tipo="$setElegido->tipo" tam="w-4 h-4" :con-nombre="true" /></p>
          </div>
        </div>
        @endif

        <label class="block text-sm font-semibold mb-1">Parte</label>
        <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
          @foreach (['completo' => 'Set completo (3)', 'equipo' => 'Equipo', 'entrenamiento' => 'Entrenamiento', 'accesorio' => 'Accesorio'] as $valorParte => $nombreParte)
          <label class="flex items-center gap-2 p-2 rounded-lg border border-black cursor-pointer {{ $parteTipo === $valorParte ? 'bg-indigo-700' : 'bg-black/40 hover:bg-black/60' }}">
            <input type="radio" wire:model.live="parteTipo" value="{{ $valorParte }}" class="text-indigo-500">
            {{ $nombreParte }}
          </label>
          @endforeach
        </div>

        <label class="flex items-start gap-2 mb-4 text-sm cursor-pointer">
          <input type="checkbox" wire:model="parteSinRequisitos" class="mt-0.5 rounded text-indigo-500">
          <span>Para probar: <b>sin nivel ni requisitos</b> <span class="block text-xs text-gray-400">Así la podés equipar con cualquier personaje.</span></span>
        </label>

        <button wire:click="agregarParteAdmin" @disabled(! $parteSetId)
                class="w-full py-2 rounded-lg border border-black text-white font-bold bg-gradient-to-b from-emerald-500 to-emerald-800
                       shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all
                       disabled:opacity-40 disabled:cursor-not-allowed">
          Agregar al inventario
        </button>
      </div>
    </div>
    @endif
    @endif


    {{-- Modal objeto seleccionado --}}
    @if ($objetoSeleccionado)
    <div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-3" wire:click.self="cerrarModal">
      <div class="flex flex-col items-center w-full max-w-[220px] text-white">

        {{-- CARD 1: info del objeto --}}
        <div class="bg-gradient-to-b from-[#232c3a] to-[#0c0f14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000] border-2 border-black rounded-xl p-3 w-full relative">
          <button wire:click="cerrarModal"
            class="absolute top-1 right-2 text-gray-400 hover:text-white text-2xl leading-none transition">&times;</button>
          {{-- Compartir en el chat (esquina de la tarjeta) --}}
          <button type="button" wire:click="compartirEnChat({{ $objetoSeleccionado->id }})" wire:loading.attr="disabled" wire:target="compartirEnChat"
            title="Compartir en el chat" aria-label="Compartir en el chat"
            class="absolute -top-3 -left-3 z-20 w-9 h-9 flex items-center justify-center rounded-full border-2 border-black text-white text-sm
                   bg-gradient-to-b from-red-700 to-red-950 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_8px_rgba(0,0,0,0.7)]
                   hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all disabled:opacity-50">
            <i class="fa-solid fa-share-nodes"></i>
          </button>

          <div class="flex flex-col items-center text-center">
          <p class="text-sm font-bold text-white mb-0.5 px-4 truncate max-w-full">{{ $objetoSeleccionado->nombre }}</p>
          {{-- Qué es (Equipo, Entrenamiento, Accesorio, Joya...) con el color de su lugar, y la imagen con ese borde --}}
          @php
            [$bordeSel, $textoSel] = ['equipo' => ['border-indigo-500', 'text-indigo-300'], 'entrenamiento' => ['border-green-500', 'text-green-300'], 'accesorio' => ['border-pink-500', 'text-pink-300'], 'joya' => ['border-amber-400', 'text-amber-300'], 'cofre' => ['border-yellow-600', 'text-yellow-300']][$objetoSeleccionado->tipo] ?? ['border-gray-700', 'text-gray-300'];
          @endphp
          <p class="text-[11px] font-bold uppercase mb-2 {{ $textoSel }}">{{ ucfirst($objetoSeleccionado->tipo) }}</p>

          <img src="{{ asset('storage/posts/' . $objetoSeleccionado->imagen) }}" alt="{{ $objetoSeleccionado->nombre }}"
            class="w-20 h-20 {{ in_array($objetoSeleccionado->tipo, ['equipo', 'entrenamiento', 'accesorio'], true) ? 'object-cover' : 'object-contain' }} rounded-lg mb-2 bg-black/50 border-2 {{ $bordeSel }} shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_3px_0_#000]" />

          <div class="w-full">

          @php
          $tipoPost = match ($objetoSeleccionado->tipo) { 'joya' => 'Joya', 'cofre' => 'Cofre', default => $objetoSeleccionado->post->tipo ?? 'desconocido' };
          $colorClase = match(strtolower($tipoPost)) {
          'fisico' => 'text-red-500',
          'elemental' => 'text-blue-500',
          'hibrido' => 'text-purple-500',
          default => 'text-gray-400',
          };
          @endphp

          {{-- Partes: el icono del tipo de daño del set (el nombre aparece al tocarlo). Joyas y cofres: el texto --}}
          <p class="text-xs font-semibold {{ $colorClase }} mb-1 flex justify-center">
            @if (in_array(strtolower($tipoPost), ['fisico', 'elemental', 'hibrido'], true))
              <x-icono-tipo :tipo="$tipoPost" tam="w-8 h-8" />
            @else
              {{ ucfirst($tipoPost) }}
            @endif
          </p>

          @php
          $statsModal = [];
          if (is_string($objetoSeleccionado->stats)) {
          $statsModal = json_decode($objetoSeleccionado->stats, true) ?: [];
          } elseif (is_array($objetoSeleccionado->stats)) {
          $statsModal = $objetoSeleccionado->stats;
          }
          @endphp

          <div class="grid grid-cols-2 gap-x-2 gap-y-1.5">
            @if(count($statsModal) > 0)
            @foreach($statsModal as $stat => $valor)
            @if($valor > 0)
            <p class="text-green-400 text-xs font-semibold px-1 py-0.5 rounded border border-black bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">{{ strtoupper(substr($stat, 0, 3)) }} +{{ $valor }}</p>
            @endif
            @endforeach
            @elseif ($objetoSeleccionado->tipo === 'cofre')
            <p class="text-amber-200 text-xs col-span-2">{{ $objetoSeleccionado->descripcion }}</p>
            @else
            <p class="text-gray-500 text-xs italic col-span-2">Sin stats disponibles</p>
            @endif
          </div>

          {{-- Requisitos para equipar: nivel + stat de la parte (verde si cumple, rojo si no) --}}
          @php
          $reqModal = match ($objetoSeleccionado->tipo) {
            'equipo' => $objetoSeleccionado->requisitos_equipo,
            'entrenamiento' => $objetoSeleccionado->requisitos_entrenamiento,
            'accesorio' => $objetoSeleccionado->requisitos_accesorio,
            default => null,
          };
          $reqModal = is_string($reqModal) ? (json_decode($reqModal, true) ?: []) : ($reqModal ?? []);
          $statsPj = is_array($personaje->stats) ? $personaje->stats : (json_decode($personaje->stats ?? '[]', true) ?: []);
          @endphp
          @if (in_array($objetoSeleccionado->tipo, ['equipo', 'entrenamiento', 'accesorio', 'joya']))
          <div class="mt-2 pt-2 border-t border-white/10 text-xs">
            <p class="text-gray-400 font-semibold mb-1">Requiere:</p>
            <div class="flex flex-wrap gap-1.5">
              <span class="px-1.5 py-0.5 rounded border border-black font-bold {{ $personaje->nivel >= ($objetoSeleccionado->nivel ?? 1) ? 'text-green-400' : 'text-red-400' }} bg-black/40">
                Nivel {{ $objetoSeleccionado->nivel ?? 1 }}
              </span>
              @foreach ($reqModal as $stat => $valor)
              <span class="px-1.5 py-0.5 rounded border border-black font-bold {{ ($statsPj[$stat] ?? 0) >= $valor ? 'text-green-400' : 'text-red-400' }} bg-black/40"
                    title="Tenés {{ $statsPj[$stat] ?? 0 }}">
                {{ strtoupper(substr($stat, 0, 3)) }} {{ $valor }}
              </span>
              @endforeach
            </div>
          </div>
          @endif
          </div>
          </div>

          @if($objetoSeleccionado->precio_venta)
          <p class="text-red-500 font-semibold text-center text-xs mt-2">
            Este objeto está en venta y no puede ser equipado.
          </p>
          @endif

          @if ($mensajeErrorEquipar)
          <p class="text-red-500 font-semibold text-center text-xs mt-2">{{ $mensajeErrorEquipar }}</p>
          @endif
        </div>

        {{-- CARD 2: menú de acciones (separado) --}}
        <div class="flex flex-col w-[85%] mt-2 rounded-lg overflow-hidden border border-black shadow-[0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.7)]">

          @if(!$objetoSeleccionado->precio_venta && $objetoSeleccionado->tipo === 'cofre')
          <button wire:click="abrirCofre({{ $objetoSeleccionado->id }})" wire:loading.attr="disabled"
            class="w-full py-1.5 bg-gradient-to-b from-amber-500 to-amber-800 hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:translate-y-px font-bold text-sm text-white transition [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             @php $costoCofre = \App\Support\RecompensasTorre::costoCofre($objetoSeleccionado); @endphp
             🎁 Abrir cofre ({{ $costoCofre > 0 ? number_format($costoCofre, 0, ',', '.') . ' oro' : 'gratis' }})
          </button>
          @endif

          @if(!$objetoSeleccionado->precio_venta && $objetoSeleccionado->tipo !== 'cofre')
          <button wire:click="equiparObjeto({{ $objetoSeleccionado->id }})"
            class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Equipar
          </button>

          <button wire:click="guardarEnClan({{ $objetoSeleccionado->id }})"
            class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Guardar en Clan
          </button>
          @endif

          @if($objetoSeleccionado->precio_venta)
          <div class="p-1.5 bg-[#0c0f14] space-y-1 border-t border-black">
            <label for="nuevoPrecio" class="text-yellow-400 text-center font-semibold text-[10px] block">Modificar Precio de Venta:</label>
            <input id="nuevoPrecio" type="number" min="1" step="1" wire:model.defer="nuevoPrecioVenta"
              placeholder="Nuevo precio" class="text-black rounded px-2 py-0.5 w-full text-center text-xs" />
          </div>
          <button wire:click="actualizarPrecioVenta({{ $objetoSeleccionado->id }})"
            class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Actualizar Precio
          </button>

          <button wire:click="quitarVentaObjeto({{ $objetoSeleccionado->id }})"
            class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Quitar de la venta
          </button>
          @else
          <div class="p-1.5 bg-[#0c0f14] space-y-1 border-t border-black">
            <input type="number" min="1" step="1" wire:model="precioVenta" placeholder="Precio de venta"
              class="text-black rounded px-2 py-0.5 w-full text-center text-xs" />
            <p class="text-gray-300 text-[10px] text-center">Precio actual: {{ $precioVenta }}</p>
          </div>
          <button wire:click="venderObjeto({{ $objetoSeleccionado->id }})"
            class="w-full py-1.5 bg-gradient-to-b from-yellow-500 to-yellow-700 hover:brightness-110 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-300 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Vender
          </button>
          @endif

          @if(!$objetoSeleccionado->precio_venta)
          <button wire:click="tirarObjetoDesdeModal({{ $objetoSeleccionado->id }})"
            class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
             Tirar
          </button>
          @endif
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

    {{-- Modal guardar oro --}}
    @if($modalGuardarOroAbierto)
    <div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-3"
      wire:click.self="cerrarModalGuardarOro">
      <div class="bg-gray-900 p-4 rounded-xl shadow-lg max-w-xs w-full text-white">
        <h3 class="text-xl font-bold mb-3 text-yellow-300 text-center">💰 Gestión de Oro</h3>

        <div class="mb-3 text-sm">
          <p class="mb-1">Oro actual: <span class="font-bold text-green-400">{{ number_format($personaje->oro, 0, ',',
              '.') }}</span></p>
          <input type="number" min="1" max="{{ $personaje->oro }}" wire:model="oroAGuardar"
            placeholder="Cantidad a guardar"
            class="w-full p-2 rounded bg-gray-800 border border-gray-600 text-white text-sm" />
          <button wire:click="guardarOro"
            class="mt-2 bg-yellow-600 hover:bg-yellow-500 text-white px-4 py-1.5 rounded w-full font-bold text-sm">Guardar
            Oro</button>
        </div>

        <div class="mb-3 border-t border-gray-600 pt-3 text-sm">
          <p class="mb-1">Oro guardado: <span class="font-bold text-yellow-500">{{
              number_format($personaje->oro_guardado, 0, ',', '.') }}</span></p>
          <input type="number" min="1" max="{{ $personaje->oro_guardado }}" wire:model="oroARetirar"
            placeholder="Cantidad a retirar" class="w-full p-2 rounded bg-gray-800 border border-gray-600 text-white" />
          <button wire:click="retirarOro"
            class="mt-2 bg-green-600 hover:bg-green-500 text-white px-4 py-1.5 rounded w-full font-bold text-sm">Retirar
            Oro</button>
        </div>

        <div class="flex justify-end mt-3">
          <button wire:click="cerrarModalGuardarOro"
            class="px-4 py-1.5 bg-gray-700 rounded hover:bg-gray-600 text-sm">Cerrar</button>
        </div>
      </div>
    </div>
    @endif



    {{-- Modal tirar objeto --}}
    @if($modalTirarObjetosAbierto)
    <div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-3" wire:click.self="cerrarModalTirarObjetos">
      <div class="bg-gradient-to-b from-[#1c2533] to-[#0a0e14] border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] p-4 rounded-lg max-w-sm w-full text-white relative">
        <button wire:click="cerrarModalTirarObjetos" aria-label="Cerrar"
          class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-full border-2 border-black bg-gradient-to-b from-red-600 to-red-900 text-white text-sm font-bold shadow-[0_2px_0_#000] hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all duration-100">&times;</button>

        <h3 class="text-lg font-bold mb-3 text-yellow-400 text-center [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">
          🗑️ Confirmar tirar
          @if($objetoParaTirarId)
          este objeto
          @else
          {{ count($objetosSeleccionadosParaTirar) }} objeto{{ count($objetosSeleccionadosParaTirar) > 1 ? 's' : '' }}
          @endif
        </h3>

        @if($objetoParaTirarId && $objetoSeleccionado)
        @php
        $statsModal = [];
        if (is_array($objetoSeleccionado->stats)) {
        $statsModal = $objetoSeleccionado->stats;
        } elseif (is_string($objetoSeleccionado->stats)) {
        $decoded = json_decode($objetoSeleccionado->stats, true);
        $statsModal = is_array($decoded) ? $decoded : [];
        }
        @endphp

        <div class="mb-3 text-center">
          <div class="mx-auto w-32 h-32 mb-3 p-1.5 rounded-md border-2 border-black bg-gradient-to-b from-[#34405a] to-[#10151d] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
            <img src="{{ asset('storage/posts/' . $objetoSeleccionado->imagen) }}" alt="{{ $objetoSeleccionado->nombre }}"
              class="w-full h-full object-cover rounded" />
          </div>

          <h4 class="text-lg font-semibold text-indigo-300 mb-2 truncate">{{ $objetoSeleccionado->nombre }}</h4>

          @if(!empty($statsModal))
          @foreach($statsModal as $stat => $valor)
          @if($valor > 0)
          <p class="text-green-400 text-xs uppercase">{{ $stat }} +{{ $valor }}</p>
          @endif
          @endforeach
          @else
          <p class="text-gray-400 italic text-xs">Sin stats</p>
          @endif

          <p class="text-gray-500 text-xs mt-1">Nivel {{ $objetoSeleccionado->nivel }}</p>
        </div>
        @else
        <p class="mb-3 text-gray-300 text-sm text-center">
          ¿Estás seguro que querés tirar estos {{ count($objetosSeleccionadosParaTirar) }} objeto{{
          count($objetosSeleccionadosParaTirar) > 1 ? 's' : '' }}?
          Esta acción no se puede deshacer.
        </p>
        @endif

        <div class="flex justify-center gap-3 mt-4">
          <button wire:click="cerrarModalTirarObjetos"
            class="flex-1 py-1.5 rounded border border-black bg-gradient-to-b from-[#4b5563] to-[#1f2937] text-white text-sm font-bold shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100">Cancelar</button>
          <button wire:click="confirmarTirarObjeto" wire:loading.attr="disabled"
            class="flex-1 py-1.5 rounded border border-black bg-gradient-to-b from-red-500 to-red-800 text-white text-sm font-bold shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50">Tirar</button>
        </div>
      </div>
    </div>
    @endif


    {{-- Modal desequipar --}}
    @if($modalDesequiparAbierto && $objetoADesequipar)
    <div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-3" wire:click.self="cerrarModalDesequipar">
      <div class="flex flex-col items-center w-full max-w-[290px] text-white">

      {{-- CARD 1: info del objeto --}}
      <div class="bg-gradient-to-b from-[#232c3a] to-[#0c0f14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000] p-4 rounded-xl w-full relative border-2 border-black">
        <button wire:click="cerrarModalDesequipar"
          class="absolute top-2 right-2 text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
        {{-- Compartir en el chat (esquina de la tarjeta) --}}
        <button type="button" wire:click="compartirEnChat({{ $objetoADesequipar->id }})" wire:loading.attr="disabled" wire:target="compartirEnChat"
          title="Compartir en el chat" aria-label="Compartir en el chat"
          class="absolute -top-3 -left-3 z-20 w-9 h-9 flex items-center justify-center rounded-full border-2 border-black text-white text-sm
                 bg-gradient-to-b from-red-700 to-red-950 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_8px_rgba(0,0,0,0.7)]
                 hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all disabled:opacity-50">
          <i class="fa-solid fa-share-nodes"></i>
        </button>

        <p class="text-sm font-bold text-white text-center mb-3 px-4 truncate">{{ $objetoADesequipar->nombre }}</p>

        @php
          // Misma tarjeta que la de las partes guardadas: borde del color de la parte, tipo de daño, stats 3D y requisitos
          [$bordeDeseq, $textoDeseq] = ['equipo' => ['border-indigo-500', 'text-indigo-300'], 'entrenamiento' => ['border-green-500', 'text-green-300'], 'accesorio' => ['border-pink-500', 'text-pink-300'], 'joya' => ['border-amber-400', 'text-amber-300']][$objetoADesequipar->tipo] ?? ['border-gray-700', 'text-gray-300'];
          $abreviaturas = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'velocidad' => 'VEL', 'resistencia' => 'RES', 'defensa' => 'DEF', 'energia' => 'ENE'];
          $stats = is_array($objetoADesequipar->stats) ? $objetoADesequipar->stats : (json_decode($objetoADesequipar->stats, true) ?? []);
          $requisitos = match ($objetoADesequipar->tipo) {
            'equipo' => $objetoADesequipar->requisitos_equipo,
            'entrenamiento' => $objetoADesequipar->requisitos_entrenamiento,
            'accesorio' => $objetoADesequipar->requisitos_accesorio,
            default => [],
          };
          $requisitos = is_string($requisitos) ? (json_decode($requisitos, true) ?: []) : ($requisitos ?? []);
          $statsPjDeseq = is_array($personaje->stats) ? $personaje->stats : (json_decode($personaje->stats ?? '[]', true) ?: []);
          $tipoDanioDeseq = $objetoADesequipar->post->tipo ?? null;
        @endphp

        <p class="text-[11px] font-bold uppercase text-center -mt-2 mb-2 {{ $textoDeseq }}">{{ ucfirst($objetoADesequipar->tipo) }}</p>

        @if($objetoADesequipar->imagen)
        <img src="{{ asset('storage/posts/' . $objetoADesequipar->imagen) }}" alt="{{ $objetoADesequipar->nombre }}"
          class="w-20 h-20 mx-auto {{ $objetoADesequipar->tipo === 'joya' ? 'object-contain' : 'object-cover' }} rounded-lg mb-2 bg-black/50 border-2 {{ $bordeDeseq }} shadow-[inset_0_0_0_1px_rgba(0,0,0,0.6),0_3px_0_#000]" />
        @endif

        @if (in_array(strtolower((string) $tipoDanioDeseq), ['fisico', 'elemental', 'hibrido'], true))
        <div class="mb-2 flex justify-center"><x-icono-tipo :tipo="$tipoDanioDeseq" tam="w-8 h-8" /></div>
        @endif

        <div class="grid grid-cols-2 gap-x-2 gap-y-1.5">
          @forelse (array_filter($stats, fn ($v) => is_numeric($v) && $v > 0) as $stat => $valor)
          <p class="text-green-400 text-xs font-semibold text-center px-1 py-0.5 rounded border border-black bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">{{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }} +{{ $valor }}</p>
          @empty
          <p class="text-gray-500 italic text-xs col-span-2 text-center">Sin stats</p>
          @endforelse
        </div>

        {{-- Requisitos: nivel + stat de la parte (verde si cumple, rojo si no) --}}
        <div class="mt-2 pt-2 border-t border-white/10 text-xs">
          <p class="text-gray-400 font-semibold mb-1">Requiere:</p>
          <div class="flex flex-wrap gap-1.5">
            <span class="px-1.5 py-0.5 rounded border border-black font-bold {{ $personaje->nivel >= ($objetoADesequipar->nivel ?? 1) ? 'text-green-400' : 'text-red-400' }} bg-black/40">Nivel {{ $objetoADesequipar->nivel ?? 1 }}</span>
            @foreach ($requisitos as $stat => $valor)
            <span class="px-1.5 py-0.5 rounded border border-black font-bold {{ ($statsPjDeseq[$stat] ?? 0) >= $valor ? 'text-green-400' : 'text-red-400' }} bg-black/40"
                  title="Tenés {{ $statsPjDeseq[$stat] ?? 0 }}">{{ $abreviaturas[strtolower($stat)] ?? strtoupper(substr($stat, 0, 3)) }} {{ $valor }}</span>
            @endforeach
          </div>
        </div>
      </div>

      {{-- CARD 2: menú de acciones (separado) --}}
      <div class="flex flex-col w-[85%] mt-2 rounded-lg overflow-hidden border-2 border-black shadow-[0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.7)]">
        <button wire:click="confirmarDesequipar"
          class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">Desequipar</button>
        <button wire:click="cerrarModalDesequipar"
          class="w-full py-1.5 bg-gradient-to-b from-[#2a3240] to-[#0c0f14] hover:brightness-125 shadow-[inset_0_1px_0_rgba(255,255,255,0.3),inset_0_-2px_0_rgba(0,0,0,0.6)] active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.8)] active:translate-y-px hover:ring-2 hover:ring-inset hover:ring-yellow-400 font-bold text-sm text-white transition border-t border-black [text-shadow:1px_1px_2px_rgba(0,0,0,0.9)]">Cancelar</button>
      </div>
      </div>
    </div>
    @endif


  </div>
</div>