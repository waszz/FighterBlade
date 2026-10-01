{{-- Sin scroll propio: usa el del área principal del juego (si no, quedaban dos barras de scroll) --}}
<div id="contenedor-explorar" class="flex flex-col w-full text-white">
  {{-- Avisa a la interfaz si hay una pelea en pantalla (enemigo o resultado): en el celular se esconden los botones de los
       costados y la barra de abajo. Al salir de la ciudad vuelve a avisar que no --}}
  @php $enPelea = (bool) ($enemigo || ! empty($resultadosRondas)); @endphp
  <div wire:key="modo-pelea-{{ $enPelea ? 1 : 0 }}" class="hidden"
       x-data="{ init() { setTimeout(() => window.dispatchEvent(new CustomEvent('modo-pelea', { detail: { activo: @js($enPelea) } })), 0) },
                 destroy() { window.dispatchEvent(new CustomEvent('modo-pelea', { detail: { activo: false } })) } }"></div>

  <div class="w-full flex justify-center">
    <div class="flex flex-col items-center justify-center px-4 space-y-4 relative w-full max-w-[1000px]">


      @if($ciudadActual)
      <h2 class="text-xl font-bold text-center text-yellow-300 drop-shadow-md animate-pulse relative z-30">
        Te encontrás en {{ $ciudadActual->nombre }}
      </h2>

      {{-- Presa de caza --}}
      @if ($enemigo && ($cazaVista = $this->cazaActiva()))
        @php $infoCaza = $cazaVista->rarezaInfo(); @endphp
        <div class="px-3 py-1.5 rounded-lg border-2 text-sm font-bold text-center
                    bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),0_3px_0_#000]
                    {{ $cazaVista->rareza === 'legendaria' ? 'border-amber-400 text-amber-200 animate-pulse' : ($cazaVista->rareza === 'rara' ? 'border-sky-400 text-sky-200' : 'border-gray-400 text-gray-200') }}">
          🎯 Presa {{ $infoCaza['nombre'] }}: {{ $enemigo->titulo }}
          <span class="text-red-300">· stats ×{{ $infoCaza['stats'] }}</span>
          <span class="text-emerald-300">· premio: {{ \App\Models\Caza::PARTES[$cazaVista->parte] }}</span>
        </div>
      @endif

      {{-- Rival de misión --}}
      @if ($enemigo && ($misionVista = $this->misionActiva()))
        <div class="px-3 py-1.5 rounded-lg border-2 border-amber-400 text-amber-200 text-sm font-bold text-center
                    bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),0_3px_0_#000]">
          📜 Misión {{ $misionVista->orden }}: {{ $enemigo->titulo }} <span class="text-gray-300">(Nv {{ $enemigo->nivel }})</span>
          <span class="text-yellow-300">· premio: {{ number_format($misionVista->recompensa_oro, 0, ',', '.') }} oro</span>
          <span>+ <span class="num-esmeralda">{{ $misionVista->recompensa_diamantes }}</span> <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-4 w-4 align-[-0.2em]"></span>
        </div>
      @endif

      {{-- Rival de un piso de la Torre --}}
      @if ($enemigo && ($pisoVista = $this->torreActiva()))
        <div class="px-3 py-1.5 rounded-lg border-2 border-violet-400 text-violet-200 text-sm font-bold text-center
                    bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),0_3px_0_#000]">
          🗼 Torre · Piso {{ $pisoVista->piso }}: {{ $enemigo->titulo }} <span class="text-gray-300">(Nv {{ $enemigo->nivel }})</span>
        </div>
      @endif
      {{-- Franja de buffs activos: totales por tipo + cada buff de a uno, rotando --}}
@php
  $buffTipos = [
    'xp'   => ['activos' => $buffsExperienciaActivos ?? [], 'nombre' => 'EXP',  'icono' => asset('images/buff-exp.png'),  'color' => 'text-green-300',  'borde' => 'border-green-500'],
    'oro'  => ['activos' => $buffsOroActivos ?? [],         'nombre' => 'ORO',  'icono' => asset('images/oro.png'),  'color' => 'text-yellow-300', 'borde' => 'border-yellow-500'],
    'drop' => ['activos' => $buffsDropActivos ?? [],        'nombre' => 'DROP', 'icono' => asset('images/buff-drop.png'), 'color' => 'text-purple-300', 'borde' => 'border-purple-500'],
  ];

  $totalesBuff = [];
  $itemsBuff = [];
  foreach ($buffTipos as $tipo => $data) {
    if (empty($data['activos'])) {
      continue;
    }

    // Los personales suman completo; los globales solo hasta llegar al 100%
    $buffsPersonales = collect($data['activos'])->where('global', false)->values();
    $restante = max(0, 100 - $buffsPersonales->sum('porcentaje'));
    $visibles = $buffsPersonales->map(fn ($b) => $b + ['visible' => $b['porcentaje']])->all();
    foreach (collect($data['activos'])->where('global', true) as $buff) {
      if ($restante <= 0) break;
      $aporte = min($restante, $buff['porcentaje']);
      $restante -= $aporte;
      $visibles[] = $buff + ['visible' => $aporte];
    }

    $totalesBuff[] = ['nombre' => $data['nombre'], 'icono' => $data['icono'], 'color' => $data['color'], 'borde' => $data['borde'],
                      'valor' => '+' . array_sum(array_column($visibles, 'visible')) . '%'];
    foreach ($visibles as $buff) {
      $itemsBuff[] = [
        'icono'  => $data['icono'],
        'titulo' => 'Buff ' . $data['nombre'] . ' +' . $buff['visible'] . '%',
        'color'  => $data['color'],
        'origen' => $buff['global'] ? 'Global' : 'Personal',
        'frase'  => $buff['frase'] ?? null,
        'fin'    => $buff['finTimestamp'],
      ];
    }
  }

  // Exploración rápida (se acumula: cada premio del casino suma horas)
  $rapidaActiva = \App\Models\ExploracionRapida::activaPara($personaje->id);
  if ($rapidaActiva) {
    $totalesBuff[] = ['nombre' => 'EXPLORACIÓN RÁPIDA', 'icono' => asset('images/casino-rapida.png'), 'color' => 'text-orange-300', 'borde' => 'border-orange-500', 'valor' => ''];
    $itemsBuff[] = [
      'icono'  => asset('images/casino-rapida.png'),
      'titulo' => 'Exploración rápida',
      'color'  => 'text-orange-300',
      'origen' => '5 → 2 · 10 → 5 · 15 → 10 min',
      'frase'  => null,
      'fin'    => $rapidaActiva->fin->timestamp,
    ];
  }
