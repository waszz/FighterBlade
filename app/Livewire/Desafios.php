<?php

namespace App\Livewire;

use App\Http\Controllers\PvpController;
use App\Models\Desafio;
use App\Models\Objeto;
use App\Models\Personaje;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

// Duelos (pelea amistosa aceptada) e intercambios entre jugadores.
// Se piden desde el modal de un jugador (evento "desafiar"); el otro tiene 30 s para aceptar o rechazar.
// No hay tiempo real: este componente consulta cada 2 s (wire:poll) y muestra avisos y la ventana de intercambio.
class Desafios extends Component
{
    public int $personajeId;

    // Lo que estoy ofreciendo en el intercambio abierto
    public int $oroOferta = 0;
    public int $diamanteOferta = 0;

    // Fin de la recuperación que ya se vio: si cambia (me atacaron en PvP) se avisa al panel y a la Ciudad
    public ?int $finRecuperacionVisto = null;

    public function mount($personajeId)
    {
        $this->personajeId = (int) $personajeId;
        $this->finRecuperacionVisto = $this->finRecuperacion();

        // Si se recarga con un intercambio abierto, los campos arrancan con lo que ya había puesto
        if ($d = $this->intercambioAbierto()) {
            $oferta = $this->miOferta($d);
            $this->oroOferta = (int) ($oferta['oro'] ?? 0);
            $this->diamanteOferta = (int) ($oferta['diamante'] ?? 0);
        }
    }

    // Hora (timestamp) en que termina la recuperación después de una pelea, o null si no se está recuperando
    protected function finRecuperacion(): ?int
    {
        $personaje = Personaje::select('id', 'fin_exploracion', 'exploracion_duracion', 'fin_recuperacion')->find($this->personajeId);
        $segundos = $personaje?->segundosRecuperacion() ?? 0;
        return $segundos > 0 ? now()->timestamp + $segundos : null;
    }

    protected function yo(): ?Personaje
    {
        return Personaje::where('user_id', auth()->id())->find($this->personajeId);
    }

    // Desafío en curso de este personaje (pedido o recibido, pendiente o intercambio abierto)
    protected function activoDe(int $personajeId): ?Desafio
    {
        return Desafio::whereIn('estado', ['pendiente', 'aceptado'])
            ->where('expira_en', '>', now())
            ->where(fn ($q) => $q->where('de_id', $personajeId)->orWhere('para_id', $personajeId))
            ->first();
    }

    #[On('desafiar')]
    public function desafiar(string $tipo, int $personajeId)
    {
        $yo = $this->yo();
        $otro = Personaje::find($personajeId);
        if (! $yo || ! in_array($tipo, ['duelo', 'intercambio'], true)) {
            return;
        }
        if (! $otro || $otro->id === $yo->id || $otro->user_id === $yo->user_id) {
            return $this->dispatch('error', ['message' => 'No podés desafiar a ese personaje.']);
        }
        if ($this->activoDe($yo->id)) {
            return $this->dispatch('error', ['message' => 'Ya tenés un duelo o intercambio en curso.']);
        }
        if ($this->activoDe($otro->id)) {
            return $this->dispatch('error', ['message' => "{$otro->nombre} está ocupado con otro duelo o intercambio."]);
        }

        Desafio::create([
            'tipo'      => $tipo,
            'de_id'     => $yo->id,
            'para_id'   => $otro->id,
            'estado'    => 'pendiente',
            'expira_en' => now()->addSeconds(Desafio::SEGUNDOS_RESPUESTA),
        ]);

        $this->dispatch('success', ['message' => ($tipo === 'duelo' ? 'Desafiaste a duelo a ' : 'Le pediste un intercambio a ') . "{$otro->nombre}. Tiene 30 s para responder."]);
    }

