{{-- Icono de la poción equipada. Al pasar el mouse o tocarla muestra nombre, qué hace y los usos que le quedan.
     Tooltip con position:fixed teletransportado al <body>, igual que x-icono-joya. --}}
@props(['pocion', 'tam' => 'w-9 h-9'])
@php
    $statsPocion = \App\Models\Personaje::decodificarStats($pocion->stats ?? []);
@endphp
<span tabindex="0" {{ $attributes->merge(['class' => 'relative inline-flex items-center align-middle outline-none group']) }}
      x-data="{ ver: false, listo: false, x: 0, y: 0, tip: null,
                abrir() {
                    const r = $el.getBoundingClientRect();
                    this.ver = true; this.listo = false;
                    // Se mide cuando el cartel ya se ve (antes mide 0×0 y queda encima del icono)
                    const medir = () => {
                        const t = this.tip; if (! t || ! this.ver) return;
                        const w = t.offsetWidth, h = t.offsetHeight;
                        if (! w) return requestAnimationFrame(medir);
                        this.x = Math.min(Math.max(8, r.left + r.width / 2 - w / 2), innerWidth - w - 8);
                        this.y = r.top - h - 6 < 8 ? r.bottom + 6 : r.top - h - 6;
                        this.listo = true;
                    };
                    this.$nextTick(medir);
                } }"
      x-on:mouseenter="abrir()" x-on:mouseleave="ver = false" x-on:focus="abrir()" x-on:blur="ver = false"
      x-on:scroll.window.capture="ver = false">
    <img src="{{ asset('images/' . $pocion->imagen) }}" alt="{{ $pocion->nombre }}"
         class="{{ $tam }} shrink-0 object-contain drop-shadow-[0_2px_2px_rgba(0,0,0,0.8)] transition-transform group-hover:scale-110">
    {{-- Tooltip --}}
    <template x-teleport="body">
    <span x-init="tip = $el" x-show="ver" x-cloak :class="listo ? 'opacity-100' : 'opacity-0'" :style="`left:${x}px;top:${y}px`"
          class="pointer-events-none fixed z-[9999] w-44 rounded border border-black bg-gradient-to-b from-gray-800 to-black px-2 py-1 text-center text-[11px] leading-snug text-white normal-case not-italic font-normal
                 shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),0_3px_0_#000]">
        <b class="block text-fuchsia-300">🧪 {{ $pocion->nombre }}</b>
        @if ($pocion->descripcion)
            <span class="block text-gray-200">{{ $pocion->descripcion }}</span>
        @endif
        <span class="text-emerald-300 font-bold">Usos: {{ $statsPocion['usos_restantes'] ?? 1 }}/{{ $statsPocion['usos_totales'] ?? 1 }}</span>
    </span>
    </template>
</span>
