{{-- Rondas y resultado de una pelea. Lo usa la Ciudad (explorar) y "Mis peleas" para volver a ver una pelea guardada.
     Variables: $resultadosRondas, $personaje, $enemigo, $recompensas, $ciudadActual, $escenarioMision y las de Explorar::VISTA_PELEA --}}
  @if($resultadosRondas)
  {{-- Tocando la pelea se baja hasta el final (el resultado y la escena), con el scroll del contenedor en el que está --}}
  <div x-data="{ bajar() {
          let el = this.$el.parentElement;
          while (el && !(el.scrollHeight > el.clientHeight && /(auto|scroll)/.test(getComputedStyle(el).overflowY))) el = el.parentElement;
          el = el || document.scrollingElement;
          el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
       } }" @click="bajar()" title="Tocá para ir al resultado">
  @php
  // Tipo de daño con el que pelea cada uno: el de su set completo equipado o su set base (igual que el combate),
  // no el del personaje con el que arrancó
  $tipoDeCombate = fn ($pj) => $pj instanceof \App\Models\Personaje
      ? ($pj->postDeCombate()?->tipo ?? $pj->tipo ?? 'fisico')
      : ($pj->tipo ?? 'fisico');
  // Al volver a ver una pelea guardada (Mis peleas) vienen los sets con los que peleó cada uno ese día
  $postPjRep = $postPjRepeticion ?? null;
  $postEnRep = $postEnRepeticion ?? null;
  $tipoVistaPersonaje = $postPjRep->tipo ?? $tipoDeCombate($personaje);
  $tipoVistaEnemigo = $postEnRep->tipo ?? $tipoDeCombate($enemigo);

  // Pelea guardada: el tipo de daño con el que peleó cada uno ese día, no el del set que tenga equipado ahora.
  // Las peleas nuevas lo guardan (tipo_personaje / tipo_enemigo); en las viejas se saca de los daños de las rondas
  if (isset($tiposRepeticion)) {
      $tipoPorRondas = function (string $lado) use ($resultadosRondas) {
          $fisico = $elemental = 0;
          foreach ($resultadosRondas as $r) {
              if (is_array($r) && ($r['atacante'] ?? null) === $lado && ($r['ronda'] ?? null) !== 'Final') {
                  $fisico += (int) ($r['danio_fisico'] ?? 0);
                  $elemental += (int) ($r['danio_elemental'] ?? 0);
              }
          }
          return match (true) {
              $fisico > 0 && $elemental > 0 => 'hibrido',
              $elemental > 0 => 'elemental',
              $fisico > 0 => 'fisico',
              default => null,
          };
      };
      $tipoVistaPersonaje = $tiposRepeticion['personaje'] ?? $tipoPorRondas('personaje') ?? $tipoVistaPersonaje;
      $tipoVistaEnemigo = $tiposRepeticion['enemigo'] ?? $tipoPorRondas('enemigo') ?? $tipoVistaEnemigo;
  }

  $golpesPersonaje = 0;
  $golpesEnemigo = 0;

  foreach ($resultadosRondas as $res) {
  if (!is_array($res) || !isset($res['ronda'], $res['atacante'], $res['danio'])) continue;
  if ($res['ronda'] === 'Final') continue;
  if (isset($res['tipo_ataque']) && strtolower($res['tipo_ataque']) === 'defensa') continue;

  if ($res['atacante'] === 'personaje') {
  $golpesPersonaje += $res['danio']; // daño hecho POR personaje
  } else {
  $golpesEnemigo += $res['danio']; // daño hecho POR enemigo
  }
  }

  $accionesPorRonda = collect($resultadosRondas)->filter(fn($r) => $r['ronda'] !==
  'Final')->groupBy('ronda')->toArray();
  // Obtener el máximo número de ronda con clave numérica (entera)
  $maxRonda = 5; // valor por defecto mínimo

  // Filtrar solo las claves que sean números enteros (rondas)
  $clavesNumericas = array_filter(array_keys($accionesPorRonda), 'is_int');

  if (!empty($clavesNumericas)) {
  $maxRonda = max($clavesNumericas);
  }
  for ($i = 1; $i <= $maxRonda; $i++) { if (!isset($accionesPorRonda[$i])) { $accionesPorRonda[$i]=[[ 'ronda'=> $i,
    'atacante' => null,
    'defensor' => null,
    'tipo_ataque' => 'normal',
    'danio' => 0,
    'gif' => null,
    'mensaje' => 'No hubo acción en esta ronda.',
    ]];
    }
    }

    ksort($accionesPorRonda);

    $resultadoFinal = collect($resultadosRondas)->firstWhere('ronda', 'Final');

    if (! function_exists('obtenerGifsPorPersonaje')) {
    function obtenerGifsPorPersonaje($personaje) {
    $gifs = [
    'normal' => null,
    'critico' => null,
    'especial' => null,
    'defensa' => null,
    'contraataque' => null,
    'base' => null, // rebote: el que resiste se muestra con su gif base
    'derrota' => null, // rebote: al que le rebota, con su gif de derrota
    ];

    $esPostDirecto = $personaje instanceof \App\Models\Post;

    if (!$esPostDirecto) {
    $equipo = $personaje->equipo ?? null;
    $entrenamiento = $personaje->entrenamiento ?? null;
    $accesorio = $personaje->accesorio ?? null;

    if ($equipo && $entrenamiento && $accesorio) {
    if (
    $equipo->origen_post_id &&
    $equipo->origen_post_id === $entrenamiento->origen_post_id &&
    $equipo->origen_post_id === $accesorio->origen_post_id
    ) {
    $postCompleto = \App\Models\Post::find($equipo->origen_post_id);
    if ($postCompleto) {
    $gifs['normal'] = $postCompleto->gif_ataque ?? $postCompleto->gif ?? null;
    $gifs['critico'] = $postCompleto->gif_critico ?? $postCompleto->gif ?? null;
    $gifs['especial'] = $postCompleto->gif_especial ?? $postCompleto->gif ?? null;
    $gifs['defensa'] = $postCompleto->gif_defensa ?? $postCompleto->gif ?? null;
    $gifs['contraataque'] = $postCompleto->gif_ataque ?? $postCompleto->gif ?? null;
    $gifs['base'] = $postCompleto->gif ?? null;
    $gifs['derrota'] = $postCompleto->gif_derrota ?? $postCompleto->gif ?? null;
    return $gifs;
    }
    }
    }
    }

    // Si es un Post directamente o no se equipó completo
    $post = $esPostDirecto ? $personaje : ($personaje->post ?? null);
    if ($post) {
    $gifs['normal'] = $post->gif_ataque ?? $post->gif ?? null;
    $gifs['critico'] = $post->gif_critico ?? $post->gif ?? null;
    $gifs['especial'] = $post->gif_especial ?? $post->gif ?? null;
    $gifs['defensa'] = $post->gif_defensa ?? $post->gif ?? null;
    $gifs['contraataque'] = $post->gif_ataque ?? $post->gif ?? null;
    $gifs['base'] = $post->gif ?? null;
    $gifs['derrota'] = $post->gif_derrota ?? $post->gif ?? null;
    }

    return $gifs;
    }
    }

    $gifsPersonaje = obtenerGifsPorPersonaje($postPjRep ?? $personaje);
    $gifsEnemigo = obtenerGifsPorPersonaje($postEnRep ?? $enemigo);
    @endphp

    @php
    $nombrePersonajeUsuario = $personaje->nombre ?? 'Personaje';

    // Poderes de cada uno: los del set con el que peleó (en Mis peleas, el guardado en la pelea; si no, el set
    // completo equipado o el base). En PvP la relación "poderes" de un jugador es la de su set base: no sirve
    $poderesDeCombate = fn ($pj) => $pj instanceof \App\Models\Personaje
        ? ($pj->postDeCombate()?->poderes ?? collect())
        : ($pj?->poderes ?? collect());

    $poderesPersonaje = collect($postPjRep?->poderes ?? $poderesDeCombate($personaje));
    $poderPrincipalPersonaje = $poderesPersonaje->first()->nombre ?? '';

    $nombreEnemigo = $enemigo->nombre ?? $enemigo->titulo ?? 'Enemigo';
    $poderesEnemigo = collect($postEnRep?->poderes ?? $poderesDeCombate($enemigo));
    $poderPrincipalEnemigo = $poderesEnemigo->first()->nombre ?? '';
    @endphp

    {{-- Poderes debajo de cada personaje: solo en pantallas chicas (en las grandes están al costado del escenario) --}}
    <div class="mb-6 flex md:hidden justify-between items-start gap-4 text-white w-full max-w-[650px] mx-auto px-[6%]">
      {{-- Poderes personaje --}}
      <div class="w-auto max-w-[48%] flex flex-col items-start text-left">
        <h4 class="text-lg font-semibold mb-2 text-white [text-shadow:0_2px_0_#000]">
          Poderes de<br>
          <span class="block text-yellow-400">{{ $nombrePersonajeUsuario }}</span>
        </h4>
     

        @if($poderesPersonaje->isNotEmpty())
        <div class="flex flex-wrap justify-start gap-1.5">
          @foreach($poderesPersonaje as $poder)
          <x-icono-poder :poder="$poder" tam="w-10 h-10" />
          @endforeach
        </div>
        @else
        <p class="text-gray-400 text-center italic">Sin poderes activos</p>
        @endif
      </div>

      {{-- Poderes enemigo --}}
      <div class="w-auto max-w-[48%] flex flex-col items-end text-right">
        <h4 class="text-lg font-semibold mb-2 text-white [text-shadow:0_2px_0_#000]">
          Poderes de<br>
          <span class="block text-yellow-400">{{ $nombreEnemigo }}</span>
        </h4>
     
        @if($poderesEnemigo->isNotEmpty())
        <div class="flex flex-wrap justify-end gap-1.5">
          @foreach($poderesEnemigo as $poder)
          <x-icono-poder :poder="$poder" tam="w-10 h-10" />
          @endforeach
        </div>
        @else
        <p class="text-gray-400 text-center italic">Sin poderes activos</p>
        @endif
      </div>
    </div>