    public function aceptar(int $id)
    {
        $yo = $this->yo();
        $desafio = Desafio::where('para_id', $this->personajeId)->where('estado', 'pendiente')->find($id);
        if (! $yo || ! $desafio) {
            return;
        }
        if ($desafio->vencido()) {
            $desafio->update(['estado' => 'expirado']);
            return $this->dispatch('error', ['message' => 'Se terminó el tiempo para responder.']);
        }

        if ($desafio->tipo === 'duelo') {
            // Pelea el que acepta contra el que desafió (aunque esté explorando o recuperándose: la exploración sigue igual)
            if ($motivo = PvpController::motivoBloqueo($yo, esDuelo: true)) {
                return $this->dispatch('error', ['message' => $motivo]);
            }
            // Queda aceptado un rato para que la pelea arranque aunque se haya aceptado en el último segundo
            $desafio->update(['estado' => 'aceptado', 'expira_en' => now()->addMinutes(2)]);

            $yo->enemigo_actual_personaje_id = $desafio->de_id;
            $yo->enemigo_actual_id = null;
            $yo->save();
            session()->forget(['enemigo', 'combate_activo']);
            // La Ciudad ve estas marcas y arranca la pelea sola, como duelo
            session(['pvp_auto_atacar' => $desafio->de_id, 'duelo_id' => $desafio->id]);

            return redirect()->route('juego.mostrar', ['personajeId' => $yo->id]);
        }

        // Intercambio: se abre la ventana para los dos
        $desafio->update([
            'estado'      => 'aceptado',
            'expira_en'   => now()->addMinutes(Desafio::MINUTOS_INTERCAMBIO),
            'oferta_de'   => Desafio::ofertaVacia(),
            'oferta_para' => Desafio::ofertaVacia(),
        ]);
        $this->oroOferta = 0;
        $this->diamanteOferta = 0;
    }

    public function rechazar(int $id)
    {
        Desafio::where('para_id', $this->personajeId)->where('estado', 'pendiente')->whereKey($id)->update(['estado' => 'rechazado']);
    }

    // Cancela un pedido propio que todavía no respondieron, o cierra el intercambio abierto (cualquiera de los dos)
    public function cancelar(int $id)
    {
        Desafio::whereKey($id)->whereIn('estado', ['pendiente', 'aceptado'])
            ->where(fn ($q) => $q->where('de_id', $this->personajeId)->orWhere('para_id', $this->personajeId))
            ->where(fn ($q) => $q->where('estado', 'aceptado')->orWhere('de_id', $this->personajeId))
            ->update(['estado' => 'cancelado', 'visto_de' => true]);
    }

    public function marcarVisto(int $id)
    {
        Desafio::where('de_id', $this->personajeId)->whereKey($id)->update(['visto_de' => true]);
    }

    // El que desafió ve cómo terminó el duelo (la misma pantalla de Mis peleas)
    public function verDuelo(int $id)
    {
        $desafio = Desafio::where('de_id', $this->personajeId)->where('estado', 'completado')->find($id);
        if ($desafio?->pelea_id) {
            $desafio->update(['visto_de' => true]);
            $this->dispatch('verPeleaCompartida', peleaId: $desafio->pelea_id);
        }
    }

    // ---------- Intercambio ----------

    protected function intercambioAbierto(): ?Desafio
    {
        return Desafio::where('tipo', 'intercambio')->where('estado', 'aceptado')->where('expira_en', '>', now())
            ->where(fn ($q) => $q->where('de_id', $this->personajeId)->orWhere('para_id', $this->personajeId))
            ->first();
    }

    // Objetos que se pueden intercambiar: los del inventario (no los equipados ni los que están a la venta)
    protected function objetosIntercambiables(Personaje $pj)
    {
        $equipados = array_filter([$pj->equipo_id, $pj->entrenamiento_id, $pj->accesorio_id, $pj->joya_id, $pj->objeto_consumible_id]);

        return Objeto::where('personaje_id', $pj->id)->whereNull('precio_venta')->whereNotIn('id', $equipados)
            ->orderByDesc('nivel')->orderBy('nombre')->get();
    }

    // Guarda mi oferta; cualquier cambio saca el "listo" de los dos para que nadie confirme algo distinto a lo que vio
    protected function guardarOferta(Desafio $d, array $oferta): void
    {
        $lado = $d->de_id === $this->personajeId ? 'de' : 'para';
        $d->update(["oferta_$lado" => $oferta, 'listo_de' => false, 'listo_para' => false]);
    }

    protected function miOferta(Desafio $d): array
    {
        return ($d->de_id === $this->personajeId ? $d->oferta_de : $d->oferta_para) ?? Desafio::ofertaVacia();
    }

