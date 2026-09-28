<div class="max-w-5xl mx-auto p-6 bg-gray-900 rounded-lg text-white shadow-lg space-y-8">

    <h2 class="text-3xl font-bold text-center">Crear Personaje / Post</h2>

    @if (session()->has('mensaje'))
        <div class="bg-green-600 p-3 rounded text-center">
            {{ session('mensaje') }}
        </div>
    @endif

    <form wire:submit.prevent="editarPost" class="space-y-6">

        {{-- Título, Nivel y Tipo --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block mb-1 font-bold">Título</label>
                <input wire:model="titulo" type="text" class="w-full rounded p-2 bg-gray-800 border border-gray-700">
                @error('titulo') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block mb-1 font-bold">Nivel</label>
                <input wire:model="nivel" type="number" min="5" max="100" step="5" class="w-full rounded p-2 bg-gray-800 border border-gray-700">
            </div>
            <div>
                <label class="block mb-1 font-bold">Tipo</label>
                <select wire:model="tipo" class="w-full rounded p-2 bg-gray-800 border border-gray-700">
                    <option value="fisico">Físico</option>
                    <option value="elemental">Elemental</option>
                    <option value="hibrido">Híbrido</option>
                </select>
            </div>
        </div>

       {{-- Imágenes y GIFs --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4">
    {{-- Imagen principal --}}
    <div>
        <label class="block mb-1">Imagen principal</label>
        <input type="file" wire:model="imagen" class="w-full" accept=".jpg,.jpeg,.png" />
        @if($imagen)
            <img src="{{ $imagen->temporaryUrl() }}" class="mt-2 max-h-32 object-contain rounded" accept=".jpg,.jpeg,.png"/>
        @elseif($imagenGuardado)
            <img src="{{ Storage::url($imagenGuardado) }}" class="mt-2 max-h-32 object-contain rounded"  accept=".jpg,.jpeg,.png"/>
        @endif

        @error('imagen') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- GIF Personaje --}}
    <div>
        <label class="block mb-1">GIF Personaje</label>
        <input type="file" wire:model="gifPersonaje" class="w-full" />
        @if($gifPersonaje)
            <img src="{{ $gifPersonaje->temporaryUrl() }}" class="mt-2 max-h-32 object-contain rounded" />
        @elseif($gifPersonajeGuardado)
            <img src="{{ Storage::url($gifPersonajeGuardado) }}" class="mt-2 max-h-32 object-contain rounded" />
        @endif
        
        @error('gifPersonaje') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- GIFs de combate --}}
    @foreach(['gifAtaque','gifDefensa','gifCritico','gifEspecial','gifDerrota','gifVictoria'] as $gif)
        <div>
            <label class="block mb-1">{{ ucfirst(str_replace('gif','GIF ',$gif)) }}</label>
            <input type="file" wire:model="{{ $gif }}" class="w-full" />
           @if (${$gif})
            <img src="{{ ${$gif}->temporaryUrl() }}" class="mt-2 max-h-32 object-contain rounded" />
        @elseif (isset(${$gif . 'Guardado'}) && ${$gif . 'Guardado'})
            <img src="{{ Storage::url(${$gif . 'Guardado'}) }}" class="mt-2 max-h-32 object-contain rounded" />
        @endif

            @error($gif) <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
        </div>
    @endforeach
</div>
        {{-- Selección de Poderes --}}
        <div>
    <label class="block font-bold mb-2">Selecciona los poderes</label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($poderesDisponibles as $poder)
            <label class="flex flex-col border rounded p-3 cursor-pointer hover:bg-gray-800">
                <div class="flex items-center space-x-2 mb-1">
                    <input type="checkbox" wire:model="poderesSeleccionados" value="{{ $poder->id }}">
                    <x-icono-poder :poder="$poder" tam="w-8 h-8" />
                    <span class="font-semibold text-lg">{{ $poder->nombre }}</span>
                </div>
                <span class="text-gray-400 text-sm leading-tight">{{ $poder->descripcion }}</span>
            </label>
        @endforeach
    </div>
    @error('poderesSeleccionados')
        <span class="text-red-400 text-sm">{{ $message }}</span>
    @enderror
