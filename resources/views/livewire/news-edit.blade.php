<form wire:submit.prevent="editarCiudad" class="space-y-6">
    <div>
        <x-input-label for="titulo" value="Nombre de la ciudad" />
        <x-text-input id="titulo" type="text" wire:model.defer="titulo" class="mt-1 block w-full" />
        @error('titulo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <x-input-label for="nivel" value="Nivel" />
        <select id="nivel" wire:model.defer="nivel" class="mt-1 block w-full rounded text-black">
            <option value="">Selecciona un nivel</option>
            @for ($i = 0; $i <= 100; $i += 5)
                <option value="{{ $i }}" @if($nivel == $i) selected @endif>Nivel {{ $i }}</option>
            @endfor
        </select>
        @error('nivel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <x-input-label for="gif" value="Nuevo GIF (opcional)" />
        <input id="gif" type="file" wire:model="gif" accept="image/gif" class="mt-1 block w-full text-white file:bg-indigo-600 file:text-white file:rounded file:px-4 file:py-2" />
        @error('gif') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

        {{-- Vista previa si se selecciona un nuevo GIF --}}
        @if ($gif)
            <p class="mt-2">Vista previa del nuevo GIF:</p>
            <img src="{{ $gif->temporaryUrl() }}" class="w-40 h-auto mt-1 rounded shadow-md border">
        @elseif ($ciudad->gif)
            <p class="mt-2">GIF actual:</p>
            <img src="{{ asset('storage/posts/' . $ciudad->gif) }}" class="w-40 h-auto mt-1 rounded shadow-md border">
        @endif
    </div>

    <x-primary-button class="mt-4">Actualizar Ciudad</x-primary-button>
</form>