    public function alternarObjeto(int $objetoId)
    {
        $d = $this->intercambioAbierto();
        $yo = $this->yo();
        if (! $d || ! $yo) {
            return;
        }
        $oferta = $this->miOferta($d);
        $ids = collect($oferta['objetos'] ?? []);

        if ($ids->contains($objetoId)) {
            $oferta['objetos'] = $ids->reject(fn ($id) => $id === $objetoId)->values()->all();
        } else {
            if ($ids->count() >= Desafio::MAX_OBJETOS) {
                return $this->dispatch('error', ['message' => 'Podés poner hasta ' . Desafio::MAX_OBJETOS . ' objetos.']);
            }
            if (! $this->objetosIntercambiables($yo)->contains('id', $objetoId)) {
                return;
            }
            $oferta['objetos'] = $ids->push($objetoId)->values()->all();
        }
        $this->guardarOferta($d, $oferta);
    }

    public function actualizarMonedas()
    {
        $d = $this->intercambioAbierto();
        $yo = $this->yo();
        if (! $d || ! $yo) {
            return;
        }
        $oferta = $this->miOferta($d);
        $oferta['oro'] = max(0, min((int) $this->oroOferta, (int) $yo->oro));
        $oferta['diamante'] = max(0, min((int) $this->diamanteOferta, (int) $yo->diamante));
        $this->oroOferta = $oferta['oro'];
        $this->diamanteOferta = $oferta['diamante'];
        $this->guardarOferta($d, $oferta);
    }

    public function confirmar()
    {
        $d = $this->intercambioAbierto();
        if (! $d) {
            return;
        }
        $lado = $d->de_id === $this->personajeId ? 'de' : 'para';
        $d->update(["listo_$lado" => true]);

        if ($d->fresh()->listo_de && $d->fresh()->listo_para) {
            $this->ejecutarIntercambio($d->id);
        }
    }

    // Pasa todo de un lado al otro de una sola vez; si algo no cierra, no se mueve nada
    protected function ejecutarIntercambio(int $desafioId): void
    {
        $error = null;

        DB::transaction(function () use ($desafioId, &$error) {
            $d = Desafio::lockForUpdate()->find($desafioId);
            if (! $d || $d->estado !== 'aceptado' || ! $d->listo_de || ! $d->listo_para) {
                return;
            }
            $pjDe = Personaje::lockForUpdate()->find($d->de_id);
            $pjPara = Personaje::lockForUpdate()->find($d->para_id);
            $ofDe = $d->oferta_de ?? Desafio::ofertaVacia();
            $ofPara = $d->oferta_para ?? Desafio::ofertaVacia();

            foreach ([[$pjDe, $ofDe], [$pjPara, $ofPara]] as [$pj, $of]) {
                $validos = $this->objetosIntercambiables($pj)->pluck('id');
                if (collect($of['objetos'] ?? [])->diff($validos)->isNotEmpty()) {
                    $error = "{$pj->nombre} ya no tiene alguno de los objetos que puso.";
                    return;
                }
                if (($of['oro'] ?? 0) > $pj->oro || ($of['diamante'] ?? 0) > $pj->diamante) {
                    $error = "{$pj->nombre} ya no tiene el oro o las esmeraldas que puso.";
                    return;
                }
            }

            // Lugar en el inventario de cada uno (con los lugares que compró)
            foreach ([[$pjDe, $ofPara, $ofDe], [$pjPara, $ofDe, $ofPara]] as [$pj, $recibe, $entrega]) {
                $ocupados = $pj->objetosEnInventario();
                if ($ocupados - count($entrega['objetos'] ?? []) + count($recibe['objetos'] ?? []) > $pj->capacidadInventario()) {
                    $error = "{$pj->nombre} no tiene lugar en el inventario.";
                    return;
                }
            }

            // Nombres de lo que entrega cada uno, para el registro de transacciones
            $nombresDe = Objeto::whereIn('id', $ofDe['objetos'] ?? [])->pluck('nombre')->all();
            $nombresPara = Objeto::whereIn('id', $ofPara['objetos'] ?? [])->pluck('nombre')->all();

            Objeto::whereIn('id', $ofDe['objetos'] ?? [])->update(['personaje_id' => $pjPara->id]);
            Objeto::whereIn('id', $ofPara['objetos'] ?? [])->update(['personaje_id' => $pjDe->id]);

            $pjDe->oro = $pjDe->oro - ($ofDe['oro'] ?? 0) + ($ofPara['oro'] ?? 0);
            $pjDe->diamante = $pjDe->diamante - ($ofDe['diamante'] ?? 0) + ($ofPara['diamante'] ?? 0);
            $pjPara->oro = $pjPara->oro - ($ofPara['oro'] ?? 0) + ($ofDe['oro'] ?? 0);
            $pjPara->diamante = $pjPara->diamante - ($ofPara['diamante'] ?? 0) + ($ofDe['diamante'] ?? 0);
            $pjDe->save();
            $pjPara->save();

            $d->update(['estado' => 'completado']);

            \App\Models\Transaccion::create([
                'tipo'              => 'intercambio',
                'de_personaje_id'   => $pjDe->id,
                'para_personaje_id' => $pjPara->id,
                'detalle'           => [
                    'de'   => \App\Models\Transaccion::lado($nombresDe, (int) ($ofDe['oro'] ?? 0), (int) ($ofDe['diamante'] ?? 0)),
                    'para' => \App\Models\Transaccion::lado($nombresPara, (int) ($ofPara['oro'] ?? 0), (int) ($ofPara['diamante'] ?? 0)),
                ],
            ]);
            foreach ([[$pjDe, $pjPara], [$pjPara, $pjDe]] as [$pj, $otro]) {
                \App\Models\NotificacionJuego::avisar($pj->id, '🤝', "Hiciste un intercambio con {$otro->nombre}. El detalle está en Transacciones.");
            }
        });

        if ($error) {
            Desafio::whereKey($desafioId)->update(['listo_de' => false, 'listo_para' => false]);
            $this->dispatch('error', ['message' => $error]);
            return;
        }

        $this->dispatch('success', ['message' => '¡Intercambio hecho!']);
        // Oro, esmeraldas e inventario cambiaron
        $this->dispatch('statsActualizados');
        $this->dispatch('actualizarInventario');
    }