@if($poderesPersonaje->contains(fn($p) => strtoupper($p->nombre) === 'COMBO VELOZ'))
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">Realiza un combo veloz!</span>
    </div>
@endif

@if($poderesEnemigo->contains(fn($p) => strtoupper($p->nombre) === 'COMBO VELOZ'))
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">Realiza un combo veloz!</span>
    </div>
@endif

{{-- Para el personaje --}}
@if($poderesPersonaje->contains(fn($p) => strtoupper($p->nombre) === 'CONTROL CLIMATICO'))
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border  border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">Realiza control climático en la zona!</span>
    </div>
@endif

{{-- Para el enemigo --}}
@if($poderesEnemigo->contains(fn($p) => strtoupper($p->nombre) === 'CONTROL CLIMATICO'))
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">Realiza Control Climático en la Zona!</span>
    </div>
@endif

{{-- Para el personaje --}}
@if($poderesPersonaje->contains(fn($p) => strtoupper($p->nombre) === 'ENEMISTAD'))
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">odia a <span class="text-yellow-400">{{ $nombreEnemigo }}</span></span>
    </div>
@endif

{{-- Para el enemigo --}}
@if($poderesEnemigo->contains(fn($p) => strtoupper($p->nombre) === 'ENEMISTAD'))
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">odia a <span class="text-yellow-400">{{ $nombrePersonajeUsuario }}</span></span>
    </div>
@endif

{{-- Para el personaje --}}
@if($poderesPersonaje->contains(fn($p) => strtoupper($p->nombre) === 'SUPER SENTIDOS'))
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border  border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">Tiene Super Sentidos</span>
    </div>
@endif

{{-- Para el enemigo --}}
@if($poderesEnemigo->contains(fn($p) => strtoupper($p->nombre) === 'SUPER SENTIDOS'))
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">Tiene Super Sentidos</span>
    </div>
@endif

{{-- Para el personaje --}}
@if($poderActivoFrenesiPersonaje)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">entra en <span class="text-white">Frenesí</span>!</span>
    </div>
@endif

{{-- Para el enemigo --}}
@if($poderActivoFrenesiEnemigo)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">entra en <span class="text-white">Frenesí</span>!</span>
    </div>
@endif

{{-- Para el personaje --}}
@if($poderActivoFuriaCiegaPersonaje)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">entra en <span class="text-white">Furia Ciega</span>!</span>
    </div>
@endif

{{-- Para el enemigo --}}
@if($poderActivoFuriaCiegaEnemigo)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">entra en <span class="text-white">Furia Ciega</span>!</span>
    </div>
@endif

@if($poderActivoTrancePersonaje)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">entra en <span class="text-white">Trance</span>!</span>
    </div>
@endif

{{-- Para el enemigo --}}
@if($poderActivoTranceEnemigo)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">entra en <span class="text-white">Trance</span>!</span>
    </div>
@endif

{{-- Para el personaje --}}
@if($poderActivoSuperNovaPersonaje)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">entró en <span class="text-white">Super Nova</span>!</span>
    </div>
