<div class="max-w-5xl mx-auto py-8 px-4">
    <a href="{{ route('personajes.especiales') }}" class="text-sm text-blue-700 hover:underline">← Personajes especiales</a>

    <div class="mt-2 mb-6 flex flex-wrap items-baseline gap-x-3">
        <h1 class="text-2xl font-bold text-gray-800">Editar {{ $post->titulo }}</h1>
        <span class="text-sm font-semibold text-indigo-700">
            @if ($post->es_enemigo == \App\Models\Post::ENEMIGO_ESPECIAL)
                ⭐ Enemigo de bienvenida
            @elseif ($mision)
                📜 Misión {{ $mision->orden }} · premio {{ number_format($mision->recompensa_oro, 0, ',', '.') }} oro + {{ $mision->recompensa_diamantes }} <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="inline-block h-4 w-4 align-[-0.2em]">
            @endif
        </span>
    </div>

    <form wire:submit="guardar" class="space-y-6">
        {{-- Datos --}}
        <section class="p-5 bg-white rounded-lg shadow border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Datos</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <label class="block text-sm">
                    <span class="font-semibold text-gray-700">Nombre</span>
                    <input type="text" wire:model="titulo" class="mt-1 w-full p-2 border border-gray-300 rounded-md">
                    @error('titulo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">
                    <span class="font-semibold text-gray-700">Nivel (1 a 100, libre)</span>
                    <input type="number" min="1" max="100" wire:model="nivel" class="mt-1 w-full p-2 border border-gray-300 rounded-md">
                    @error('nivel') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">
                    <span class="font-semibold text-gray-700">Tipo de daño</span>
                    <select wire:model.live="tipo" class="mt-1 w-full p-2 border border-gray-300 rounded-md">
                        <option value="fisico">Físico</option>
                        <option value="elemental">Elemental</option>
                        <option value="hibrido">Híbrido</option>
                    </select>
                    <x-icono-tipo :tipo="$tipo" tam="w-6 h-6" class="mt-1" />
                </label>
            </div>
        </section>

        {{-- Stats: se cargan directo, sin reparto de puntos --}}
        <section class="p-5 bg-white rounded-lg shadow border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">Stats</h2>
            <p class="text-xs text-gray-500 mb-3">Valores finales del rival. No hace falta repartir puntos.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach (\App\Livewire\EditarEspecial::STATS as $stat)
                    <label class="block text-sm">
                        <span class="font-semibold text-gray-700 capitalize">{{ $stat }}</span>
                        <input type="number" min="0" wire:model="stats.{{ $stat }}" class="mt-1 w-full p-2 border border-gray-300 rounded-md">
                        @error("stats.$stat") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                @endforeach
            </div>
        </section>

        {{-- Gifs --}}
        <section class="p-5 bg-white rounded-lg shadow border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">Foto y gifs</h2>
            <p class="text-xs text-gray-500 mb-3">Elegí solo los que quieras cambiar; el resto queda como está. El tamaño en pantalla se ajusta solo al guardar.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach (\App\Livewire\EditarEspecial::ARCHIVOS as $campo => [$columna, $etiqueta])
                    @php
                        $actual = $post->$columna;
                        $esGif = str_ends_with(strtolower((string) $actual), '.gif');
                        $nuevo = $archivos[$campo] ?? null;
                    @endphp
                    <div wire:key="archivo-{{ $campo }}" class="rounded-lg border {{ $nuevo ? 'border-blue-500 ring-2 ring-blue-200' : 'border-gray-200' }} p-2 flex flex-col">
                        <p class="text-sm font-semibold text-gray-700 flex justify-between">
                            {{ $etiqueta }}
                            @if ($campo !== 'imagen')
                                <span class="text-xs {{ $esGif ? 'text-green-700' : 'text-amber-700' }}">{{ $esGif ? '✔ gif' : '⚠ sin gif' }}</span>
                            @endif
                        </p>
                        <div class="my-2 h-32 rounded bg-slate-800 flex items-end justify-center overflow-hidden">
                            @if ($nuevo)
                                <img src="{{ $nuevo->temporaryUrl() }}" alt="" class="max-h-full max-w-full object-contain">
                            @elseif ($actual)
                                <img src="{{ asset('storage/' . $actual) }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy">
                            @endif
                        </div>
                        @if ($nuevo)
                            <p class="text-xs text-blue-700 font-semibold truncate">Nuevo: {{ $nuevo->getClientOriginalName() }}</p>
                            <button type="button" wire:click="quitarArchivo('{{ $campo }}')" class="text-xs text-red-600 hover:underline self-start">Quitar</button>
                        @else
                            <input type="file" wire:model="archivos.{{ $campo }}" accept=".gif,.png,.jpg,.jpeg,.webp" class="text-xs w-full">
                        @endif
                        <div wire:loading wire:target="archivos.{{ $campo }}" class="text-xs text-gray-500">Subiendo…</div>
                        @error("archivos.$campo") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Poderes --}}
        <section class="p-5 bg-white rounded-lg shadow border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Poderes</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach ($poderesDisponibles as $poder)
                    <label wire:key="poder-{{ $poder->id }}" class="flex items-center gap-2 p-2 rounded border border-gray-200 hover:bg-gray-50 cursor-pointer text-sm">
                        <input type="checkbox" value="{{ $poder->id }}" wire:model="poderesSeleccionados">
                        <x-icono-poder :poder="$poder" tam="w-8 h-8" />
                        <span class="font-semibold text-gray-800">{{ $poder->nombre }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" wire:loading.attr="disabled"
                    class="bg-blue-700 hover:bg-blue-800 disabled:opacity-50 text-white font-bold py-2 px-8 rounded-lg">
                Guardar
            </button>
            <span wire:loading wire:target="guardar" class="text-sm text-gray-500">Guardando…</span>
            <a href="{{ route('posts.show', $post->id) }}" class="text-sm text-blue-700 hover:underline">Ver cómo queda (Girar / Tamaño)</a>
        </div>
    </form>
</div>
