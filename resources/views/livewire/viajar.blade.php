<div class="text-white p-4 max-w-6xl mx-auto">
    @php
        // Estilos 3D (mismos que Extra / Casino)
        $panel3d  = 'bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]';
        $card3d   = 'bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)]';
        $boton3d  = 'font-bold rounded border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:brightness-100 disabled:active:translate-y-0 disabled:active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
        $numDiamante = 'font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]';
        // $costoTeleport lo manda el componente: 0 si tiene Teletransportarse
    @endphp

    <h2 class="text-2xl font-bold mb-3 text-center text-indigo-300 [text-shadow:0_2px_0_#000]">🌍 Viajar</h2>

    {{-- Recursos del personaje --}}
    <div class="flex justify-center gap-3 mb-6 text-sm">
        <div class="{{ $card3d }} px-3 py-1 rounded-lg border border-yellow-600 flex items-center gap-2">
            <img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-5 w-5 [filter:drop-shadow(1px_2px_0_#000)]">
            <span class="font-bold text-yellow-300 [text-shadow:1px_1px_0_#000]">{{ number_format($personaje->oro, 0, ',', '.') }}</span>
        </div>
        <div class="{{ $card3d }} px-3 py-1 rounded-lg border border-cyan-600 flex items-center gap-2">
            <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="h-5 w-5 [filter:drop-shadow(1px_2px_0_#000)]">
            <span class="{{ $numDiamante }}">{{ number_format($personaje->diamante, 0, ',', '.') }}</span>
        </div>
    </div>

   @if (session()->has('message'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 3000)"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-8"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-8"
        class="fixed top-4 right-4 z-[100] max-w-xs w-full px-4 py-3 rounded-lg bg-green-600 border-l-4 border-green-300 text-white flex items-center gap-3 shadow-2xl"
        role="alert"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <span class="text-sm font-semibold">{{ session('message') }}</span>
        <button
            @click="show = false"
            class="ml-auto text-white/70 hover:text-white focus:outline-none"
            aria-label="Cerrar mensaje"
        >
            &times;
        </button>
    </div>
@endif

@if (session()->has('error'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 3000)"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-8"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-8"
        class="fixed top-4 right-4 z-[100] max-w-xs w-full px-4 py-3 rounded-lg bg-red-600 border-l-4 border-red-300 text-white flex items-center gap-3 shadow-2xl"
        role="alert"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span class="text-sm font-semibold">{{ session('error') }}</span>
        <button
            @click="show = false"
            class="ml-auto text-white/70 hover:text-white focus:outline-none"
            aria-label="Cerrar mensaje"
        >
            &times;
        </button>
    </div>
@endif

@if($personaje->estadosTemporales->where('estado', 'Congelado')->filter(fn($e) => $e->estaActivo())->isNotEmpty())
    <div class="text-blue-200 text-xs font-semibold bg-blue-900 bg-opacity-30 border border-blue-400 rounded px-3 py-2 mb-3 text-center">
        ❄️ Estás congelado y no puedes viajar por el momento.
    </div>
@endif

@if($personaje->estadosTemporales->where('estado', 'Aturdido')->filter(fn($e) => $e->estaActivo())->isNotEmpty())
    <div class="text-red-200 text-xs font-semibold bg-red-900 bg-opacity-30 border border-red-400 rounded px-3 py-2 mb-3 text-center">
    💫 Estás Aturdido y no puedes viajar por el momento.
    </div>
@endif

@if($personaje->estadosTemporales->where('estado', 'Envenenado')->filter(fn($e) => $e->estaActivo())->isNotEmpty())
    <div class="text-green-200 text-xs font-semibold bg-green-900 bg-opacity-30 border border-green-400 rounded px-3 py-2 mb-3 text-center">
    ☠️ Estás Envenenado y no puedes viajar por el momento.
    </div>
@endif

@if($personaje->estadosTemporales->where('estado', 'Paralizado')->filter(fn($e) => $e->estaActivo())->isNotEmpty())
    <div class="text-yellow-200 text-xs font-semibold bg-yellow-900 bg-opacity-30 border border-yellow-400 rounded px-3 py-2 mb-3 text-center">
    ⚡ Estás Paralizado y no puedes viajar por el momento.
    </div>
@endif


    @php
        use Carbon\Carbon;
        $viajando = $personaje->viajando_hasta && now()->lessThan(Carbon::parse($personaje->viajando_hasta));
        $tiempoRestante = $viajando ? Carbon::parse($personaje->viajando_hasta)->diffForHumans(null, true) : null;
    @endphp

    @if ($viajando)
        <div class="{{ $panel3d }} max-w-md mx-auto mb-6 p-4 rounded-xl border border-indigo-500 text-center">
            <p class="text-xs uppercase tracking-wide text-indigo-300 font-bold">✈️ Estás viajando</p>

            @if ($ciudadDestino)
                <h3 class="text-xl font-bold mt-1 text-white [text-shadow:0_2px_0_#000]">{{ $ciudadDestino->nombre }}</h3>
                <div class="mt-3 rounded-lg overflow-hidden border-2 border-black shadow-[0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]">
                    <img src="{{ asset('storage/posts/' . $ciudadDestino->gif) }}" alt="{{ $ciudadDestino->nombre }}" class="w-full h-40 object-cover" />
                </div>
                <div class="mt-3 flex justify-center gap-4 text-sm">
                    <span class="text-gray-300">Nivel {{ $ciudadDestino->nivel }}</span>
                    <span class="flex items-center gap-1 text-yellow-300 font-bold">
                        <img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-4 w-4">
                        {{ number_format($costosViaje[$ciudadDestino->id] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
            @endif

            <p class="mt-2 text-sm text-gray-200">Tiempo restante: <span class="font-bold text-white">{{ $tiempoRestante }}</span></p>

            <div class="mt-4 grid gap-2 {{ $ciudadDestino && $personaje->diamante >= $costoTeleport ? 'grid-cols-2' : 'grid-cols-1' }}">
                @if ($ciudadDestino && $personaje->diamante >= $costoTeleport)
                    <button wire:click="teleportarA({{ $ciudadDestino->id }})"
                        class="{{ $boton3d }} px-3 py-2 bg-gradient-to-b from-purple-500 to-purple-800 text-white text-sm flex items-center justify-center gap-1">
                        Teleport
                        @if ($costoTeleport > 0)
                        <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="h-4 w-4">
                        <span class="{{ $numDiamante }}">{{ $costoTeleport }}</span>
                        @else
                        <span class="text-emerald-300">· Gratis</span>
                        @endif
                    </button>
                @endif
                <button wire:click="cancelarViaje" type="button"
                    class="{{ $boton3d }} px-3 py-2 bg-gradient-to-b from-red-500 to-red-800 text-white text-sm select-none">
                    Cancelar viaje
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 justify-items-center">
        @foreach ($ciudadesDisponibles as $ciudad)
            @php
                $costo = $costosViaje[$ciudad->id] ?? 0;
                $puedeViajar = !$viajando && $personaje->nivel >= $ciudad->nivel && $personaje->oro >= $costo;
                $puedeTeleportar = $personaje->diamante >= $costoTeleport && $personaje->nivel >= $ciudad->nivel;
                $bloqueada = $ciudad->nivel > $personaje->nivel; // se ve, pero no se puede ir hasta tener su nivel
            @endphp
            <div class="{{ $card3d }} rounded-xl border {{ $bloqueada ? 'border-gray-700' : 'border-indigo-600 hover:-translate-y-1' }} p-3 flex flex-col text-center w-full max-w-xs
                        transition-transform duration-200">
                <h3 class="text-lg font-bold {{ $bloqueada ? 'text-gray-500' : 'text-indigo-300' }} mb-2 [text-shadow:0_2px_0_#000] truncate">{{ $ciudad->nombre }}</h3>

                <div class="relative rounded-lg overflow-hidden border-2 border-black shadow-[0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] mb-3">
                    <img src="{{ asset('storage/posts/' . $ciudad->gif) }}" alt="{{ $ciudad->nombre }}" class="w-full h-36 object-cover {{ $bloqueada ? 'grayscale brightness-50' : '' }}" loading="lazy" />
                    <span class="absolute top-1 left-1 px-2 py-0.5 rounded bg-black/70 border {{ $bloqueada ? 'border-gray-500 text-gray-300' : 'border-indigo-400 text-indigo-200' }} text-[11px] font-bold">
                        Nv {{ $ciudad->nivel }}
                    </span>
                    {{-- Cuánta exp da explorar acá con el nivel actual (baja 10% por cada nivel que le pasás a la zona) --}}
                    @unless ($bloqueada)
                        @php $porcExp = (int) round(\App\Livewire\Explorar::factorExpZona((int) $personaje->nivel, (int) $ciudad->nivel) * 100); @endphp
                        <span class="absolute top-1 right-1 px-2 py-0.5 rounded bg-black/70 border text-[11px] font-bold
                                     {{ $porcExp >= 100 ? 'border-green-400 text-green-300' : ($porcExp >= 50 ? 'border-yellow-400 text-yellow-300' : 'border-red-400 text-red-300') }}"
                              title="Experiencia que te da explorar en esta zona">
                            EXP {{ $porcExp }}%
                        </span>
                    @endunless
                    @if ($bloqueada)
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-4xl [filter:drop-shadow(0_2px_0_#000)]">🔒</span>
                            <span class="mt-1 px-2 py-0.5 rounded bg-black/70 border border-black text-xs font-bold text-gray-200">Bloqueada</span>
                        </div>
                    @endif
                </div>

                @if ($bloqueada)
                    <div class="mt-auto w-full px-3 py-2 rounded border border-black bg-gradient-to-b from-gray-700 to-gray-900 text-gray-400 text-sm font-bold select-none">
                        🔒 Necesitás nivel {{ $ciudad->nivel }}
                    </div>
                @else

                <div class="flex items-center justify-center gap-1 mb-3 text-sm">
                    <img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-5 w-5 [filter:drop-shadow(1px_2px_0_#000)]">
                    <span class="font-bold {{ $personaje->oro >= $costo ? 'text-yellow-300' : 'text-red-400' }} [text-shadow:1px_1px_0_#000]">
                        {{ number_format($costo, 0, ',', '.') }}
                    </span>
                </div>

                <button
                    wire:click="viajarA({{ $ciudad->id }})"
                    @if (!$puedeViajar) disabled @endif
                    class="{{ $boton3d }} w-full px-3 py-2 mb-2 bg-gradient-to-b from-indigo-500 to-indigo-800 text-white text-sm"
                >
                    {{ $puedeViajar ? 'Viajar' : ($viajando ? 'Ya estás viajando' : 'No podés viajar') }}
                </button>

                <button
                    wire:click="teleportarA({{ $ciudad->id }})"
                    @if (!$puedeTeleportar) disabled @endif
                    class="{{ $boton3d }} w-full px-3 py-2 bg-gradient-to-b from-purple-500 to-purple-800 text-white text-sm flex items-center justify-center gap-1"
                >
                    Teleport
                    @if ($costoTeleport > 0)
                    <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="h-4 w-4">
                    <span class="{{ $numDiamante }}">{{ $costoTeleport }}</span>
                    @else
                    <span class="text-emerald-300">· Gratis</span>
                    @endif
                </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
