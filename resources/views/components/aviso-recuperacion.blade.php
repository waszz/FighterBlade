{{-- Cartel "Te estás recuperando" con cuenta regresiva. Va dentro de un x-data que tenga `recup` (segundos). --}}
<div x-show="recup > 0" x-cloak
     class="w-full max-w-xl mx-auto px-3 py-2 rounded-lg border border-black text-center font-bold
            bg-gradient-to-b from-[#3a2a10] to-[#120c04] text-yellow-200
            shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]">
    ⏳ Te estás recuperando:
    <span class="text-white" x-text="String(Math.floor(recup / 60)).padStart(2, '0') + ':' + String(recup % 60).padStart(2, '0')"></span>
    <span class="block text-xs font-normal text-yellow-300/80">Podés recuperarte ya con oro desde el panel de tu personaje.</span>
</div>