</div>
{{-- Secciones de Equipo, Entrenamiento y Accesorio --}}
@foreach (['equipo' => 'Equipo', 'entrenamiento' => 'Entrenamiento', 'accesorio' => 'Accesorio'] as $campo => $label)
    <div class="bg-gray-800 p-4 rounded shadow-md mb-6">
        <h3 class="text-xl font-bold mb-2">{{ $label }}</h3>

        {{-- Nombre personalizado --}}
        <input
            type="text"
            wire:model="{{ $campo . '_nombre' }}"
            placeholder="Nombre para {{ strtolower($label) }}"
            class="w-full mb-3 p-2 rounded bg-gray-700 border border-gray-600 text-white"
        >

        {{-- Subir imagen --}}
        <input
            type="file"
            wire:model="{{ $campo . '_imagen' }}"
            accept=".jpg,.jpeg,.png"
            class="w-full mb-4"
        >
        @if(${"{$campo}_imagen"})
            <img src="{{ ${$campo . '_imagen'}->temporaryUrl() }}" class="mb-4 max-h-32 object-contain rounded" accept=".jpg,.jpeg,.png" />
        @elseif(isset(${$campo . '_imagen_preview'}))
            <img src="{{ ${$campo . '_imagen_preview'} }}" class="mb-4 max-h-32 object-contain rounded" />
        @endif

        {{-- Ajustes Manuales --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @foreach (array_keys($statsBase) as $stat)
                <div>
                    <label class="block font-medium capitalize">{{ $stat }}</label>
                    <div class="flex items-center flex-wrap gap-1">
    <button type="button" wire:click="decrementarStat('{{ $stat }}','{{ $campo }}', 10)" class="bg-red-500 px-2 rounded text-sm">-10</button>
    <button type="button" wire:click="decrementarStat('{{ $stat }}','{{ $campo }}', 5)" class="bg-red-500 px-2 rounded text-sm">-5</button>
    <button type="button" wire:click="decrementarStat('{{ $stat }}','{{ $campo }}', 1)" class="bg-red-500 px-2 rounded text-sm">-1</button>

    <span class="w-10 text-center font-bold">
        {{ ${'ajustesManuales' . ucfirst($campo)}[$stat] ?? 0 }}
    </span>

    <button type="button" wire:click="incrementarStat('{{ $stat }}','{{ $campo }}', 1)" class="bg-green-500 px-2 rounded text-sm">+1</button>
    <button type="button" wire:click="incrementarStat('{{ $stat }}','{{ $campo }}', 5)" class="bg-green-500 px-2 rounded text-sm">+5</button>
    <button type="button" wire:click="incrementarStat('{{ $stat }}','{{ $campo }}', 10)" class="bg-green-500 px-2 rounded text-sm">+10</button>
</div>
                </div>
            @endforeach
        </div>

        {{-- 🔥 Requisitos de Stats (Agregalo acá justo después de los ajustes) --}}
        @if ($nivel >= 10)
    <div class="mt-4">
        <h4 class="font-bold mb-2">Requisitos para {{ strtolower($label) }} (opcional)</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @foreach (array_keys($statsBase) as $stat)
                <div>
                    <label class="block text-sm capitalize">{{ $stat }}</label>
                    <input
                        type="number"
                        min="0"
                        max="{{ $nivel }}" {{-- Limita el valor al nivel actual --}}
                        wire:model.defer="requisitos{{ ucfirst($campo) }}.{{ $stat }}"

                        class="w-full rounded p-1 bg-gray-700 border border-gray-600 text-white text-sm"
                    >
                </div>
            @endforeach
        </div>
    </div>
@endif


    </div>
@endforeach





        {{-- Puntos Totales --}}
        <div class="text-center">
            <p>Puntos totales: <strong>{{ $puntosTotales }}</strong> —
            Usados: <strong>{{ $puntosUsados }}</strong> —
            Restantes: <strong>{{ $puntosRestantes }}</strong></p>
            <div class="w-full bg-gray-700 h-3 mt-2 rounded">
                <div class="h-3 {{ $this->barraColor() }} rounded" style="width: {{ round(($puntosUsados / max($puntosTotales, 1)) * 100, 1) }}%"></div>
            </div>
        </div>

        {{-- Stats Finales --}}
        <div class="bg-gray-800 p-4 rounded shadow-md">
            <h3 class="text-xl font-bold mb-2 text-center">Estadísticas Finales</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach ($statsDesglose as $stat => $valores)
                    <div class="bg-gray-900 p-3 rounded text-sm">
                        <p class="font-bold capitalize">{{ $stat }}</p>
                        <p>Base: {{ $valores['base'] }}</p>
                        <p>Ajuste equipo: {{ $valores['ajuste_equipo'] }}</p>
                        <p>Ajuste entrenamiento: {{ $valores['ajuste_entrenamiento'] }}</p>
                        <p>Ajuste accesorio: {{ $valores['ajuste_accesorio'] }}</p>
                        <p>Stats objetos: {{ $valores['ajuste_equipo'] + $valores['ajuste_entrenamiento'] + $valores['ajuste_accesorio'] }}</p>
                        <p class="mt-2 font-bold text-green-400">Total: {{ $valores['total'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="mt-4 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 rounded text-white font-bold">
                Actualizar Personaje
            </button>
        </div>

    </form>
</div>


