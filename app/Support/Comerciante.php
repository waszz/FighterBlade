<?php

namespace App\Support;

use App\Models\Objeto;
use App\Models\Personaje;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

// El Comerciante Khonshu: al terminar una exploración, a veces (CHANCE %) aparece él en vez del enemigo.
// Vende de 1 a MAX_PARTES partes de sets de la zona: nivel de la zona + 5 (zona 25 → sets de nivel 30), como los
// enemigos que salen ahí. La oferta queda guardada en el personaje (comerciante_oferta) hasta que se despide.
class Comerciante
{
    const CHANCE = 10;           // % de que aparezca al terminar una exploración
    const MAX_PARTES = 6;
    const PRECIO_POR_NIVEL = 150; // oro por parte = nivel del set × esto
    const PARTES = ['equipo', 'entrenamiento', 'accesorio'];

    // Lo que dice al aparecer (uno al azar)
    const SALUDOS = [
        'Bajo la luz de la luna, todo tiene un precio… y hoy los míos son justos.',
        'Viajero, la luna me trajo hasta vos. Mirá lo que tengo para esta zona.',
        'No peleo: comercio. Partes de los guerreros de estas tierras, solo por oro.',
        'Cada noche cambio de mercancía. Lo que no compres hoy, mañana será de otro.',
    ];

    public static function post(): ?Post
    {
        return Post::conRivales()->where('es_enemigo', Post::COMERCIANTE)->first();
    }

    public static function aparece(): bool
    {
        return random_int(1, 100) <= self::CHANCE && self::post() !== null;
    }

    // Oferta nueva: entre 1 y MAX_PARTES partes al azar de sets del nivel de la zona + 5 (si no hay, los más cercanos)
    public static function generarOferta(int $nivelZona): array
    {
        $nivel = min(100, $nivelZona + 5);
        $cantidad = random_int(1, self::MAX_PARTES);

        $sets = Post::query()->where('nivel', $nivel)->whereNotNull('equipo_nombre')->inRandomOrder()->limit($cantidad)->get();
        if ($sets->isEmpty()) {
            $sets = Post::query()->whereNotNull('equipo_nombre')->orderByRaw('ABS(nivel - ?)', [$nivel])->limit(10)->get()->shuffle()->take($cantidad);
        }

        $partes = [];
        for ($i = 0; $i < $cantidad && $sets->isNotEmpty(); $i++) {
            $set = $sets[$i % $sets->count()];
            $tipo = self::PARTES[array_rand(self::PARTES)];
            $partes[] = [
                'post_id'  => $set->id,
                'set'      => $set->titulo,
                'tipo'     => $tipo,
                'nombre'   => $set->{$tipo . '_nombre'} ?: (ucfirst($tipo) . ' de ' . $set->titulo),
                'imagen'   => $set->{$tipo . '_imagen'} ?: preg_replace('#^posts/#', '', (string) $set->imagen),
                'stats'    => self::decodificar($set->{'ajustes_manuales_' . $tipo}),
                'requisitos' => self::decodificar($set->{'requisitos_' . $tipo}),
                'nivel'    => (int) $set->nivel,
                'precio'   => (int) $set->nivel * self::PRECIO_POR_NIVEL,
                'comprada' => false,
            ];
        }

        return [
            'saludo'     => self::SALUDOS[array_rand(self::SALUDOS)],
            'nivel_zona' => $nivelZona,
            'partes'     => $partes,
        ];
    }

    // Compra la parte $indice de la oferta del personaje. Devuelve un mensaje de error, o null si salió bien
    public static function comprar(int $personajeId, int $indice): ?string
    {
        return DB::transaction(function () use ($personajeId, $indice) {
            $personaje = Personaje::where('id', $personajeId)->where('user_id', auth()->id())->lockForUpdate()->first();
            $oferta = $personaje?->comerciante_oferta;
            $parte = $oferta['partes'][$indice] ?? null;
            if (! $parte) {
                return 'El comerciante ya no tiene esa parte.';
            }
            if (! empty($parte['comprada'])) {
                return 'Ya compraste esa parte.';
            }
            if ($personaje->oro < $parte['precio']) {
                return 'No tenés suficiente oro.';
            }
            if (! $personaje->tieneLugar()) {
                return '🎒 ' . Personaje::MENSAJE_INVENTARIO_LLENO;
            }

            $personaje->oro -= $parte['precio'];
            $oferta['partes'][$indice]['comprada'] = true;
            $personaje->comerciante_oferta = $oferta;
            $personaje->save();

            Objeto::create([
                'personaje_id'             => $personaje->id,
                'nombre'                   => $parte['nombre'],
                'tipo'                     => $parte['tipo'],
                'nivel'                    => $parte['nivel'],
                'stats'                    => $parte['stats'],
                'imagen'                   => $parte['imagen'] ?: ('default_' . $parte['tipo'] . '.png'),
                'origen_post_id'           => $parte['post_id'],
                'requisitos_equipo'        => $parte['tipo'] === 'equipo' ? $parte['requisitos'] : [],
                'requisitos_entrenamiento' => $parte['tipo'] === 'entrenamiento' ? $parte['requisitos'] : [],
                'requisitos_accesorio'     => $parte['tipo'] === 'accesorio' ? $parte['requisitos'] : [],
                'pocion'                   => false,
            ]);

            return null;
        });
    }

    private static function decodificar($valor): array
    {
        $array = is_array($valor) ? $valor : (json_decode($valor ?? '[]', true) ?: []);
        return array_filter($array, fn ($v) => is_numeric($v) && $v > 0);
    }
}