@endphp

@if (count($itemsBuff))
<div class="w-full max-w-[650px] rounded-xl border border-black px-3 py-2 bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]"
     x-data="{
        items: @js($itemsBuff),
        actual: 0,
        ahora: Math.floor(Date.now() / 1000),
        init() {
            setInterval(() => { this.ahora = Math.floor(Date.now() / 1000) }, 1000);
            if (this.items.length > 1) setInterval(() => { this.actual = (this.actual + 1) % this.items.length }, 4000);
        },
        restante(fin) {
            const s = fin - this.ahora;
            if (s <= 0) return 'Finalizado';
            const h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60;
            return h > 0 ? `${h}h ${m}m` : `${m}m ${String(x).padStart(2, '0')}s`;
        },
     }">

    {{-- Totales activos --}}
    <div class="flex flex-wrap justify-center gap-1.5">
        @foreach ($totalesBuff as $total)
            <span class="flex items-center gap-1 px-2 py-0.5 rounded-md border bg-black/40 text-xs font-extrabold tracking-wide {{ $total['borde'] }} {{ $total['color'] }}">
                <img src="{{ $total['icono'] }}" alt="" class="h-4 w-4 object-contain [filter:drop-shadow(1px_1px_0_#000)]">
                {{ $total['nombre'] }} <span class="text-white">{{ $total['valor'] }}</span>
            </span>
        @endforeach
    </div>

    {{-- Un buff por vez, rotando --}}
    <div class="relative h-12 mt-1.5 overflow-hidden">
        <template x-for="(item, i) in items" :key="i">
            <div x-show="actual === i"
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-3"
                 class="absolute inset-0 flex items-center justify-center gap-2 text-center">
                <img :src="item.icono" alt="" class="h-8 w-8 object-contain shrink-0 [filter:drop-shadow(1px_2px_0_#000)]">
                <div class="leading-tight min-w-0">
                    <p class="text-sm font-extrabold [text-shadow:0_1px_0_#000]" :class="item.color">
                        <span x-text="item.titulo"></span>
                        <span class="text-[11px] font-semibold text-gray-300" x-text="'· ' + item.origen"></span>
                    </p>
                    <p class="text-xs text-gray-200 truncate">
                        <span x-show="item.frase" class="italic" x-text="'“' + item.frase + '” · '"></span>
                        ⏳ <span class="font-bold text-white" x-text="restante(item.fin)"></span>
                    </p>
                </div>
            </div>
        </template>
    </div>

    {{-- Puntitos de posición --}}
    <div class="flex justify-center gap-1" x-show="items.length > 1">
        <template x-for="(item, i) in items" :key="'p' + i">
            <button type="button" @click="actual = i" class="h-1.5 rounded-full transition-all"
                    :class="actual === i ? 'w-4 bg-white' : 'w-1.5 bg-white/30'"></button>
        </template>
    </div>