@endif

{{-- Para el enemigo --}}
@if($poderActivoSuperNovaEnemigo)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">entró en <span class="text-white">Super Nova</span>!</span>
    </div>
@endif

{{-- Para el personaje --}}
@if($poderActivoSuperCargaPersonaje)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombrePersonajeUsuario }}</span>
        <span class="text-white font-bold">entra en <span class="text-white">Super Carga</span>!</span>
    </div>
@endif

{{-- Para el enemigo --}}
@if($poderActivoSuperCargaEnemigo)
    <div class="flex justify-center items-center gap-2 mb-4 px-4 py-2 border border-gray-700 bg-gray-800 bg-opacity-20 rounded-lg">
        <span class="text-yellow-400 font-semibold">{{ $nombreEnemigo }}</span>
        <span class="text-white font-bold">entra en <span class="text-white">Super Carga</span>!</span>
    </div>
@endif



    @php $rondasOrdenadas = collect($accionesPorRonda); @endphp
    <div class="w-full max-w-2xl mx-auto px-4">
      @foreach ($rondasOrdenadas as $numeroRonda => $accionesRonda)
      <div class="py-4 border-b border-white/30">
        <div class="text-center mb-2">
          <h4 class="text-base font-bold text-indigo-300 tracking-wider uppercase">Ronda {{ $numeroRonda }}</h4>
        </div>
        @php
        $skipNext = false; // Para saltar ronda siguiente en caso de contraataque
        @endphp

        @foreach ($accionesRonda as $index => $res)
        {{-- =========================
        Caso especial ronda 6 con atacante definido
        Mostrar texto y gif usando gifs oficiales
        ========================= --}}
        @if($res['ronda'] == 6 && isset($res['atacante']))
        @php
        $atacante = $res['atacante'];
        $nombreAtacante = $atacante === 'personaje' ? $personaje->nombre : ($enemigo->titulo ?? $enemigo->nombre ??
        'Enemigo');
        $nombreDefensor = $atacante === 'personaje' ? ($enemigo->titulo ?? $enemigo->nombre ?? 'Enemigo') :
        $personaje->nombre;

        $tipoAtaque = strtolower($res['tipo_ataque'] ?? 'normal');

        // Forzar un tipo de gif válido (si 'extra' no existe en gifs)
        $tipoGif = in_array($tipoAtaque, ['normal', 'critico', 'especial']) ? $tipoAtaque : 'normal';

        $gifAtaque = $atacante === 'personaje'
        ? ($gifsPersonaje[$tipoGif] ?? null)
        : ($gifsEnemigo[$tipoGif] ?? null);

        $claseFlip = $atacante === 'personaje' ? '' : 'scale-x-[-1]';

        $danioFisico = $res['danio_fisico'] ?? 0;
        $danioElemental = $res['danio_elemental'] ?? 0;
        $tipoAtacante = $atacante === 'personaje' ? $tipoVistaPersonaje : $tipoVistaEnemigo;
        @endphp

        {{-- <h3 class="text-2xl font-bold mb-2 text-center">Ronda 6</h3> --}}

        <p class="text-lg font-medium text-center mb-2 leading-relaxed">
          <strong class="text-indigo-600">{{ $nombreAtacante }}</strong> ataca a <strong>{{ $nombreDefensor }}</strong>.
        </p>
        @if ($tipoAtaque === 'especial')
        <p class="text-center text-yellow-400 text-xl italic mb-2 font-semibold">
          ¡Hace un movimiento especial!
        </p>
        @elseif ($tipoAtaque === 'critico')
        <p class="text-center text-red-400 text-xl italic mb-2 font-semibold">
          ¡Hace un golpe crítico!
        </p>
        @endif

        @if ($gifAtaque)
        <div class="flex justify-center items-end w-full min-h-[160px] overflow-hidden mt-2 mb-4">
          <img src="{{ asset('storage/' . $gifAtaque) }}" alt="Gif ataque ronda 6"
            style="{{ \App\Models\Post::estiloGif($gifAtaque) }}" class="block max-w-none {{ $claseFlip }}">
        </div>
        @endif

        <div class="text-center text-gray-300 mt-1 text-xl leading-relaxed font-semibold">
          @if($tipoAtacante === 'hibrido')
          <p><strong class="text-lg">Ha recibido:</strong></p>
          <p><x-icono-tipo tipo="fisico" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-red-400 text-xl font-bold">{{ $danioFisico }}</span> golpes y</p>
          <p><x-icono-tipo tipo="elemental" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-indigo-400 text-xl font-bold">{{ $danioElemental }}</span> puntos de daño elemental.</p>
          @elseif($tipoAtacante === 'elemental')
          <p><strong class="text-lg">Ha recibido:</strong> <x-icono-tipo tipo="elemental" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-indigo-400 text-xl font-bold">{{
              $danioElemental }}</span> puntos de daño elemental.</p>
          @else
          <p><strong class="text-lg">Ha recibido:</strong> <x-icono-tipo tipo="fisico" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-red-400 text-xl font-bold">{{ $danioFisico
              }}</span> golpes.</p>
          @endif
        </div>

        @continue
        @endif

        {{-- =========================
        Caso especial ronda 6 sin atacante definido
        Mostrar texto y gif manualmente
        ========================= --}}
        @if($res['ronda'] == 6 && !isset($res['atacante']))
        @php
        $atacante = $golpesPersonaje < $golpesEnemigo ? 'personaje' : 'enemigo' ;
          $nombreAtacante=$atacante==='personaje' ? $personaje->nombre : ($enemigo->titulo ?? $enemigo->nombre ??
          'Enemigo');
          $nombreDefensor = $atacante === 'personaje' ? ($enemigo->titulo ?? $enemigo->nombre ?? 'Enemigo') :
          $personaje->nombre;

          $tipoAtaque = 'normal'; // Puedes ajustar si quieres mostrar otro tipo
          $gifAtaque = $atacante === 'personaje'
          ? ($gifsPersonaje[$tipoAtaque] ?? null)
          : ($gifsEnemigo[$tipoAtaque] ?? null);
          $claseFlip = $atacante === 'personaje' ? '' : 'scale-x-[-1]';

          $danioFisico = $res['danio_fisico'] ?? 0;
          $danioElemental = $res['danio_elemental'] ?? 0;

          $tipoAtacante = $atacante === 'personaje' ? $tipoVistaPersonaje : $tipoVistaEnemigo;
          @endphp

          <h3 class="text-2xl font-bold mb-2 text-center">Ronda 6</h3>

          <p class="text-lg font-medium text-center mb-2 leading-relaxed">
            <strong class="text-indigo-600">{{ $nombreAtacante }}</strong> ataca a <strong>{{ $nombreDefensor
              }}</strong>.
          </p>

          @if ($gifAtaque)
          <div class="flex justify-center items-end w-full min-h-[160px] overflow-hidden mt-2 mb-4">
            <img src="{{ asset('storage/' . $gifAtaque) }}" alt="Gif ataque ronda 6"
              style="{{ \App\Models\Post::estiloGif($gifAtaque) }}" class="block max-w-none {{ $claseFlip }}">
          </div>
          @endif

          <div class="text-center text-gray-300 mt-1 text-xl leading-relaxed font-semibold">
            @if($tipoAtacante === 'hibrido')
            <p><strong class="text-lg">Ha recibido:</strong></p>
            <p><x-icono-tipo tipo="fisico" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-red-400 text-xl font-bold">{{ $danioFisico }}</span> golpes y</p>
            <p><x-icono-tipo tipo="elemental" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-indigo-400 text-xl font-bold">{{ $danioElemental }}</span> puntos de daño elemental.
            </p>
            @elseif($tipoAtacante === 'elemental')
            <p><strong class="text-lg">Ha recibido:</strong> <x-icono-tipo tipo="elemental" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-indigo-400 text-xl font-bold">{{
                $danioElemental }}</span> puntos de daño elemental.</p>
            @else
            <p><strong class="text-lg">Ha recibido:</strong> <x-icono-tipo tipo="fisico" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-red-400 text-xl font-bold">{{
                $danioFisico }}</span> golpes.</p>
            @endif
          </div>

          @continue
          @endif

          {{-- ======= Código para las demás rondas ======= --}}

          @php
          $tipoAtaque = strtolower($res['tipo_ataque'] ?? 'normal');
          $esBloqueo = $tipoAtaque === 'bloqueo';
          $esContra = $tipoAtaque === 'contraataque';

          $nombreAtacante = $res['atacante'] === 'personaje' ? $personaje->nombre : ($enemigo->titulo ??
          $enemigo->nombre ?? 'Enemigo');
          $nombreDefensor = $res['atacante'] === 'personaje' ? ($enemigo->titulo ?? $enemigo->nombre ?? 'Enemigo') :
          $personaje->nombre;

          $siguiente = $accionesRonda[$index + 1] ?? null;
          $hayContra = $siguiente && strtolower($siguiente['tipo_ataque'] ?? '') === 'contraataque';

          $bloqueadoYContra = $tipoAtaque === 'normal' && $res['danio'] == 0 && $hayContra && isset($res['defensor']);

          // Tipo de daño del que ataca: el de la pelea (ver $tipoVistaPersonaje arriba), no el del set equipado ahora
          $tipoAtacante = $res['atacante'] === 'personaje' ? $tipoVistaPersonaje : $tipoVistaEnemigo;
          @endphp

          {{-- <h3 class="text-2xl font-bold mb-2 text-center">Ronda {{ $res['ronda'] }}</h3> --}}

          {{-- Mensaje de ataque --}}
          @if ($tipoAtaque === 'normal' || $tipoAtaque === 'especial' || $tipoAtaque === 'critico')
          <p class="text-lg font-medium text-center mb-2 leading-relaxed">
            <strong class="text-indigo-600">{{ $nombreAtacante }}</strong> ataca a <strong>{{ $nombreDefensor
              }}</strong>.
          </p>

          @if ($tipoAtaque === 'especial')
          <p class="text-center text-yellow-400 text-xl italic mb-2 font-semibold">
            ¡Hace un movimiento especial!
          </p>
          @elseif ($tipoAtaque === 'critico')
          <p class="text-center text-red-400 text-xl italic mb-2 font-semibold">
            ¡Hace un golpe crítico!
          </p>
          @endif
          @endif

          {{-- Mostrar ataque si no fue bloqueado con contraataque (el rebote se muestra aparte, más abajo) --}}
          @if (!$bloqueadoYContra && !$esBloqueo && !$esContra && $tipoAtaque !== 'rebote' && !is_null($res['gif']))
          @php
          $gifAtaque = $res['atacante'] === 'personaje'
          ? ($gifsPersonaje[$tipoAtaque] ?? null)
          : ($gifsEnemigo[$tipoAtaque] ?? null);
          $claseFlip = $res['atacante'] === 'personaje' ? '' : 'scale-x-[-1]';

          $danioFisico = $res['danio_fisico'] ?? 0;
          $danioElemental = $res['danio_elemental'] ?? 0;
          @endphp

          @if ($gifAtaque)
          <div class="flex justify-center items-end w-full min-h-[160px] overflow-hidden mt-2 mb-4">
            <img src="{{ asset('storage/' . $gifAtaque) }}" alt="Gif ataque"
              style="{{ \App\Models\Post::estiloGif($gifAtaque) }}" class="block max-w-none {{ $claseFlip }}">
          </div>
          @endif

          {{-- Mostrar texto contextual del daño --}}
          <div class="text-center text-gray-300 mt-1 text-xl leading-relaxed font-semibold">
            @if(strtolower($siguiente['tipo_ataque'] ?? '') === 'rebote')
            {{-- El golpe no le hizo daño: lo resistió y abajo se lo rebota --}}
            <p><strong class="text-amber-400">{{ $nombreDefensor }}</strong> resiste el golpe y no recibe daño.</p>
            @elseif($tipoAtacante === 'hibrido')
            <p><strong class="text-lg">Ha recibido:</strong></p>
            <p><x-icono-tipo tipo="fisico" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-red-400 text-xl font-bold">{{ $danioFisico }}</span> golpes y</p>
            <p><x-icono-tipo tipo="elemental" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-indigo-400 text-xl font-bold">{{ $danioElemental }}</span> puntos de daño elemental.
            </p>
            @elseif($tipoAtacante === 'elemental')
            <p><strong class="text-lg">Ha recibido:</strong> <x-icono-tipo tipo="elemental" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-indigo-400 text-xl font-bold">{{
                $danioElemental }}</span> puntos de daño elemental.</p>
            @else
            <p><strong class="text-lg">Ha recibido:</strong> <x-icono-tipo tipo="fisico" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-red-400 text-xl font-bold">{{
                $danioFisico }}</span> golpes.</p>
            @endif
          </div>
          @endif

          {{-- Defensa y contraataque --}}
          @if ($esBloqueo)
          @php
          $gifDefensa = $res['atacante'] === 'personaje' ? $gifsPersonaje['defensa'] : $gifsEnemigo['defensa'];
          $claseFlipDef = $res['atacante'] === 'personaje' ? '' : 'scale-x-[-1]';
          @endphp

          <p class="text-lg font-medium text-center mb-4 leading-relaxed">
            <strong class="text-indigo-600">{{ $nombreAtacante }}</strong> bloqueó el golpe de <strong
              class="text-indigo-600">{{ $nombreDefensor }}</strong>.
          </p>

          @if ($gifDefensa)
          <div class="flex justify-center items-end w-full min-h-[160px] overflow-hidden mt-2 mb-4">
            <img src="{{ asset('storage/' . $gifDefensa) }}" alt="Gif defensa"
              style="{{ \App\Models\Post::estiloGif($gifDefensa) }}" class="block max-w-none {{ $claseFlipDef }}">
          </div>
          @endif

          @if ($hayContra)
          @php
          $contra = $accionesRonda[$index + 1];
          $nombreContra = $contra['atacante'] === 'personaje' ? $personaje->nombre : ($enemigo->titulo ??
          $enemigo->nombre ?? 'Enemigo');
          $gifContra = $contra['atacante'] === 'personaje' ? $gifsPersonaje['contraataque'] :
          $gifsEnemigo['contraataque'];
          $claseFlipContra = $contra['atacante'] === 'personaje' ? '' : 'scale-x-[-1]';

          // Tipo de daño del contraataque: el de la pelea, no el del set equipado ahora
          $tipoAtacanteContra = $contra['atacante'] === 'personaje' ? $tipoVistaPersonaje : $tipoVistaEnemigo;

          $danioFisicoContra = $contra['danio_fisico'] ?? 0;
          $danioElementalContra = $contra['danio_elemental'] ?? 0;
          @endphp

          <p class="text-lg font-medium text-center mb-4 leading-relaxed">
            <strong class="text-indigo-600">{{ $nombreContra }}</strong> realiza un <span
              class="text-green-500 font-bold">contraataque</span>!
          </p>

          @if ($gifContra)
          <div class="flex justify-center items-end w-full min-h-[160px] overflow-hidden mt-2 mb-4">
            <img src="{{ asset('storage/' . $gifContra) }}" alt="Gif contraataque"
              style="{{ \App\Models\Post::estiloGif($gifContra) }}" class="block max-w-none {{ $claseFlipContra }}">
          </div>
          @endif

          {{-- Daño contextual del contraataque --}}
          <div class="text-center text-gray-300 mt-1 text-xl leading-relaxed font-semibold">
            @if($tipoAtacanteContra === 'hibrido')
            <p><strong class="text-lg">Ha recibido:</strong></p>
            <p><x-icono-tipo tipo="fisico" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-red-400 text-xl font-bold">{{ $danioFisicoContra }}</span> golpes y</p>
            <p><x-icono-tipo tipo="elemental" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-indigo-400 text-xl font-bold">{{ $danioElementalContra }} puntos de daño
              elemental</span></p>
            @elseif($tipoAtacanteContra === 'elemental')
            <p><strong class="text-lg">Ha recibido:</strong> <x-icono-tipo tipo="elemental" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-indigo-400 text-xl font-bold">{{
                $danioElementalContra }} puntos de daño elemental.</span></p>
            @else
            <p><strong class="text-lg">Ha recibido:</strong> <x-icono-tipo tipo="fisico" tam="w-7 h-7" class="align-[-0.35em]" /> <span class="text-red-400 text-xl font-bold">{{
                $danioFisicoContra }}</span> golpes.</p>
            @endif
          </div>

          @php $skipNext = true; @endphp
          @endif
          @endif

          {{-- Rebote (resistencia): el que resiste con su gif base y al que le rebota, con su gif de derrota --}}
          @if ($tipoAtaque === 'rebote')
          @php
          // En el rebote, 'atacante' es el que resistió; el que recibe el rebote es el otro
          $gifResiste = $res['atacante'] === 'personaje' ? ($gifsPersonaje['base'] ?? null) : ($gifsEnemigo['base'] ?? null);
          $gifRebotado = $res['atacante'] === 'personaje' ? ($gifsEnemigo['derrota'] ?? null) : ($gifsPersonaje['derrota'] ?? null);
          $claseFlipResiste = $res['atacante'] === 'personaje' ? '' : 'scale-x-[-1]';
          $claseFlipRebotado = $res['atacante'] === 'personaje' ? 'scale-x-[-1]' : '';
          @endphp

          <p class="text-lg font-medium text-center mb-2 leading-relaxed">
            <strong class="text-indigo-600">{{ $nombreAtacante }}</strong> le <span class="text-amber-400 font-bold">rebota</span>
            el golpe a <strong>{{ $nombreDefensor }}</strong>!
          </p>

          <div class="flex justify-center items-end gap-2 w-full min-h-[160px] overflow-hidden mt-2 mb-4">
            @if ($gifResiste)
              <img src="{{ asset('storage/' . $gifResiste) }}" alt="Gif resiste"
                style="{{ \App\Models\Post::estiloGif($gifResiste) }}" class="block max-w-none {{ $claseFlipResiste }}">
            @endif
            @if ($gifRebotado)
              <img src="{{ asset('storage/' . $gifRebotado) }}" alt="Gif rebotado"
                style="{{ \App\Models\Post::estiloGif($gifRebotado) }}" class="block max-w-none {{ $claseFlipRebotado }}">
            @endif
          </div>

          <div class="text-center text-gray-300 mt-1 text-xl leading-relaxed font-semibold">
            <p><strong class="text-lg">{{ $nombreDefensor }} ha recibido:</strong> <span class="text-amber-400 text-xl font-bold">{{ $res['danio'] ?? 0 }}</span> de daño rebotado.</p>
          </div>
          @endif

          @if ($skipNext)
          @php $skipNext = false; @endphp
          @continue
          @endif
          @endforeach


      </div>
      @endforeach

      @php
      // Daño extra (de poderes) que hizo enemigo -> recibido por personaje, incluyendo Ataque Desesperado
      $danioPoderesEnemigo = ($danioExtraTotalEnemigo ?? 0) + ($danioAtaqueDesesperadoEnemigo ?? 0);

      // Daño extra (de poderes) que hizo personaje -> recibido por enemigo, incluyendo Ataque Desesperado
      $danioPoderesPersonaje = ($danioExtraTotalPersonaje ?? 0) + ($danioAtaqueDesesperadoPersonaje ?? 0);

      // Daño normal total que personaje hizo al enemigo (sin daño extra)
      $danioTotalPersonaje = $totalDanioPersonaje ?? 0;

      // Daño normal total que enemigo hizo al personaje (sin daño extra)
      $danioTotalEnemigo = $totalDanioEnemigo ?? 0;

      // Regeneración aplicada por absorción de salud
      $absorcionPersonaje = $absorcionTotalPersonaje ?? 0;
      $absorcionEnemigo = $absorcionTotalEnemigo ?? 0;
      @endphp


      <div class="mt-6 max-w-md w-full mx-auto text-white font-semibold text-lg">
        <div class="bg-gradient-to-b from-[#1c2533] to-[#0a0e14] border border-black p-4 rounded-xl mb-8 text-center space-y-2 shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]">

          {{-- Mostrar daño extra causado por poderes --}}
          @if($danioPoderesEnemigo > 0 || $danioPoderesPersonaje > 0)
          <div
            class="border border-indigo-500 rounded-md px-4 py-3 text-sm text-purple-300 bg-purple-900 bg-opacity-20 mb-4 space-y-1 text-center">
            @if($danioPoderesEnemigo > 0)
            <p>
              <span class="font-bold">{{ $personaje->nombre }}</span> ha recibido
              <span class="font-bold">{{ $danioPoderesEnemigo }}</span> puntos de daño directo
            </p>
            @endif

            @if($danioPoderesPersonaje > 0)
            <p>
              <span class="font-bold">{{ $enemigo->nombre }}</span> ha recibido
              <span class="font-bold">{{ $danioPoderesPersonaje }}</span> puntos de daño directo
            </p>
            @endif
          </div>
          @endif


          {{-- Mostrar regeneración solo si hubo absorción --}}
          @if($absorcionPersonaje > 0 || $absorcionEnemigo > 0)
          <div
            class="border border-indigo-500 rounded-md px-4 py-3 text-sm text-purple-300 bg-purple-900 bg-opacity-20 mb-4 space-y-1 text-center">
            @if($absorcionPersonaje > 0)
            <p>
              <span class="font-bold">{{ $personaje->nombre }}</span> se ha regenerado
              <span class="text-green-400 font-bold">{{ $absorcionPersonaje }}</span> puntos de daño
            </p>
            @endif

            @if($absorcionEnemigo > 0 && $enemigo)
            <p>
              <span class="font-bold">{{ $enemigo->nombre }}</span> se ha regenerado
              <span class="text-green-400 font-bold">{{ $absorcionEnemigo }}</span> puntos de daño
            </p>
            @endif

          </div>
          @endif


          @if($fueCongeladoEnEstaRonda)
              <div class="text-center text-blue-200 bg-blue-900 bg-opacity-30 border border-blue-400 px-3 py-2 rounded mb-3 text-sm font-semibold">
                  ❄️ Has sido congelado: tu ENE y VEL se reducen un 15% por 30 minutos.
              </div>
          @endif

          @if($fueAturdidoEnEstaRonda)
              <div class="text-center text-red-200 bg-red-900 bg-opacity-30 border border-red-400 px-3 py-2 rounded mb-3 text-sm font-semibold">
                  💫 Has sido aturdido: tu FUE y VEL se reducen un 15% por 30 minutos.
              </div>
          @endif

          @if($fueEnvenenadoEnEstaRonda)
            <div class="text-center text-green-200 bg-green-900 bg-opacity-30 border border-green-400 px-3 py-2 rounded mb-3 text-sm font-semibold">
                ☠️ Has sido envenenado: tu FUE y DEF se reducen un 15% por 30 minutos.
            </div>
          @endif

            @if($fueDesangradoEnEstaRonda)
            <div class="text-center text-purple-200 bg-purple-900 bg-opacity-30 border border-purple-400 px-3 py-2 rounded mb-3 text-sm font-semibold">
                🩸 Has sido Desangrado: tu FUE y ENE se reducen un 15% por 30 minutos.
            </div>
          @endif

           @if($fueParalizadoEnEstaRonda)
            <div class="text-center text-yellow-200 bg-yellow-900 bg-opacity-30 border border-yellow-400 px-3 py-2 rounded mb-3 text-sm font-semibold">
                ⚡ Has sido Paralizado: tu VEL y ATA se reducen un 15% por 30 minutos.
            </div>
          @endif

           @if($fueQuemadoEnEstaRonda)
            <div class="text-center text-red-300 bg-red-900 bg-opacity-30 border border-red-500 px-3 py-2 rounded mb-3 text-sm font-semibold">
                🔥 Has sido Quemado: tu RES y DEF se reducen un 15% por 30 minutos.
            </div>
          @endif


          {{-- Daño total recibido (daño normal + daño extra) --}}
          <p class="text-lg">
            <span class="font-bold">{{ $personaje->nombre }}</span> ha recibido
            <span class="text-red-400 font-extrabold">{{ $danioTotalEnemigo }}</span> puntos de daño
          </p>

          @if($enemigo)
          <p class="text-lg">
            <span class="font-bold">{{ $enemigo->nombre }}</span> ha recibido
            <span class="text-red-400 font-extrabold">{{ $danioTotalPersonaje }}</span> puntos de daño
          </p>
          @endif

          @if($resultadoFinal)
          <div>
            @if($resultadoFinal === 'Victoria')
            <p class="text-green-400 font-bold">¡Has ganado la batalla!</p>
            @elseif($resultadoFinal === 'Derrota')
            <p class="text-red-500 font-bold">Has perdido la batalla...</p>
            @else
            <p class="text-yellow-400 font-bold">¡Empate!</p>
            @endif
          </div>
          @endif


          {{-- Recompensas solo si gana --}}
          @if($danioTotalPersonaje > $danioTotalEnemigo)
          <div class="mt-4 p-3 rounded-lg border border-black bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] space-y-1">
          <p>
            <span class="font-bold">{{ $personaje->nombre }}</span> ha recibido:
          </p>
          <p class="text-lg font-semibold">
            <span style="color: #00ff00;">
              {{ number_format($recompensas['exp'] ?? 0, 0, '.', '.') }}
            </span>

            <span class="text-white">puntos de experiencia.</span>
          </p>
          <p class="text-lg font-semibold flex items-center justify-center gap-2">
            <img src="{{ asset('images/oro.png') }}" alt="Oro" class="w-5 h-5">
            <span style="color:#ffd700">{{ number_format($recompensas['oro'] ?? 0) }}</span>
            <span class="text-white">monedas de oro.</span>
          </p>
          @if(!empty($recompensas['diamante']))
          <p class="text-xl font-semibold flex items-center justify-center gap-2">
            <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="w-6 h-6">
            <span style="color:#00ffff">{{ number_format($recompensas['diamante'] ?? 0) }}</span>
            <span class="text-white">esmeraldas.</span>
          </p>
        
          @endif
          </div>
         
          @else
          <div class="mt-4 text-yellow-300 font-semibold">
            No obtuviste recompensas en esta batalla.
          </div>
          @endif
        </div>


      </div>
    </div>
    @if($ciudadActual && $ciudadActual->gif)
    @php
    $equipo = $personaje->equipo;
    $entrenamiento = $personaje->entrenamiento;
    $accesorio = $personaje->accesorio;

    $gifVictoriaPersonaje = $personaje->post->gif_victoria ?? null;
    $gifDerrotaPersonaje = $personaje->post->gif_derrota ?? null;

    if (
    $equipo && $entrenamiento && $accesorio &&
    $equipo->origen_post_id === $entrenamiento->origen_post_id &&
    $equipo->origen_post_id === $accesorio->origen_post_id
    ) {
    $postSet = \App\Models\Post::find($equipo->origen_post_id);
    if ($postSet) {
    $gifVictoriaPersonaje = $postSet->gif_victoria ?? $gifVictoriaPersonaje;
    $gifDerrotaPersonaje = $postSet->gif_derrota ?? $gifDerrotaPersonaje;
    }
    }

    // Si el rival es un jugador (PvP), sus gifs están en su set
    $postGifsEnemigo = $postEnRep ?? ($enemigo instanceof \App\Models\Personaje ? ($enemigo->postDeCombate() ?? $enemigo) : $enemigo);
    $gifVictoriaEnemigo = $postGifsEnemigo->gif_victoria ?? null;
    $gifDerrotaEnemigo = $postGifsEnemigo->gif_derrota ?? null;

    // Pelea guardada: los gifs del set con el que peleó ese día
    if ($postPjRep) {
        $gifVictoriaPersonaje = $postPjRep->gif_victoria ?? $gifVictoriaPersonaje;
        $gifDerrotaPersonaje = $postPjRep->gif_derrota ?? $gifDerrotaPersonaje;
    }
    @endphp
    

    <div class="w-full flex flex-col items-center mt-6 space-y-6">

      <div
        class="relative rounded-xl overflow-hidden select-none w-full max-w-[650px] min-h-[240px] pt-6">

      <div class="w-full h-full">
    {{-- Fondo de la ciudad (absoluto contra el contenedor de 240px) --}}
    <img src="{{ asset('storage/posts/' . ($escenarioMision ?? $ciudadActual->gif)) }}"
        alt="GIF de la ciudad"
        class="absolute inset-0 w-full h-full object-cover object-bottom z-0" />
