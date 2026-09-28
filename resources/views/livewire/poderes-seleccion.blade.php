<div class="p-4">
    <h2 class="text-xl font-bold mb-2">Selecciona hasta 3 poderes:</h2>

    @if (session()->has('message'))
        <div class="bg-green-200 text-green-800 p-2 rounded mb-2">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
        @foreach ($poderes as $poder)
            <label class="flex items-center space-x-2">
                <input
                    type="checkbox"
                    value="{{ $poder->id }}"
                    wire:model="seleccionados"
                >
                <x-icono-poder :poder="$poder" tam="w-7 h-7" :con-nombre="true" />
            </label>
        @endforeach
    </div>
</div>
