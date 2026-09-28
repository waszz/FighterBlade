{{-- Botón Atacar (PvP). Mientras el atacante explora o se recupera queda gris con la cuenta regresiva y se prende solo al terminar
     (el servidor igual lo vuelve a controlar al atacar) --}}
@props(['personaje', 'href'])
@php
    $segundosEspera = $personaje->fin_exploracion
        ? max(0, \Carbon\Carbon::parse($personaje->fin_exploracion)->timestamp - now()->timestamp)
        : 0;
    $motivoEspera = $personaje->exploracion_duracion > 0 ? '🧭 Estás explorando' : '⏳ Te estás recuperando';
    // El entrenamiento también bloquea atacar (y suele durar más)
    if ($personaje->segundosEntreno() > $segundosEspera) {
        $segundosEspera = $personaje->segundosEntreno();
        $motivoEspera = '🏋️ Estás entrenando';
    }
@endphp
<div wire:key="boton-atacar-{{ $segundosEspera }}"
     x-data="{ fin: Date.now() / 1000 + {{ $segundosEspera }}, s: {{ $segundosEspera }} }"
     x-init="if (s > 0) { const t = setInterval(() => { s = Math.max(0, Math.ceil(fin - Date.now() / 1000)); if (s <= 0) clearInterval(t); }, 250) }">
    <a href="{{ $href }}" x-show="s <= 0" @if ($segundosEspera > 0) x-cloak @endif
       class="block w-full text-center py-2 rounded-lg border border-black font-bold text-white bg-gradient-to-b from-red-500 to-red-800
              shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all">
        Atacar
    </a>
    <p x-show="s > 0" @if ($segundosEspera <= 0) x-cloak @endif title="No podés atacar hasta que termine"
       class="w-full text-center py-2 rounded-lg border border-black font-bold text-gray-300 bg-gradient-to-b from-gray-600 to-gray-800 shadow-[0_3px_0_#000] cursor-not-allowed select-none">
        Atacar
        <span class="block text-[11px] font-semibold">{{ $motivoEspera }} ·
            <span x-text="(s >= 3600 ? Math.floor(s / 3600) + ':' : '') + String(Math.floor(s % 3600 / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')">{{ $segundosEspera >= 3600 ? gmdate('G:i:s', $segundosEspera) : gmdate('i:s', $segundosEspera) }}</span>
        </span>
    </p>
</div>
