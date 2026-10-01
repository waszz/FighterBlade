@php
    $panel3d = 'bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]';
    $boton3d = 'rounded-lg border border-black font-bold shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all';
    $input = 'w-full px-2 py-1.5 rounded-lg border border-black bg-black/50 text-white text-sm shadow-[inset_0_2px_6px_rgba(0,0,0,0.9)] focus:outline-none focus:ring-2 focus:ring-yellow-500';
    $semana = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'];
    // Una línea de evento (ícono de su tipo + hora + título)
    $linea = fn ($e, $chico = false) => '<span class="flex items-center gap-1.5 min-w-0 ' . ($chico ? 'text-[11px]' : 'text-sm') . '">'
        . '<i class="fa-solid ' . ($tipos[$e['tipo']]['icono'] ?? 'fa-star') . ' ' . ($tipos[$e['tipo']]['color'] ?? 'text-gray-300') . ' shrink-0 w-4 text-center"></i>'
        . '<span class="shrink-0 font-bold text-white">' . e($e['hora']) . '</span>'
        . '<span class="truncate text-gray-300">' . e($e['titulo']) . '</span></span>';
@endphp

<div class="text-white p-2 sm:p-4 max-w-6xl mx-auto space-y-4">
    {{-- Título y mes --}}
    <div class="text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-[0.2em] text-orange-400 [text-shadow:0_0_10px_rgba(251,146,60,0.45),2px_2px_0_#7c2d12]">CALENDARIO</h2>
        <div class="mt-2 inline-flex items-center gap-2">
            <button type="button" wire:click="mesAnterior" aria-label="Mes anterior" class="{{ $boton3d }} w-8 h-8 bg-gradient-to-b from-red-600 to-red-900"><i class="fa-solid fa-chevron-left text-xs"></i></button>
            <p class="min-w-[11rem] px-3 py-1 rounded-lg border border-black text-lg font-bold text-yellow-300 {{ $panel3d }}">{{ $nombreMes }}</p>
            <button type="button" wire:click="mesSiguiente" aria-label="Mes siguiente" class="{{ $boton3d }} w-8 h-8 bg-gradient-to-b from-red-600 to-red-900"><i class="fa-solid fa-chevron-right text-xs"></i></button>
            <button type="button" wire:click="irAHoy" class="{{ $boton3d }} px-2 h-8 text-xs bg-gradient-to-b from-[#2f5470] to-[#0a1a26]">Hoy</button>
        </div>
        <p class="mt-1 text-[11px] text-gray-400">Horarios de Uruguay. Tocá un día para ver el detalle.</p>
    </div>

    {{-- Referencias --}}
    <div class="flex flex-wrap justify-center gap-x-3 gap-y-1 text-[11px]">
        @foreach ($tipos as $t)
            <span class="flex items-center gap-1 text-gray-300"><i class="fa-solid {{ $t['icono'] }} {{ $t['color'] }}"></i>{{ $t['nombre'] }}</span>
        @endforeach
    </div>

    {{-- Admin: agregar un evento --}}
    @if ($esAdmin)
        <details class="rounded-xl border border-black {{ $panel3d }}">
            <summary class="cursor-pointer select-none px-3 py-2 text-sm font-bold text-yellow-300"><i class="fa-solid fa-plus mr-1"></i> Agregar evento (admin)</summary>
            <form wire:submit="guardarEvento" class="grid grid-cols-1 sm:grid-cols-6 gap-2 p-3 pt-0 text-xs">
                <label class="sm:col-span-3">Título <input type="text" wire:model="titulo" maxlength="80" class="{{ $input }}" placeholder="Ej: Torneo PvP"></label>
                <label class="sm:col-span-3">Tipo
                    <select wire:model="tipo" class="{{ $input }}">
                        @foreach ($tipos as $clave => $t)
                            @continue(in_array($clave, ['mercado', 'buff'], true))
                            <option value="{{ $clave }}">{{ $t['nombre'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="sm:col-span-2">Día <input type="date" wire:model="fecha" class="{{ $input }}"></label>
                <label>Hora <input type="time" wire:model="hora" class="{{ $input }}"></label>
                <label class="sm:col-span-2">Termina el día (opcional) <input type="date" wire:model="fechaFin" class="{{ $input }}"></label>
                <label>Hora fin <input type="time" wire:model="horaFin" class="{{ $input }}"></label>
                <label class="sm:col-span-6">Descripción (opcional) <input type="text" wire:model="descripcion" maxlength="300" class="{{ $input }}"></label>
                @if ($errors->any())
                    <p class="sm:col-span-6 text-red-400">{{ $errors->first() }}</p>
                @endif
                <button type="submit" class="sm:col-span-6 {{ $boton3d }} py-2 text-sm text-white bg-gradient-to-b from-green-500 to-green-800">Agregar al calendario</button>
            </form>
        </details>
    @endif

    {{-- PC: la grilla del mes --}}
    <div class="hidden md:grid grid-cols-7 gap-1.5">
        @foreach ($dias as $dia)
            <button type="button" wire:click="abrirDia('{{ $dia['fecha'] }}')" wire:key="dia-{{ $dia['fecha'] }}"
                    class="min-h-[6.5rem] p-1.5 rounded-lg border text-left flex flex-col gap-1 transition hover:brightness-125
                           {{ $dia['hoy'] ? 'border-yellow-400 shadow-[0_0_12px_rgba(250,204,21,0.45)]' : 'border-black' }}
                           {{ $dia['delMes'] ? 'bg-gradient-to-b from-[#1c2533] to-[#0a0e14]' : 'bg-black/30' }}
                           {{ ! $dia['delMes'] ? 'opacity-30' : ($dia['pasado'] ? 'opacity-50' : '') }}">
                <span class="flex items-center justify-between text-[10px] font-bold {{ $dia['hoy'] ? 'text-yellow-300' : 'text-gray-400' }}">
                    <span>{{ $dia['numero'] }}</span><span>{{ $dia['semana'] }}</span>
                </span>
                @foreach (array_slice($dia['eventos'], 0, 3) as $e)
                    {!! $linea($e, true) !!}
                @endforeach
                @if (count($dia['eventos']) > 3)
                    <span class="text-[10px] text-gray-400">+{{ count($dia['eventos']) - 3 }} más</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Celular: lista de los días del mes que tienen eventos (y hoy) --}}
    <div class="md:hidden space-y-1.5">
        @php $diasConEventos = array_filter($dias, fn ($d) => $d['delMes'] && ($d['eventos'] || $d['hoy'])); @endphp
        @forelse ($diasConEventos as $dia)
            <button type="button" wire:click="abrirDia('{{ $dia['fecha'] }}')" wire:key="dia-m-{{ $dia['fecha'] }}"
                    class="w-full flex gap-3 p-2 rounded-lg border text-left {{ $panel3d }} {{ $dia['hoy'] ? 'border-yellow-400' : 'border-black' }} {{ $dia['pasado'] ? 'opacity-50' : '' }}">
                <span class="w-12 shrink-0 text-center">
                    <span class="block text-2xl font-extrabold leading-none {{ $dia['hoy'] ? 'text-yellow-300' : 'text-white' }}">{{ $dia['numero'] }}</span>
                    <span class="block text-[9px] font-bold text-gray-400">{{ mb_substr($dia['semana'], 0, 3) }}</span>
                </span>
                <span class="flex-1 min-w-0 flex flex-col gap-1 justify-center">
                    @forelse ($dia['eventos'] as $e)
                        {!! $linea($e) !!}
                    @empty
                        <span class="text-xs text-gray-400 italic">Hoy no hay eventos</span>
                    @endforelse
                </span>
            </button>
        @empty
            <p class="text-center text-sm text-gray-400 italic">No hay eventos este mes.</p>
        @endforelse
    </div>

    {{-- Detalle de un día --}}
    @if ($diaDetalle)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-3" wire:click.self="$set('diaAbierto', null)">
            <div class="relative w-full max-w-sm p-4 rounded-xl border border-black {{ $panel3d }}">
                <button type="button" wire:click="$set('diaAbierto', null)" class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center {{ $boton3d }} bg-gradient-to-b from-red-500 to-red-800">&times;</button>
                <h3 class="pr-8 text-lg font-extrabold text-yellow-300 [text-shadow:0_2px_0_#000]">{{ $diaDetalle['fecha'] }}</h3>
                <div class="mt-3 space-y-2">
                    @forelse ($diaDetalle['eventos'] as $e)
                        <div class="p-2 rounded-lg bg-black/40 border border-white/10">
                            <div class="flex items-center justify-between gap-2">
                                {!! $linea($e) !!}
                                @if ($esAdmin && $e['id'])
                                    <x-confirmar titulo="¿Borrar el evento?" texto="{{ e($e['titulo']) }}" accion="borrarEvento" :parametros="[$e['id']]" boton="Borrar">
                                        <button type="button" class="shrink-0 text-xs text-red-400 hover:text-red-300"><i class="fa-solid fa-trash"></i></button>
                                    </x-confirmar>
                                @endif
                            </div>
                            @if ($e['descripcion'])
                                <p class="mt-1 text-xs text-gray-400">{{ $e['descripcion'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No hay eventos este día.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
