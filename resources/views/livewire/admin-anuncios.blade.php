<div class="max-w-4xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-1">Anuncios</h1>
    <p class="text-sm text-gray-600 mb-5">
        Se muestran en el panel "Anuncios" de la ciudad (los {{ \App\Models\Anuncio::MAXIMO_EN_PANEL }} activos más nuevos),
        con la foto del admin que publicó el último.
    </p>

    @if (session('mensaje'))
        <div class="mb-4 p-3 rounded-md bg-green-100 border border-green-300 text-green-800 text-sm">{{ session('mensaje') }}</div>
    @endif

    {{-- Formulario: nuevo o editando --}}
    <form wire:submit="guardar" class="mb-8 p-4 bg-white rounded-lg shadow-md border border-gray-200 space-y-3">
        <h2 class="font-semibold text-gray-800">{{ $editandoId ? 'Editar anuncio' : 'Nuevo anuncio' }}</h2>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
            <input type="text" wire:model="titulo" maxlength="120" placeholder="Ej: ¡Nuevo evento de fin de semana!"
                   class="p-2 border border-gray-300 rounded-md w-full">
            @error('titulo') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Texto</label>
            <textarea wire:model="texto" rows="3" maxlength="1000" placeholder="Qué querés contarles a los jugadores"
                      class="p-2 border border-gray-300 rounded-md w-full"></textarea>
            @error('texto') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">
                {{ $editandoId ? 'Guardar cambios' : 'Publicar' }}
            </button>
            @if ($editandoId)
                <button type="button" wire:click="cancelar" class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-semibold">Cancelar</button>
            @endif
        </div>
    </form>

    {{-- Lista --}}
    <div class="space-y-3">
        @forelse ($anuncios as $anuncio)
            <div wire:key="anuncio-{{ $anuncio->id }}"
                 class="p-4 bg-white rounded-lg shadow-md border {{ $anuncio->activo ? 'border-gray-200' : 'border-gray-200 opacity-60' }} {{ $editandoId === $anuncio->id ? 'ring-2 ring-indigo-400' : '' }}">
                <div class="flex items-start gap-3">
                    @if ($foto = $anuncio->fotoAutor())
                        <img src="{{ asset('storage/' . $foto) }}" alt="" class="w-10 h-10 shrink-0 rounded-full object-cover border border-gray-300">
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-800">{{ $anuncio->titulo }}
                            @unless ($anuncio->activo)
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 text-xs font-medium">Oculto</span>
                            @endunless
                        </p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $anuncio->texto }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $anuncio->autor?->name ?? 'Sin autor' }} · {{ $anuncio->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-sm">
                    <button wire:click="editar({{ $anuncio->id }})" class="px-3 py-1 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800">Editar</button>
                    <button wire:click="alternarActivo({{ $anuncio->id }})" class="px-3 py-1 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800">
                        {{ $anuncio->activo ? 'Ocultar' : 'Mostrar' }}
                    </button>
                    <button wire:click="borrar({{ $anuncio->id }})" wire:confirm="¿Borrar el anuncio «{{ $anuncio->titulo }}»?"
                            class="px-3 py-1 rounded-md bg-red-50 hover:bg-red-100 text-red-700">Borrar</button>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 italic">Todavía no hay anuncios.</p>
        @endforelse
    </div>
</div>
