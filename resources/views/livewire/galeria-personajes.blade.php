<div>
  @php
    // Mismos estilos 3D que los paneles y modales del juego
    $panel3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]';
    $etiqueta3d = 'border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';
  @endphp

  <div class="mb-6 text-center">
    <h2 class="text-3xl font-extrabold text-yellow-400 uppercase tracking-wide [text-shadow:0_3px_0_#000]">
      Mis personajes
    </h2>
    <p class="text-gray-300 mt-1 [text-shadow:0_1px_0_#000]">Tu colección de personajes equipados</p>
  </div>

  <div class="grid gap-2 p-2 [grid-template-columns:repeat(auto-fill,minmax(90px,1fr))]">
    @foreach($personajesDisponibles as $post)
    @php
    // Equipado = su set base o las 3 partes de ese set a la vez (con una parte sola no cuenta)
    $esEquipado = (
    $personaje->post_id === $post->id ||
    (optional($personaje->equipo)->origen_post_id === $post->id &&
     optional($personaje->entrenamiento)->origen_post_id === $post->id &&
     optional($personaje->accesorio)->origen_post_id === $post->id)
    );

    $esDesbloqueado = in_array($post->id, $postsDesbloqueados);

    // Luz para personaje base o desbloqueado
    $tieneLuz = $esEquipado || $personaje->post_id === $post->id || $esDesbloqueado;
    $nombreCarta = $post->titulo ?? $post->nombre;
    @endphp

    {{-- Carta 3D: las disponibles con borde dorado y brillo; las bloqueadas en gris --}}
    <div wire:key="galeria-{{ $post->id }}"
      @if($tieneLuz) wire:click="abrirModalPost({{ $post->id }})" @endif
      class="relative flex flex-col items-center p-1 rounded-md select-none transition duration-200 {{ $panel3d }}
             {{ $tieneLuz ? 'cursor-pointer !border-yellow-400 glow-personaje hover:brightness-125 hover:-translate-y-1' : 'cursor-default grayscale opacity-60 hover:grayscale-0 hover:opacity-100' }}">
      <div class="w-full aspect-square rounded-md overflow-hidden border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
        <img src="{{ asset('storage/' . $post->imagen) }}" alt="{{ $nombreCarta }}" class="w-full h-full object-cover" loading="lazy" />
      </div>
      <p class="mt-0.5 w-full text-center text-white text-[10px] leading-tight font-bold truncate [text-shadow:0_1px_0_#000]">{{ $nombreCarta }}</p>
      <span class="mt-0.5 inline-flex items-center gap-1 px-1 py-px rounded-full text-[8px] font-bold text-yellow-300 {{ $etiqueta3d }}">
        Nv {{ $post->nivel }}
      </span>
    </div>
    @endforeach
  </div>


  @if ($mostrarModalPost)
  @php
    $postModal = $postIdSeleccionado ? \App\Models\Post::conRivales()->find($postIdSeleccionado) : null;
    $abreviaturas = ['fuerza' => 'FUE', 'ataque' => 'ATA', 'velocidad' => 'VEL', 'resistencia' => 'RES', 'defensa' => 'DEF', 'energia' => 'ENE', 'recuperacion' => 'REC'];
  @endphp
  <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2" wire:click="cerrarModalPost">
    {{-- Modal 3D (mismo estilo que los del juego) --}}
    <div class="relative w-full max-w-xs max-h-[85vh] overflow-auto p-4 rounded-xl border border-black text-white
                bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]"
         wire:click.stop>

      <button wire:click="cerrarModalPost"
              class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                     bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                     hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

      {{-- Gif --}}
      <div class="mx-auto mb-3 h-32 w-40 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50
                  shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
        @if ($gifPost)
          <img src="{{ asset('storage/' . $gifPost) }}" alt="{{ $nombrePost }}"
               style="{{ \App\Models\Post::estiloGif($gifPost, 0.8) }}" class="block max-w-none">
        @else
          <p class="self-center text-gray-400 text-sm">Sin gif disponible.</p>
        @endif
      </div>

      {{-- Nombre y nivel --}}
      <h2 class="text-xl font-bold text-center mb-1 [text-shadow:0_2px_0_#000]">{{ $nombrePost }}</h2>
      @if ($postModal)
      <p class="text-center mb-3">
        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold text-yellow-300 {{ $etiqueta3d }}">Nivel {{ $postModal->nivel }}</span>
      </p>
      @endif

      {{-- Stats: etiqueta 3D + número (como el panel lateral) --}}
      @if ($statsPost && is_array($statsPost))
      <div class="font-mono rounded-lg p-2 space-y-1.5 mb-4 {{ $panel3d }}">
        @foreach ($statsPost as $clave => $valor)
          <div class="flex items-center gap-2 select-none text-sm">
            <div class="w-8 h-6 shrink-0 flex items-center justify-center font-bold text-yellow-300 tracking-wide {{ $etiqueta3d }}">{{ $abreviaturas[$clave] ?? strtoupper(substr($clave, 0, 3)) }}</div>
            <span class="font-bold text-white">+{{ $valor }}</span>
          </div>
        @endforeach
      </div>
      @endif

      {{-- Tipo de daño y poderes: solo iconos (el nombre y la descripción aparecen al pasar el mouse o al tocar) --}}
      @if ($postModal?->tipo || ($poderesPersonaje && $poderesPersonaje->isNotEmpty()))
      <div class="rounded-lg p-2 {{ $panel3d }}">
        <div class="flex flex-wrap justify-center gap-2">
          @if ($postModal?->tipo)
            <x-icono-tipo :tipo="$postModal->tipo" tam="w-11 h-11" class="cursor-pointer" />
          @endif
          @foreach ($poderesPersonaje ?? [] as $poder)
            <x-icono-poder :poder="$poder" tam="w-11 h-11" class="cursor-pointer" />
          @endforeach
        </div>
      </div>
      @endif
    </div>
  </div>
  @endif

</div>
