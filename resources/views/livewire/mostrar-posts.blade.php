<div>
    <!-- Campo de búsqueda y botón -->
   <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="w-full md:w-1/2 flex flex-col md:flex-row gap-2">
        <input type="text" 
               wire:model="search" 
               class="p-2 border border-gray-300 rounded-md w-full" 
               placeholder="Buscar por nombre de personaje"
               @if(!$isAdmin) disabled @endif>

        <select wire:model="nivelSeleccionado"
                class="p-2 border border-gray-300 rounded-md w-full md:w-40"
                @if(!$isAdmin) disabled @endif>
            <option value="">Todos los niveles</option>
            @for ($i = 5; $i <= 100; $i += 5)
                <option value="{{ $i }}">{{ $i }}</option>
            @endfor
        </select>
    </div>

    @if(!$isAdmin)
        <p class="text-red-500 text-sm">Solo los administradores pueden buscar.</p>
    @else
        <button wire:click="buscarPosts" class="bg-blue-600 py-2 px-6 rounded-lg text-white text-sm font-semibold hover:bg-blue-700 transition">
            Buscar
        </button>
    @endif
</div>

    @forelse ($posts as $post)
    <div class="p-6 bg-white rounded-lg shadow-md border border-gray-200 mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-6">
            <!-- Información del post -->
            <div class="flex-1 space-y-3">
                <a href="{{ route('posts.show', $post->id) }}" class="text-xl font-bold text-gray-800 hover:underline">
                    {{ $post->titulo }}
                </a>

              

              

                <div>
                    <p class="text-sm font-semibold text-gray-700">Imagen:</p>
                    <img src="{{ asset('storage/' . $post->gif) }}" alt="Imagen" class="w-40 rounded-md shadow">
                </div>

                <!-- Nuevo campo de nivel-->
                <div>
                    <p class="text-sm font-semibold text-gray-700">Nivel:</p>
                    <p class="text-gray-600">{{ ucfirst($post->nivel) }}</p>
                </div>

               
                
            </div>

            <!-- Acciones -->
            <div class="flex flex-col gap-3 md:w-52 w-full">
                <a href="{{ route('posts.show', $post->id) }}" class="bg-slate-800 py-2 px-4 rounded-lg text-white text-xs font-bold uppercase text-center hover:bg-slate-900">
                    Ver Personaje
                </a>
                <a href="{{ route('posts.edit', $post->id) }}" class="bg-blue-800 py-2 px-4 rounded-lg text-white text-xs font-bold uppercase text-center hover:bg-blue-900">
                    Editar
                </a>
                <button wire:click="$dispatch('mostrarAlerta', {{ $post->id }})" class="bg-red-800 py-2 px-4 rounded-lg text-white text-xs font-bold uppercase text-center hover:bg-red-900">
                    Eliminar
                </button>
              
            </div>
        </div>
    </div>
@empty
    <p class="p-3 text-center text-sm text-gray-600">No hay posts creados aún</p>
@endforelse

    <!-- Paginación -->
    <div class="mt-10 mb-2">
        {{ $posts->links() }}
    </div>
</div>

@push('scripts')
   
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   
    <script>
        Livewire.on('mostrarAlerta', postId => {
                Swal.fire({
                title: '¿Eliminar Post?',
                text: "Un Post eliminado no se puede recuperar",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Si, ¡Eliminar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
            if (result.isConfirmed) {
                    //eliminar vacante
                    Livewire.dispatch('eliminarPost', { id: postId });
                    Swal.fire(
                    'Se eliminó el Post',
                    'Eliminado Correctamente',
                    'success'
                    )
            }
            })
        })
        
    </script>
@endpush
