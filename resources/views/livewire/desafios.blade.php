{{-- Duelos e intercambios: avisos abajo a la derecha y la ventana de intercambio. Se actualiza cada 10 s (antes 2 s: gastaba mucho ancho de banda) --}}
<div wire:poll.10s>
    @php
        $panel3d = 'rounded-xl border border-black text-white bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]';
        $caja3d = 'rounded-lg border border-black bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.3),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000]';
        $boton = 'px-3 py-1.5 rounded-lg border border-black text-sm font-bold text-white bg-gradient-to-b shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none transition-all disabled:opacity-50 disabled:active:translate-y-0';
        $nombreTipo = ['duelo' => '⚔️ Duelo', 'intercambio' => '🔁 Intercambio'];
        $imgObjeto = fn ($o) => $o->pocion ? asset('images/' . $o->imagen) : asset('storage/posts/' . $o->imagen);
        // Color de cada parte (los mismos del Inventario): [borde, texto, nombre corto]
        $colorParte = fn ($o) => match ($o->pocion ? 'pocion' : $o->tipo) {
            'equipo'        => ['border-indigo-500', 'text-indigo-300', 'Equipo'],
            'entrenamiento' => ['border-green-500', 'text-green-300', 'Entrenam.'],
            'accesorio'     => ['border-pink-500', 'text-pink-300', 'Accesorio'],
            'joya'          => ['border-yellow-400', 'text-yellow-300', 'Joya'],
            'pocion'        => ['border-orange-400', 'text-orange-300', 'Poción'],
            default         => ['border-gray-500', 'text-gray-300', ucfirst((string) $o->tipo)],
        };
        // Cuenta regresiva (se calcula contra la hora de fin, así no se atrasa entre actualizaciones)
        $cuenta = fn ($segundos) => "{ fin: Date.now() / 1000 + $segundos, s: $segundos }";
        $cuentaInit = "setInterval(() => s = Math.max(0, Math.ceil(fin - Date.now() / 1000)), 250)";
    @endphp

    {{-- Avisos abajo a la derecha --}}
    <div class="fixed bottom-3 right-3 z-[60] flex flex-col gap-2 w-[min(20rem,calc(100vw-1.5rem))]">

        {{-- Me desafiaron / me pidieron un intercambio: 30 s para responder --}}
        @if ($entrante)
            <div wire:key="entrante-{{ $entrante->id }}" x-data="{{ $cuenta($entrante->segundosRestantes()) }}" x-init="{{ $cuentaInit }}"
                 class="p-3 {{ $panel3d }} border-yellow-500/70">
                <div class="flex items-center gap-2">
                    @if ($entrante->de?->post?->imagen)
                        <img src="{{ asset('storage/' . $entrante->de->post->imagen) }}" alt="" class="w-10 h-10 shrink-0 rounded-full object-cover border border-black">
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-tight">
                            <b class="{{ $entrante->de?->claseNombre() }}">{{ $entrante->de?->nombre }}</b>
                            {{ $entrante->tipo === 'duelo' ? 'te desafía a un duelo' : 'quiere hacer un intercambio' }}
                        </p>
                        <p class="text-[11px] text-gray-300">{{ $entrante->tipo === 'duelo' ? 'Pelea amistosa: sin premio ni recuperación' : 'Hasta 3 objetos, oro y esmeraldas' }}</p>
                    </div>
                    <span class="shrink-0 font-mono font-bold text-yellow-300" x-text="s + 's'">{{ $entrante->segundosRestantes() }}s</span>
                </div>
                <div class="mt-2 h-1 rounded-full bg-black/60 overflow-hidden">
                    <div class="h-full bg-yellow-400 transition-all duration-300" :style="'width:' + (s / {{ \App\Models\Desafio::SEGUNDOS_RESPUESTA }} * 100) + '%'"></div>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <button type="button" wire:click="aceptar({{ $entrante->id }})" wire:loading.attr="disabled" class="{{ $boton }} from-emerald-500 to-emerald-800">Aceptar</button>
                    <button type="button" wire:click="rechazar({{ $entrante->id }})" wire:loading.attr="disabled" class="{{ $boton }} from-red-500 to-red-800">Rechazar</button>
                </div>
            </div>
        @endif

        {{-- Pedí algo y espero la respuesta --}}
        @if ($saliente)
            <div wire:key="saliente-{{ $saliente->id }}" x-data="{{ $cuenta($saliente->segundosRestantes()) }}" x-init="{{ $cuentaInit }}"
                 class="p-3 {{ $panel3d }}">
                <p class="text-sm leading-tight">
                    {{ $nombreTipo[$saliente->tipo] ?? '' }} · Esperando a <b class="{{ $saliente->para?->claseNombre() }}">{{ $saliente->para?->nombre }}</b>…
                    <span class="font-mono font-bold text-yellow-300" x-text="s + 's'">{{ $saliente->segundosRestantes() }}s</span>
                </p>
                <button type="button" wire:click="cancelar({{ $saliente->id }})" class="mt-2 w-full {{ $boton }} from-gray-600 to-gray-800">Cancelar</button>
            </div>
        @endif

        {{-- Cómo terminó lo que pedí --}}
        @if ($aviso)
            @php
                $nombreOtro = $aviso->para?->nombre ?? 'El otro jugador';
                $textoAviso = match (true) {
                    $aviso->estado === 'rechazado' => "$nombreOtro rechazó tu " . ($aviso->tipo === 'duelo' ? 'duelo.' : 'intercambio.'),
                    $aviso->estado === 'expirado' && $aviso->tipo === 'intercambio' && $aviso->oferta_de !== null => 'Se terminó el tiempo del intercambio: no se pasó nada.',
                    $aviso->estado === 'expirado' => "$nombreOtro no respondió a tiempo.",
                    $aviso->estado === 'cancelado' => "$nombreOtro cerró el intercambio: no se pasó nada.",
                    $aviso->tipo === 'duelo' => "$nombreOtro aceptó el duelo y ya pelearon.",
                    default => "¡Intercambio con $nombreOtro hecho!",
                };
            @endphp
            <div wire:key="aviso-{{ $aviso->id }}-{{ $aviso->estado }}" class="p-3 {{ $panel3d }}">
                <p class="text-sm leading-tight">{{ $nombreTipo[$aviso->tipo] ?? '' }} · {{ $textoAviso }}</p>
                <div class="mt-2 flex gap-2">
                    @if ($aviso->tipo === 'duelo' && $aviso->estado === 'completado' && $aviso->pelea_id)
                        <button type="button" wire:click="verDuelo({{ $aviso->id }})" class="flex-1 {{ $boton }} from-sky-500 to-sky-800">Ver la pelea</button>
                    @endif
                    <button type="button" wire:click="marcarVisto({{ $aviso->id }})" class="flex-1 {{ $boton }} from-gray-600 to-gray-800">Cerrar</button>
                </div>
            </div>
        @endif
    </div>

    {{-- Ventana de intercambio: los dos personajes, lo que pone cada uno y abajo mi inventario --}}
    @if ($intercambio && $datosIntercambio)
        @php
            $di = $datosIntercambio;
            $gifDe = fn ($pj) => $pj?->postDeCombate()?->gif ?? $pj?->post?->gif;
        @endphp
        <div wire:key="intercambio-{{ $intercambio->id }}" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/80 px-2">
            <div class="relative w-full max-w-3xl max-h-[94vh] overflow-y-auto p-3 sm:p-5 {{ $panel3d }}"
                 x-data="{{ $cuenta($intercambio->segundosRestantes()) }}" x-init="{{ $cuentaInit }}">
                <h2 class="text-center text-lg font-bold text-yellow-300 [text-shadow:0_2px_0_#000]">🔁 Intercambio</h2>
                <p class="text-center text-[11px] text-gray-300 mb-3">
                    Cierra en <span class="font-mono font-bold text-yellow-300" x-text="String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0')"></span>
                    · Si alguien cambia algo, los dos tienen que volver a confirmar
                </p>

                {{-- Los dos lados --}}
                <div class="grid grid-cols-2 gap-2 sm:gap-3">
                    @foreach ([['pj' => $di['yo'], 'oferta' => $di['miOferta'], 'objetos' => $di['misObjetos'], 'listo' => $di['yoListo'], 'soyYo' => true],
                               ['pj' => $di['otro'], 'oferta' => $di['suOferta'], 'objetos' => $di['susObjetos'], 'listo' => $di['otroListo'], 'soyYo' => false]] as $lado)
                        <div class="p-2 {{ $caja3d }} {{ $lado['listo'] ? '!border-emerald-400' : '' }}">
                            <div class="h-28 flex items-end justify-center overflow-hidden rounded-lg border-2 border-black bg-black/50 shadow-[inset_0_4px_10px_rgba(0,0,0,0.9)]">
                                @if ($g = $gifDe($lado['pj']))
                                    <img src="{{ asset('storage/' . $g) }}" alt="" style="{{ \App\Models\Post::estiloGif($g, 0.6) }}"
                                         class="block max-w-none {{ $lado['soyYo'] ? '' : 'scale-x-[-1]' }}">
                                @endif
                            </div>
                            <p class="mt-1 text-center text-sm font-bold truncate">
                                <span class="{{ $lado['pj']?->claseNombre() }}">{{ $lado['pj']?->nombre }}</span>{{ $lado['soyYo'] ? ' (vos)' : '' }}
                                <span class="text-[11px] text-sky-300">Nv. {{ $lado['pj']?->nivel }}</span>
                            </p>

                            {{-- 3 lugares para objetos --}}
                            <div class="mt-2 grid grid-cols-3 gap-1.5 sm:gap-2">
                                @for ($i = 0; $i < \App\Models\Desafio::MAX_OBJETOS; $i++)
                                    @php
                                        $obj = $lado['objetos'][$i] ?? null;
                                        [$bordeParte, $textoParte, $nombreParte] = $obj ? $colorParte($obj) : ['border-black', '', ''];
                                    @endphp
                                    <div class="flex flex-col items-center">
                                        <div class="w-full aspect-square rounded-lg border-[3px] {{ $obj ? $bordeParte : 'border-black border-dashed' }} bg-black/50 flex items-center justify-center overflow-hidden relative"
                                             @if ($obj) title="{{ $obj->nombre }} · Nv {{ $obj->nivel }}" @endif>
                                            @if ($obj)
                                                <img src="{{ $imgObjeto($obj) }}" alt="{{ $obj->nombre }}" class="w-full h-full object-cover">
                                                <span class="absolute bottom-0 inset-x-0 text-[10px] font-bold text-yellow-300 bg-black/70 text-center leading-tight">Nv {{ $obj->nivel }}</span>
                                                @if ($lado['soyYo'])
                                                    <button type="button" wire:click="alternarObjeto({{ $obj->id }})" aria-label="Sacar"
                                                            class="absolute top-0 right-0 w-6 h-6 flex items-center justify-center rounded-bl-md bg-red-700 text-white text-sm font-bold">&times;</button>
                                                @endif
                                            @endif
                                        </div>
                                        <span class="mt-0.5 h-4 text-[10px] font-bold truncate max-w-full {{ $textoParte }}">{{ $nombreParte }}</span>
                                    </div>
                                @endfor
                            </div>

                            {{-- Oro y esmeraldas --}}
                            <div class="mt-1 grid grid-cols-2 gap-1 font-mono text-xs font-bold">
                                <span class="flex items-center justify-center gap-1 py-0.5 rounded bg-black/50 text-yellow-400"><img src="{{ asset('images/oro.png') }}" alt="" class="h-3.5">{{ number_format($lado['oferta']['oro'] ?? 0, 0, ',', '.') }}</span>
                                <span class="flex items-center justify-center gap-1 py-0.5 rounded bg-black/50 text-emerald-300"><img src="{{ asset('images/diamante.png') }}" alt="" class="h-3.5"><span class="num-esmeralda">{{ number_format($lado['oferta']['diamante'] ?? 0, 0, ',', '.') }}</span></span>
                            </div>

                            <p class="mt-1 text-center text-[11px] font-bold {{ $lado['listo'] ? 'text-emerald-300' : 'text-gray-400' }}">
                                {{ $lado['listo'] ? '✔ Confirmó' : 'Sin confirmar' }}
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Oro y esmeraldas que pongo --}}
                <div class="mt-3 p-2 {{ $caja3d }}">
                    <div class="flex flex-wrap items-center justify-center gap-2 text-sm">
                        <label class="flex items-center gap-1">
                            <img src="{{ asset('images/oro.png') }}" alt="Oro" class="h-4">
                            <input type="number" min="0" max="{{ $di['yo']->oro }}" wire:model="oroOferta"
                                   class="w-28 px-2 py-1 rounded border border-black bg-black/50 text-white text-sm">
                        </label>
                        <label class="flex items-center gap-1">
                            <img src="{{ asset('images/diamante.png') }}" alt="Esmeraldas" class="h-4">
                            <input type="number" min="0" max="{{ $di['yo']->diamante }}" wire:model="diamanteOferta"
                                   class="w-24 px-2 py-1 rounded border border-black bg-black/50 text-white text-sm">
                        </label>
                        <button type="button" wire:click="actualizarMonedas" class="{{ $boton }} from-[#2f5470] to-[#0a1a26] !py-1">Poner</button>
                    </div>
                    <p class="mt-1 text-center text-[10px] text-gray-400">Tenés {{ number_format($di['yo']->oro, 0, ',', '.') }} de oro y {{ number_format($di['yo']->diamante, 0, ',', '.') }} esmeraldas</p>
                </div>

                {{-- Mini inventario: tocando un objeto se pone o se saca (hasta 3) --}}
                <div class="mt-3">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-1">
                        <p class="text-xs font-bold text-yellow-300">Tu inventario <span class="text-gray-400 font-normal">· tocá hasta {{ \App\Models\Desafio::MAX_OBJETOS }} objetos</span></p>
                        {{-- Leyenda de colores --}}
                        <p class="flex flex-wrap gap-2 text-[10px] font-bold">
                            <span class="flex items-center gap-1 text-indigo-300"><span class="w-2.5 h-2.5 rounded-sm border-2 border-indigo-500"></span>Equipo</span>
                            <span class="flex items-center gap-1 text-green-300"><span class="w-2.5 h-2.5 rounded-sm border-2 border-green-500"></span>Entrenamiento</span>
                            <span class="flex items-center gap-1 text-pink-300"><span class="w-2.5 h-2.5 rounded-sm border-2 border-pink-500"></span>Accesorio</span>
                            <span class="flex items-center gap-1 text-yellow-300"><span class="w-2.5 h-2.5 rounded-sm border-2 border-yellow-400"></span>Joya</span>
                            <span class="flex items-center gap-1 text-orange-300"><span class="w-2.5 h-2.5 rounded-sm border-2 border-orange-400"></span>Poción</span>
                        </p>
                    </div>
                    <div class="max-h-60 overflow-y-auto sidebar-pj p-1 grid grid-cols-5 sm:grid-cols-7 gap-1.5 sm:gap-2">
                        @forelse ($di['inventario'] as $obj)
                            @php
                                $puesto = in_array($obj->id, $di['miOferta']['objetos'] ?? [], true);
                                [$bordeParte, $textoParte, $nombreParte] = $colorParte($obj);
                            @endphp
                            <button type="button" wire:click="alternarObjeto({{ $obj->id }})" wire:key="inv-{{ $obj->id }}"
                                    title="{{ $nombreParte }} · {{ $obj->nombre }} · Nv {{ $obj->nivel }}"
                                    class="relative aspect-square rounded-lg overflow-hidden border-[3px] {{ $bordeParte }} {{ $puesto ? 'ring-2 ring-white' : 'hover:brightness-125' }} bg-black/50">
                                <img src="{{ $imgObjeto($obj) }}" alt="{{ $obj->nombre }}" class="w-full h-full object-cover {{ $puesto ? 'opacity-50' : '' }}">
                                <span class="absolute bottom-0 inset-x-0 text-[10px] font-bold text-yellow-300 bg-black/70 leading-tight">Nv {{ $obj->nivel }}</span>
                                @if ($puesto)<span class="absolute inset-0 flex items-center justify-center text-white text-2xl font-extrabold [text-shadow:0_2px_0_#000]">✔</span>@endif
                            </button>
                        @empty
                            <p class="col-span-full text-xs text-gray-400 italic">No tenés objetos para intercambiar (los equipados y los que están a la venta no cuentan).</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2" x-data="{ preguntar: false }">
                    <button type="button" wire:click="confirmar" wire:loading.attr="disabled" @disabled($di['yoListo'])
                            class="{{ $boton }} from-emerald-500 to-emerald-800">{{ $di['yoListo'] ? 'Esperando al otro…' : 'Confirmar' }}</button>
                    <button type="button" x-on:click="preguntar = true" class="{{ $boton }} from-red-500 to-red-800">Cancelar</button>

                    {{-- Aviso antes de cerrar el intercambio (con el estilo del juego) --}}
                    <div x-show="preguntar" x-cloak x-transition.opacity x-on:click.self="preguntar = false"
                         class="fixed inset-0 z-[80] flex items-center justify-center bg-black/70 px-3">
                        <div class="w-full max-w-xs p-4 text-center {{ $panel3d }}">
                            <h3 class="text-lg font-extrabold text-red-400 [text-shadow:0_2px_0_#000]">¿Cerrar el intercambio?</h3>
                            <p class="mt-1 text-sm text-gray-300">No se pasa nada: cada uno se queda con lo suyo.</p>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <button type="button" wire:click="cancelar({{ $intercambio->id }})" x-on:click="preguntar = false" class="{{ $boton }} from-red-500 to-red-800">Sí, cerrar</button>
                                <button type="button" x-on:click="preguntar = false" class="{{ $boton }} from-gray-600 to-gray-800">Seguir</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
