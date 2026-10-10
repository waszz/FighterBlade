@php
    $panel3d = 'border border-black bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.6)]';
    $caja3d = 'border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
    $boton3d = 'px-5 py-2 rounded-lg border border-black text-white font-bold bg-gradient-to-b shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all duration-100';
    $cuenta = fn ($ts) => "x-data=\"{ fin: {$ts} * 1000, ahora: Date.now() }\" x-init=\"setInterval(() => ahora = Date.now(), 1000)\" x-text=\"(() => { const s = Math.max(0, Math.floor((fin - ahora) / 1000)); const d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60), x = s % 60; return (d ? d + 'd ' : '') + (h ? h + 'h ' : '') + String(m).padStart(2, '0') + ':' + String(x).padStart(2, '0'); })()\"";
@endphp

<div class="max-w-3xl mx-auto text-white space-y-4" @if ($guerra) wire:poll.15s @endif>
    <div class="p-4 rounded-xl {{ $panel3d }}">
        <h2 class="text-2xl font-bold text-orange-400 text-center [text-shadow:0_2px_0_#000]"><i class="fa-solid fa-shield-halved"></i> Guerra de clanes</h2>
        <p class="text-center text-sm text-gray-300 mt-1">Domingos de {{ \App\Models\GuerraClanes::HORA_INICIO }} a {{ \Carbon\Carbon::parse(\App\Models\GuerraClanes::HORA_INICIO)->addMinutes(\App\Models\GuerraClanes::MINUTOS)->format('H:i') }}</p>
        <ul class="mt-3 text-xs text-gray-300 space-y-1 max-w-xl mx-auto list-disc pl-5">
            <li>Viajá gratis a la zona de la guerra (necesitás estar en un clan). Al terminar volvés solo a tu zona.</li>
            <li>Atacá a jugadores de <b class="text-white">otros clanes</b> en esa zona: ganar suma <b class="text-white">+{{ \App\Models\GuerraClanes::PUNTOS_VICTORIA }}</b> a tu clan y le resta <b class="text-white">{{ \App\Models\GuerraClanes::PUNTOS_DERROTA }}</b> al del perdedor. Esas peleas <b class="text-white">no dan exp</b>.</li>
            <li>Gana el clan con más puntos: suma esos puntos a su <b class="text-white">prestigio</b> y sus miembros reciben <b class="text-white">+{{ \App\Models\GuerraClanes::BUFF_PORCENTAJE }}% de exp, oro y drop</b> por {{ \App\Models\GuerraClanes::BUFF_HORAS }} horas.</li>
        </ul>
    </div>

    <div class="p-4 rounded-xl text-center {{ $panel3d }}">
        @if ($guerra)
            <p class="text-sm text-gray-300">¡La guerra está en curso! Termina en</p>
            <p class="text-3xl font-bold text-orange-300 font-mono" {!! $cuenta($guerra->fin()->timestamp) !!}></p>
            <div class="relative w-full max-w-[650px] mx-auto mt-3 rounded-xl overflow-hidden border-2 border-gray-900 shadow-lg bg-gray-800" style="height: 220px">
                @if ($guerra->ciudad?->gif)
                    <img src="{{ asset('storage/posts/' . $guerra->ciudad->gif) }}" alt="" class="absolute inset-0 w-full h-full object-cover object-bottom">
                @endif
                <span class="absolute top-2 left-2 px-2 py-0.5 rounded bg-black/60 text-[11px] font-bold text-yellow-300">Zona: {{ $guerra->ciudad->nombre }}</span>
            </div>
            <div class="mt-3">
                @if (! $personaje->clan_id)
                    <p class="text-sm text-red-400">Necesitás estar en un clan para participar.</p>
                @elseif ($enLaZona)
                    <p class="text-sm text-emerald-300 font-bold">Estás en la zona de la guerra: atacá desde los usuarios de la zona (en la Ciudad).</p>
                    <button type="button" x-on:click="Livewire.dispatch('cambiarSeccion', { nuevaSeccion: 'inicio' })" class="mt-2 {{ $boton3d }} from-red-500 to-red-800">Ir a la Ciudad</button>
                @else
                    <button wire:click="viajar" wire:loading.attr="disabled" class="{{ $boton3d }} from-sky-500 to-sky-800">
                        <i class="fa-solid fa-plane"></i> Viajar a {{ $guerra->ciudad->nombre }} (gratis)
                    </button>
                @endif
            </div>
        @else
            <p class="text-sm text-gray-300">Próxima guerra: <b class="text-white">domingo {{ $proximo->format('d/m') }} a las {{ $proximo->format('H:i') }}</b></p>
            <p class="text-3xl font-bold text-orange-300 font-mono mt-1" {!! $cuenta($proximo->timestamp) !!}></p>
        @endif

        @if ($mostrar && $mostrar->estado === 'terminada')
            <div class="mt-4 p-3 rounded-lg {{ $caja3d }}">
                <p class="text-xs text-gray-400">Última guerra ({{ $mostrar->fecha->format('d/m') }})</p>
                <p class="text-lg font-bold text-yellow-300"><i class="fa-solid fa-crown"></i> {{ $mostrar->ganador ? $mostrar->ganador->nombre . ' [' . $mostrar->ganador->tag . ']' : 'Sin ganador' }}</p>
            </div>
        @endif
    </div>

    @if ($tabla->isNotEmpty())
        <div class="p-4 rounded-xl {{ $panel3d }}">
            <h3 class="text-lg font-bold text-yellow-300 text-center mb-3 [text-shadow:0_2px_0_#000]">Puntos de la guerra</h3>
            <div class="space-y-1.5 max-w-md mx-auto">
                @foreach ($tabla as $clanId => $puntos)
                    <div class="flex items-center justify-between px-3 py-1.5 rounded-lg {{ $caja3d }} {{ (int) $clanId === (int) $personaje->clan_id ? 'ring-2 ring-yellow-400' : '' }}">
                        <span class="font-bold truncate">{{ $loop->iteration }}. {{ $clanes[$clanId]->nombre ?? 'Clan' }} <span class="text-xs text-sky-300">[{{ $clanes[$clanId]->tag ?? '?' }}]</span></span>
                        <span class="font-mono font-bold {{ $puntos >= 0 ? 'text-emerald-300' : 'text-red-400' }}">{{ $puntos }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
