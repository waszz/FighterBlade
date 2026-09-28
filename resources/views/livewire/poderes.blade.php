<div class="flex flex-col p-2 bg-gray-900 text-white rounded-lg shadow-md h-full max-h-full">
    <h2 class="text-lg font-bold mb-3 text-purple-400 text-center">🧬 Poderes</h2>

    <div class="flex items-center space-x-2 mb-3 max-w-md mx-auto">
        <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar poder..."
            class="flex-grow bg-gray-800 border border-gray-600 rounded px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-purple-400" />
        <button wire:click="buscar"
            class="bg-purple-500 text-gray-900 font-semibold px-3 py-1 rounded hover:bg-purple-600 transition text-sm">
            Buscar
        </button>
    </div>

    @if ($poderes->isEmpty())
    <p class="text-center text-gray-400 italic text-xs flex-grow flex items-center justify-center">No se encontraron
        poderes.</p>
    @else
    <div class="overflow-auto flex-grow max-h-[calc(100vh-150px)]">
        <ul class="space-y-1 text-sm">
            @foreach ($poderes as $poder)
            <li
                class="bg-gray-800 rounded border border-gray-700 p-2 flex items-center space-x-3 hover:bg-gray-700 transition">
                <x-icono-poder :poder="$poder" tam="w-12 h-12" />
                <div class="flex-grow">
                    <div class="font-semibold text-lg text-yellow-400">{{ $poder->nombre }}</div>
                    <div class="text-gray-300 text-sm truncate">{{ $poder->descripcion }}</div>
                    @if ($poder->tipo || $poder->nivel)
                    <div class="text-xs text-gray-400 mt-0.5 flex space-x-2">
                        @if ($poder->tipo)
                        <span class="italic">{{ ucfirst($poder->tipo) }}</span>
                        @endif
                    </div>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>