@php
    $equipo = $personaje->equipo;
    $entrenamiento = $personaje->entrenamiento;
    $accesorio = $personaje->accesorio;

    $mostrarImagenCompleta = false;
    $imagenMostrar = $personaje->post?->imagen ?? $personaje->imagen; // foto del set base (la misma que en el ranking)

    // Si los 3 objetos son del mismo origen_post_id, busco la imagen de ese post (el conjunto)
    if ($equipo && $entrenamiento && $accesorio) {
        if (
            $equipo->origen_post_id &&
            $equipo->origen_post_id === $entrenamiento->origen_post_id &&
            $equipo->origen_post_id === $accesorio->origen_post_id
        ) {
            $postCompleto = \App\Models\Post::find($equipo->origen_post_id);
            if ($postCompleto && $postCompleto->imagen) {
                $imagenMostrar = $postCompleto->imagen;
                $mostrarImagenCompleta = true;
            }
        }
    }
    // Pelea guardada: la foto del set con el que peleó ese día
    if ($postPjRep?->imagen) {
        $imagenMostrar = $postPjRep->imagen;
    }
@endphp

{{-- Mostrar gif del personaje o enemigo con nombre y frase arriba de la ciudad --}}
<div class="absolute top-2 left-1/2 transform -translate-x-1/2 z-20">
    @if($danioTotalPersonaje > $danioTotalEnemigo)
        {{-- Personaje ganó --}}
        <div class="flex items-center bg-black bg-opacity-60 px-3 py-2 rounded-xl shadow-lg space-x-3">
            {{-- Imagen del personaje con equipo completo --}}
            <img src="{{ asset('storage/' . $imagenMostrar) }}" alt="Personaje equipado"
                class="w-16 h-16 rounded-full shadow-md object-cover">

            <div class="flex flex-col">
                {{-- Nombre --}}
                <h3 class="text-sm text-white font-bold">{{ $personaje->nombre }}</h3>

                {{-- Frase (si existe) --}}
                @if($personaje->frase)
                    <p class="text-[15px] text-white italic mt-1">“{{ $personaje->frase }}”</p>
                @endif
            </div>
        </div>
    @else
        {{-- Personaje perdió, mostrar enemigo --}}
        <div class="flex items-center bg-black bg-opacity-60 px-3 py-2 rounded-xl shadow-lg space-x-3">
            {{-- Imagen enemigo --}}
            @php $imagenEnemigoFinal = $postEnRep?->imagen ?? ($enemigo instanceof \App\Models\Personaje ? ($enemigo->postDeCombate()?->imagen ?? $enemigo->imagen) : $enemigo->imagen); @endphp
            <img src="{{ asset('storage/' . ($imagenEnemigoFinal ?? 'default-enemigo.png')) }}" alt="Enemigo"
                class="w-16 h-16 rounded-full shadow-md object-cover">

            <div class="flex flex-col">
                {{-- Nombre enemigo --}}
                <h3 class="text-sm text-white font-bold">{{ $enemigo->nombre ?? $enemigo->titulo ?? 'Enemigo desconocido' }}</h3>
            </div>
        </div>
    @endif
