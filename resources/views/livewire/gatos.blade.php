<h1 class="text-2xl font-bold mb-6">
    @if ($categoria)
        Publicaciones de {{ ucfirst($categoria) }}
    @else
        Todas las publicaciones
    @endif
</h1>

<div class="mb-6">
    <input type="text" wire:model="search"
           placeholder="Buscar por título..."
           class="border border-gray-300 p-2 rounded w-full md:w-1/2 focus:outline-none focus:ring focus:border-blue-300">
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse ($posts as $post)
        <div class="bg-white rounded shadow p-4">
            <h2 class="text-xl font-semibold mb-2">{{ $post->titulo }}</h2>
            <p class="text-gray-700">{{ $post->extracto }}</p>
            <p class="text-sm text-gray-500 mt-2">Categoría: {{ ucfirst($post->categoria) }}</p>
        </div>
    @empty
        <p class="text-gray-600">No hay publicaciones para mostrar.</p>
    @endforelse
</div>

<div class="mt-6">
    {{ $posts->links() }}
</div>