<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    @forelse ($ciudades as $ciudad)
        <div class="p-6 bg-gray-50 rounded-lg shadow-md border border-gray-200 mb-6 border-t-4">
            <div class="p-6 text-gray-900 md:flex md:justify-between md:items-center">
            <div class="space-y-3">
                <h2 class="text-xl font-bold text-gray-800">{{ $ciudad->nombre }}</h2>

                <p class="text-sm text-gray-600">Nivel: <span class="font-semibold text-gray-900">{{ $ciudad->nivel }}</span></p>

                <p class="text-sm text-gray-500">Zona:</p>
                <div class="my-5 w-40">
                    <img src="{{ asset('storage/posts/' . $ciudad->gif) }}" alt="GIF de {{ $ciudad->nombre }}"
                        class="rounded-md shadow-md border border-gray-300">
                </div>
            </div>
                <div class="flex flex-col md:flex-row items-stretch gap-3 md:mt-0 mt-5">
                    <a href="{{ route('news.show', $ciudad->id) }}"
                       class="bg-slate-800 py-2 px-4 rounded-lg text-white text-xs font-bold uppercase text-center hover:bg-slate-700 transition duration-200">
                        Ver Ciudad
                    </a>

                    <a href="{{ route('news.edit', $ciudad->id) }}"
                       class="bg-blue-800 py-2 px-4 rounded-lg text-white text-xs font-bold uppercase text-center hover:bg-blue-700 transition duration-200">
                        Editar
                    </a>

                    <button wire:click="$dispatch('mostrarAlertaCiudad', {{ $ciudad->id }})"
                            class="bg-red-800 py-2 px-4 rounded-lg text-white text-xs font-bold uppercase text-center hover:bg-red-700 transition duration-200">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @empty
        <p class="p-3 text-center text-sm text-gray-600">No hay ciudades creadas aún.</p>
    @endforelse

    <!-- Paginación -->
    <div class="mt-10 mb-2">
        {{ $news->links() }}
    </div>
</div>

@push('scripts')
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    Livewire.on('mostrarAlertaCiudad', ciudadId => {
        Swal.fire({
            title: '¿Eliminar Ciudad?',
            text: "Una ciudad eliminada no se puede recuperar",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('eliminarCiudad', { id: ciudadId });
                Swal.fire(
                    'Se eliminó la ciudad',
                    'Eliminado correctamente',
                    'success'
                )
            }
        });
    });
</script>
@endpush