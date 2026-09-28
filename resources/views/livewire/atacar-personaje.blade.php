<div class="relative p-4 bg-gray-800 rounded-lg text-white max-w-3xl mx-auto">

    {{-- Personaje y objetivo --}}
    <div class="flex justify-between items-center mb-4">
        <div class="flex flex-col items-center">
            <img src="{{ asset('storage/' . ($personaje->post->gif ?? 'default.gif')) }}"
                 alt="Personaje" class="w-24 h-24 object-contain rounded-md shadow-lg">
            <span class="font-bold mt-1">{{ $personaje->nombre }}</span>
        </div>

        <div class="flex flex-col items-center">
            <img src="{{ asset('storage/' . ($objetivo->post->gif ?? 'default-enemigo.png')) }}"
                 alt="Objetivo" class="w-24 h-24 object-contain rounded-md shadow-lg">
            <span class="font-bold mt-1">{{ $objetivo->nombre }}</span>
        </div>
    </div>

    {{-- Botón atacar --}}
    <div class="flex justify-center mb-4">
        <button wire:click="atacar"
                class="bg-black text-yellow-400 font-bold py-2 px-6 rounded-lg shadow-[0_0_8px_2px_rgba(255,215,0,0.7)]">
            Atacar
        </button>
    </div>


{{-- Acciones por ronda --}}
@if($accionesRonda)
    <div class="space-y-4 mb-4">
        @foreach($accionesRonda as $accion)
            <div class="p-2 bg-gray-700 rounded flex flex-col items-center">

                {{-- Ronda --}}
                <div class="font-semibold mb-2">Ronda {{ $accion['ronda'] }}:</div>

                {{-- Gif del atacante centrado --}}
                <div class="w-24 h-24 mb-2">
                    <img src="{{ asset('storage/' . ($accion['gif_personaje'] ?? $gifAtaquePersonaje)) }}"
                         alt="Ataque"
                         class="w-full h-full object-contain rounded-md shadow-lg">
                </div>

               {{-- Descripción de la acción --}}
{{-- Descripción de la acción --}}
<div class="text-center">
    <div>
        <strong>{{ $accion['atacante'] }}</strong> hace
        @php
            $tipo = $accion['tipo_personaje'] ?? 'fisico'; // default a físico si no viene
        @endphp

        @if($tipo === 'fisico')
            <span class="text-red-400 font-bold">{{ $accion['danio_fisico'] ?? 0 }}</span> de daño físico
        @elseif($tipo === 'elemental')
            <span class="text-indigo-400 font-bold">{{ $accion['danio_elemental'] ?? 0 }}</span> de daño elemental
        @elseif($tipo === 'hibrido')
            <span class="text-red-400 font-bold">{{ $accion['danio_fisico'] ?? 0 }}</span> de daño físico
            y
            <span class="text-indigo-400 font-bold">{{ $accion['danio_elemental'] ?? 0 }}</span> de daño elemental
        @endif
    </div>

    <div class="text-yellow-300 mt-1">
        {{ $accion['enemigo'] }} recibe 
        <span class="font-bold">{{ $accion['danio_recibido'] ?? 0 }}</span>
    </div>
</div>

            </div>
        @endforeach
    </div>
@endif

    {{-- Resultado final --}}
    @if($resultadoFinal)
        <div class="mt-4 p-2 bg-green-600 rounded">
            Resultado: <strong>{{ $resultadoFinal }}</strong>
        </div>
    @endif

    {{-- Recompensas --}}
    @if($recompensas)
        <div class="mt-4 p-2 bg-yellow-700 rounded">
            <div>Recompensas:</div>
            <div>Exp: {{ $recompensas['exp'] ?? 0 }}</div>
            <div>Oro: {{ $recompensas['oro'] ?? 0 }}</div>
        </div>
    @endif
    {{-- GIFs de resultado --}}
    @if($resultadoFinal)
        <div class="flex justify-between mb-4">
            <div class="w-1/2 pr-2">
                <img src="{{ asset('storage/' . ($resultadoFinal == 'Victoria' ? $gifVictoriaPersonaje : $gifDerrotaPersonaje)) }}"
                     class="w-full h-auto object-contain rounded-lg shadow-lg">
            </div>
            <div class="w-1/2 pl-2">
                <img src="{{ asset('storage/' . ($resultadoFinal == 'Victoria' ? $gifDerrotaEnemigo : $gifVictoriaEnemigo)) }}"
                     class="w-full h-auto object-contain rounded-lg shadow-lg scale-x-[-1]">
            </div>
        </div>
    @endif

</div>