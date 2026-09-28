<?php

namespace App\Support;

// Catálogo de cosméticos que se compran en Extras con diamantes (compra permanente).
// Cada tipo se guarda en una columna del personaje: chat → efecto_chat, nombre → efecto_nombre, aura → aura.
// La clase CSS de cada uno está en resources/css/app.css (sección "Cosméticos").
class Cosmeticos
{
    const TIPOS = [
        'chat'   => ['columna' => 'efecto_chat',   'titulo' => 'Efectos de chat',   'icono' => '💬', 'descripcion' => 'Tus mensajes del chat salen en una burbuja especial.'],
        'nombre' => ['columna' => 'efecto_nombre', 'titulo' => 'Efectos de nombre', 'icono' => '✨', 'descripcion' => 'Tu nombre brilla en el chat, el panel y la lista de la ciudad.'],
        'aura'   => ['columna' => 'aura',          'titulo' => 'Auras',             'icono' => '🔥', 'descripcion' => 'Un resplandor alrededor de tu personaje, en tu panel y en la ciudad.'],
        'ranking' => ['columna' => 'efecto_ranking', 'titulo' => 'Ranking',         'icono' => '🏆', 'descripcion' => 'Tu fila se destaca en los rankings y en la lista de usuarios de la ciudad.'],
    ];

