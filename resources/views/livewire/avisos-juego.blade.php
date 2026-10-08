{{-- Campanita de notificaciones del juego + panel con Notificaciones y Transacciones (ver App\Livewire\AvisosJuego).
     wire:poll.visible: solo pregunta si hay avisos nuevos mientras el botón se ve (en PC y en el celular hay uno cada uno) --}}
<div wire:poll.30s.visible class="relative shrink-0">
    @php
        $boton3d = 'shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000,0_4px_6px_rgba(0,0,0,0.6)] active:translate-y-[3px] active:shadow-[inset_1px_1px_0_rgba(255,255,255,0.2),inset_-1px_-1px_0_rgba(0,0,0,0.6)] transition-all duration-100 hover:brightness-125';
        $badge = 'absolute -top-1.5 -right-1.5 min-w-[1.1rem] h-[1.1rem] px-1 flex items-center justify-center rounded-full bg-red-600 border border-black text-[10px] font-extrabold text-white';
    @endphp

    @if ($estilo === 'celular')
        <button type="button" wire:click="abrir" title="Notificaciones" aria-label="Notificaciones"
            class="relative w-9 h-9 text-base flex items-center justify-center rounded-full border-2 border-black text-white bg-gradient-to-b from-amber-400 to-amber-700 {{ $boton3d }}">
            <i class="fa-solid fa-bell"></i>
            @if ($sinLeer > 0)
                <span class="{{ $badge }}">{{ $sinLeer > 99 ? '99+' : $sinLeer }}</span>
            @endif
        </button>
    @else
        <button type="button" wire:click="abrir"
            class="relative flex items-center gap-1.5 px-3 py-1 rounded-md font-semibold uppercase border-2 border-yellow-500 text-yellow-300 bg-gradient-to-b from-amber-700 to-gray-900 {{ $boton3d }}">
            <i class="fa-solid fa-bell"></i> Avisos
            @if ($sinLeer > 0)
                <span class="{{ $badge }}">{{ $sinLeer > 99 ? '99+' : $sinLeer }}</span>
            @endif
        </button>
    @endif

    @if ($abierto)
        @teleport('body')
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 p-3" wire:click.self="cerrar">
            <div class="relative w-full max-w-md max-h-[85dvh] flex flex-col rounded-xl border border-black text-white
                        bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_4px_0_#000,0_6px_10px_rgba(0,0,0,0.6)]">
                <button type="button" wire:click="cerrar" aria-label="Cerrar"
                    class="absolute top-2 right-2 z-10 w-7 h-7 flex items-center justify-center rounded-full border-2 border-black bg-gradient-to-b from-red-600 to-red-900 text-white text-sm font-bold shadow-[0_2px_0_#000] hover:brightness-125 active:translate-y-[2px] active:shadow-none transition-all duration-100">&times;</button>

                {{-- Pestañas --}}
                <div class="flex gap-2 p-3 pr-11 shrink-0">
                    @foreach (['avisos' => ['fa-bell', 'Notificaciones'], 'transacciones' => ['fa-right-left', 'Transacciones']] as $clave => [$icono, $nombre])
                        <button type="button" wire:click="verPestana('{{ $clave }}')"
                            class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-md border border-black text-xs font-bold uppercase bg-gradient-to-b {{ $boton3d }}
                                   {{ $pestana === $clave ? 'from-yellow-400 to-yellow-700 text-black' : 'from-[#34405a] to-[#10151d] text-gray-200' }}">
                            <i class="fa-solid {{ $icono }}"></i> {{ $nombre }}
                        </button>
                    @endforeach
                </div>

                <div class="flex-1 min-h-0 overflow-y-auto px-3 pb-3 space-y-2 sidebar-pj">
                    @if ($pestana === 'avisos')
                        @forelse ($avisos as $aviso)
                            <div wire:key="aviso-{{ $aviso->id }}"
                                 class="flex gap-2.5 items-start p-2 rounded-lg border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.15),0_2px_0_#000]">
                                <span class="text-xl leading-none shrink-0">{{ $aviso->icono ?? '🔔' }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm leading-snug">{{ $aviso->mensaje }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $aviso->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm italic text-gray-400">No tenés notificaciones.</p>
                        @endforelse
                    @else
                        @php
                            // "2 objetos: X, Y · 500 oro · 3 esmeraldas" (o "nada")
                            $describir = function (array $lado) {
                                $partes = [];
                                if (! empty($lado['objetos'])) {
                                    $partes[] = implode(', ', $lado['objetos']);
                                }
                                if (($lado['oro'] ?? 0) > 0) {
                                    $partes[] = number_format($lado['oro'], 0, ',', '.') . ' de oro';
                                }
                                if (($lado['diamante'] ?? 0) > 0) {
                                    $partes[] = number_format($lado['diamante'], 0, ',', '.') . ' ' . ($lado['diamante'] == 1 ? 'esmeralda' : 'esmeraldas');
                                }
                                return $partes ? implode(' · ', $partes) : 'nada';
                            };
                        @endphp
                        @forelse ($transacciones as $t)
                            @php
                                $soyDe = (int) $t->de_personaje_id === (int) $personajeId;
                                $otro = ($soyDe ? $t->para : $t->de)?->nombre ?? 'un jugador que ya no está';
                                $yoDi = $t->detalle[$soyDe ? 'de' : 'para'] ?? [];
                                $recibi = $t->detalle[$soyDe ? 'para' : 'de'] ?? [];
                            @endphp
                            <div wire:key="transaccion-{{ $t->id }}"
                                 class="p-2 rounded-lg border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.15),0_2px_0_#000] text-sm space-y-0.5">
                                <p class="flex items-center justify-between gap-2">
                                    <span class="font-bold {{ $t->tipo === 'mercado' ? 'text-yellow-300' : 'text-sky-300' }}">
                                        @if ($t->tipo === 'mercado')
                                            {{ $soyDe ? '💰 Venta' : '🛒 Compra' }} en el mercado
                                        @else
                                            🤝 Intercambio
                                        @endif
                                    </span>
                                    <span class="text-[10px] text-gray-400 shrink-0">{{ $t->created_at->diffForHumans() }}</span>
                                </p>
                                <p class="text-gray-300 text-xs">{{ $t->tipo === 'mercado' ? ($soyDe ? 'Le vendiste a' : 'Le compraste a') : 'Con' }} <span class="font-bold text-white">{{ $otro }}</span></p>
                                <p class="text-xs"><span class="text-red-300 font-semibold">Diste:</span> {{ $describir($yoDi) }}</p>
                                <p class="text-xs"><span class="text-emerald-300 font-semibold">Recibiste:</span> {{ $describir($recibi) }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm italic text-gray-400">Todavía no hiciste compras ni intercambios con otros jugadores.</p>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
