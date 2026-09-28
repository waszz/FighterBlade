{{-- Icono del tipo de daño (fisico / elemental / hibrido) en public/images/tipos.
     Al pasar el mouse o tocarlo muestra el nombre del tipo (tooltip con position:fixed, no lo corta ningún contenedor). --}}
@props(['tipo', 'tam' => 'w-6 h-6', 'conNombre' => false])
@php
    $tipoDanio = strtolower((string) $tipo);
    $nombresTipo = ['fisico' => 'Daño físico', 'elemental' => 'Daño elemental', 'hibrido' => 'Daño híbrido'];
    $nombreTipo = $nombresTipo[$tipoDanio] ?? ucfirst($tipoDanio);
    $tieneIcono = is_file(public_path("images/tipos/$tipoDanio.png"));
@endphp
<span {{ $attributes->merge(['class' => 'relative inline-flex items-center gap-1 align-middle outline-none']) }}
      tabindex="0"
      x-data="{ ver: false, listo: false, x: 0, y: 0,
                abrir() {
                    if (! this.$refs.tip) return; // sin tooltip (se muestra el nombre al lado)
                    const r = $el.getBoundingClientRect();
                    this.ver = true; this.listo = false;
                    this.$nextTick(() => {
                        const t = this.$refs.tip, w = t.offsetWidth, h = t.offsetHeight;
                        this.x = Math.min(Math.max(8, r.left + r.width / 2 - w / 2), innerWidth - w - 8);
                        this.y = r.top - h - 6 < 8 ? r.bottom + 6 : r.top - h - 6;
                        this.listo = true;
                    });
                } }"
      x-on:mouseenter="abrir()" x-on:mouseleave="ver = false" x-on:focus="abrir()" x-on:blur="ver = false"
      x-on:scroll.window.capture="ver = false">
    @if ($tieneIcono)
        <img src="{{ asset("images/tipos/$tipoDanio.png") }}" alt="" class="{{ $tam }} shrink-0 object-contain drop-shadow-[0_2px_2px_rgba(0,0,0,0.7)]">
    @endif
    @if ($conNombre || ! $tieneIcono)
        <span>{{ $conNombre ? ucfirst($tipoDanio) : $nombreTipo }}</span>
    @else
        {{-- Tooltip --}}
        <span x-ref="tip" x-show="ver" x-cloak :class="listo ? 'opacity-100' : 'opacity-0'" :style="`left:${x}px;top:${y}px`"
              class="pointer-events-none fixed z-[9999] whitespace-nowrap rounded border border-black bg-gradient-to-b from-gray-800 to-black px-2 py-1 text-[11px] font-bold not-italic normal-case text-yellow-300
                     shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),0_3px_0_#000]">{{ $nombreTipo }}</span>
    @endif
</span>