    const CATALOGO = [
        // Chat
        'chat_fuego'     => ['tipo' => 'chat', 'nombre' => 'Burbuja de fuego',  'precio' => 150, 'clase' => 'cos-chat-fuego'],
        'chat_hielo'     => ['tipo' => 'chat', 'nombre' => 'Burbuja de hielo',  'precio' => 150, 'clase' => 'cos-chat-hielo'],
        'chat_neon'      => ['tipo' => 'chat', 'nombre' => 'Burbuja neón',      'precio' => 200, 'clase' => 'cos-chat-neon'],
        'chat_oro'       => ['tipo' => 'chat', 'nombre' => 'Burbuja Gold',    'precio' => 250, 'clase' => 'cos-chat-oro'],
        'chat_arcoiris'  => ['tipo' => 'chat', 'nombre' => 'Burbuja arcoíris',  'precio' => 300, 'clase' => 'cos-chat-arcoiris'],
        'chat_black'         => ['tipo' => 'chat', 'nombre' => 'Burbuja Black',  'precio' => 250, 'clase' => 'cos-chat-black'],
        'chat_old'           => ['tipo' => 'chat', 'nombre' => 'Burbuja Old',    'precio' => 200, 'clase' => 'cos-chat-old'],
        'chat_plata'         => ['tipo' => 'chat', 'nombre' => 'Burbuja Plata',  'precio' => 200, 'clase' => 'cos-chat-plata'],
        'chat_zafiro'        => ['tipo' => 'chat', 'nombre' => 'Burbuja Zafiro', 'precio' => 300, 'clase' => 'cos-chat-zafiro'],
        'chat_amatista'      => ['tipo' => 'chat', 'nombre' => 'Burbuja Amatista', 'precio' => 300, 'clase' => 'cos-chat-amatista'],
        'chat_ruby'          => ['tipo' => 'chat', 'nombre' => 'Burbuja Ruby',   'precio' => 350, 'clase' => 'cos-chat-ruby'],
        'chat_esmeralda'     => ['tipo' => 'chat', 'nombre' => 'Burbuja Esmeralda', 'precio' => 350, 'clase' => 'cos-chat-esmeralda'],
        'chat_diamante'      => ['tipo' => 'chat', 'nombre' => 'Burbuja Diamante', 'precio' => 450, 'clase' => 'cos-chat-diamante'],
        // Nombre
        'nombre_fuego'    => ['tipo' => 'nombre', 'nombre' => 'Nombre de fuego',   'precio' => 200, 'clase' => 'cos-nombre-fuego'],
        'nombre_hielo'    => ['tipo' => 'nombre', 'nombre' => 'Nombre de hielo',   'precio' => 200, 'clase' => 'cos-nombre-hielo'],
        'nombre_neon'     => ['tipo' => 'nombre', 'nombre' => 'Nombre neón',       'precio' => 250, 'clase' => 'cos-nombre-neon'],
        'nombre_oro'      => ['tipo' => 'nombre', 'nombre' => 'Nombre Gold',     'precio' => 300, 'clase' => 'cos-nombre-oro'],
        'nombre_arcoiris' => ['tipo' => 'nombre', 'nombre' => 'Nombre arcoíris',   'precio' => 400, 'clase' => 'cos-nombre-arcoiris'],
        'nombre_black'       => ['tipo' => 'nombre', 'nombre' => 'Nombre Black', 'precio' => 300, 'clase' => 'cos-nombre-black'],
        'nombre_old'         => ['tipo' => 'nombre', 'nombre' => 'Nombre Old',   'precio' => 250, 'clase' => 'cos-nombre-old'],
        'nombre_plata'       => ['tipo' => 'nombre', 'nombre' => 'Nombre Plata', 'precio' => 250, 'clase' => 'cos-nombre-plata'],
        'nombre_zafiro'      => ['tipo' => 'nombre', 'nombre' => 'Nombre Zafiro', 'precio' => 350, 'clase' => 'cos-nombre-zafiro'],
        'nombre_amatista'    => ['tipo' => 'nombre', 'nombre' => 'Nombre Amatista', 'precio' => 350, 'clase' => 'cos-nombre-amatista'],
        'nombre_ruby'        => ['tipo' => 'nombre', 'nombre' => 'Nombre Ruby',  'precio' => 400, 'clase' => 'cos-nombre-ruby'],
        'nombre_esmeralda'   => ['tipo' => 'nombre', 'nombre' => 'Nombre Esmeralda', 'precio' => 400, 'clase' => 'cos-nombre-esmeralda'],
        'nombre_diamante'    => ['tipo' => 'nombre', 'nombre' => 'Nombre Diamante', 'precio' => 500, 'clase' => 'cos-nombre-diamante'],
        // Aura
        'aura_fuego'     => ['tipo' => 'aura', 'nombre' => 'Aura de fuego',     'precio' => 300, 'clase' => 'cos-aura-fuego'],
        'aura_hielo'     => ['tipo' => 'aura', 'nombre' => 'Aura de hielo',     'precio' => 300, 'clase' => 'cos-aura-hielo'],
        'aura_electrica' => ['tipo' => 'aura', 'nombre' => 'Aura eléctrica',    'precio' => 400, 'clase' => 'cos-aura-electrica'],
        'aura_oscura'    => ['tipo' => 'aura', 'nombre' => 'Aura oscura',       'precio' => 400, 'clase' => 'cos-aura-oscura'],
        'aura_dorada'    => ['tipo' => 'aura', 'nombre' => 'Aura Gold',       'precio' => 500, 'clase' => 'cos-aura-dorada'],
        'aura_black'         => ['tipo' => 'aura', 'nombre' => 'Aura Black',     'precio' => 400, 'clase' => 'cos-aura-black'],
        'aura_old'           => ['tipo' => 'aura', 'nombre' => 'Aura Old',       'precio' => 350, 'clase' => 'cos-aura-old'],
        'aura_plata'         => ['tipo' => 'aura', 'nombre' => 'Aura Plata',     'precio' => 350, 'clase' => 'cos-aura-plata'],
        'aura_zafiro'        => ['tipo' => 'aura', 'nombre' => 'Aura Zafiro',    'precio' => 450, 'clase' => 'cos-aura-zafiro'],
        'aura_amatista'      => ['tipo' => 'aura', 'nombre' => 'Aura Amatista',  'precio' => 450, 'clase' => 'cos-aura-amatista'],
        'aura_ruby'          => ['tipo' => 'aura', 'nombre' => 'Aura Ruby',      'precio' => 500, 'clase' => 'cos-aura-ruby'],
        'aura_esmeralda'     => ['tipo' => 'aura', 'nombre' => 'Aura Esmeralda', 'precio' => 500, 'clase' => 'cos-aura-esmeralda'],
        'aura_diamante'      => ['tipo' => 'aura', 'nombre' => 'Aura Diamante',  'precio' => 600, 'clase' => 'cos-aura-diamante'],
        // Ranking
        'ranking_fuego'    => ['tipo' => 'ranking', 'nombre' => 'Fila de fuego',    'precio' => 250, 'clase' => 'cos-ranking-fuego'],
        'ranking_hielo'    => ['tipo' => 'ranking', 'nombre' => 'Fila de hielo',    'precio' => 250, 'clase' => 'cos-ranking-hielo'],
        'ranking_galaxia'  => ['tipo' => 'ranking', 'nombre' => 'Fila galaxia',     'precio' => 350, 'clase' => 'cos-ranking-galaxia'],
        'ranking_oro'      => ['tipo' => 'ranking', 'nombre' => 'Fila Gold',      'precio' => 400, 'clase' => 'cos-ranking-oro'],
        'ranking_arcoiris' => ['tipo' => 'ranking', 'nombre' => 'Fila arcoíris',    'precio' => 500, 'clase' => 'cos-ranking-arcoiris'],
        'ranking_black'      => ['tipo' => 'ranking', 'nombre' => 'Fila Black',  'precio' => 350, 'clase' => 'cos-ranking-black'],
        'ranking_old'        => ['tipo' => 'ranking', 'nombre' => 'Fila Old',    'precio' => 300, 'clase' => 'cos-ranking-old'],
        'ranking_plata'      => ['tipo' => 'ranking', 'nombre' => 'Fila Plata',  'precio' => 300, 'clase' => 'cos-ranking-plata'],
        'ranking_zafiro'     => ['tipo' => 'ranking', 'nombre' => 'Fila Zafiro', 'precio' => 400, 'clase' => 'cos-ranking-zafiro'],
        'ranking_amatista'   => ['tipo' => 'ranking', 'nombre' => 'Fila Amatista', 'precio' => 400, 'clase' => 'cos-ranking-amatista'],
        'ranking_ruby'       => ['tipo' => 'ranking', 'nombre' => 'Fila Ruby',   'precio' => 450, 'clase' => 'cos-ranking-ruby'],
        'ranking_esmeralda'  => ['tipo' => 'ranking', 'nombre' => 'Fila Esmeralda', 'precio' => 450, 'clase' => 'cos-ranking-esmeralda'],
        'ranking_diamante'   => ['tipo' => 'ranking', 'nombre' => 'Fila Diamante', 'precio' => 550, 'clase' => 'cos-ranking-diamante'],
    ];

    public static function delTipo(string $tipo): array
    {
        return array_filter(self::CATALOGO, fn ($c) => $c['tipo'] === $tipo);
    }

    // Clase CSS del cosmético equipado (o '' si no tiene)
    public static function clase(?string $clave): string
    {
        return $clave ? (self::CATALOGO[$clave]['clase'] ?? '') : '';
    }
}