</div>

</div>

        {{-- GIFs del resultado (abajo en las puntas) --}}
        @if($totalDanioPersonaje > $totalDanioEnemigo)

        {{-- Victoria del personaje --}}
        <div class="absolute bottom-1 left-[3%] sm:left-[8%] z-10">
          <img src="{{ asset('storage/' . $gifVictoriaPersonaje) }}" alt="GIF Victoria Personaje"
            style="{{ \App\Models\Post::estiloGif($gifVictoriaPersonaje) }}" class="block max-w-none">
        </div>
        <div class="absolute bottom-1 right-[3%] sm:right-[8%] z-10 scale-x-[-1]">
          <img src="{{ asset('storage/' . $gifDerrotaEnemigo) }}" alt="GIF Derrota Enemigo"
            style="{{ \App\Models\Post::estiloGif($gifDerrotaEnemigo) }}" class="block max-w-none">
        </div>
        @elseif($totalDanioPersonaje < $totalDanioEnemigo) {{-- Victoria del enemigo --}} <div
          class="absolute bottom-1 left-[3%] sm:left-[8%] z-10 ">
          <img src="{{ asset('storage/' . $gifDerrotaPersonaje) }}" alt="GIF Derrota Personaje"
            style="{{ \App\Models\Post::estiloGif($gifDerrotaPersonaje) }}" class="block max-w-none drop-shadow-md">
      </div>
      <div class="absolute bottom-1 right-[3%] sm:right-[8%] z-10 scale-x-[-1]">
        <img src="{{ asset('storage/' . $gifVictoriaEnemigo) }}" alt="GIF Victoria Enemigo"
          style="{{ \App\Models\Post::estiloGif($gifVictoriaEnemigo) }}" class="block max-w-none drop-shadow-md">
      </div>
      @else
      {{-- Empate --}}
      <div class="absolute bottom-1 left-[3%] sm:left-[8%] z-10">
        <img src="{{ asset('storage/' . $gifVictoriaPersonaje) }}" alt="GIF Empate Personaje"
          style="{{ \App\Models\Post::estiloGif($gifVictoriaPersonaje) }}" class="block max-w-none drop-shadow-md">
      </div>
      <div class="absolute bottom-1 right-[3%] sm:right-[8%] z-10 scale-x-[-1] ">
        <img src="{{ asset('storage/' . $gifVictoriaEnemigo) }}" alt="GIF Empate Enemigo"
          style="{{ \App\Models\Post::estiloGif($gifVictoriaEnemigo) }}" class="block max-w-none drop-shadow-md">
      </div>
      @endif
    </div>

    {{-- DROP solo si gana y hay drop --}}
    @if($danioTotalPersonaje > $danioTotalEnemigo && isset($recompensas['drop']) && $recompensas['drop'])
  <div class=" -top-4 w-fit min-w-[150px] mt-2 bg-gradient-to-b from-[#1c2533] to-[#0a0e14] border border-black rounded-xl shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] px-3 pt-2 pb-10 flex flex-col items-center text-center text-yellow-100 relative text-sm leading-tight space-y-1">
  <div class="font-bold">Obtuviste</div>
  {{-- Partes de set con el color de su ranura (Equipo índigo, Entrenamiento verde, Accesorio rosa); lo demás, dorado --}}
  @php
    [$bordeDrop, $textoDrop] = [
      'equipo' => ['border-indigo-500', 'text-indigo-400'],
      'entrenamiento' => ['border-green-500', 'text-green-400'],
      'accesorio' => ['border-pink-500', 'text-pink-400'],
    ][$recompensas['drop']['tipo'] ?? ''] ?? ['border-yellow-300', null];
  @endphp

  <div class="flex justify-center">
    <img src="{{ $recompensas['drop']['tipo'] === 'pocion' 
        ? asset('images/' . $recompensas['drop']['imagen']) 
        : Storage::url('posts/' . $recompensas['drop']['imagen']) }}"
      alt="{{ $recompensas['drop']['nombre'] ?? 'Objeto' }}"
      class="w-28 h-28 object-cover rounded-md border-2 {{ $bordeDrop }} shadow-[0_0_0_1px_#000,0_3px_0_#000,0_4px_8px_rgba(0,0,0,0.7)]" />
  </div>

  {{-- Mostrar tipo + nombre en una sola línea --}}
  @if(isset($recompensas['drop']['nombre']))
  <div class="text-white font-semibold text-sm">
    {{ $recompensas['drop']['nombre'] }}
  </div>
  @if($textoDrop)
  <div class="{{ $textoDrop }} text-[11px] font-bold uppercase tracking-wider">{{ $recompensas['drop']['tipo'] }}</div>
  @endif
