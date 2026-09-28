<?php

namespace App\Livewire;

use App\Models\Mensaje;
use App\Models\Personaje;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

// Chat del juego: sala general y privados entre personajes. Arriba la casilla (privados sin leer) y los
// conectados; abajo el texto y la subida de GIFs. Los objetos y las peleas se comparten desde el Inventario
// y Mis Peleas (App\Support\ChatCompartir). Se actualiza solo cada pocos segundos (wire:poll en la vista).
class ChatComponent extends Component
{
    use WithFileUploads;

    // Minutos sin actividad para dejar de contar a alguien como conectado
    const MINUTOS_ONLINE = 5;
    const MAX_GIF_KB = 3072;

    #[Locked]
    public ?int $personajeId = null;

    public $mensaje = '';
    public $gif;

    // Privado abierto con este personaje (null = chat general)
    public ?int $conId = null;
    // Panel desplegado arriba: 'casilla' | 'online' | null
    public ?string $panel = null;

    protected $listeners = ['chatCompartido' => '$refresh'];

    public function mount($personajeId = null)
    {
        // El personaje con el que se está jugando (tiene que ser de la cuenta); si no, el primero de la cuenta
        $personaje = $personajeId ? Personaje::where('user_id', auth()->id())->find($personajeId) : null;
        $personaje ??= Personaje::where('user_id', auth()->id())->first();
        $this->personajeId = $personaje?->id;
    }

    private function yo(): ?Personaje
    {
        return $this->personajeId ? Personaje::where('user_id', auth()->id())->find($this->personajeId) : null;
    }

    public function enviarMensaje()
    {
        $this->validate(['mensaje' => 'required|string|max:255'], [
            'mensaje.required' => 'Escribí un mensaje.',
            'mensaje.max'      => 'El mensaje puede tener hasta 255 caracteres.',
        ]);

        if ($this->publicar('texto', trim($this->mensaje))) {
            $this->mensaje = '';
        }
    }

    // Al elegir un GIF se sube y se manda enseguida
    public function updatedGif()
    {
        $this->validate(['gif' => 'file|mimes:gif|max:' . self::MAX_GIF_KB], [
            'gif.mimes' => 'Solo se pueden subir GIFs.',
            'gif.max'   => 'El GIF puede pesar hasta ' . (self::MAX_GIF_KB / 1024) . ' MB.',
        ]);

        $ruta = $this->gif->store('chat/gifs', 'public');
        $this->publicar('gif', '', ['ruta' => $ruta]);
        $this->reset('gif');
    }

    private function publicar(string $tipo, string $contenido, ?array $adjunto = null): bool
    {
        $yo = $this->yo();
        if (! $yo) {
            $this->addError('mensaje', 'Necesitás un personaje para usar el chat.');
            return false;
        }
        if ($this->conId && ! Personaje::whereKey($this->conId)->exists()) {
            $this->conId = null;
        }

        Mensaje::create([
            'user_id'         => auth()->id(),
            'personaje_id'    => $yo->id,
            'destinatario_id' => $this->conId,
            'contenido'       => $contenido,
            'tipo'            => $tipo,
            'adjunto'         => $adjunto,
        ]);

        return true;
    }

    public function abrirPrivado(int $personajeId)
    {
        if ($personajeId !== $this->personajeId && Personaje::whereKey($personajeId)->exists()) {
            $this->conId = $personajeId;
        }
        $this->panel = null;
    }

    public function volverGeneral()
    {
        $this->conId = null;
    }

    public function alternarPanel(string $panel)
    {
        $this->panel = $this->panel === $panel ? null : $panel;
    }

    public function reiniciarChat()
    {
        if (auth()->user()->role !== 'admin') {
            session()->flash('error', 'No tienes permiso para reiniciar el chat.');
            return;
        }

        Mensaje::truncate();
        Storage::disk('public')->deleteDirectory('chat/gifs');

        session()->flash('success', 'Chat reiniciado correctamente.');
    }

    // Personajes conectados: cuentas con actividad en los últimos minutos (tabla de sesiones), con su último personaje usado
    private function conectados()
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }
        $usuarios = DB::table(config('session.table', 'sessions'))
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(self::MINUTOS_ONLINE)->timestamp)
            ->distinct()->pluck('user_id');

        return Personaje::whereIn('user_id', $usuarios)->orderByDesc('updated_at')->get()
            ->unique('user_id')->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    public function render()
    {
        $yoId = $this->personajeId;
        $con = $this->conId ? Personaje::find($this->conId) : null;
        if ($this->conId && ! $con) {
            $this->conId = null;
        }

        $relaciones = ['personaje.post', 'personaje.equipo', 'personaje.entrenamiento', 'personaje.accesorio'];
        $mensajes = ($con
                ? Mensaje::entre($yoId, $con->id)
                : Mensaje::general())
            ->with($relaciones)->latest('id')->take(50)->get()->reverse();

        // Al abrir un privado, lo que me mandaron queda leído
        if ($con && $yoId) {
            Mensaje::where('personaje_id', $con->id)->where('destinatario_id', $yoId)->whereNull('leido_en')->update(['leido_en' => now()]);
        }

        // Casilla: una fila por conversación (el último mensaje y cuántos sin leer)
        $conversaciones = collect();
        $sinLeer = 0;
        if ($yoId) {
            $sinLeerPor = Mensaje::where('destinatario_id', $yoId)->whereNull('leido_en')
                ->selectRaw('personaje_id, COUNT(*) as c')->groupBy('personaje_id')->pluck('c', 'personaje_id');
            $sinLeer = (int) $sinLeerPor->sum();

            if ($this->panel === 'casilla') {
                $ultimos = Mensaje::where(fn ($q) => $q->where('personaje_id', $yoId)->orWhere('destinatario_id', $yoId))
                    ->whereNotNull('destinatario_id')->latest()->take(300)->get();
                $conversaciones = $ultimos->groupBy(fn ($m) => $m->personaje_id === $yoId ? $m->destinatario_id : $m->personaje_id)
                    ->map(fn ($grupo, $otroId) => ['ultimo' => $grupo->first(), 'sinLeer' => (int) ($sinLeerPor[$otroId] ?? 0)]);
                $personajes = Personaje::whereIn('id', $conversaciones->keys())->get()->keyBy('id');
                $conversaciones = $conversaciones->map(fn ($c, $otroId) => $c + ['personaje' => $personajes[$otroId] ?? null])
                    ->filter(fn ($c) => $c['personaje']);
            }
        }

        $conectados = $this->conectados();

        return view('livewire.chat-component', [
            'mensajes'       => $mensajes,
            'con'            => $con,
            'conversaciones' => $conversaciones,
            'sinLeer'        => $sinLeer,
            'conectados'     => $conectados,
        ]);
    }
}
