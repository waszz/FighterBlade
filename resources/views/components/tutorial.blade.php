{{-- Tutorial del juego: se abre con el botón "?" (evento 'abrir-tutorial') y solo la primera vez a los personajes nuevos.
     Los números salen del código (Explorar, Viajar, Caza, RecompensasTorre, Mercado): si cambian allá, actualizarlos acá. --}}
@props(['personaje'])
@php
    $nivelMaxNovato = \App\Livewire\Explorar::NIVEL_MAX_ENEMIGO_ESPECIAL;
    $costoTeleport = \App\Livewire\Viajar::COSTO_TELEPORT;
    $rarezas = \App\Models\Caza::RAREZAS;

    // "Cómo repartir tus puntos": qué atributo aprovecha cada poder. Sale de Post::AFINIDAD_PODERES, de los SUPER
    // (PoderesStats::MULTIPLICAR) y de los poderes que optimizan stats (sus modificadores); se guarda 1 hora
    $abrevAtributo = ['ataque' => 'Ataque', 'fuerza' => 'Fuerza', 'energia' => 'Energía', 'velocidad' => 'Velocidad', 'defensa' => 'Defensa', 'resistencia' => 'Resistencia'];
    $poderesPorAtributo = cache()->remember('tutorial-poderes-por-atributo', 3600, function () use ($abrevAtributo) {
        $afinidad = \App\Models\Post::AFINIDAD_PODERES;
        foreach (\App\Support\PoderesStats::MULTIPLICAR as $nombre => $stat) {
            $afinidad[$nombre] = [$stat];
        }
        foreach (\App\Models\Poder::all() as $poder) {
            $mods = is_array($poder->modificadores) ? $poder->modificadores : (json_decode($poder->modificadores ?? '[]', true) ?: []);
            foreach ($mods as $mod) {
                if (($mod['tipo'] ?? '') === 'optimizar_stats' && ! empty($mod['stats'])) {
                    $afinidad[mb_strtoupper($poder->nombre)] = $mod['stats'];
                }
            }
        }
        $porAtributo = array_fill_keys(array_keys($abrevAtributo), []);
        foreach ($afinidad as $nombre => $stats) {
            foreach ($stats as $stat) {
                if (isset($porAtributo[$stat])) {
                    $porAtributo[$stat][] = mb_convert_case(mb_strtolower($nombre), MB_CASE_TITLE);
                }
            }
        }
        return array_map(fn ($lista) => array_values(array_unique($lista)), $porAtributo);
    });
    $listaPoderesPorAtributo = [];
    foreach ($poderesPorAtributo as $stat => $poderes) {
        if ($poderes) {
            sort($poderes);
            $listaPoderesPorAtributo[] = $abrevAtributo[$stat] . ': ' . implode(', ', $poderes) . '.';
        }
    }
    $contraPorDefensa = str_replace('.', ',', (string) \App\Livewire\Explorar::CONTRA_POR_DEFENSA);
    $topeContra = \App\Livewire\Explorar::CONTRA_TOPE;
    $topeRebote = \App\Livewire\Explorar::REBOTE_TOPE;
    $rebotePorResistencia = str_replace('.', ',', (string) \App\Livewire\Explorar::REBOTE_POR_RESISTENCIA);
    $minimoContraRebote = \App\Livewire\Explorar::MINIMO_CONTRA_REBOTE;
    // Reset de stats: lo que cuesta (ver AsignarStats::costoReset...), con un ejemplo en nivel 30
    $resetOroPorNivel = \App\Livewire\AsignarStats::costoResetOro(1);
    $resetOroEjemplo = number_format(\App\Livewire\AsignarStats::costoResetOro(30), 0, ',', '.');
    $resetEsmeraldasEjemplo = \App\Livewire\AsignarStats::costoResetEsmeraldas(30);

    // [título, ícono, [párrafos o listas]]. Un string es un párrafo; un array es una lista de puntos
    $temas = [
        ['¡Bienvenido!', 'fa-hand-fist', [
            'FighterBlade es un juego de peleas por turnos: explorás zonas, peleás contra enemigos y otros jugadores, juntás las partes de los sets de personajes y subís de nivel hasta el 100.',
            'El recorrido básico es:',
            ['Explorás una zona y te aparece un enemigo.', 'Peleás: si ganás, cobrás experiencia y oro, y a veces te cae una parte de su set.', 'Juntás las 3 partes de un set y peleás como ese personaje, con sus poderes.', 'Subís de nivel, repartís tus puntos y viajás a zonas más difíciles.'],
            'Podés volver a este tutorial cuando quieras con el botón "?" de arriba.',
        ]],
        ['Primeros pasos', 'fa-seedling', [
            'Al empezar tenés un regalo y una ayuda:',
            ['En tu inventario hay un Cofre de Bienvenida: abrilo gratis y te da un set completo de nivel 5.', 'Arrancás con 100 esmeraldas. Cada 24 horas, si tenés menos de 100, al entrar al juego se te completan hasta 100 (con 90 recibís 10).', "Hasta el nivel " . ($nivelMaxNovato + 1) . " te enfrentás a Wolverine en la Ciudad: cada victoria te sube un nivel entero. Aparece solo, no hace falta explorar.", "Mientras seas nivel 1 o 2 estás protegido: nadie te puede atacar (y vos tampoco podés atacar a otros jugadores)."],
        ]],
        ['Tu panel', 'fa-id-card', [
            'A la izquierda (en el celular, con el botón de perfil) está tu personaje:',
            ['Nivel, oro y esmeraldas. Tocando el oro abrís el Banco de Oro.', 'Si estás explorando o recuperándote, la cuenta regresiva.', 'Tus atributos y los puntos que te quedan para repartir.', 'La barra de experiencia: pasando el mouse ves cuánto te falta para subir.', 'Tu tipo de daño, tus poderes y tu joya: tocalos para leer qué hacen.'],
            'Cómo sumar puntos a tus atributos:',
            ['Arriba de los atributos, el globito rosa ("5 pts", por ejemplo) dice cuántos puntos te quedan para repartir.', 'Tocá el botón verde "+" a la derecha de cualquier atributo: se abre la ventana "Asignar Stats".', 'Arriba elegí de a cuánto sumar: +1, +5 o +10.', 'Tocá el "+" verde al lado del atributo que querés subir. Podés repartir entre varios.', 'Si te equivocaste, elegí -1, -5 o -10 y tocá el "-" rojo: saca los puntos que pusiste en esa ventana.', 'Cuando termines, tocá "Guardar". Una vez guardados ya no se pueden sacar con el "-": para cambiarlos hay que resetear.'],
            'Cómo resetear tus puntos (volver a repartirlos desde cero):',
            ["Tocá el nombre o el número de cualquier atributo del panel (no el botón \"+\"): se abre \"¿Resetear stats?\".", "Elegí cómo pagarlo: con oro ({$resetOroPorNivel} por nivel; en el nivel 30 son {$resetOroEjemplo}) o con esmeraldas (tu nivel menos 4; en el nivel 30 son {$resetEsmeraldasEjemplo}).", 'Te vuelven todos los puntos que repartiste y los podés poner de nuevo con el "+".'],
        ]],
        ['Atributos y niveles', 'fa-chart-simple', [
            'Tenés 6 atributos: Fuerza (FUE), Resistencia (RES), Ataque (ATA), Defensa (DEF), Velocidad (VEL) y Energía (ENE). Todos influyen en la pelea.',
            ['Cada nivel te da 5 puntos para repartir con los botones "+" del panel (en "Tu panel" está el paso a paso).', 'El número blanco es tu atributo base; el amarillo, lo que te suma lo que tenés equipado.', 'Si te equivocaste, podés resetear los puntos pagando oro o esmeraldas (también en "Tu panel").'],
            'Cada victoria te da un porcentaje de la experiencia que pide tu nivel: 3% hasta el nivel 25 y 2% después (1% en los niveles 50 y 75). Las misiones y la Torre dan el doble.',
        ]],
        ['Explorar', 'fa-compass', [
            'En la Ciudad elegís cuánto tiempo explorar. Al terminar te aparece un enemigo de la zona.',
            ['5 minutos: podés conseguir el Equipo del enemigo.', '10 minutos: el Entrenamiento.', '15 minutos: el Accesorio.', 'En El Comienzo, la primera zona, la exploración dura 2 minutos.'],
            'Si le ganás, hay un 10% de chance de que te caiga esa parte (con el buff o la Poción de Búsqueda, más o seguro). A veces también caen pociones.',
            'Mientras explorás podés cancelar la exploración desde el panel.',
        ]],
        ['Pelear', 'fa-khanda', [
            'Cuando aparece un enemigo elegís "Atacar" o "Huir". La pelea se juega sola, por rondas: los atributos, el tipo de daño y los poderes de cada uno deciden quién gana.',
            'Defensa: si el ataque del rival no la supera, bloqueás el golpe. Los críticos se miden con su Fuerza: si la fuerza es claramente mayor entra, si tu defensa es claramente mayor lo bloqueás, y si están parejas se tiran dados.',
            'Con más de 30 de Defensa podés contraatacar (bloqueás el golpe y pegás): cuanta más defensa, más chance. Con más de 30 de Resistencia podés rebotar golpes (no recibís el daño y se lo rebotás entero al que te pegó): cuanta más resistencia, más chance.',
            'Después de cada pelea hay un tiempo de recuperación:',
            ['Explorando, en misiones y en la torre: 15 segundos si ganás y 1 minuto si perdés.', 'Contra jugadores (PvP): 10 segundos hasta el nivel 20 y 1 minuto desde el 21.', 'Si empatás: 5 segundos, siempre.', 'Podés saltearlo pagando oro con "Recuperar ya".'],
            'En Mis Peleas tenés tus últimas peleas: podés volver a verlas o compartirlas en el chat.',
        ]],
        ['Sets y partes', 'fa-shirt', [
            'Cada personaje del juego es un set de 3 partes: Equipo, Entrenamiento y Accesorio. Cada parte suma atributos y puede pedir requisitos (un mínimo de algún atributo o de nivel).',
            'Si te equipás las 3 partes del mismo set, peleás como ese personaje: con su imagen, su tipo de daño y sus poderes.',
            'En Mis Personajes ves todos los sets del juego; los que ya usaste quedan desbloqueados.',
        ]],
        ['Tipos de daño y poderes', 'fa-bolt', [
            'Cada set tiene un tipo de daño (físico, elemental o híbrido) y uno o más poderes.',
            'Los poderes pueden aumentar tu daño, curarte, dejar al rival aturdido, congelado, envenenado o quemado, acortar los viajes y mucho más.',
            'En todo el juego, tocando o pasando el mouse por el ícono de un poder ves su nombre y qué hace.',
        ]],
        ['Cómo repartir tus puntos', 'fa-list-check', [
            'Qué hace cada atributo en la pelea (los puntos amarillos de tus partes, tu joya y tus poderes también cuentan):',
            [
                'Ataque: la base de todo tu daño. Si no supera la defensa del rival, el golpe se bloquea (el ataque y la defensa suben un 3% por nivel).',
                'Fuerza: suma daño físico. Con más de 30, 40% de golpes críticos: el crítico se mide contra la defensa del rival y, si están parejas, se tiran dados.',
                'Energía: suma daño elemental.',
                'Velocidad: decide quién pega primero (velocidad + nivel). Con más de 30, 40% de movimientos especiales.',
                "Defensa: bloquea los golpes que no la superan. Con más de {$minimoContraRebote}, podés contraatacar (defensa × {$contraPorDefensa} % de chance, hasta {$topeContra}%), también cuando bloqueás.",
                "Resistencia: con más de {$minimoContraRebote}, podés rebotar golpes: no recibís el daño y se lo devolvés entero al que te pegó (resistencia × {$rebotePorResistencia} % de chance, hasta {$topeRebote}%).",
            ],
            'Según el tipo de daño de tu set (lo ves en tu panel):',
            [
                'Físico: Ataque y Fuerza. La Energía no te sirve para pegar.',
                'Elemental: Ataque y Energía. La Fuerza no te sirve para pegar (solo para los críticos).',
                'Híbrido: Ataque, Fuerza y Energía. Pega físico y elemental a la vez (65% de cada uno).',
                'En los tres: un poco de Velocidad para pegar primero, y Defensa o Resistencia si querés aguantar y devolver golpes.',
            ],
            'Según tus poderes, conviene sumarle a:',
            $listaPoderesPorAtributo,
            'Ojo con los requisitos: cada parte de un set pide un mínimo de un atributo para equiparla (en Mis Personajes ves cuál).',
        ]],
        ['Inventario', 'fa-bag-shopping', [
            'Tocando un objeto de tu inventario podés:',
            ['Equiparlo.', 'Ponerlo a la venta para otros jugadores, con el precio que quieras.', 'Tirarlo a cambio de oro.', 'Guardarlo en el inventario de tu clan.', 'Compartirlo en el chat con el ícono de la esquina.'],
            'Desde el inventario también elegís la foto que usás en el chat.',
        ]],
        ['Pociones', 'fa-flask', [
            'Las pociones se equipan y se gastan al pelear:',
            ['Poción de Búsqueda: la parte del enemigo te cae seguro.', 'Poción de Recuperación: la espera después de perder queda en 15 segundos como máximo.', 'Pociones de atributo: multiplican un atributo durante la pelea.', 'Poción de Oro: duplica el oro de la próxima pelea que ganes.', 'Poción de Esmeraldas: te da 100 esmeraldas en la próxima pelea que ganes.'],
            'Las conseguís explorando, en el Mercado, en los cofres, en el Casino y en Extras (las Super Pociones).',
        ]],
        ['Joyas y cofres', 'fa-gem', [
            'En los niveles 10, 20, 30… hasta 100, la Torre y las Misiones te dan un cofre o una joya (una sola vez).',
            ['La joya (anillo) se equipa en su lugar del inventario y te suma 2 atributos.', 'El cofre se abre pagando oro (100 por nivel del cofre, mínimo 1.000) y trae 3 pociones o un set completo.'],
        ]],
        ['Viajar', 'fa-plane', [
            'Cada zona tiene un nivel: podés viajar a las de tu nivel o menos. Las más altas tienen enemigos más fuertes y dan más oro.',
            ['Viajar tarda 1 hora y cuesta 50 de oro por tu nivel.', "El Teleport te lleva al instante por {$costoTeleport} esmeraldas.", 'Con el poder Súper Velocidad el viaje tarda la mitad; con Teletransportarse, el viaje es instantáneo y el Teleport es gratis.'],
        ]],
        ['Misiones', 'fa-scroll', [
            'Las Misiones son una escalera de rivales especiales, cada uno en su escenario. Las vas ganando en orden y cada una se gana una sola vez.',
            ['Dan el doble de experiencia que una pelea común.', 'Dan un premio fijo de oro y esmeraldas (no dan partes).', 'La primera misión de cada nivel 10, 20, 30… da además un cofre o una joya.'],
        ]],
        ['Torre', 'fa-chess-rook', [
            'La Torre tiene un piso por cada nivel, del 5 al 100, con un rival y un escenario. Subís piso por piso.',
            ['Cada piso da el doble de experiencia y oro según su nivel.', 'Los pisos de nivel 20, 30… hasta 100 dan además un cofre o una joya la primera vez que los superás.'],
        ]],
        ['Caza', 'fa-crosshairs', [
            'Cada ciudad tiene un tablero de presas que cambia cada 8 horas. Elegís una presa y la parte que querés conseguir.',
            ['Primero la rastreás: tarda 30 minutos (15 con Exploración rápida).', 'Si le ganás, la parte elegida te cae seguro.', 'Las presas son más fuertes según su rareza, pero dan más oro: ' . collect($rarezas)->map(fn ($r) => $r['nombre'] . ' ×' . $r['oro'] . ' de oro')->implode(', ') . '. Las legendarias dan además ' . $rarezas['legendaria']['diamantes'] . ' esmeraldas.', 'Tenés hasta ' . \App\Models\Caza::CARGAS_MAX . ' cargas: cada caza usa una, se recuperan de a una cada ' . \App\Models\Caza::CARGA_HORAS . ' horas y podés comprar una extra por ' . \App\Models\Caza::COSTO_CARGA_DIAMANTES . ' esmeraldas.'],
        ]],
        ['Pelear contra jugadores (PvP)', 'fa-user-ninja', [
            'Podés atacar a otro jugador desde la lista de usuarios de la zona o desde el Ranking.',
            ['Los dos tienen que estar en la misma zona.', "Los dos tienen que ser nivel " . ($nivelMaxNovato + 1) . " o más.", 'Si le ganás, cobrás el 4% de la experiencia de tu nivel si la diferencia de nivel es de 5 o menos, y el 1% si es mayor.', 'Si te atacan y el otro pierde, esa experiencia la cobrás vos.', 'Si te atacan, vos también quedás en recuperación, igual que el que atacó.'],
        ]],
        ['Mercado', 'fa-store', [
            ['Sets completos para comprar con esmeraldas.', 'Pociones.', 'Objetos de la semana: partes sueltas que se compran con oro.', 'Vendidos por jugadores: lo que otros pusieron a la venta.'],
        ]],
        ['Banco de Oro', 'fa-building-columns', [
            'Tocando tu oro en el panel abrís el banco: podés guardar oro y retirarlo cuando quieras.',
        ]],
        ['Casino', 'fa-dice', [
            'En el Casino jugás a la tragamonedas con tus vidas (se recargan con el tiempo). Según la combinación podés ganar sets, buffs, exploración rápida, pociones, vidas, cargas de caza y más.',
        ]],
        ['Clanes', 'fa-shield-halved', [
            'Podés fundar un clan o pedir entrar a uno desde el Ranking de clanes. Los miembros comparten un inventario del clan y suman prestigio juntos.',
        ]],
        ['Extras', 'fa-star', [
            ['Comprar oro con esmeraldas.', 'Cosméticos: efectos para tu burbuja del chat, tu nombre, tu aura y tu fila en el ranking. Se compran una vez y los ponés o sacás cuando quieras.', 'Super Pociones.', 'Buffs de experiencia, oro y drop.', 'Exploración rápida: las exploraciones y los rastreos de caza duran menos.'],
        ]],
        ['Ranking', 'fa-trophy', [
            'En el Ranking ves a los mejores por pestañas: Nivel, PvP, Exploración, Misiones, Torre, Clanes y los últimos Campeones (los que llegaron al nivel 100). Tocando a un jugador ves su personaje.',
        ]],
        ['Chat', 'fa-comments', [
            ['Chat general para hablar con todos.', 'Privados: tocá "Online" o el nombre de alguien para escribirle. La casilla ✉ te avisa cuántos mensajes sin leer tenés.', 'Podés subir GIFs y compartir objetos del inventario y peleas de Mis Peleas.'],
        ]],
        ['Mis Drops y Anuncios', 'fa-bullhorn', [
            ['Mis Drops: todo lo que te cayó explorando, por zona.', 'Anuncios: en la Ciudad, las novedades y eventos que publican los administradores. Podés darles "me gusta".'],
        ]],
    ];
    $abrirSolo = ($personaje->nivel ?? 1) <= $nivelMaxNovato;
