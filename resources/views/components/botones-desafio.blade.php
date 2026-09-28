{{-- Duelo (pelea amistosa) e Intercambio con otro jugador: el pedido lo maneja App\Livewire\Desafios --}}
@props(['objetivoId'])
<div class="mt-2 grid grid-cols-2 gap-2">
    <button type="button" x-on:click="Livewire.dispatch('desafiar', { tipo: 'duelo', personajeId: {{ (int) $objetivoId }} })"
        title="Pelea amistosa: sin premio ni recuperación"
        class="py-2 rounded-lg border border-black text-sm font-bold text-white bg-gradient-to-b from-orange-500 to-orange-800
               shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">
        ⚔️ Duelo
    </button>
    <button type="button" x-on:click="Livewire.dispatch('desafiar', { tipo: 'intercambio', personajeId: {{ (int) $objetivoId }} })"
        title="Pasarse hasta 3 objetos, oro y esmeraldas"
        class="py-2 rounded-lg border border-black text-sm font-bold text-white bg-gradient-to-b from-sky-500 to-sky-800
               shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">
        🔁 Intercambiar
    </button>
</div>
