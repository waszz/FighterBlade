<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Buff;
use App\Models\Clan;
use App\Models\Post;
use App\Models\User;
use App\Models\Poder;
use App\Models\Ciudad;
use App\Models\Objeto;
use App\Models\Mensaje;
use App\Models\EstadoTemporal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Personaje extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nombre',
        'titulo',
        'tipo',
        'nivel',
        'imagen',
        'gif',
        'ciudad_id',
        'stats',
        'oro',
        'diamante',
        'experiencia',
        'exploracion_inicio',
        'exploracion_duracion',
        'enemigo_id',
        'enemigo_actual_id',
        'exploracion_finaliza_en',
        'post_id',
        'orientacion_gif',
        'stats_base',
        'tipo',
        'fin_exploracion',
        'pvp_puntos',
        'pve_puntos',
        'clan_id',
        'fin_recuperacion',
        'tiempo_recuperacion',
        'pocion_equipada',
        'stats_modificados',
        'stats_guardados',
        'minutos_originales',
        
        

    ];
protected $casts = [
    'foto_chat_post_id' => 'integer', // null = automática, 0 = imagen subida, >0 = set elegido
    'stats' => 'array',
    'stats_base' => 'array',
    'stats_modificados' => 'array',
    'stats_guardados' => 'boolean',
    'fin_exploracion' => 'datetime',
    'fin_recuperacion' => 'datetime',
    'viajando_hasta' => 'datetime',
    'minutos_originales' => 'integer',
    'casino_vidas' => 'integer',
    'casino_vidas_desde' => 'datetime',
    'caza_cargas' => 'integer',
    'caza_cargas_desde' => 'datetime',
];

  

    // Poderes del personaje = los de su set (se usan cuando es el rival en PvP)
    public function poderes()
    {
        return $this->belongsToMany(Poder::class, 'poder_post', 'post_id', 'poder_id', 'post_id', 'id');
    }

    // Segundos que le quedan de recuperación después de una pelea (0 si no se está recuperando)
    public function segundosRecuperacion(): int
    {
        if (! $this->fin_exploracion || $this->exploracion_duracion > 0) {
            return 0;
        }
        return max(0, \Carbon\Carbon::parse($this->fin_exploracion)->timestamp - now()->timestamp);
    }

    // Set con el que pelea (sus gifs y apariencia): el del set completo equipado, o su set base
    public function postDeCombate(): ?Post
    {
        $equipo = $this->equipo;
        $entrenamiento = $this->entrenamiento;
        $accesorio = $this->accesorio;
        if ($equipo && $entrenamiento && $accesorio && $equipo->origen_post_id
            && $equipo->origen_post_id === $entrenamiento->origen_post_id
            && $equipo->origen_post_id === $accesorio->origen_post_id) {
            $completo = Post::find($equipo->origen_post_id);
            if ($completo) {
                return $completo;
            }
        }
        return $this->post;
    }

    // Stats para pelear: los base más los de las partes equipadas
    public function statsDeCombate(): array
    {
        $stats = self::decodificarStats($this->stats);
        foreach (['equipo', 'entrenamiento', 'accesorio', 'joya'] as $parte) {
            $objeto = $this->$parte;
            if (! $objeto) {
                continue;
            }
            foreach (self::decodificarStats($objeto->stats) as $stat => $valor) {
                if (isset($stats[$stat]) && is_numeric($valor)) {
                    $stats[$stat] += (int) $valor;
                }
            }
        }
        return $stats;
    }

    // Stats como array: los personajes creados desde el juego los guardan como texto JSON y otros
    // (ej. los bots) como array. Acepta las dos formas.
    public static function decodificarStats($stats): array
    {
        if (is_array($stats)) {
            return $stats;
        }
        if (is_string($stats) && $stats !== '') {
            $decodificado = json_decode($stats, true);
            // Algunos quedaron codificados dos veces ("{\"fuerza\":5,...}")
            if (is_string($decodificado)) {
                $decodificado = json_decode($decodificado, true);
            }
            return is_array($decodificado) ? $decodificado : [];
        }
        return [];
    }

    protected static function booted()
{
    static::creating(function ($personaje) {
        if (empty($personaje->stats_base) && !empty($personaje->stats)) {
            $personaje->stats_base = $personaje->stats;
        }
    });

    // Campeones: al llegar al nivel máximo queda anotado (solo la primera vez), sea por peleas, misiones o admin
    static::saved(function ($personaje) {
        if ($personaje->wasChanged('nivel') || $personaje->wasRecentlyCreated) {
            if ((int) $personaje->nivel >= 100) {
                \Illuminate\Support\Facades\DB::table('campeones')->insertOrIgnore([
                    'personaje_id' => $personaje->id,
                    'alcanzado_en' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    });
}

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Novato: todavía le toca el enemigo especial de bienvenida (nivel 1 y 2). No puede atacar ni ser atacado en PvP
    public function esNovato(): bool
    {
        return ($this->nivel ?? 1) <= \App\Livewire\Explorar::NIVEL_MAX_ENEMIGO_ESPECIAL;
    }

    // Excluye a los personajes de cuentas admin (no aparecen en los rankings)
    public function scopeSinAdmins($query)
    {
        return $query->whereDoesntHave('user', fn ($q) => $q->where('role', 'admin'));
    }

    public function ciudadActual()
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id'); // o el campo que uses
    }

    public function clan()
{
    return $this->belongsTo(Clan::class, 'clan_id');
}

public function buffs()
{
    return $this->hasMany(Buff::class);
}

    // Relación con el enemigo (que es un Post)
    public function enemigo()
    {
        return $this->belongsTo(Post::class, 'enemigo_id');
    }
    
    public function mensajes()
    {
        return $this->hasMany(Mensaje::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Objeto::class, 'equipo_id');
    }

    public function entrenamiento()
    {
        return $this->belongsTo(Objeto::class, 'entrenamiento_id');
    }
    

    public function accesorio()
    {
        return $this->belongsTo(Objeto::class, 'accesorio_id');
    }

    // Joya (anillo de la Torre): suma sus 2 stats
    public function joya()
    {
        return $this->belongsTo(Objeto::class, 'joya_id');
    }

  public function objetos()
{
    return $this->belongsToMany(Objeto::class)->withPivot(['cantidad'])->withTimestamps();
}
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    // Método para cargar un personaje con su post relacionado (con gifs)
    public static function findWithPost($id)
    {
        return self::with('post')->find($id);
    }

   public function agregarExperiencia(int $exp)
{
    $nivelAntes = $this->nivel;
    $this->experiencia += $exp;

    $nivelNuevo = $this->calcularNivelDesdeExperiencia($this->experiencia);

    if ($nivelNuevo > $nivelAntes) {
        $nivelesGanados = $nivelNuevo - $nivelAntes;

        $this->nivel = $nivelNuevo;

        // Asegurate de tener la columna 'puntos_stats' en tu tabla personajes
        if (!isset($this->puntos_stats)) {
            $this->puntos_stats = 0;
        }

        $this->puntos_stats += 5 * $nivelesGanados;
    }

    $this->save();
}

   public function calcularNivelDesdeExperiencia(int $exp): int
{
    $nivel = 1;

    // Se incrementa el nivel mientras la experiencia acumulada alcance la necesaria
    while ($exp >= 10000 * pow($nivel, 2)) {
        $nivel++;
    }

    return $nivel;
}

public function enemigoActual()
{
    return $this->belongsTo(Post::class, 'enemigo_actual_id');
}

public function objeto_consumible()
{
    return $this->belongsTo(Objeto::class, 'objeto_consumible_id');
}


public function isAdmin()
{
    return $this->role === 'admin';
}

public function postsUsados()
{
    return $this->belongsToMany(Post::class, 'personaje_post_historial');
}

// Cosméticos (Extras): claves compradas y clases CSS de lo que tiene puesto
public function cosmeticosComprados(): array
{
    return \Illuminate\Support\Facades\DB::table('personaje_cosmeticos')->where('personaje_id', $this->id)->pluck('clave')->all();
}

public function claseChat(): string
{
    return \App\Support\Cosmeticos::clase($this->efecto_chat);
}

public function claseNombre(): string
{
    return \App\Support\Cosmeticos::clase($this->efecto_nombre);
}

public function claseAura(): string
{
    return \App\Support\Cosmeticos::clase($this->aura);
}

public function claseRanking(): string
{
    return \App\Support\Cosmeticos::clase($this->efecto_ranking);
}

// Sets cuya foto puede elegir para el chat: su set base y los que equipó alguna vez (los de "Mis personajes")
public function idsFotosChat(): array
{
    $ids = \Illuminate\Support\Facades\DB::table('personaje_post_historial')->where('personaje_id', $this->id)->pluck('post_id')->all();
    return array_values(array_unique(array_filter(array_merge([$this->post_id], $ids))));
}

// Foto del chat: la imagen subida (foto_chat_post_id = 0), la de un set elegido en el Inventario
// o, en automático (null), la del set con el que pelea
public function fotoChat(): ?string
{
    if ($this->foto_chat_post_id === 0 && $this->foto_chat_propia) {
        return $this->foto_chat_propia;
    }
    if ($this->foto_chat_post_id) {
        $elegida = Post::find($this->foto_chat_post_id)?->imagen;
        if ($elegida) {
            return $elegida;
        }
    }
    return $this->postDeCombate()?->imagen ?? $this->imagen;
}

public function estadosTemporales()
{
    return $this->hasMany(EstadoTemporal::class);
}

}