</div>
@endif



      @php
      $equipo = $personaje->equipo;
      $entrenamiento = $personaje->entrenamiento;
      $accesorio = $personaje->accesorio;

      $mostrarGifCompleto = false;
      $gifMostrar = $personaje->gif;

      if ($equipo && $entrenamiento && $accesorio) {
      if (
      $equipo->origen_post_id &&
      $equipo->origen_post_id === $entrenamiento->origen_post_id &&
      $equipo->origen_post_id === $accesorio->origen_post_id
      ) {
      $gifCompleto = \App\Models\Post::find($equipo->origen_post_id)?->gif;
      if ($gifCompleto) {
      $gifMostrar = $gifCompleto;
      $mostrarGifCompleto = true;
      }
      }
      }

      // Poderes a los costados del escenario: los del set completo equipado o los del personaje, y los del enemigo
      $poderesLadoPersonaje = collect();
      if ($enemigo) {
        $postSetCompleto = $mostrarGifCompleto ? \App\Models\Post::with('poderes')->find($equipo->origen_post_id) : null;
        $poderesLadoPersonaje = $postSetCompleto?->poderes?->isNotEmpty() ? $postSetCompleto->poderes : ($personaje->post?->poderes ?? collect());
      }
      $poderesLadoEnemigo = $enemigo ? ($this->gifsEnemigo()->poderes ?? collect()) : collect();
      $pildoraPoder = 'block px-2 py-1 rounded border border-black text-xs bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]';

      // Si en la pelea entró en un estado (Frenesí, Trance...), su gif del escenario también se tiñe (como en las rondas).
      // Solo con la pelea en pantalla: si aparece otro enemigo, los estados de la pelea anterior no cuentan
      $claseEstadoEscena = [];
      foreach (['personaje' => 'Personaje', 'enemigo' => 'Enemigo'] as $ladoEstado => $sufijoEstado) {
          $claseEstadoEscena[$ladoEstado] = empty($resultadosRondas) ? '' : match (true) {
              ! empty(${'poderActivoFrenesi' . $sufijoEstado}) => 'estado-frenesi',
              ! empty(${'poderActivoFuriaCiega' . $sufijoEstado}) => 'estado-furia',
              ! empty(${'poderActivoTrance' . $sufijoEstado}) => 'estado-trance',
              ! empty(${'poderActivoSuperNova' . $sufijoEstado}) => 'estado-supernova',
              ! empty(${'poderActivoSuperCarga' . $sufijoEstado}) => 'estado-supercarga',
              default => '',
          };
      }
      @endphp

      <div class="w-full flex items-center justify-center gap-3">
      {{-- Poderes del personaje (a su costado) --}}
      @if ($enemigo)
      <aside class="hidden md:flex w-36 shrink-0 flex-col items-end gap-1.5 text-right text-white">
        <p class="text-sm font-semibold leading-tight [text-shadow:0_2px_0_#000]">Poderes de<br><span class="text-yellow-400">{{ $personaje->nombre }}</span></p>
        <div class="flex flex-wrap justify-end gap-1.5">
        {{-- Tipo de daño con el que pelea (el del set completo equipado o el base) --}}
        @if ($tipoLadoPersonaje = $personaje->postDeCombate()?->tipo)
          <x-icono-tipo :tipo="$tipoLadoPersonaje" tam="w-10 h-10" />
        @endif
        @forelse ($poderesLadoPersonaje as $poder)
          <x-icono-poder :poder="$poder" tam="w-10 h-10" />
        @empty
          <span class="text-xs italic text-gray-400">Sin poderes</span>
        @endforelse
        </div>
      </aside>
      @endif

      {{-- Contenedor ciudad con overflow-hidden para evitar desbordes.
           Celular: de borde a borde (tapa el padding del main y de esta columna: 0.5rem + 1rem por lado) y más alto --}}
      <div
        class="relative border-y-2 sm:border-2 border-gray-900 sm:rounded-xl shadow-lg overflow-hidden select-none -mx-6 w-[calc(100%+3rem)] shrink-0 sm:mx-0 sm:w-full sm:shrink max-w-none sm:max-w-[650px] min-h-[300px] sm:min-h-[240px] pt-6">

        {{-- Fondo: la ciudad, o el escenario si es una misión --}}
        <img src="{{ asset('storage/posts/' . ($escenarioMision ?? $ciudadActual->gif)) }}" alt="Ciudad {{ $ciudadActual->nombre }}"
          class="absolute inset-0 w-full h-full object-cover object-bottom z-0" />

        {{-- Personaje: estiloGif lo escala para que todos midan lo mismo y pisen la misma línea --}}
        <div class="absolute bottom-1 left-0 sm:left-[8%] z-10">
          <img src="{{ asset('storage/' . $gifMostrar) }}" alt="Personaje {{ $personaje->nombre }}"
            style="{{ \App\Models\Post::estiloGif($gifMostrar) }}"
            class="block max-w-none {{ $personaje->orientacion_gif === 'derecha' ? 'scale-x-[-1]' : '' }} {{ $claseEstadoEscena['personaje'] ?: $personaje->claseAura() }}" />
        </div>

        {{-- Enemigo --}}
        @if($enemigo)
        <div class="absolute bottom-1 right-0 sm:right-[8%] z-10 scale-x-[-1] cursor-pointer" wire:click="mostrarModalEnemigo">
          <img src="{{ asset('storage/' . ($this->gifEnemigo() ?? 'default_enemigo.gif')) }}"
            alt="Enemigo {{ $enemigo->nombre ?? $enemigo->titulo }}"
            style="{{ \App\Models\Post::estiloGif($this->gifEnemigo() ?? null) }}" class="block max-w-none {{ $claseEstadoEscena['enemigo'] ?: ($enemigo instanceof \App\Models\Personaje ? $enemigo->claseAura() : '') }}" />
        </div>
        @elseif ($personaje->comerciante_oferta)
        {{-- 💰 El Comerciante Khonshu, donde va el enemigo --}}
        @php
          $postComerciante = \App\Support\Comerciante::post();
          $gifComerciante = $postComerciante?->gif;
          // Foto del círculo: la que se carga en Personajes especiales → Editar → Foto (si todavía es el gif, se recorta la cara)
          $fotoComerciante = $postComerciante?->imagen && ! str_ends_with(strtolower($postComerciante->imagen), '.gif') ? $postComerciante->imagen : null;
        @endphp
        <div class="absolute bottom-1 right-0 sm:right-[8%] z-10 scale-x-[-1]">
          <img src="{{ $gifComerciante ? asset('storage/' . $gifComerciante) : asset('images/comerciante/khonshu.gif') }}" alt="El Comerciante Khonshu"
            style="{{ $gifComerciante ? \App\Models\Post::estiloGif($gifComerciante) : '' }}" class="block max-w-none [filter:drop-shadow(0_0_8px_rgba(250,204,21,0.45))]" />
        </div>
        {{-- Lo que dice, en un globo arriba de él --}}
        <div class="absolute top-3 right-2 sm:right-[5%] z-20 max-w-[62%] sm:max-w-[48%] rounded-lg border-2 border-black bg-[#fdf6e3] pl-9 pr-2.5 py-1.5 text-left text-black shadow-[0_3px_0_#000]">
          {{-- Su cara en un círculo, en la esquina del globo --}}
          <span class="absolute -top-3 -left-3 w-11 h-11 rounded-full overflow-hidden border-2 border-amber-500 bg-gradient-to-b from-[#3b2a10] to-[#120c04] shadow-[0_2px_0_#000]">
            @if ($fotoComerciante)
              <img src="{{ asset('storage/' . $fotoComerciante) }}" alt="" class="absolute inset-0 w-full h-full object-cover">
            @else
              <img src="{{ asset('images/comerciante/khonshu.gif') }}" alt="" class="absolute left-1/2 -top-2.5 -translate-x-1/2 w-[160%] max-w-none [image-rendering:pixelated]">
            @endif
          </span>
          <p class="text-[10px] font-extrabold uppercase tracking-wide text-amber-700">El Comerciante Khonshu</p>
          <p class="text-xs sm:text-sm italic leading-snug">“{{ $personaje->comerciante_oferta['saludo'] ?? '' }}”</p>
          <span class="absolute -bottom-2 right-10 w-3 h-3 rotate-45 bg-[#fdf6e3] border-r-2 border-b-2 border-black"></span>
        </div>
        @endif
      </div> {{-- FIN overflow-hidden --}}

      {{-- Poderes del enemigo (a su costado) --}}
      @if ($enemigo)
      <aside class="hidden md:flex w-36 shrink-0 flex-col items-start gap-1.5 text-left text-white">
        <p class="text-sm font-semibold leading-tight [text-shadow:0_2px_0_#000]">Poderes de<br><span class="text-yellow-400">{{ $enemigo->nombre ?? $enemigo->titulo }}</span></p>
        <div class="flex flex-wrap justify-start gap-1.5">
        @if ($tipoLadoEnemigo = $this->gifsEnemigo()?->tipo ?? $enemigo->tipo ?? null)
          <x-icono-tipo :tipo="$tipoLadoEnemigo" tam="w-10 h-10" />
        @endif
        @forelse ($poderesLadoEnemigo as $poder)
          <x-icono-poder :poder="$poder" tam="w-10 h-10" />
        @empty
          <span class="text-xs italic text-gray-400">Sin poderes</span>
        @endforelse
        </div>
      </aside>
      @endif
      </div>{{-- FIN fila escenario + poderes --}}
      
  {{-- Opciones para explorar --}}
  @if ($mostrarOpciones)
    <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 z-10">
        <div class="flex flex-col items-center justify-center gap-1.5 rounded-lg p-1.5 w-20 mt-2 border border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]">
            @php
                // Si hay exploración rápida activa, mostrar los tiempos reducidos
                $minutosBase = [5, 10, 15];
                $minutosMostrar = $minutosBase;

                if (\App\Models\ExploracionRapida::activaPara($personaje->id)) {
                    $minutosMostrar = [
                        5 => 2,
                        10 => 5,
                        15 => 10,
                    ];
                } else {
                    // Sin buff, simplemente usar los tiempos normales
                    $minutosMostrar = [
                        5 => 5,
                        10 => 10,
                        15 => 15,
                    ];
                }

                // Zona inicial (El Comienzo): una sola exploración de 2 minutos (1 con exploración rápida)
                if ($this->esZonaInicial()) {
                    $minutosMostrar = [5 => \App\Models\ExploracionRapida::activaPara($personaje->id)
                        ? \App\Livewire\Explorar::MINUTOS_ZONA_INICIAL_RAPIDA
                        : \App\Livewire\Explorar::MINUTOS_ZONA_INICIAL];
                }
            @endphp

            @foreach($minutosMostrar as $original => $modificado)
                <button wire:click="explorar({{ $original }})"
                    class="text-white w-full h-8 flex flex-col items-center justify-center text-[9px] leading-none font-semibold rounded border border-black bg-gradient-to-b from-[#2f5470] to-[#0a1a26] hover:brightness-125 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100">
                    <span>Explorar</span>
                    <span class="text-sm font-bold mt-0.5">
                        {{ $modificado }} min
                    </span>
                </button>
            @endforeach

            <button wire:click="$set('mostrarOpciones', false)"
                class="mt-1 text-white text-[10px] underline hover:text-gray-200">
                Cancelar
            </button>
        </div>
    </div>
