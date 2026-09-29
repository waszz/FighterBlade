<?php

namespace App\Support;

use App\Models\Mensaje;
use App\Models\Objeto;
use App\Models\Pelea;
use App\Models\Personaje;

// Compartir en el chat general un objeto (parte de set, poción, joya, cofre) o una pelea.
// Se guarda una "foto" de lo compartido en mensajes.adjunto, así se sigue viendo aunque después se venda o se borre.
class ChatCompartir
{
    const NOMBRE_PARTE = ['equipo' => 'Equipo', 'entrenamiento' => 'Entrenamiento', 'accesorio' => 'Accesorio', 'joya' => 'Joya', 'cofre' => 'Cofre'];

    const ORIGEN_PELEA = ['explorar' => 'Exploración', 'pvp' => 'PvP', 'mision' => 'Misión', 'torre' => 'Torre', 'caza' => 'Caza'];

    public static function objeto(Personaje $personaje, Objeto $objeto): Mensaje
    {
        $esPocion = $objeto->pocion || $objeto->tipo === 'pocion';
        $stats = is_array($objeto->stats) ? $objeto->stats : (json_decode($objeto->stats ?? '[]', true) ?: []);
        $set = $objeto->origen_post_id ? \App\Models\Post::conRivales()->find($objeto->origen_post_id) : null;
        $requisitos = in_array($objeto->tipo, ['equipo', 'entrenamiento', 'accesorio'], true) ? ($objeto->{'requisitos_' . $objeto->tipo} ?? []) : [];
        $requisitos = is_array($requisitos) ? $requisitos : (json_decode($requisitos ?: '[]', true) ?: []);

        return self::publicar($personaje, 'objeto', [
            'nombre'      => $objeto->nombre,
            'tipo'        => $esPocion ? 'pocion' : $objeto->tipo,
            'nivel'       => $objeto->nivel,
            // Ruta dentro de public/ (las pociones están en images/, el resto en storage/posts/)
            'imagen'      => $objeto->imagen ? ($esPocion ? 'images/' : 'storage/posts/') . $objeto->imagen : null,
            'set'         => $set?->titulo,
            'set_id'      => $set?->id,
            'danio'       => $set?->tipo,
            'requisitos'  => array_filter(array_map('intval', $requisitos), fn ($v) => $v > 0),
            'stats'       => $esPocion ? [] : array_filter(array_map('intval', $stats), fn ($v) => $v > 0),
            'descripcion' => $esPocion ? ($objeto->descripcion ?: null) : null,
        ]);
    }

    public static function pelea(Personaje $personaje, Pelea $pelea): Mensaje
    {
        $datos = $pelea->datos_combate ?? [];
        // Peleas viejas sin el origen guardado: se deduce (igual que en Mis Drops)
        $origen = $datos['origen'] ?? (! empty($datos['enemigo_es_personaje']) ? 'pvp' : (! empty($datos['escenario_mision']) ? 'mision' : 'explorar'));

        return self::publicar($personaje, 'pelea', [
            'pelea_id'         => $pelea->id,
            'origen'           => $origen,
            'resultado'        => $pelea->resultado,
            'exp'              => (int) $pelea->exp_ganada,
            'oro'              => (int) $pelea->oro_ganado,
            'nombre_personaje' => $datos['nombre_personaje'] ?? $personaje->nombre,
            'nombre_enemigo'   => $pelea->nombreRival(),
            'gif_personaje'    => $datos['gif_personaje'] ?? null,
            'gif_enemigo'      => $datos['gif_enemigo'] ?? null,
            'fecha'            => $pelea->realizada_en?->toIso8601String(),
        ]);
    }

    private static function publicar(Personaje $personaje, string $tipo, array $adjunto): Mensaje
    {
        return Mensaje::create([
            'user_id'      => $personaje->user_id,
            'personaje_id' => $personaje->id,
            'contenido'    => '',
            'tipo'         => $tipo,
            'adjunto'      => $adjunto,
        ]);
    }
}
