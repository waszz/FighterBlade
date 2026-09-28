<div class="max-w-xl mx-auto bg-gray-800 p-6 rounded-lg shadow text-black">
    <form wire:submit.prevent="crearCiudad">
        <div class="mb-4">
            <x-input-label for="titulo" value="Nombre de la ciudad" />
            <x-text-input type="text" id="titulo" wire:model.defer="titulo" class="mt-1 block w-full" />
            @error('titulo') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <x-input-label for="nivel" value="Nivel requerido para acceder" />
            <select id="nivel" wire:model.defer="nivel" class="mt-1 block w-full rounded text-black">
                <option value="">Selecciona un nivel</option>
                @for ($i = 0; $i <= 100; $i += 5)
                    <option value="{{ $i }}">Nivel {{ $i }}</option>
                @endfor
            </select>
            @error('nivel') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <x-input-label for="gif" value="GIF animado de la ciudad" />
            <input type="file" id="gif" wire:model="gif" accept="image/gif"
                class="mt-1 block w-full text-white file:bg-blue-600 file:text-white file:rounded file:px-4 file:py-2" />
            @error('gif') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
        </div>

        <div wire:loading wire:target="gif" class="text-yellow-300 mb-2">Subiendo gif...</div>

        <x-primary-button>
            Crear Ciudad
        </x-primary-button>

        @if (session()->has('message'))
            <div class="text-green-400 mt-4">{{ session('message') }}</div>
        @endif
    </form>
</div>