    public function render()
    {
        // Recuperación nueva que no puso este navegador (me atacaron en PvP): se muestra sin recargar
        $fin = $this->finRecuperacion();
        if ($fin && $fin !== $this->finRecuperacionVisto) {
            $this->dispatch('statsActualizados');
            $this->dispatch('recuperacionPorPvp');
        }
        $this->finRecuperacionVisto = $fin;

        // Lo que se venció sin respuesta queda como expirado (así el que pidió ve el aviso)
        Desafio::whereIn('estado', ['pendiente', 'aceptado'])->where('expira_en', '<=', now())
            ->where(fn ($q) => $q->where('de_id', $this->personajeId)->orWhere('para_id', $this->personajeId))
            ->update(['estado' => 'expirado']);

        $entrante = Desafio::with('de.post')->where('para_id', $this->personajeId)->where('estado', 'pendiente')->latest('id')->first();
        $saliente = Desafio::with('para')->where('de_id', $this->personajeId)->where('estado', 'pendiente')->latest('id')->first();

        // Avisos para el que pidió: rechazado, sin respuesta, duelo terminado o intercambio hecho o cancelado
        $aviso = Desafio::with('para')->where('de_id', $this->personajeId)->where('visto_de', false)
            ->whereIn('estado', ['rechazado', 'expirado', 'completado', 'cancelado'])
            ->where('updated_at', '>=', now()->subMinutes(3))
            ->latest('updated_at')->first();

        $intercambio = $this->intercambioAbierto()?->load('de.post', 'para.post');
        $datosIntercambio = null;
        if ($intercambio) {
            $soyDe = $intercambio->de_id === $this->personajeId;
            $yo = $soyDe ? $intercambio->de : $intercambio->para;
            $otro = $soyDe ? $intercambio->para : $intercambio->de;
            $miOferta = ($soyDe ? $intercambio->oferta_de : $intercambio->oferta_para) ?? Desafio::ofertaVacia();
            $suOferta = ($soyDe ? $intercambio->oferta_para : $intercambio->oferta_de) ?? Desafio::ofertaVacia();
            $datosIntercambio = [
                'yo'          => $yo,
                'otro'        => $otro,
                'miOferta'    => $miOferta,
                'suOferta'    => $suOferta,
                'misObjetos'  => Objeto::whereIn('id', $miOferta['objetos'] ?? [])->get(),
                'susObjetos'  => Objeto::whereIn('id', $suOferta['objetos'] ?? [])->get(),
                'yoListo'     => $soyDe ? $intercambio->listo_de : $intercambio->listo_para,
                'otroListo'   => $soyDe ? $intercambio->listo_para : $intercambio->listo_de,
                'inventario'  => $this->objetosIntercambiables($yo),
            ];
        }

        return view('livewire.desafios', compact('entrante', 'saliente', 'aviso', 'intercambio', 'datosIntercambio'));
    }
}