@endif

  @if($recompensas['drop']['tipo'] === 'pocion')
  {{-- Mostrar descripción especial para poción sin repetir nombre --}}
  <div class="text-xs text-blue-300 mt-1 space-y-1">
    {{-- Usos --}}
    <div class="text-purple-400">
      Usos: {{ $recompensas['drop']['stats']['usos_restantes'] ?? 1 }}/{{ $recompensas['drop']['stats']['usos_totales'] ?? 1 }}
    </div>

    {{-- Descripción --}}
    @if(!empty($recompensas['drop']['descripcion']))
    <div class="italic text-blue-200">
      {{ $recompensas['drop']['descripcion'] }}
    </div>
    @endif
  </div>
  @else
  @if(!empty($recompensas['drop']['stats']) && is_array($recompensas['drop']['stats']))
  <ul class="text-yellow-200 text-xs flex flex-wrap justify-center gap-1">
    @foreach($recompensas['drop']['stats'] as $stat => $value)
    @if($value)
    <li class="capitalize px-2 py-0.5 mt-1 rounded whitespace-nowrap border border-black bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000]">
      {{ str_replace('_', ' ', $stat) }} +{{ $value }}
    </li>
    @endif
    @endforeach
  </ul>
  @endif
  @endif

  {{-- Nivel + Requisitos --}}
  <div class="absolute bottom-1 left-2 right-2 flex justify-between items-center text-[10px]">

    {{-- Nivel --}}
    @if(isset($recompensas['drop']['nivel']))
    <span class="px-1.5 py-0.5 rounded text-white font-semibold border border-black bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000]">
      Nivel {{ $recompensas['drop']['nivel'] }}
    </span>
    @endif

    {{-- Requisitos si existen --}}
    @if(!empty($recompensas['drop']['requisitos']) && is_array($recompensas['drop']['requisitos']))
    <span class="px-1.5 py-0.5 rounded text-white font-semibold border border-black bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_0_#000]">
      @foreach($recompensas['drop']['requisitos'] as $stat => $valor)
      @if($valor > 0)
      {{ ucfirst($stat) }} {{ $valor }}
      @endif
      @endforeach
    </span>
    @endif
  </div>
</div>

    @endif


</div>
@endif

  {{-- Compartir la pelea en el chat (explorar, misión, torre, caza o PvP); solo las propias --}}
  @if (! empty($idPeleaCompartir))
    <div class="flex justify-center mt-4 mb-2">
      <button type="button" wire:click="compartirPelea({{ $idPeleaCompartir }})" @click.stop wire:loading.attr="disabled" title="Compartir en el chat"
        class="px-4 py-1.5 rounded-lg border border-black text-white text-sm font-bold bg-gradient-to-b from-[#2f5470] to-[#0a1a26]
               shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100 disabled:opacity-50">
        💬 Compartir en el chat
      </button>
    </div>
  @endif

  </div>
@endif
