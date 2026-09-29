<?php

namespace App\Livewire;

use App\Models\NotificacionJuego;
use App\Models\Personaje;
use App\Models\Transaccion;
use Livewire\Component;

// Campanita de notificaciones del juego (arriba, al lado de Mis Drops; en el celular en la barra de arriba).
// El panel tiene dos pestañas: los avisos (recarga de esmeraldas, te atacaron, te compraron, intercambios)
// y las transacciones con otros jugadores (compras del mercado e intercambios)
class AvisosJuego extends Component
{
    public int $personajeId;
    public string $estilo = 'pc'; // pc | celular: solo cambia el botón
    public bool $abierto = false;
    public string $pestana = 'avisos'; // avisos | transacciones

    const CANTIDAD = 40;

    protected $listeners = ['statsActualizados' => '$refresh'];

    public function mount(int $personajeId, string $estilo = 'pc')
    {
        $this->personajeId = $personajeId;
        $this->estilo = $estilo;
    }

    // Solo el dueño del personaje ve sus avisos
    protected function esMio(): bool
    {
        return Personaje::where('user_id', auth()->id())->whereKey($this->personajeId)->exists();
    }

    public function abrir(string $pestana = 'avisos')
    {
        $this->abierto = true;
        $this->pestana = $pestana;
        $this->marcarLeidas();
    }

    public function cerrar()
    {
        $this->abierto = false;
    }

    public function verPestana(string $pestana)
    {
        $this->pestana = in_array($pestana, ['avisos', 'transacciones'], true) ? $pestana : 'avisos';
    }

    protected function marcarLeidas(): void
    {
        if ($this->esMio()) {
            NotificacionJuego::where('personaje_id', $this->personajeId)->where('leida', false)->update(['leida' => true]);
        }
    }

    public function render()
    {
        $mio = $this->esMio();
        $sinLeer = $mio ? NotificacionJuego::where('personaje_id', $this->personajeId)->where('leida', false)->count() : 0;

        // Con el panel abierto, lo que llega mientras tanto ya se da por leído
        if ($this->abierto && $sinLeer > 0) {
            $this->marcarLeidas();
            $sinLeer = 0;
        }

        $avisos = collect();
        $transacciones = collect();
        if ($this->abierto && $mio) {
            $avisos = $this->pestana === 'avisos'
                ? NotificacionJuego::where('personaje_id', $this->personajeId)->latest('id')->limit(self::CANTIDAD)->get()
                : collect();
            $transacciones = $this->pestana === 'transacciones'
                ? Transaccion::with('de:id,nombre', 'para:id,nombre')->de($this->personajeId)->latest('id')->limit(self::CANTIDAD)->get()
                : collect();
        }

        return view('livewire.avisos-juego', compact('sinLeer', 'avisos', 'transacciones'));
    }
}
