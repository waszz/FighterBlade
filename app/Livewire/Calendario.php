<?php

namespace App\Livewire;

use App\Models\Buff;
use App\Models\Caza;
use App\Models\Evento;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Livewire\Component;

// Calendario de eventos, en hora local del juego (la misma zona que el Mercado y la Caza). Muestra:
//  - automáticos: la renovación del Mercado (lunes y sábados) y los buffs globales
//  - los eventos que carga el admin (tabla eventos) desde acá mismo
class Calendario extends Component
{
    public string $mes = '';          // Y-m del mes que se ve
    public ?string $diaAbierto = null; // Y-m-d del día que se ve en detalle

    // Formulario del admin (fecha y hora en hora local del juego)
    public string $titulo = '';
    public string $tipo = 'torneo';
    public string $fecha = '';
    public string $hora = '20:00';
    public string $fechaFin = '';
    public string $horaFin = '';
    public string $descripcion = '';

    public function mount()
    {
        $hoy = now()->setTimezone(Caza::ZONA_HORARIA);
        $this->mes = $hoy->format('Y-m');
        $this->fecha = $hoy->toDateString();
    }

    private function esAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mesAnterior()
    {
        $this->mes = Carbon::createFromFormat('Y-m-d', $this->mes . '-01')->subMonth()->format('Y-m');
    }

    public function mesSiguiente()
    {
        $this->mes = Carbon::createFromFormat('Y-m-d', $this->mes . '-01')->addMonth()->format('Y-m');
    }

    public function irAHoy()
    {
        $this->mes = now()->setTimezone(Caza::ZONA_HORARIA)->format('Y-m');
    }

    public function abrirDia(string $dia)
    {
        $this->diaAbierto = $dia;
    }

    public function guardarEvento()
    {
        abort_unless($this->esAdmin(), 403);
        $this->validate([
            'titulo'      => 'required|string|max:80',
            'tipo'        => 'required|in:' . implode(',', array_keys(Evento::TIPOS)),
            'fecha'       => 'required|date',
            'hora'        => 'required|date_format:H:i',
            'fechaFin'    => 'nullable|date',
            'horaFin'     => 'nullable|date_format:H:i',
            'descripcion' => 'nullable|string|max:300',
        ]);
        $zona = Caza::ZONA_HORARIA;
        $inicio = Carbon::createFromFormat('Y-m-d H:i', "{$this->fecha} {$this->hora}", $zona)->utc();
        $fin = $this->fechaFin
            ? Carbon::createFromFormat('Y-m-d H:i', $this->fechaFin . ' ' . ($this->horaFin ?: '23:59'), $zona)->utc()
            : null;
        if ($fin && $fin->lt($inicio)) {
            $this->addError('fechaFin', 'El fin tiene que ser después del inicio.');
            return;
        }

        Evento::create(['titulo' => $this->titulo, 'tipo' => $this->tipo, 'descripcion' => $this->descripcion ?: null, 'inicio' => $inicio, 'fin' => $fin]);
        $this->reset(['titulo', 'descripcion', 'fechaFin', 'horaFin']);
        $this->mes = substr($this->fecha, 0, 7);
        $this->dispatch('success', ['message' => 'Evento agregado al calendario.']);
    }

    public function borrarEvento(int $id)
    {
        abort_unless($this->esAdmin(), 403);
        Evento::whereKey($id)->delete();
    }

    // Eventos de cada día entre $desde y $hasta (fechas locales): ['Y-m-d' => [[hora, titulo, tipo, descripcion, id], ...]]
    private function eventosPorDia(Carbon $desde, Carbon $hasta): array
    {
        $zona = Caza::ZONA_HORARIA;
        $dias = [];
        $agregar = function (string $dia, array $evento) use (&$dias) {
            $dias[$dia][] = $evento;
        };

        // Mercado: lunes y sábados a la hora de la renovación
        for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
            if ($d->isMonday() || $d->isSaturday()) {
                $agregar($d->toDateString(), ['hora' => sprintf('%02d:00', Mercado::HORA_ROTACION), 'titulo' => 'Se renueva el Mercado', 'tipo' => 'mercado', 'descripcion' => 'Nuevos sets y objetos de la semana.', 'id' => null]);
            }
        }