@endphp

<div wire:ignore x-data="{ abierto: false, paso: 0, total: {{ count($temas) }}, abrirSolo: @js($abrirSolo),
        // Se abre solo la primera vez a los personajes nuevos (queda recordado en el navegador)
        init() { if (this.abrirSolo) { try { if (! localStorage.getItem('fb_tutorial_visto')) this.abierto = true; } catch (e) {} } },
        cerrar() { this.abierto = false; try { localStorage.setItem('fb_tutorial_visto', '1'); } catch (e) {} },
        ir(i) { this.paso = Math.max(0, Math.min(this.total - 1, i)); this.$nextTick(() => { this.$refs.contenido.scrollTop = 0; const b = this.$refs.indice.querySelector(`[data-tema='${this.paso}']`); b && b.scrollIntoView({ block: 'nearest', inline: 'nearest' }); }); } }"
     @abrir-tutorial.window="abierto = true"
     @keydown.escape.window="abierto && cerrar()"
     @keydown.arrow-right.window="abierto && ir(paso + 1)" @keydown.arrow-left.window="abierto && ir(paso - 1)">
    <div x-show="abierto" x-cloak x-transition.opacity class="fixed inset-0 z-[9000] flex items-center justify-center bg-black/80 p-2 sm:p-4" @click.self="cerrar()">
        <div class="relative w-full max-w-3xl h-[88vh] sm:h-[80vh] flex flex-col sm:flex-row overflow-hidden rounded-xl border border-black text-white
                    bg-gradient-to-b from-[#1c2533] to-[#0a0e14] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_6px_0_#000,0_12px_24px_rgba(0,0,0,0.8)]">

            <button type="button" @click="cerrar()" aria-label="Cerrar tutorial"
                class="absolute top-2 right-2 z-10 w-8 h-8 flex items-center justify-center rounded-md border border-black font-bold text-white bg-gradient-to-b from-red-500 to-red-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000] hover:brightness-125">&times;</button>

            {{-- Índice de temas: columna en PC, fila con scroll en el celular --}}
            <nav x-ref="indice" class="shrink-0 sm:w-56 flex sm:flex-col gap-1 overflow-x-auto sm:overflow-y-auto sidebar-pj p-2 pr-12 sm:pr-2 border-b sm:border-b-0 sm:border-r border-black bg-black/30">
                <p class="hidden sm:block px-2 pt-1 pb-2 text-xs font-bold uppercase tracking-wide text-yellow-300">Cómo jugar</p>
                @foreach ($temas as $i => [$titulo, $icono])
                    <button type="button" data-tema="{{ $i }}" @click="ir({{ $i }})"
                        :class="paso === {{ $i }} ? 'bg-gradient-to-b from-yellow-300 to-yellow-600 text-black border-black' : 'text-gray-200 border-transparent hover:bg-white/5'"
                        class="shrink-0 flex items-center gap-2 px-2 py-1.5 rounded-md border text-xs sm:text-sm font-semibold text-left whitespace-nowrap sm:whitespace-normal">
                        <i class="fa-solid {{ $icono }} w-4 text-center"></i>{{ $titulo }}
                    </button>
                @endforeach
            </nav>

            {{-- Contenido del tema --}}
            <div class="flex-1 min-h-0 flex flex-col">
                <div x-ref="contenido" class="flex-1 min-h-0 overflow-y-auto sidebar-pj p-4 sm:p-6">
                    @foreach ($temas as $i => [$titulo, $icono, $bloques])
                        <section x-show="paso === {{ $i }}" {{ $i === 0 ? '' : 'x-cloak' }}>
                            <h2 class="flex items-center gap-2 mb-3 pr-8 text-xl sm:text-2xl font-bold text-yellow-300 [text-shadow:0_2px_0_#000]">
                                <span class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full border border-black bg-gradient-to-b from-red-700 to-red-950 text-white text-base shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_2px_0_#000]"><i class="fa-solid {{ $icono }}"></i></span>
                                {{ $titulo }}
                            </h2>
                            <div class="space-y-3 text-sm sm:text-base leading-relaxed text-gray-200">
                                @foreach ($bloques as $bloque)
                                    @if (is_array($bloque))
                                        <ul class="space-y-1.5">
                                            @foreach ($bloque as $punto)
                                                <li class="flex gap-2"><span class="mt-2 w-1.5 h-1.5 shrink-0 rounded-full bg-yellow-400"></span><span>{{ $punto }}</span></li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p>{{ $bloque }}</p>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                {{-- Anterior · progreso · Siguiente --}}
                <div class="shrink-0 flex items-center gap-2 p-3 border-t border-black bg-black/30">
                    <button type="button" @click="ir(paso - 1)" :disabled="paso === 0"
                        class="px-3 py-1.5 rounded-lg border border-black text-sm font-bold bg-gradient-to-b from-[#2a3240] to-[#10141b] shadow-[inset_1px_1px_0_rgba(255,255,255,0.25),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none disabled:opacity-40 disabled:pointer-events-none">
                        <i class="fa-solid fa-arrow-left"></i> Anterior
                    </button>
                    <p class="flex-1 text-center text-xs text-gray-400"><span x-text="paso + 1"></span> / <span x-text="total"></span></p>
                    <button type="button" x-show="paso < total - 1" @click="ir(paso + 1)"
                        class="px-3 py-1.5 rounded-lg border border-black text-sm font-bold bg-gradient-to-b from-green-500 to-green-800 shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),0_3px_0_#000] hover:brightness-125 active:translate-y-[3px] active:shadow-none">
                        Siguiente <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <button type="button" x-show="paso === total - 1" x-cloak @click="cerrar()"
                        class="px-3 py-1.5 rounded-lg border border-black text-sm font-bold text-black bg-gradient-to-b from-yellow-300 to-yellow-600 shadow-[inset_1px_1px_0_rgba(255,255,255,0.5),0_3px_0_#000] hover:brightness-110 active:translate-y-[3px] active:shadow-none">
                        ¡A jugar!
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
