<div>
    {{-- Botón para abrir modal --}}
    <button wire:click="openModal" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-300">
        Editar stats y recursos
    </button>

    {{-- Modal --}}
    @if($modalOpen)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" wire:click.self="closeModal">
            <div class="bg-gray-800 rounded-lg p-6 w-96 text-white shadow-lg relative">
                <h2 class="text-xl font-bold mb-4">Editar Personaje</h2>

                <form wire:submit.prevent="save" class="space-y-4">

                    @foreach (['fuerza', 'ataque', 'velocidad', 'resistencia', 'defensa', 'energia'] as $stat)
                        <div>
                            <label class="block mb-1 capitalize" for="{{ $stat }}">{{ $stat }}</label>
                            <input type="number" id="{{ $stat }}" min="0"  wire:model.defer="{{ $stat }}"
                                   class="w-full rounded bg-gray-700 border border-gray-600 px-2 py-1 text-white" />
                            @error($stat) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @endforeach

                    <div>
                        <label class="block mb-1" for="oro">Oro</label>
                        <input type="number" id="oro" min="0" wire:model.defer="oro"
                               class="w-full rounded bg-gray-700 border border-gray-600 px-2 py-1 text-white" />
                        @error('oro') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1" for="diamante">Esmeraldas</label>
                        <input type="number" id="diamante" min="0" wire:model.defer="diamante"
                               class="w-full rounded bg-gray-700 border border-gray-600 px-2 py-1 text-white" />
                        @error('diamante') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1" for="nivel">Nivel</label>
                        <input type="number" id="nivel" min="1" wire:model.defer="nivel"
                               class="w-full rounded bg-gray-700 border border-gray-600 px-2 py-1 text-white" />
                        @error('nivel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" wire:click="closeModal" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded">Cancelar</button>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-white">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>