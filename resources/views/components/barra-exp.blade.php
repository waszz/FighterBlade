{{-- Barra de EXP del personaje (la del sidebar): progreso del nivel actual, divisiones cada 10%
     y al pasar el mouse la EXP que falta para subir. Nivel N necesita 10000 × N² de EXP total. --}}
@props(['personaje'])
@php
    $nivel = $personaje->nivel ?? 1;
    $expActual = $personaje->experiencia ?? 0;
    $expNivelActual = 10000 * pow($nivel - 1, 2);
    $expSiguienteNivel = 10000 * pow($nivel, 2);
    $expProgreso = max(0, $expActual - $expNivelActual);
    $expNecesaria = $expSiguienteNivel - $expNivelActual;
    $porcentaje = $expNecesaria > 0 ? min(100, ($expProgreso / $expNecesaria) * 100) : 100;
    $expFaltante = max(0, $expSiguienteNivel - $expActual);
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 select-none']) }}>
    <div class="w-8 h-6 shrink-0 flex items-center justify-center border border-black rounded bg-gradient-to-b from-[#2f5470] to-[#0a1a26] shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_2px_2px_rgba(0,0,0,0.6)] text-sm font-bold uppercase tracking-wide text-fuchsia-400">Exp</div>
    <div class="group relative flex-1 min-w-0">
        <div class="h-5 rounded-sm overflow-hidden relative bg-[#0a1020] border border-black shadow-[inset_0_1px_3px_rgba(0,0,0,0.8),0_0_0_1px_rgba(90,130,200,0.35)] cursor-help">
            <div class="h-full transition-all duration-700 ease-in-out"
                style="width: {{ round($porcentaje) }}%; background: linear-gradient(to bottom, #f87171, #dc2626 55%, #991b1b); box-shadow: 0 0 6px rgba(239,68,68,0.6);">
            </div>
            {{-- Líneas divisorias cada 10% --}}
            <div class="absolute inset-0 pointer-events-none"
                style="background-image: repeating-linear-gradient(90deg, transparent 0, transparent calc(10% - 1px), rgba(0,0,0,0.45) calc(10% - 1px), rgba(0,0,0,0.45) 10%);"></div>
            <div class="absolute inset-0 flex items-center px-1.5 text-white font-bold text-[10px] tracking-tight whitespace-nowrap select-none [text-shadow:1px_1px_0_#000]">
                {{ round($porcentaje) }}% · {{ number_format($expProgreso, 0, ',', '.') }}/{{ number_format($expNecesaria, 0, ',', '.') }}
            </div>
        </div>

        {{-- Tooltip: EXP que falta para subir --}}
        <div class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 z-20 whitespace-nowrap px-2 py-1 rounded border border-[#3d7fd6] bg-[#0a1628] text-[10px] text-white shadow-[0_2px_6px_rgba(0,0,0,0.7)] opacity-0 group-hover:opacity-100 transition-opacity duration-150">
            Faltan <span class="font-bold text-emerald-400">{{ number_format($expFaltante, 0, ',', '.') }}</span> EXP
            <div class="text-gray-400">para Nv. {{ $nivel + 1 }}</div>
        </div>
    </div>
</div>
