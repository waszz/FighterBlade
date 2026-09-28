<div class="px-4 py-8 bg-gray-100">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-semibold text-center text-gray-800 mb-12">
            @if ($estado === 'adoptado')
                Publicaciones adoptadas
            @elseif ($categoria)
                Publicaciones de {{ ucfirst($categoria) }}
            @else
                Todas las publicaciones en adopción
            @endif
        </h1>

        <!-- Filtros -->
        <div class="mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Género -->
                <div class="w-full">
                    <select wire:model="genero" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">Filtrar por Género</option>
                        <option value="hembra">Hembra</option>
                        <option value="macho">Macho</option>
                    </select>
                </div>
                <!-- Edad -->
                <div class="w-full">
                    <select wire:model="edad" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">Filtrar por Edad</option>
                        <option value="cachorro">Cachorro</option>
                        <option value="adulto">Adulto</option>
                    </select>
                </div>
                <!-- Búsqueda por nombre -->
                <div class="w-full">
                    <input wire:model="search" type="text" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Buscar por nombre">
                </div>
                <!-- Botón de Buscar -->
                <div class="w-full sm:w-auto">
                    <button wire:click="aplicarFiltros" class="w-full sm:w-auto p-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Buscar
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid de posts -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 justify-items-center mb-20">
            @forelse ($posts as $post)
                <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden group">
                    <div class="relative overflow-hidden">
                        <img
                            src="{{ asset('storage/posts/' . $post->imagen) }}"
                            alt="{{ 'Imagen de ' . $post->titulo }}"
                            class="w-full h-48 object-cover transform transition-transform duration-500 group-hover:scale-105"
                        >
                        <div class="absolute bottom-0 left-0 right-0 text-white text-center py-2 font-semibold uppercase
                            {{ $post->estado === 'en_adopcion' ? 'bg-blue-600' : 'bg-green-600' }}">
                            {{ $post->estado === 'en_adopcion' ? 'En Adopción' : 'Adoptado' }}
                        </div>
                    </div>
                    <div class="p-6 flex flex-col justify-between h-70">
                        <div>
                            <h2 class="text-xl text-center font-semibold text-gray-800 mb-4">{{ $post->titulo }}</h2>

                            <!-- Mostrar género y edad -->
                            <div class="text-center text-sm text-gray-600 mb-2">
                                <p>Género: {{ ucfirst($post->genero) }}</p>
                                <p>Edad: {{ ucfirst($post->edad) }}</p>
                            </div>

                            <div class="text-center bottom-2 mb-2 left-2 bg-white bg-opacity-80 px-3 py-1 rounded-md text-xs text-gray-800 shadow-md">
                                {{ \Carbon\Carbon::parse($post->created_at)->format('d M Y') }}
                            </div>
                            <p class="text-gray-600 text-sm line-clamp-4">{{ $post->descripcion }}</p>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="{{ route('posts.show', $post->id) }}"
                               class="inline-block text-indigo-600 hover:text-indigo-800 font-medium transition duration-300 ease-in-out transform hover:scale-105">
                                Leer más →
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-600 col-span-full text-center">No hay publicaciones para mostrar.</p>
            @endforelse
        </div>

        <!-- Paginación -->
        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    </div>
</div>