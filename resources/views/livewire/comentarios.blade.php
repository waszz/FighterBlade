<div class="space-y-6">
    @auth
        @if (!auth()->user()->hasVerifiedEmail())
            <div class="text-center text-red-500 font-semibold">
                Debes confirmar tu correo electrónico para dejar un comentario.
                <br>
                <a href="{{ route('verification.notice') }}" class="text-blue-600 underline">Verificar ahora</a>
            </div>
        @else
            <h3 class="text-lg font-semibold text-gray-800 text-center">Deja tu comentario</h3>

            @if (session()->has('mensaje'))
                <div class="text-green-600 text-center text-sm">
                    {{ session('mensaje') }}
                </div>
            @endif

            <form wire:submit.prevent="enviar" class="mt-2">
                <textarea
                    wire:model.defer="comentario"
                    wire:key="input-{{ $inputKey }}"
                    rows="4"
                    class="w-full p-3 border border-amber-900 rounded-xl shadow-sm transition"
                    placeholder="Escribe tu comentario aquí..."
                    required
                ></textarea>
                @error('comentario') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                <div class="flex justify-end mt-2">
                    <button
                        type="submit"
                        class="bg-amber-900 hover:bg-amber-800 text-white px-5 py-2 rounded-xl font-medium transition duration-300 shadow-md hover:shadow-lg"
                    >
                        {{ $comentarioPadreId ? 'Responder' : 'Enviar' }}
                    </button>
                </div>
            </form>
        @endif
    @else
        <p class="text-center text-gray-600">Inicia sesión para dejar un comentario.</p>
    @endauth

    {{-- Listado de comentarios raíz --}}
    @foreach($comentarios as $comentario)
        @if(is_null($comentario->parent_id))
            @include('livewire.partials.comentario', ['comentario' => $comentario, 'nivel' => 0])
        @endif
    @endforeach
</div>