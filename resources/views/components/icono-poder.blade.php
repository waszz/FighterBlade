{{-- Icono de un poder (public/images/poderes). Al pasar el mouse o tocarlo muestra nombre y descripción.
     Acepta un modelo Poder o un array con nombre/imagen/descripcion.
     El tooltip va con position:fixed (calculado desde el icono) y teletransportado al <body>: así no lo corta ningún
     contenedor con scroll ni lo desplaza un padre con transform (que cambia la referencia de position:fixed). --}}
@props(['poder', 'tam' => 'w-9 h-9', 'conNombre' => false])
@php
    $nombrePoder = data_get($poder, 'nombre', '');
    $descripcionPoder = data_get($poder, 'descripcion');
    $archivo = data_get($poder, 'imagen') ?: \Illuminate\Support\Str::slug(\Illuminate\Support\Str::ascii($nombrePoder), '_') . '.png';
    $tieneIcono = is_file(public_path('images/poderes/' . $archivo));
@endphp
<span tabindex="0" {{ $attributes->merge(['class' => 'relative inline-flex items-center gap-1.5 align-middle outline-none group']) }}
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
    @if ($tieneIcono)
        <img src="{{ asset('images/poderes/' . $archivo) }}" alt="{{ $nombrePoder }}" loading="lazy"
             class="{{ $tam }} shrink-0 object-contain drop-shadow-[0_2px_2px_rgba(0,0,0,0.7)] transition-transform group-hover:scale-110">
    @else
        <span class="{{ $tam }} shrink-0 inline-flex items-center justify-center rounded-full border-2 border-yellow-600 bg-gradient-to-b from-gray-700 to-gray-900 text-[10px] font-bold text-yellow-300">
            {{ mb_substr($nombrePoder, 0, 2) }}
        </span>
    @endif
    @if ($conNombre)
        <span class="font-semibold">{{ $nombrePoder }}</span>
    @endif
    {{-- Tooltip --}}
    <template x-teleport="body">
    <span x-init="tip = $el" x-show="ver" x-cloak :class="listo ? 'opacity-100' : 'opacity-0'" :style="`left:${x}px;top:${y}px`"
          class="pointer-events-none fixed z-[9999] w-44 rounded border border-black bg-gradient-to-b from-gray-800 to-black px-2 py-1 text-center text-[11px] leading-snug text-white normal-case not-italic font-normal
                 shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),0_3px_0_#000]">
        <b class="block text-yellow-300">{{ $nombrePoder }}</b>
        @if ($descripcionPoder)<span class="text-gray-300">{{ $descripcionPoder }}</span>@endif
    </span>
    </template>
</span>
