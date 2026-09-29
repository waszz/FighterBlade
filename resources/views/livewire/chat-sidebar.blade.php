{{-- flex-1 y no h-full: en el celular arriba está el botón de cerrar y con h-full el cuadro para escribir quedaba fuera de la pantalla --}}
<div class="relative flex-1 min-h-0 w-full flex flex-col">

    {{-- Chat visible sólo si no está minimizado --}}
    @unless($minimizado)
        <div class="flex-1 min-h-0">
            @livewire('chat-component', ['personajeId' => $personajeId])
        </div>
    @endunless
</div>