<div class="flex h-full w-full text-white">

    {{-- Sidebar del chat --}}
    <div class="w-full h-full border-l border-indigo-700 flex flex-col min-h-0">
        {{-- Contenedor interior del chat --}}
        <div class="w-full h-full min-h-0 p-3 md:p-4 flex flex-col">

            {{-- Título --}}
            <h3 class="text-xl md:text-2xl font-bold mb-3 text-white text-center md:text-left">Chat</h3>

            @php
                // Color fijo del nombre para cada jugador (si no tiene un efecto de nombre comprado)
                $coloresNombre = ['text-emerald-400', 'text-sky-400', 'text-amber-400', 'text-pink-400', 'text-violet-400', 'text-lime-400', 'text-orange-400', 'text-cyan-300', 'text-rose-400', 'text-teal-300'];
            @endphp

            {{-- Área de mensajes: todos a la izquierda; la foto va pegada dentro de la tarjeta, con nombre y hora --}}
            <div id="chat-box" class="flex-1 min-h-0 overflow-y-auto mb-3 space-y-2.5 pr-1 [scrollbar-width:thin] [scrollbar-color:#4b5563_transparent]">
                @php
                    // Foto actual de cada personaje: la del set con el que pelea (set completo equipado o su set base),
                    // así si cambia de set cambia también en los mensajes viejos. Una consulta por personaje, no por mensaje.
                    $fotosChat = [];
                @endphp
                @foreach ($mensajes as $msg)
                    @php
                        $pj = $msg->personaje;
                        if ($pj && ! array_key_exists($pj->id, $fotosChat)) {
                            $fotosChat[$pj->id] = $pj->fotoChat(); // la elegida en el Inventario o la del set con el que pelea
                        }
                        $fotoChat = ($pj ? $fotosChat[$pj->id] : null) ?? 'default.png';
                        $claseNombreChat = $pj?->claseNombre() ?: $coloresNombre[($pj->id ?? 0) % count($coloresNombre)];
                        $horaChat = $msg->created_at?->locale('es')->diffForHumans(['short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
                    @endphp

                    {{-- La foto va montada sobre la esquina superior izquierda de la tarjeta (mitad afuera, mitad adentro);
                         la tarjeta ocupa todo el ancho y es baja: nombre, hora y mensaje van a la derecha de la foto --}}
                    <div wire:key="msg-{{ $msg->id }}" class="relative pl-5 pt-0.5 w-fit max-w-[90%]">
                        <img src="{{ asset('storage/' . $fotoChat) }}" alt=""
                             class="absolute left-0 top-0 z-10 w-11 h-11 rounded-full object-cover bg-black/40 shadow-[0_2px_6px_rgba(0,0,0,0.7)]" loading="lazy" />

                        <div class="min-w-[10rem] min-h-[2.5rem] rounded-xl pl-8 pr-4 py-1 border border-white/5
                                    bg-gradient-to-b from-[#1b2230] to-[#121722] shadow-[0_2px_6px_rgba(0,0,0,0.5)]
                                    {{ $pj?->claseChat() }}">
                            <p class="flex items-baseline gap-2 leading-tight">
                                <span class="font-bold text-sm truncate {{ $claseNombreChat }}">{{ $pj->nombre ?? 'Sin nombre' }}</span>
                                <span class="text-[11px] text-gray-400 shrink-0">{{ $horaChat }}</span>
                            </p>
                            <p class="text-sm leading-snug break-words whitespace-pre-wrap">{{ $msg->contenido }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Input para escribir mensaje --}}
            <form wire:submit.prevent="enviarMensaje" class="space-y-2">
                <div class="relative w-full">
                    <input
                        type="text"
                        wire:model.defer="mensaje"
                        placeholder="Escribí un mensaje..."
                        maxlength="255"
                        class="w-full rounded-xl pl-4 pr-12 py-2.5 text-sm bg-[#121722] text-white placeholder-gray-500 border border-white/10
                               shadow-[inset_0_2px_6px_rgba(0,0,0,0.7)] focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                    <button
                        type="submit"
                        aria-label="Enviar"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center rounded-lg border border-white/10 bg-[#1b2230] text-gray-200 hover:bg-indigo-600 hover:text-white transition">
                        <i class="fa-solid fa-paper-plane text-sm"></i>
                    </button>
                </div>

                @if(auth()->user()->role === 'admin')
                    <button
                        type="button"
                        wire:click="reiniciarChat"
                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg whitespace-nowrap text-sm"
                        onclick="confirm('¿Estás seguro de reiniciar el chat? Se borrarán todos los mensajes.') || event.stopImmediatePropagation()">
                        Reiniciar Chat
                    </button>
                @endif
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    Livewire.hook('message.processed', () => {
        const chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    });
</script>
@endpush