@endif





      {{-- "Explorando..." se muestra en el panel lateral; acá queda solo el temporizador invisible que hace aparecer al enemigo --}}
      @if($tiempoExploracion !== null && $personaje->exploracion_duracion > 0)
      <div wire:key="contador-{{ $tiempoExploracion }}-{{ $personaje->exploracion_duracion }}" class="hidden"
        x-data="{ fin: Date.now() / 1000 + {{ (int) $tiempoExploracion }}, t: null,
                  {{-- Al redibujar, este contador se reemplaza: el viejo se apaga (si no, el aviso llegaba dos veces) --}}
                  destroy() { clearInterval(this.t) } }"
        x-init="t = setInterval(() => { if (Date.now() / 1000 >= fin) { clearInterval(t); $wire.onTimerTerminado(); } }, 250)"></div>
      @endif



      @push('scripts')
      <script>
    window.addEventListener('recargarPagina', () => {
      location.reload();
    });
      </script>
      @endpush



      @if($enemigo)
      {{-- VS (centrado en el gif) --}}
      <div class="absolute top-[62%] left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-white z-10 flex flex-col items-center">
        {{-- Referencia: la parte que está en juego ya te la tiró este enemigo --}}
        @php $parteSacada = $this->parteYaSacada(); @endphp
        @if ($parteSacada)
          <div class="absolute bottom-full mb-2 flex flex-col items-center" title="Ya sacaste {{ $parteSacada['nombre'] }}">
            @if ($parteSacada['imagen'])
              <img src="{{ asset('storage/posts/' . $parteSacada['imagen']) }}" alt="{{ $parteSacada['nombre'] }}"
                   class="w-14 h-14 rounded-md object-cover border-2 shadow-[0_2px_0_#000] {{ ['equipo' => 'border-indigo-500', 'entrenamiento' => 'border-green-500', 'accesorio' => 'border-pink-500'][$parteSacada['tipo']] ?? 'border-black' }}" />
            @endif
          </div>
        @endif
        <span class="vs-animado text-4xl font-extrabold text-white tracking-wider italic [text-shadow:2px_2px_0_#000,-1px_-1px_0_#000]">
          VS
        </span>
      </div>

      {{-- Botones --}}
      @if($mostrarBotonesBatalla)
      <div class="absolute bottom-3 left-1/2 transform -translate-x-1/2 z-10 flex items-center gap-2">
        <button wire:click="atacar"
          class="bg-gradient-to-b from-[#2f5470] to-[#0a1a26] hover:brightness-125 text-white font-bold py-2 px-4 text-sm rounded transition-all duration-100 border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)]">
          Atacar
        </button>
        <button wire:click="huir"
          class="bg-gradient-to-b from-[#2f5470] to-[#0a1a26] hover:brightness-125 text-white font-bold py-2 px-4 text-sm rounded transition-all duration-100 border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)]">
          Huir
        </button>
      </div>
      @endif

      <style>
        @keyframes vsPulso {
          0%   { transform: scale(1) rotate(-6deg);   filter: drop-shadow(0 0 4px rgba(255,80,80,0.6)); }
          50%  { transform: scale(1.25) rotate(-6deg); filter: drop-shadow(0 0 14px rgba(255,60,60,1)); }
          100% { transform: scale(1) rotate(-6deg);   filter: drop-shadow(0 0 4px rgba(255,80,80,0.6)); }
        }
        .vs-animado {
          display: inline-block;
          animation: vsPulso 1.2s ease-in-out infinite;
        }
      </style>
      @endif

      @else
      <p class="text-gray-400 text-xl">No hay ciudad actual asignada.</p>
      @endif
    </div>
  </div>

  {{-- Botón Explorar --}}
  <div class="{{ $resultadosRondas ? 'mt-4' : 'mt-3' }} text-center w-full">
    @php
      // Botones de la ciudad (estilo barra): icono de un color con el nombre abajo en mayúsculas.
      // Al pasar el mouse se rellena un cuadrado del color del botón y el icono y el texto se ponen oscuros.
      // El icono es una máscara CSS pintada con el color del texto (bg-current), así cambia de color con el estado.
      $botonCiudad = 'flex flex-col items-center justify-center gap-0.5 w-[4.5rem] h-[3.75rem] rounded-lg text-[color:var(--c)] text-[10px] font-extrabold uppercase tracking-wide transition-colors duration-150 hover:bg-[color:var(--c)] hover:text-[#0b0f14] active:translate-y-[2px] disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-[color:var(--c)] disabled:active:translate-y-0';
      $iconoCiudad = fn (string $archivo) => 'class="block w-7 h-7 bg-current" style="-webkit-mask: url(' . asset('images/iconos/' . $archivo) . ') center / contain no-repeat; mask: url(' . asset('images/iconos/' . $archivo) . ') center / contain no-repeat;"';
    @endphp
    {{-- 💰 El Comerciante Khonshu: su saludo y las partes que vende (hasta despedirse no hay botones de la ciudad) --}}
    @if (! $enemigo && ($oferta = $personaje->comerciante_oferta))
      @include('livewire.partials.comerciante', ['oferta' => $oferta])
    @endif

    {{-- Tarjeta transparente con los botones (en una pelea no hay botones, así que no se muestra) --}}
    @if (!$enemigo && ! $personaje->comerciante_oferta)
    <div class="inline-flex flex-wrap justify-center items-start gap-2 p-2 rounded-2xl border border-white/10 bg-black/20 backdrop-blur-[2px] shadow-[0_4px_12px_rgba(0,0,0,0.5)]">
      @if (!$mostrarOpciones && !$enemigo && !$tiempoExploracion)
      <button wire:click="toggleExplorar" class="{{ $botonCiudad }}" style="--c:#ef4444">
        <span {!! $iconoCiudad('explorar.png') !!}></span>
        Explorar
      </button>
      @endif

      {{-- Caza y Misiones abren su sección del juego (no se muestran durante una pelea;
           mientras te recuperás quedan apagados y se prenden solos al terminar la espera) --}}
      @if (!$enemigo)
      @php $recupCiudad = $personaje->segundosRecuperacion(); @endphp
      <div class="contents" wire:key="botones-ciudad-{{ $recupCiudad }}"
           x-data="{ recup: {{ $recupCiudad }}, finRecup: Date.now() / 1000 + {{ $recupCiudad }} }"
           x-init="setInterval(() => recup = Math.max(0, Math.ceil(finRecup - Date.now() / 1000)), 500)">
      <button type="button" x-on:click="Livewire.dispatch('cambiarSeccion', { nuevaSeccion: 'caza' })" x-bind:disabled="recup > 0"
        x-bind:title="recup > 0 ? 'Te estás recuperando' : ''"
        class="{{ $botonCiudad }}" style="--c:#22c55e">
        <span {!! $iconoCiudad('caza.svg') !!}></span>
        Caza
      </button>
      <button type="button" x-on:click="Livewire.dispatch('cambiarSeccion', { nuevaSeccion: 'misiones' })" x-bind:disabled="recup > 0"
        x-bind:title="recup > 0 ? 'Te estás recuperando' : ''"
        class="{{ $botonCiudad }}" style="--c:#f59e0b">
        <span {!! $iconoCiudad('misiones.svg') !!}></span>
        Misiones
      </button>
      <button type="button" x-on:click="Livewire.dispatch('cambiarSeccion', { nuevaSeccion: 'torre' })" x-bind:disabled="recup > 0"
        x-bind:title="recup > 0 ? 'Te estás recuperando' : ''"
        class="{{ $botonCiudad }}" style="--c:#a78bfa">
        <span {!! $iconoCiudad('torre.svg') !!}></span>
        Torre
      </button>
      <button type="button" x-on:click="Livewire.dispatch('cambiarSeccion', { nuevaSeccion: 'mazmorra' })" x-bind:disabled="recup > 0"
        x-bind:title="recup > 0 ? 'Te estás recuperando' : ''"
        class="{{ $botonCiudad }}" style="--c:#f43f5e">
        <span {!! $iconoCiudad('mazmorra.svg') !!}></span>
        Mazmorra
      </button>
      {{-- Entrenamiento con el maestro: se puede aunque te estés recuperando --}}
      <button type="button" x-on:click="Livewire.dispatch('cambiarSeccion', { nuevaSeccion: 'entrenar' })"
        class="{{ $botonCiudad }}" style="--c:#fb923c">
        <span {!! $iconoCiudad('entrenar.svg') !!}></span>
        Entrenar
      </button>
      </div>
      @endif
    </div>
    @endif

    @if ($mensajeExploracion)
    <p class="mt-2 text-red-500 font-semibold">{{ $mensajeExploracion }}</p>
    @endif
  </div>

