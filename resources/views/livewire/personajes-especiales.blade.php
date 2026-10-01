<div class="max-w-6xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-1">Personajes especiales</h1>
    <p class="text-sm text-gray-600 mb-5">
        No aparecen en Mis Personajes ni en el juego como sets: el enemigo de bienvenida y los rivales de Misiones.
        Desde acá los podés ver y cargarles los gifs.
    </p>

    {{-- Búsqueda y filtro --}}
    <div class="mb-6 flex flex-col sm:flex-row gap-2">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre"
               class="p-2 border border-gray-300 rounded-md w-full sm:w-72">
        <select wire:model.live="filtro" class="p-2 border border-gray-300 rounded-md w-full sm:w-56">
            <option value="todos">Todos</option>
            <option value="sin_gifs">Les faltan gifs</option>
            <option value="completos">Con todos los gifs</option>
        </select>
        <p class="sm:ml-auto self-center text-sm text-gray-500">{{ $especiales->count() }} personajes</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($especiales as $post)
            @php
                $completo = $post->gifs_cargados === $totalGifs;
                $esBienvenida = $post->es_enemigo == \App\Models\Post::ENEMIGO_ESPECIAL;
                $esComerciante = $post->es_enemigo == \App\Models\Post::COMERCIANTE;
            @endphp
            <div wire:key="especial-{{ $post->id }}" class="p-4 bg-white rounded-lg shadow-md border border-gray-200 flex gap-4">
                <div class="w-24 h-24 shrink-0 rounded-md bg-slate-800 flex items-end justify-center overflow-hidden">
                    <img src="{{ asset('storage/' . ($post->gif ?: $post->imagen)) }}" alt="{{ $post->titulo }}"
                         class="max-h-full max-w-full object-contain" loading="lazy">
                </div>
                <div class="flex-1 min-w-0 flex flex-col">
                    <p class="text-xs font-semibold {{ $esBienvenida ? 'text-emerald-700' : ($esComerciante ? 'text-amber-700' : 'text-indigo-700') }}">
                        {{ $esBienvenida ? '⭐ Enemigo de bienvenida' : ($esComerciante ? 'Comerciante · aparece al explorar: ' . ($post->chance_aparicion ?? \App\Support\Comerciante::CHANCE) . '%' : '📜 Misión ' . $post->orden_mision) }}
                    </p>
                    <a href="{{ route('posts.show', $post->id) }}" class="text-lg font-bold text-gray-800 hover:underline truncate">{{ $post->titulo }}</a>
                    <p class="text-xs text-gray-600 flex items-center gap-2">
                        Nv {{ $post->nivel }} · <x-icono-tipo :tipo="$post->tipo" tam="w-4 h-4" :con-nombre="true" />
                    </p>
                    <p class="mt-1 text-xs font-semibold {{ $completo ? 'text-green-700' : 'text-amber-700' }}">
                        {{ $completo ? '✔' : '⚠' }} Gifs: {{ $post->gifs_cargados }} / {{ $totalGifs }}
                    </p>
                    <div class="mt-auto pt-2 flex gap-2">
                        <a href="{{ route('posts.show', $post->id) }}" class="flex-1 bg-slate-800 py-1.5 rounded text-white text-xs font-bold uppercase text-center hover:bg-slate-900">Ver</a>
                        <a href="{{ route('personajes.especiales.editar', $post->id) }}" class="flex-1 bg-blue-800 py-1.5 rounded text-white text-xs font-bold uppercase text-center hover:bg-blue-900">Editar / Gifs</a>
                    </div>
                </div>
            </div>
        @empty
            <p class="col-span-full p-3 text-center text-sm text-gray-600">No hay personajes especiales con ese filtro.</p>
        @endforelse
    </div>
</div>
