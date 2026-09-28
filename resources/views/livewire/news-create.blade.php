<div class="md:w-1/2 mx-auto space-y-5 bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-semibold mb-6 text-center">Crear Nueva Noticia</h2>

    <!-- Mensaje de éxito -->
    @if (session()->has('message'))
        <div class="bg-green-500 text-white p-4 mb-4 rounded">
            {{ session('message') }}
        </div>
    @endif

    <!-- Formulario -->
    <form wire:submit.prevent="crearNew" enctype="multipart/form-data">
        <!-- Título -->
        <div class="mb-4">
            <label for="titulo" class="block text-sm font-medium text-gray-700">Título</label>
            <input type="text" id="titulo" wire:model="titulo" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            @error('titulo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <!-- Contenido -->
        <div class="mb-4">
            <label for="contenido" class="block text-sm font-medium text-gray-700">Contenido</label>
            <textarea id="contenido" wire:model="contenido" rows="5" class="mt-1 block w-full h-72 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required></textarea>
            @error('contenido') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <!-- Imagen principal -->
        <div class="mb-4">
            <label for="imagen" class="block text-sm font-medium text-gray-700">Imagen Principal</label>
            <input type="file" id="imagen" wire:model="imagen" class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('imagen') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

            <!-- Vista previa de la imagen principal -->
            @if ($imagen)
                <div class="mt-4">
                    <span class="block text-sm text-gray-500 mb-1">Vista previa:</span>
                    <img src="{{ $imagen->temporaryUrl() }}" class="h-40 rounded-md object-cover">
                </div>
            @endif
        </div>

        <!-- Galería de imágenes -->
        <div class="mb-4">
            <label for="imagenes" class="block text-sm font-medium text-gray-700">Galería de Imágenes (Máximo 5)</label>
            <input type="file" id="imagenes" wire:model="imagenes" class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" multiple>
            @error('imagenes.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

            <!-- Vista previa de las imágenes de la galería -->
            @if ($imagenes)
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach ($imagenes as $imagen)
                        <div class="w-24 h-24">
                            <img src="{{ $imagen->temporaryUrl() }}" class="h-full w-full rounded-md object-cover">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Botón de guardar -->
        <div class="mt-6 text-center">
            <button type="submit" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-full transition">
                Crear Noticia
            </button>
        </div>
    </form>
</div>