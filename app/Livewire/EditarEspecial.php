<?php

namespace App\Livewire;

use App\Models\Mision;
use App\Models\Poder;
use App\Models\Post;
use Livewire\Component;
use Livewire\WithFileUploads;

// Admin: editar un personaje especial (rival de misión / enemigo de bienvenida).
// A diferencia del editor de sets, el nivel es libre y los stats se cargan directo (sin reparto de puntos).
class EditarEspecial extends Component
{
    use WithFileUploads;

    const STATS = ['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'];

    // Campo del formulario => [columna, nombre en pantalla]
    const ARCHIVOS = [
        'imagen'       => ['imagen', 'Foto'],
        'gif'          => ['gif', 'Parado'],
        'gif_ataque'   => ['gif_ataque', 'Ataque'],
        'gif_critico'  => ['gif_critico', 'Crítico'],
        'gif_especial' => ['gif_especial', 'Especial'],
        'gif_defensa'  => ['gif_defensa', 'Defensa'],
        'gif_derrota'  => ['gif_derrota', 'Derrota'],
        'gif_victoria' => ['gif_victoria', 'Victoria'],
    ];

    public Post $especial;
    public string $titulo = '';
    public int $nivel = 1;
    public string $tipo = 'fisico';
    public array $stats = [];
    public array $poderesSeleccionados = [];
    public array $archivos = [];
    // Comerciante: % de que aparezca al terminar una exploración
    public int $chance = 0;

    public function mount($post)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->especial = Post::conRivales()->with('poderes')->findOrFail($post);
        abort_unless(in_array((int) $this->especial->es_enemigo, [Post::ENEMIGO_ESPECIAL, Post::RIVAL_MISION, Post::COMERCIANTE], true), 404);

        $this->titulo = $this->especial->titulo;
        $this->nivel = (int) $this->especial->nivel;
        $this->tipo = $this->especial->tipo ?? 'fisico';
        $stats = is_array($this->especial->stats) ? $this->especial->stats : [];
        foreach (self::STATS as $stat) {
            $this->stats[$stat] = (int) ($stats[$stat] ?? 0);
        }
        $this->poderesSeleccionados = $this->especial->poderes->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->chance = (int) ($this->especial->chance_aparicion ?? \App\Support\Comerciante::CHANCE);
    }

    protected function rules()
    {
        $reglas = [
            'titulo' => 'required|string|max:100',
            'nivel'  => 'required|integer|min:1|max:100',
            'tipo'   => 'required|in:fisico,elemental,hibrido',
            'stats.*' => 'required|integer|min:0|max:100000',
            'poderesSeleccionados' => 'array',
            'chance' => 'required|integer|min:0|max:100',
        ];
        foreach (array_keys(self::ARCHIVOS) as $campo) {
            $reglas["archivos.$campo"] = 'nullable|file|mimes:gif,png,jpg,jpeg,webp|max:12288';
        }
        return $reglas;
    }

    protected $validationAttributes = ['archivos.*' => 'archivo', 'chance' => 'probabilidad'];

    public function quitarArchivo(string $campo)
    {
        unset($this->archivos[$campo]);
    }

    public function guardar()
    {
        $this->validate();

        $post = Post::conRivales()->findOrFail($this->especial->id);
        $post->titulo = $this->titulo;
        $post->nivel = $this->nivel;
        $post->tipo = $this->tipo;
        $post->stats = array_map('intval', $this->stats);
        if ((int) $post->es_enemigo === Post::COMERCIANTE) {
            $post->chance_aparicion = $this->chance;
        }

        foreach (self::ARCHIVOS as $campo => [$columna]) {
            $archivo = $this->archivos[$campo] ?? null;
            if ($archivo && method_exists($archivo, 'storeAs')) {
                $nombre = time() . '_esp' . $post->id . '_' . $campo . '.' . strtolower($archivo->getClientOriginalExtension());
                $archivo->storeAs('public/posts', $nombre);
                $post->$columna = 'posts/' . $nombre;
            }
        }

        $post->save(); // al cambiar gifs se vuelven a medir solos (Post::booted)
        $post->poderes()->sync($this->poderesSeleccionados);

        $this->archivos = [];
        $this->especial = $post->fresh('poderes');
        $this->dispatch('success', ['message' => "{$post->titulo} guardado."]);
    }

    public function render()
    {
        return view('livewire.editar-especial', [
            'post' => $this->especial,
            'poderesDisponibles' => Poder::orderBy('nombre')->get(),
            'mision' => Mision::where('post_id', $this->especial->id)->first(),
        ])->layout('layouts.app');
    }
}