@if($mostrarRanking)
<div class="mt-4 w-full max-w-4xl mx-auto flex flex-col md:flex-row md:justify-between md:items-start gap-4 px-2">
  {{-- Ranking (esquina izquierda) --}}
  {{-- Usuarios en la zona: sin panel de fondo, solo el encabezado y las filas 3D --}}
  <div class="w-full md:w-[22rem] text-white text-sm">
    @php $usuariosEnZona = count($rankingCiudad ?? []); @endphp
    <h2 class="text-lg font-bold text-yellow-300 text-center mb-2 [text-shadow:0_2px_0_#000]">
      {{ $usuariosEnZona }} {{ $usuariosEnZona === 1 ? 'usuario' : 'usuarios' }} en la zona
    </h2>

    @if($rankingCiudad && count($rankingCiudad) > 0)
      {{-- Todos los usuarios de la zona; con muchos aparece el scroll --}}
      <div class="max-h-[34rem] overflow-y-auto pr-1 [scrollbar-width:thin] [scrollbar-color:#4b5563_transparent]">
        @foreach($rankingCiudad as $index => $personajeRanking)
          @php
            $img = null;
            $eq = $personajeRanking->equipo;
            $ent = $personajeRanking->entrenamiento;
            $acc = $personajeRanking->accesorio;

            if ($eq && $ent && $acc && $eq->origen_post_id === $ent->origen_post_id && $eq->origen_post_id === $acc->origen_post_id) {
              $img = \App\Models\Post::find($eq->origen_post_id)?->imagen;
            } elseif ($personajeRanking->post?->imagen) {
              $img = $personajeRanking->post->imagen;
            }
          @endphp

          @php
            $esYo = $personajeRanking->id === $personaje->id;
            // Puesto en la zona (mismos colores que el Ranking: oro, plata y bronce)
            $colorPuesto = match($loop->iteration) {
                1 => 'from-yellow-300 to-yellow-600 text-black',
                2 => 'from-gray-200 to-gray-400 text-black',
                3 => 'from-amber-500 to-amber-800 text-white',
                default => 'from-neutral-700 to-black text-yellow-400',
            };
          @endphp
          {{-- Fila simple con una línea abajo que separa a cada usuario --}}
          <div class="flex items-center justify-between gap-2 px-1 py-1.5 cursor-pointer transition hover:bg-white/5
                      {{ $loop->last ? '' : 'border-b border-white/20' }} {{ $esYo ? 'font-bold text-yellow-300' : '' }}
                      {{ $personajeRanking->claseRanking() ? 'rounded-lg border ' . $personajeRanking->claseRanking() : '' }}"
              wire:click="mostrarModalPersonaje({{ $personajeRanking->id }})">

            <div class="flex items-center gap-3 min-w-0">
              <span class="w-7 h-7 shrink-0 flex items-center justify-center rounded-full border border-black bg-gradient-to-b {{ $colorPuesto }} text-xs font-extrabold shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]">{{ $loop->iteration }}</span>
              @if($img)
                <img src="{{ asset('storage/' . $img) }}"
                     alt="{{ $personajeRanking->nombre }}"
                     class="w-12 h-12 shrink-0 rounded-full object-cover" />
              @endif
              <span class="truncate text-sm font-semibold {{ $personajeRanking->claseNombre() }}">{{ $personajeRanking->nombre }}</span>
              {{-- Clan: su etiqueta entre corchetes (como en el Ranking) --}}
              @if ($personajeRanking->clan)
                <span class="shrink-0 text-xs font-bold text-sky-300" title="Clan {{ $personajeRanking->clan->nombre }}">[{{ $personajeRanking->clan->tag ?? $personajeRanking->clan->nombre }}]</span>
              @endif
            </div>

            {{-- Nivel y peleas PvP ganadas / perdidas --}}
            <div class="shrink-0 flex items-center gap-2 text-xs font-bold">
              <span class="text-yellow-300">Nivel {{ $personajeRanking->nivel }}</span>
              <span title="PvP: ganadas / perdidas">
                <span class="text-green-400">{{ $personajeRanking->pvp_ganadas ?? 0 }}</span><span class="text-white">/</span><span class="text-red-400">{{ $personajeRanking->pvp_perdidas ?? 0 }}</span>
              </span>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-center text-gray-400 italic mt-2">No hay jugadores en esta ciudad.</p>
    @endif
  </div>

  {{-- Anuncios (esquina derecha): los publican los admins en Administración → Anuncios --}}
  @php
    $anuncios = \App\Models\Anuncio::activos()->with('autor')->withCount('likes')
      ->withExists(['likes as me_gusta' => fn ($q) => $q->where('users.id', auth()->id())])
      ->take(\App\Models\Anuncio::MAXIMO_EN_PANEL)->get();
  @endphp
  {{-- Anuncios: panel 3D con tarjetas (foto del autor, título, detalle, texto, llamado y "me gusta") --}}
  <div class="w-full md:w-[24rem] rounded-xl p-4 text-white text-sm border border-yellow-600
              bg-gradient-to-b from-[#1c2533]/95 to-[#0a0e14]/95
              shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_8px_16px_rgba(0,0,0,0.7)]">
    <h2 class="text-xl font-bold text-yellow-300 text-center mb-3 [text-shadow:0_2px_0_#000]">Anuncios</h2>

    @if($anuncios->isNotEmpty())
      <div class="max-h-[26rem] overflow-y-auto sidebar-pj pr-1 pl-3 pt-3 space-y-5">
        @foreach($anuncios as $anuncio)
          @php $fotoAutor = $anuncio->fotoAutor(); @endphp
          <div wire:key="anuncio-{{ $anuncio->id }}" class="relative pl-7 pr-3 pt-2 pb-2 rounded-xl border border-black
                      bg-gradient-to-b from-[#2a3240] to-[#10141b]
                      shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
            {{-- Foto del autor, asomando por la esquina --}}
            <div class="absolute -left-3 -top-3 w-10 h-10 rounded-full overflow-hidden bg-black shadow-[0_2px_0_#000]">
              @if ($fotoAutor)
                <img src="{{ asset('storage/' . $fotoAutor) }}" alt="{{ $anuncio->autor?->name }}" class="w-full h-full object-cover">
              @else
                <span class="w-full h-full flex items-center justify-center text-sm font-bold text-yellow-300">{{ mb_substr($anuncio->autor?->name ?? 'A', 0, 1) }}</span>
              @endif
            </div>

            {{-- Autor · me gusta --}}
            <div class="flex items-center justify-between gap-2">
              <p class="min-w-0 truncate font-bold text-yellow-300 [text-shadow:0_1px_0_#000]">{{ $anuncio->autor?->name ?? 'Admin' }}</p>
              <button type="button" wire:click="meGustaAnuncio({{ $anuncio->id }})" aria-label="Me gusta"
                      class="shrink-0 flex items-center gap-1 text-sm font-bold transition-transform active:scale-90 {{ $anuncio->me_gusta ? 'text-red-500' : 'text-gray-300 hover:text-red-400' }}">
                <i class="{{ $anuncio->me_gusta ? 'fa-solid' : 'fa-regular' }} fa-heart"></i>
                @if ($anuncio->likes_count > 0)<span class="text-white">{{ $anuncio->likes_count }}</span>@endif
              </button>
            </div>

            <p class="mt-0.5 font-bold text-orange-400 [text-shadow:0_1px_0_#000]">{{ $anuncio->titulo }}</p>
            @if ($anuncio->detalle)
              <p class="mt-1 text-xs font-bold text-white">{{ $anuncio->detalle }}</p>
            @endif
            <p class="text-xs text-gray-200 whitespace-pre-line">{{ $anuncio->texto }}</p>
            @if ($anuncio->llamado)
              <p class="mt-1 font-bold text-orange-400 [text-shadow:0_1px_0_#000]">{{ $anuncio->llamado }}</p>
            @endif
            <p class="mt-1 text-right text-[10px] text-gray-400">{{ $anuncio->created_at->format('d/m/Y H:i') }}</p>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-center text-gray-400 italic mt-2">No hay anuncios por ahora.</p>
    @endif
  </div>
</div>

{{-- Personajes y Poderes: centrados en la pantalla, en una tarjeta transparente --}}
<div class="mt-4 flex justify-center px-2">
  <div class="inline-flex justify-center gap-2 p-1.5 rounded-xl border border-white/10 bg-black/20 backdrop-blur-[2px] shadow-[0_4px_12px_rgba(0,0,0,0.5)]">
    @foreach (['sets' => 'Personajes', 'poderes' => 'Poderes'] as $seccionBoton => $nombreBoton)
      <button type="button" x-on:click="Livewire.dispatch('cambiarSeccion', { nuevaSeccion: '{{ $seccionBoton }}' })"
        class="px-2.5 py-1 text-xs rounded-md font-semibold uppercase text-white transition-all duration-100 border-2 border-black bg-gradient-to-b from-neutral-700 to-black hover:brightness-125 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)]">
        {{ $nombreBoton }}
      </button>
    @endforeach
  </div>
</div>
@endif


@if($mostrarModal && $personajeSeleccionadoModal)
  @php
    $abreviaturasModal = ["fuerza" => "FUE", "resistencia" => "RES", "ataque" => "ATA", "defensa" => "DEF", "velocidad" => "VEL", "energia" => "ENE"];
    $gifModalJugador = $gifPersonajeEquipado ?? $personajeSeleccionadoModal->post->gif ?? null;
    $esMiPersonaje = $personajeSeleccionadoModal->user_id === auth()->id();
  @endphp
  <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2"
       wire:click="$set('mostrarModal', false)">
    {{-- Modal 3D del jugador (mismo estilo que el del enemigo) --}}
    <div class="relative w-full max-w-xs max-h-[85vh] overflow-auto p-4 rounded-xl border border-black text-white
                bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]"
         wire:click.stop>

      <!-- Botón cerrar -->
      <button wire:click="$set('mostrarModal', false)"
              class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                     bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                     hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

      <!-- GIF del personaje sobre la zona (ciudad) en la que está -->
      @php $gifZonaModal = $personajeSeleccionadoModal->ciudadActual?->gif; @endphp
      <div class="relative mx-auto mb-3 h-32 w-48 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50
                  shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
        @if ($gifZonaModal)
          <img src="{{ asset('storage/posts/' . $gifZonaModal) }}" alt="Zona" class="absolute inset-0 w-full h-full object-cover object-bottom">
        @endif
        @if ($gifModalJugador)
          <img src="{{ asset('storage/' . $gifModalJugador) }}" alt="Personaje"
               style="{{ \App\Models\Post::estiloGif($gifModalJugador, 0.8) }}" class="relative z-10 block max-w-none scale-x-[-1] {{ $personajeSeleccionadoModal->claseAura() }}">
        @endif
      </div>

      <!-- Nombre y nivel -->
      <h2 class="text-xl font-bold text-center mb-1 [text-shadow:0_2px_0_#000]"><span class="{{ $personajeSeleccionadoModal->claseNombre() }}">{{ $personajeSeleccionadoModal->nombre }}</span></h2>
      <p class="text-center mb-3">
        <span class="inline-block px-2 py-0.5 rounded-full border border-black text-xs font-bold text-yellow-300 bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]">
          Nivel {{ $personajeSeleccionadoModal->nivel }}
        </span>
      </p>
      <x-barra-exp :personaje="$personajeSeleccionadoModal" class="mb-3" />
      <!-- Oro, diamantes y stats: del personaje propio, o de todos para admins y cuentas con permiso -->
      @if ($esMiPersonaje || auth()->user()?->puedeVerDatosDeOtros())
      <div class="grid grid-cols-2 gap-2 mb-3 text-sm font-bold">
        <div class="flex items-center justify-center gap-1.5 py-1 rounded-lg border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]">
          <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-4 h-4">
          <span class="text-yellow-300">{{ number_format($personajeSeleccionadoModal->oro ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-center gap-1.5 py-1 rounded-lg border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]">
          <img src="{{ asset('images/diamante.png') }}" alt="Esmeralda" class="w-4 h-4">
          <span class="num-esmeralda">{{ number_format($personajeSeleccionadoModal->diamante ?? 0, 0, ',', '.') }}</span>
        </div>
      </div>

      <!-- Stats -->
      <div class="font-mono rounded-lg border border-black p-2 space-y-1.5 mb-4 bg-gradient-to-b from-[#2a3240] to-[#10141b]
                  shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
        {{-- Mismo estilo que los atributos del panel lateral: etiqueta 3D + número --}}
        @foreach ($personajeSeleccionadoModalStats ?? [] as $statName => $value)
          <div class="flex items-center gap-2 select-none text-sm">
            <div class="w-8 h-6 shrink-0 flex items-center justify-center font-bold text-yellow-300 tracking-wide border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">{{ $abreviaturasModal[$statName] ?? strtoupper(substr($statName, 0, 3)) }}</div>
            <span class="font-bold text-white">+{{ $value }}</span>
          </div>
        @endforeach
      </div>
      @endif

      <!-- Tipo de daño y poderes (los del set con el que pelea: el completo equipado o, si no, el base) -->
      @php
        $postCombateModal = $personajeSeleccionadoModal->postDeCombate();
        $tipoModalJugador = $postCombateModal?->tipo ?? $personajeSeleccionadoModal->post?->tipo;
        $poderesModalJugador = $postCombateModal?->poderes ?? collect();
        $joyaModalJugador = $personajeSeleccionadoModal->joya;
        $pocionModalJugador = $personajeSeleccionadoModal->objeto_consumible;
      @endphp
      @if ($tipoModalJugador || $poderesModalJugador->isNotEmpty() || $joyaModalJugador || $pocionModalJugador)
        <div class="rounded-lg border border-black p-2 mb-4 text-left bg-gradient-to-b from-[#2a3240] to-[#10141b]
                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
          {{-- Solo los iconos: el nombre y la descripción aparecen al pasar el mouse o al tocar --}}
          <div class="flex flex-wrap justify-center gap-2">
            @if ($tipoModalJugador)
              <x-icono-tipo :tipo="$tipoModalJugador" tam="w-11 h-11" class="cursor-pointer" />
            @endif
            @foreach ($poderesModalJugador as $poder)
              <x-icono-poder :poder="$poder" tam="w-11 h-11" class="cursor-pointer" />
            @endforeach
            @if ($joyaModalJugador)
              <x-icono-joya :joya="$joyaModalJugador" tam="w-11 h-11" class="cursor-pointer" />
            @endif
            @if ($pocionModalJugador)
              <x-icono-pocion :pocion="$pocionModalJugador" tam="w-11 h-11" class="cursor-pointer" />
            @endif
          </div>
        </div>
      @endif

      <!-- Botón atacar (no a uno mismo; los novatos no atacan ni son atacados) -->
      @if (! $esMiPersonaje && ($personaje->esNovato() || $personajeSeleccionadoModal->esNovato()))
        <p class="w-full text-center py-2 rounded-lg border border-black text-xs font-bold text-gray-300 bg-gradient-to-b from-gray-600 to-gray-800 shadow-[0_3px_0_#000]">
          🛡️ {{ $personaje->esNovato() ? 'Podés atacar jugadores desde el nivel ' . (\App\Livewire\Explorar::NIVEL_MAX_ENEMIGO_ESPECIAL + 1) : 'Jugador nuevo: todavía no se lo puede atacar' }}
        </p>
      @elseif (! $esMiPersonaje)
        <x-boton-atacar :personaje="$personaje" :objetivo="$personajeSeleccionadoModal"
            :href="route('atacar.personaje', ['personajeId' => $personaje->id, 'objetivoId' => $personajeSeleccionadoModal->id])" />
      @endif
      @if (! $esMiPersonaje)
        <x-botones-desafio :objetivo-id="$personajeSeleccionadoModal->id" />
      @endif
    </div>
  </div>
@endif

{{-- Modal del enemigo --}}
@if($modalEnemigoVisible && $enemigo)
  <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 px-2"
       wire:click="$set('modalEnemigoVisible', false)">
    {{-- Modal 3D (mismo estilo que los paneles del juego) --}}
    <div class="relative w-full max-w-xs max-h-[85vh] overflow-auto p-4 rounded-xl border border-black text-white
                bg-gradient-to-b from-[#1c2533] to-[#0a0e14]
                shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]"
         wire:click.stop>

      <!-- Botón cerrar -->
      <button wire:click="$set('modalEnemigoVisible', false)"
              class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-md border border-black text-white font-bold
                     bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]
                     hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all">&times;</button>

      <!-- GIF del enemigo -->
      <div class="mx-auto mb-3 h-32 w-40 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50
                  shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
        <img src="{{ asset('storage/' . ($this->gifEnemigo() ?? 'default_enemigo.gif')) }}" alt="Enemigo"
             style="{{ \App\Models\Post::estiloGif($this->gifEnemigo() ?? null, 0.8) }}" class="block max-w-none scale-x-[-1]">
      </div>

      <!-- Nombre y nivel -->
      <h2 class="text-xl font-bold text-center mb-1 [text-shadow:0_2px_0_#000]">{{ $enemigo->nombre ?? $enemigo->titulo }}</h2>
      <p class="text-center mb-3">
        <span class="inline-block px-2 py-0.5 rounded-full border border-black text-xs font-bold text-yellow-300 bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_2px_0_#000]">
          Nivel {{ $enemigo->nivel }}
        </span>
      </p>

      <!-- Stats: los mismos con los que te pelea (refuerzo, sus poderes y tu Anulación de poder) -->
      @php
        $anuladosModalEnemigo = \App\Support\AnulacionPoder::anulados($this->gifsEnemigo()->poderes ?? [], $personaje->postDeCombate()?->poderes ?? []);
      @endphp
      <p class="text-center text-[11px] text-gray-400 -mt-1 mb-1">Stats con los que te pelea</p>
      <div class="font-mono rounded-lg border border-black p-2 space-y-1.5 mb-3 bg-gradient-to-b from-[#2a3240] to-[#10141b]
                  shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
        @foreach ($enemigoModalStats as $statName => $value)
          @php
            $abreviaturas = [
                'fuerza' => 'FUE',
                'resistencia' => 'RES',
                'ataque' => 'ATA',
                'defensa' => 'DEF',
                'velocidad' => 'VEL',
                'energia' => 'ENE',
            ];
            $abreviatura = $abreviaturas[$statName] ?? strtoupper(substr($statName, 0, 3));
          @endphp
          <div class="flex items-center gap-2 select-none text-sm">
            <div class="w-9 h-6 flex items-center justify-center font-bold text-yellow-300 tracking-wide border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)]">{{ $abreviatura }}</div>
            <div class="flex-1 font-bold text-white">{{ $value }}</div>
          </div>
        @endforeach
      </div>

      <!-- Poderes -->
      @php $joyaEnemigo = $enemigo instanceof \App\Models\Personaje ? $enemigo->joya : null; @endphp
      @if(($this->gifsEnemigo()->poderes && $this->gifsEnemigo()->poderes->count()) || $joyaEnemigo)
        <div class="rounded-lg border border-black p-2 text-left bg-gradient-to-b from-[#2a3240] to-[#10141b]
                    shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]">
          {{-- Solo los iconos: el nombre y la descripción aparecen al pasar el mouse o al tocar --}}
          <div class="flex flex-wrap justify-center gap-2">
            @foreach ($this->gifsEnemigo()->poderes ?? [] as $poder)
              <x-icono-poder :poder="$poder" tam="w-11 h-11" class="cursor-pointer {{ in_array($poder->nombre, $anuladosModalEnemigo, true) ? 'grayscale opacity-40' : '' }}" />
            @endforeach
            @if ($joyaEnemigo)
              <x-icono-joya :joya="$joyaEnemigo" tam="w-11 h-11" class="cursor-pointer" />
            @endif
          </div>
        </div>
      @endif
    </div>
  </div>
@endif



  {{-- Resultados de las rondas de combate y recompensas (fuera del overflow-hidden para no cortarse) --}}
  @include('livewire.partials.resultado-pelea', ['idPeleaCompartir' => $ultimaPeleaId])

</div>