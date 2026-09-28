{{-- Icono de la joya equipada (anillo de la Torre / Misiones). Al pasar el mouse o tocarla muestra nombre, nivel y stats.
     Tooltip con position:fixed teletransportado al <body> (no lo corta ningún contenedor ni lo desplaza un padre con transform), igual que x-icono-poder. --}}
@props(['joya', 'tam' => 'w-9 h-9'])
@php
    $abrevJoya = ['fuerza' => 'FUE', 'resistencia' => 'RES', 'ataque' => 'ATA', 'defensa' => 'DEF', 'velocidad' => 'VEL', 'energia' => 'ENE'];
    $statsJoya = array_filter(\App\Models\Personaje::decodificarStats($joya->stats ?? []), fn ($v) => is_numeric($v) && $v > 0);
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
    <img src="{{ asset('storage/posts/' . $joya->imagen) }}" alt="{{ $joya->nombre }}"
         class="{{ $tam }} shrink-0 object-contain drop-shadow-[0_2px_2px_rgba(0,0,0,0.8)] transition-transform group-hover:scale-110">
    {{-- Tooltip --}}
    <template x-teleport="body">
    <span x-init="tip = $el" x-show="ver" x-cloak :class="listo ? 'opacity-100' : 'opacity-0'" :style="`left:${x}px;top:${y}px`"
          class="pointer-events-none fixed z-[9999] w-40 rounded border border-black bg-gradient-to-b from-gray-800 to-black px-2 py-1 text-center text-[11px] leading-snug text-white normal-case not-italic font-normal
                 shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),0_3px_0_#000]">
        <b class="block text-amber-300">💍 {{ $joya->nombre }}</b>
        @foreach ($statsJoya as $stat => $valor)
            <span class="text-emerald-300 font-bold">{{ $abrevJoya[$stat] ?? strtoupper(substr($stat, 0, 3)) }} +{{ $valor }}</span>@if (! $loop->last) · @endif
        @endforeach
    </span>
    </template>
</span>
