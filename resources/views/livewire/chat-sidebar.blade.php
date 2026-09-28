<div class="relative h-full w-full flex flex-col">

    {{-- Chat visible sólo si no está minimizado --}}
    @unless($minimizado)
        <div class="h-full min-h-0">
            @livewire('chat-component', ['personajeId' => $personajeId])
        </div>
    @endunless
</div>