        // Torneo: viernes y sábados a la hora de la inscripción (ver App\Models\Torneo)
        for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
            if (\App\Models\Torneo::esDiaDeTorneo($d)) {
                $agregar($d->toDateString(), ['hora' => \App\Models\Torneo::horaDe($d), 'titulo' => 'Torneo', 'tipo' => 'torneo',
                    'descripcion' => 'Inscripción de ' . \App\Models\Torneo::MINUTOS_INSCRIPCION . ' minutos: te toca un set al azar y todos pelean como nivel ' . \App\Models\Torneo::NIVEL . '. Premio: ' . \App\Models\Torneo::PREMIO_ESMERALDAS . ' esmeraldas y un set de tu nivel.', 'id' => null]);
            }
        }

        // Buffs globales: el día que empiezan (hora →), los del medio (todo el día) y el que terminan (→ hora)
        $nombresBuff = ['xp' => 'EXP', 'oro' => 'Oro', 'drop' => 'Drop'];
        $buffs = Buff::whereNull('personaje_id')->where('global', true)
            ->where('inicio', '<=', $hasta->copy()->endOfDay()->utc())->where('fin', '>=', $desde->copy()->startOfDay()->utc())->get();
        foreach ($buffs as $buff) {
            $ini = $buff->inicio->copy()->setTimezone($zona);
            $fin = $buff->fin->copy()->setTimezone($zona);
            $titulo = 'Buff ' . ($nombresBuff[$buff->tipo] ?? ucfirst((string) $buff->tipo)) . ' +' . $buff->porcentaje . '%';
            for ($d = $ini->copy()->startOfDay(); $d->lte($fin); $d->addDay()) {
                if ($d->lt($desde->copy()->startOfDay()) || $d->gt($hasta)) {
                    continue;
                }
                $mismoDiaIni = $d->isSameDay($ini);
                $mismoDiaFin = $d->isSameDay($fin);
                $hora = match (true) {
                    $mismoDiaIni && $mismoDiaFin => $ini->format('H:i') . ' - ' . $fin->format('H:i'),
                    $mismoDiaIni => $ini->format('H:i') . ' →',
                    $mismoDiaFin => '→ ' . $fin->format('H:i'),
                    default => 'Todo el día',
                };
                $agregar($d->toDateString(), ['hora' => $hora, 'titulo' => $titulo, 'tipo' => 'buff', 'descripcion' => $buff->frase, 'id' => null]);
            }
        }

        // Eventos del admin (igual que los buffs si duran varios días)
        $eventos = Evento::where('inicio', '<=', $hasta->copy()->endOfDay()->utc())
            ->where(fn ($q) => $q->where('fin', '>=', $desde->copy()->startOfDay()->utc())->orWhere(fn ($q) => $q->whereNull('fin')->where('inicio', '>=', $desde->copy()->startOfDay()->utc())))
            ->orderBy('inicio')->get();
        foreach ($eventos as $evento) {
            $ini = $evento->inicio->copy()->setTimezone($zona);
            $fin = $evento->fin?->copy()->setTimezone($zona);
            for ($d = $ini->copy()->startOfDay(); $d->lte($fin ?? $ini); $d->addDay()) {
                if ($d->lt($desde->copy()->startOfDay()) || $d->gt($hasta)) {
                    continue;
                }
                $hora = match (true) {
                    ! $fin => $ini->format('H:i'),
                    $d->isSameDay($ini) && $d->isSameDay($fin) => $ini->format('H:i') . ' - ' . $fin->format('H:i'),
                    $d->isSameDay($ini) => $ini->format('H:i') . ' →',
                    $d->isSameDay($fin) => '→ ' . $fin->format('H:i'),
                    default => 'Todo el día',
                };
                $agregar($d->toDateString(), ['hora' => $hora, 'titulo' => $evento->titulo, 'tipo' => $evento->tipo, 'descripcion' => $evento->descripcion, 'id' => $evento->id]);
            }
        }

        // Dentro de cada día, por hora ("Todo el día" y "→ hh:mm" primero)
        foreach ($dias as &$lista) {
            usort($lista, fn ($a, $b) => strcmp(preg_replace('/^(Todo|→)/u', '0', $a['hora']), preg_replace('/^(Todo|→)/u', '0', $b['hora'])));
        }
        return $dias;
    }

    public function render()
    {
        $zona = Caza::ZONA_HORARIA;
        $primero = Carbon::createFromFormat('Y-m-d', $this->mes . '-01', $zona)->startOfDay();
        $inicioGrilla = $primero->copy()->startOfWeek(Carbon::MONDAY);
        $finGrilla = $primero->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $hoy = now()->setTimezone($zona)->toDateString();

        $eventos = $this->eventosPorDia($inicioGrilla, $finGrilla);
        $dias = [];
        for ($d = $inicioGrilla->copy(); $d->lte($finGrilla); $d->addDay()) {
            $dias[] = [
                'fecha'   => $d->toDateString(),
                'numero'  => $d->day,
                'delMes'  => $d->month === $primero->month,
                'pasado'  => $d->toDateString() < $hoy,
                'hoy'     => $d->toDateString() === $hoy,
                'semana'  => mb_strtoupper(SupportCarbon::parse($d)->locale('es')->isoFormat('dddd')),
                'eventos' => $eventos[$d->toDateString()] ?? [],
            ];
        }

        return view('livewire.calendario', [
            'dias'        => $dias,
            'nombreMes'   => mb_convert_case(SupportCarbon::parse($primero)->locale('es')->isoFormat('MMMM YYYY'), MB_CASE_TITLE),
            'tipos'       => Evento::TIPOS,
            'esAdmin'     => $this->esAdmin(),
            'diaDetalle'  => $this->diaAbierto ? [
                'fecha'   => mb_convert_case(SupportCarbon::parse($this->diaAbierto)->locale('es')->isoFormat('dddd D [de] MMMM'), MB_CASE_TITLE),
                'eventos' => $this->eventosPorDia(Carbon::parse($this->diaAbierto, $zona), Carbon::parse($this->diaAbierto, $zona))[$this->diaAbierto] ?? [],
            ] : null,
        ]);
    }
}
