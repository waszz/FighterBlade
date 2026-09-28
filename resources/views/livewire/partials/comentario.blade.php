

@php
    $colores = ['bg-pink-50', 'bg-pink-100', 'bg-indigo-100', 'bg-indigo-200'];
    $colorFondo = $colores[$nivel % count($colores)];
@endphp

<div class="mt-3 p-3 rounded-xl relative {{ $colorFondo }}">
    {{-- Formulario de edición --}}
    @if($comentarioIdEditar === $comentario->id)
        <textarea wire:model="comentarioEditado" rows="3" class="w-full p-2 border rounded-md"></textarea>
        @error('comentarioEditado') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        <div class="mt-2 flex justify-end space-x-2">
            <button wire:click="actualizar" class="px-3 py-1 bg-green-500 text-white rounded">Guardar</button>
            <button wire:click="cancelarEdicion" class="px-3 py-1 bg-gray-400 text-white rounded">Cancelar</button>
        </div>
    @else
        {{-- Comentario --}}
        <p class="text-sm text-gray-800 mb-1">{{ $comentario->comentario }}</p>
        <p class="text-xs text-gray-500 text-right">
            {{ $comentario->user->name ?? 'Usuario eliminado' }} | {{ $comentario->created_at->diffForHumans() }}
        </p>

        {{-- Acciones --}}
        @if(Auth::id() === $comentario->user_id || Auth::user()?->is_admin)
            <div class="absolute top-2 right-2 flex space-x-2">
                <button wire:click="editar({{ $comentario->id }})" class="text-xs text-blue-600 hover:underline">Editar</button>
                <button wire:click="eliminar({{ $comentario->id }})" class="text-xs text-red-600 hover:underline">Eliminar</button>
            </div>
        @endif

        @auth
        <button wire:click="responder({{ $comentario->id }})" class="text-xs text-amber-900 hover:underline mt-2">Responder</button>

        {{-- Formulario de respuesta --}}
        @if ($comentarioPadreId === $comentario->id)
            <form wire:submit.prevent="enviar" class="mt-2">
                <textarea
                    wire:model="comentario"
                    rows="2"
                    class="w-full p-2 border border-amber-900 rounded-md shadow-sm  transition"
                    placeholder="Escribí tu respuesta..."
                ></textarea>
                @error('comentario') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                <div class="flex justify-end mt-1">
                    <button type="submit" class="bg-amber-900 hover:bg-amber-800 text-white px-4 py-1 rounded-md text-sm">
                        Responder
                    </button>
                </div>
            </form>
        @endif
        @endauth

        {{-- Mostrar respuestas si hay --}}
        @if ($comentario->respuestas->count() > 0 && $nivel < 3)
            <button wire:click="toggleRespuestas({{ $comentario->id }})" class="text-xs text-gray-600 hover:underline mt-2">
                {{ $openStates[$comentario->id] ?? false ? 'Ocultar respuestas' : 'Mostrar respuestas ('. $comentario->respuestas->count() .')' }}
            </button>

            @if($openStates[$comentario->id] ?? false)
                @foreach($comentario->respuestas as $respuesta)
                    @include('livewire.partials.comentario', ['comentario' => $respuesta, 'nivel' => $nivel + 1])
                @endforeach
            @endif
        @endif
    @endif
</div>