<div class="p-4 space-y-6 text-white">

    {{-- ⚠️ Aviso para menores --}}
    <div class="bg-gradient-to-b from-red-700 to-red-900 p-3 rounded-lg border border-black border-l-4 border-l-red-400 text-sm text-center shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]">
        ⚠️ Si sos menor de edad, <span class="font-bold text-yellow-300">NO OLVIDES consultar a tus padres</span> antes
        de realizar cualquier tipo de compra por internet.
    </div>



    <div class="bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] p-4 rounded-xl border border-yellow-500 space-y-4">
        <h2 class="text-xl font-bold text-center text-yellow-400">💰 Comprar Oro</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-center">
            @foreach ($costos as $cantidad => $costo)
            <div class="bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] p-3 rounded-lg border border-yellow-600">
                <p class="text-yellow-300 text-lg font-bold flex justify-center items-center gap-2">
                    <img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-5 w-5">
                    {{ number_format($cantidad, 0, ',', '.') }} Oro
                </p>
                <p class="text-white text-sm">Costo: <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-4 w-4 align-[-0.2em]"> <span class="font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">{{ number_format($costo, 0, ',', '.')
                        }}</span></p>
                <button wire:click="comprarOro({{ $cantidad }})"
                    class="mt-2 px-3 py-1 bg-gradient-to-b from-green-500 to-green-800 text-white font-bold rounded border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0">Comprar</button>
            </div>
            @endforeach
        </div>
    </div>


    {{-- ✨ Cosméticos: efectos de chat, efectos de nombre y auras (catálogo en App\Support\Cosmeticos) --}}
    @php
        $tiposCos = \App\Support\Cosmeticos::TIPOS;
        $tipoCos = $tiposCos[$tipoCosmetico] ?? $tiposCos['chat'];
        $equipadoCos = $personaje->{$tipoCos['columna']};
        $postPreview = $personaje->postDeCombate();
        $gifPreview = $postPreview?->gif ?? $personaje->post?->gif;
    @endphp
    {{-- En el celular la sección es un botón que abre los cosméticos a pantalla completa (así no hay que bajar tanto);
         en PC se ve siempre --}}
    <div x-data="{ abierto: false }">
    <button type="button" x-show="! abierto" @click="abierto = true"
        class="lg:hidden w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-fuchsia-500 text-left
               bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]
               active:translate-y-[3px] transition-all">
        <span>
            <span class="block text-xl font-bold text-fuchsia-300 [text-shadow:0_2px_0_#000]">✨ Cosméticos</span>
            <span class="block text-xs text-gray-300">{{ collect($tiposCos)->pluck('titulo')->implode(' · ') }}</span>
        </span>
        <i class="fa-solid fa-chevron-right text-fuchsia-300"></i>
    </button>
    <div :class="abierto ? 'fixed inset-0 z-50 overflow-y-auto rounded-none' : 'hidden lg:block rounded-xl'"
        class="bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] p-4 border border-fuchsia-500 space-y-4">
        <div class="relative">
            {{-- Volver (solo celular) --}}
            <button type="button" @click="abierto = false" aria-label="Volver"
                class="lg:hidden absolute left-0 top-0 w-8 h-8 flex items-center justify-center rounded-full border-2 border-black text-white
                       bg-gradient-to-b from-red-600 to-red-900 shadow-[0_2px_0_#000]">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </button>
            <h2 class="text-xl font-bold text-center text-fuchsia-300 [text-shadow:0_2px_0_#000]">✨ Cosméticos</h2>
        </div>

        {{-- Pestañas --}}
        <div class="flex flex-wrap justify-center gap-2">
            @foreach ($tiposCos as $claveTipo => $datosTipo)
            <button wire:click="$set('tipoCosmetico', '{{ $claveTipo }}')"
                class="px-4 py-1.5 rounded-lg border border-black text-sm font-bold transition-all duration-100 shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_3px_0_#000] active:translate-y-[3px] active:shadow-none
                       {{ $tipoCosmetico === $claveTipo ? 'bg-gradient-to-b from-fuchsia-500 to-fuchsia-800 text-white' : 'bg-gradient-to-b from-[#2a3240] to-[#10141b] text-gray-300 hover:brightness-125' }}">
                {{ $datosTipo['icono'] }} {{ $datosTipo['titulo'] }}
            </button>
            @endforeach
        </div>
        <p class="text-center text-sm text-gray-300">{{ $tipoCos['descripcion'] }} Se compran una vez y los podés poner o sacar cuando quieras.</p>

        <div wire:key="cosmeticos-{{ $tipoCosmetico }}" class="grid grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
            @foreach (\App\Support\Cosmeticos::delTipo($tipoCosmetico) as $claveCos => $cos)
            @php
                $comprado = in_array($claveCos, $cosmeticosComprados, true);
                $puesto = $equipadoCos === $claveCos;
            @endphp
            <div class="p-3 rounded-lg border {{ $puesto ? 'border-fuchsia-400' : 'border-black' }} bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] flex flex-col items-center gap-2 text-center">
                <h4 class="font-bold text-white">{{ $cos['nombre'] }}</h4>

                {{-- Vista previa --}}
                <div class="w-full h-28 rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)] flex items-center justify-center overflow-hidden px-3">
                    @if ($cos['tipo'] === 'chat')
                        <div class="max-w-[85%] text-left">
                            <p class="text-xs font-semibold text-white">{{ $personaje->nombre }}</p>
                            <div class="rounded-lg px-2.5 py-1.5 text-xs bg-gray-700 text-white {{ $cos['clase'] }}">¡Hola! ¿Quién se anima a pelear? ⚔️</div>
                        </div>
                    @elseif ($cos['tipo'] === 'ranking')
                        <div class="w-full flex items-center gap-2 p-2 rounded-lg border border-gray-600 bg-gray-800 text-left {{ $cos['clase'] }}">
                            <span class="text-yellow-400 font-extrabold">1°</span>
                            <img src="{{ asset('storage/' . $personaje->fotoChat()) }}" alt="" class="w-9 h-9 rounded-full object-cover">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-white truncate">{{ $personaje->nombre }}</p>
                                <p class="text-xs font-bold text-yellow-400">Nivel {{ $personaje->nivel }}</p>
                            </div>
                        </div>
                    @elseif ($cos['tipo'] === 'nombre')
                        <span class="text-2xl font-extrabold tracking-wide {{ $cos['clase'] }}">{{ $personaje->nombre }}</span>
                    @else
                        @if ($gifPreview)
                        <div class="self-end pb-1">
                            <img src="{{ asset('storage/' . $gifPreview) }}" alt="" style="{{ \App\Models\Post::estiloGif($gifPreview, 0.7) }}" class="block max-w-none {{ $cos['clase'] }}">
                        </div>
                        @endif
                    @endif
                </div>

                @if ($comprado)
                    @if ($puesto)
                        <span class="text-xs font-bold text-fuchsia-300">✔ Puesto</span>
                        <button wire:click="quitarCosmetico('{{ $cos['tipo'] }}')"
                            class="w-full px-3 py-1 rounded border border-black text-white text-sm font-bold bg-gradient-to-b from-gray-500 to-gray-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100">
                            Quitar
                        </button>
                    @else
                        <span class="text-xs font-bold text-emerald-300">Comprado</span>
                        <button wire:click="equiparCosmetico('{{ $claveCos }}')"
                            class="w-full px-3 py-1 rounded border border-black text-white text-sm font-bold bg-gradient-to-b from-fuchsia-500 to-fuchsia-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100">
                            Poner
                        </button>
                    @endif
                @else
                    <div class="font-bold text-sm"><img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-4 w-4 align-[-0.2em]"> <span class="font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">{{ number_format($cos['precio'], 0, ',', '.') }}</span></div>
                    <button wire:click="comprarCosmetico('{{ $claveCos }}')" wire:loading.attr="disabled"
                        class="w-full px-3 py-1 rounded border border-black text-white text-sm font-bold bg-gradient-to-b from-green-500 to-green-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100 disabled:opacity-50">
                        Comprar
                    </button>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    </div>

    {{-- Super Pociones por diamantes --}}

    <div class="bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] p-4 mt-6 rounded-xl border border-red-500 space-y-4">
        <h2 class="text-xl font-bold text-red-400 text-center">🧪 Super Pociones</h2>
        <div wire:key="extra-{{ $reloadExtra}}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-center">
                @forelse ($superPociones as $pocion)
                @php
                $stats = is_string($pocion->stats) ? json_decode($pocion->stats, true) : ($pocion->stats ?? []);
                $usosTotales = $stats['usos_totales'] ?? 1;
                $usosRestantes = $stats['usos_restantes'] ?? $usosTotales;
                @endphp

                <div class="bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] p-3 rounded-lg border border-red-600">
                    <h4 class="text-pink-300 font-bold text-lg mb-2">{{ $pocion->nombre }}</h4>
                    <img src="{{ asset('images/' . $pocion->imagen) }}" alt="{{ $pocion->nombre }}"
                        class="w-20 h-20 mx-auto rounded mb-2">
                    <p class="text-xs text-white mb-1">Nivel: {{ $pocion->nivel }}</p>
                    <p class="text-xs text-pink-400 mb-1">Usos: {{ $usosRestantes }}/{{ $usosTotales }}</p>
                    <p class="text-xs text-gray-300 italic mb-2">{{ $pocion->descripcion }}</p>

                    <div class="font-bold text-sm mb-2"><img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-4 w-4 align-[-0.2em]"> <span class="font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">{{ number_format($pocion->precio) }}</span></div>

                    <button wire:click="comprarSuperPocion({{ $pocion->id }})"
                        class="mt-1 px-3 py-1 bg-gradient-to-b from-green-500 to-green-800 text-white font-bold rounded w-full text-sm border border-black shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 disabled:opacity-50 disabled:active:translate-y-0">
                        Comprar
                    </button>
                </div>
                @empty
                <p class="col-span-3 text-center text-gray-400 italic">No hay super pociones disponibles.</p>
                @endforelse
            </div>
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



    {{-- Sección BUFF (siempre para todos) --}}
    @php
        $tiposBuff = [
            'xp'   => ['nombre' => 'Experiencia', 'icono' => 'fa-brain', 'imagen' => 'images/buff-exp.png', 'ajuste' => 'translate-y-1', 'color' => 'text-green-400',  'borde' => 'border-green-400 shadow-[0_0_10px_rgba(74,222,128,0.6)]'],
            'drop' => ['nombre' => 'Drops',       'icono' => 'fa-dice', 'imagen' => 'images/buff-drop.png', 'redonda' => true,  'color' => 'text-orange-500', 'borde' => 'border-orange-400 shadow-[0_0_10px_rgba(251,146,60,0.6)]'],
            'oro'  => ['nombre' => 'Oro',         'icono' => 'fa-coins', 'imagen' => 'images/oro.png', 'color' => 'text-yellow-400', 'borde' => 'border-yellow-400 shadow-[0_0_10px_rgba(250,204,21,0.6)]'],
        ];
    @endphp
    <div class="mt-6 grid gap-6 md:grid-cols-2 max-w-4xl mx-auto">
    <div class="p-4 rounded-xl border-2 border-gray-500/70 bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] w-full max-w-md mx-auto space-y-3">
        {{-- Título --}}
        <h2 class="flex items-center justify-center gap-2 text-2xl font-extrabold uppercase tracking-wide bg-gradient-to-b from-yellow-300 via-orange-400 to-red-500 bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">
            <img src="{{ asset('images/buff-combo.png') }}" alt="" class="h-12 w-12 object-contain">
            ¡Comprá buffs!
        </h2>
        <p class="text-sm font-semibold text-white leading-snug">
            Sponsoreá un buff y ayudá a todos los jugadores del servidor a subir más rápido, ganar más oro o conseguir más items!
        </p>

        {{-- Tipo de buff --}}
        <div class="flex justify-center gap-3">
            @foreach ($tiposBuff as $tipo => $info)
                @php $activo = $tipoBuff === $tipo; @endphp
                <button type="button" wire:click="seleccionarTipoBuff('{{ $tipo }}')"
                    class="w-20 h-20 flex flex-col items-center justify-center gap-1 rounded-lg border-2 transition-all duration-150 hover:brightness-125 {{ $activo ? $info['borde'] . ' bg-black/40' : 'border-transparent' }}">
                    @if (isset($info['imagen']))
                        <img src="{{ asset($info['imagen']) }}" alt="{{ $info['nombre'] }}" class="h-9 w-9 {{ $info['ajuste'] ?? '' }} {{ !empty($info['redonda']) ? 'object-cover rounded-md ring-1 ring-orange-400/60' : 'object-contain' }} [filter:drop-shadow(1px_2px_0_#000)]">
                    @else
                        <i class="fa-solid {{ $info['icono'] }} text-3xl {{ $info['color'] }} [filter:drop-shadow(1px_2px_0_#000)]"></i>
                    @endif
                    <span class="text-xs font-bold {{ $info['color'] }} [text-shadow:1px_1px_0_#000]">{{ $info['nombre'] }}</span>
                    <span class="text-[9px] text-gray-400"><img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-2.5 w-2.5 align-[-0.1em]"><span class="font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">{{ \App\Livewire\Extra::COSTO_BUFF_POR_5[$tipo] }}</span> / 5%</span>
                </button>
            @endforeach
        </div>

        {{-- Frase --}}
        <input type="text" wire:model.defer="fraseBuff" maxlength="100"
            class="w-full rounded-md border-2 border-gray-300 bg-white px-3 py-2 text-gray-800 placeholder-gray-500 shadow-[inset_0_2px_3px_rgba(0,0,0,0.3)] focus:border-yellow-400 focus:ring-0"
            placeholder="Una frase para acompañar">

        {{-- Porcentaje + comprar --}}
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center rounded-full border-2 border-red-900 bg-black shadow-[inset_0_2px_4px_rgba(0,0,0,0.9)] overflow-hidden">
                <button type="button" wire:click="cambiarPorcentajeBuff(-1)" {{ $porcentajeBuff <= 5 ? 'disabled' : '' }}
                    class="w-8 h-9 flex items-center justify-center bg-gradient-to-b from-red-700 to-red-950 text-white text-xl font-extrabold hover:brightness-125 disabled:opacity-40">−</button>
                <span class="w-20 text-center text-2xl font-extrabold text-green-400 [text-shadow:0_0_6px_rgba(74,222,128,0.6)]">+{{ $porcentajeBuff }}%</span>
                <button type="button" wire:click="cambiarPorcentajeBuff(1)" {{ $porcentajeBuff >= 100 ? 'disabled' : '' }}
                    class="w-8 h-9 flex items-center justify-center bg-gradient-to-b from-red-700 to-red-950 text-white text-xl font-extrabold hover:brightness-125 disabled:opacity-40">+</button>
            </div>

            <button type="button" wire:click="comprarBuff" wire:loading.attr="disabled" {{ !$tipoBuff || !$porcentajeBuff ? 'disabled' : '' }}
                title="Comprar +{{ $porcentajeBuff }}% de {{ $tiposBuff[$tipoBuff]['nombre'] ?? '' }}"
                class="flex items-center gap-2 pl-3 pr-1 py-1 rounded-full border-2 border-gray-400 bg-gradient-to-b from-gray-600 to-gray-900 shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all disabled:opacity-50">
                <i class="fa-solid fa-cart-shopping text-lg text-white"></i>
                <span class="flex items-center gap-1 pl-1 pr-2.5 py-0.5 rounded-full bg-black/60 border border-gray-500">
                    <img src="{{ asset('images/diamante.png') }}" alt="" class="h-4 w-4">
                    <span class="text-sm font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">{{ number_format($costoBuff, 0, ',', '.') }}</span>
                </span>
            </button>
        </div>
    </div>

    {{-- Sección EXPLORACIÓN RÁPIDA (al costado de los buffs) --}}
    @php
        $opcionesRapida = [3 => 300, 5 => 600, 7 => 900];
        $rapidaActiva = \App\Models\ExploracionRapida::activaPara($personaje->id);
    @endphp
    <div x-data="{ dias: 3, costos: @js($opcionesRapida) }"
        class="p-4 rounded-xl border-2 border-gray-500/70 bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)] w-full max-w-md mx-auto space-y-3">
        {{-- Título --}}
        <h2 class="flex items-center justify-center gap-2 text-2xl font-extrabold uppercase tracking-wide bg-gradient-to-b from-sky-200 via-blue-400 to-indigo-500 bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">
            <img src="{{ asset('images/casino-rapida.png') }}" alt="" class="h-8 w-8 object-contain">
            Exploración rápida
        </h2>
        <p class="text-sm font-semibold text-white leading-snug">
            Activá la exploración rápida y explorá más rápido durante los días que elijas!
        </p>

        @if ($rapidaActiva)
            <div class="flex flex-col items-center justify-center gap-1 py-6 rounded-lg border-2 border-green-400 bg-black/40 shadow-[0_0_10px_rgba(74,222,128,0.6)]">
                <span class="text-lg font-extrabold text-green-400 [text-shadow:1px_1px_0_#000]">¡Activa!</span>
                <span class="text-xs text-gray-300">Termina {{ $rapidaActiva->fin->diffForHumans() }}</span>
            </div>
        @else
            {{-- Duración --}}
            <div class="flex justify-center gap-3">
                @foreach ($opcionesRapida as $dias => $costo)
                    <button type="button" @click="dias = {{ $dias }}"
                        class="w-20 h-20 flex flex-col items-center justify-center gap-0.5 rounded-lg border-2 transition-all duration-150 hover:brightness-125"
                        :class="dias === {{ $dias }} ? 'border-blue-400 shadow-[0_0_10px_rgba(96,165,250,0.6)] bg-black/40' : 'border-transparent'">
                        <span class="text-3xl font-extrabold leading-none text-blue-300 [text-shadow:1px_2px_0_#000]">{{ $dias }}</span>
                        <span class="text-xs font-bold text-blue-300 [text-shadow:1px_1px_0_#000]">días</span>
                        <span class="text-[9px] text-gray-400"><img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-2.5 w-2.5 align-[-0.1em]"><span class="font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]">{{ $costo }}</span></span>
                    </button>
                @endforeach
            </div>

            {{-- Duración elegida + comprar --}}
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center rounded-full border-2 border-blue-900 bg-black shadow-[inset_0_2px_4px_rgba(0,0,0,0.9)] px-4 h-9">
                    <span class="text-2xl font-extrabold text-blue-300 [text-shadow:0_0_6px_rgba(96,165,250,0.6)]" x-text="dias + ' días'"></span>
                </div>

                <button type="button" @click="$wire.comprarExploracionRapida(dias)" wire:loading.attr="disabled"
                    :title="'Activar exploración rápida por ' + dias + ' días'"
                    class="flex items-center gap-2 pl-3 pr-1 py-1 rounded-full border-2 border-gray-400 bg-gradient-to-b from-gray-600 to-gray-900 shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all disabled:opacity-50">
                    <i class="fa-solid fa-cart-shopping text-lg text-white"></i>
                    <span class="flex items-center gap-1 pl-1 pr-2.5 py-0.5 rounded-full bg-black/60 border border-gray-500">
                        <img src="{{ asset('images/diamante.png') }}" alt="" class="h-4 w-4">
                        <span class="text-sm font-bold bg-gradient-to-r from-[#6ee7b7] via-[#34d399] to-[#10b981] bg-clip-text text-transparent [filter:drop-shadow(1px_1px_0_#000)]" x-text="costos[dias]"></span>
                    </span>
                </button>
            </div>
        @endif
    </div>
    </div>
</div>