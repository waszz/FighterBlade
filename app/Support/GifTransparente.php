<?php

namespace App\Support;

// Hace transparente el fondo magenta (#FF00FF) de los GIF de los sets (sprites que se guardaron sin transparencia).
// También el fondo blanco o verde (#00FF00): esos solo si las 4 esquinas del primer cuadro lo tienen, y solo lo pegado al borde.
// - Cuadro sin color transparente: se marca el magenta de su paleta como el transparente (Graphic Control Extension)
//   y el cuadro se borra antes del siguiente, para que no queden rastros. No hace falta re-comprimir.
// - Cuadro que ya tiene otro color transparente (o varios tonos de magenta): se descomprime (LZW), los píxeles
//   magenta pasan al índice transparente y se vuelve a comprimir.
class GifTransparente
{
    // Arregla un GIF del disco público (ruta relativa, ej. "posts/123_gifAtaque.gif") si tiene fondo magenta.
    // Antes de cambiarlo deja una copia del original en storage/app/gifs-originales/. Devuelve qué hizo, o null si
    // no hacía falta (sin magenta, no es gif o no existe). Con $probar solo dice qué haría
    public static function arreglarArchivo(?string $ruta, bool $probar = false): ?string
    {
        $disco = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $ruta || ! str_ends_with(strtolower($ruta), '.gif') || ! $disco->exists($ruta)) {
            return null;
        }
        [$nuevo, , $motivo] = self::procesar($disco->get($ruta));
        if ($nuevo === null) {
            return null;
        }
        if (! $probar) {
            $copia = storage_path('app/gifs-originales/' . $ruta);
            if (! file_exists($copia)) {
                if (! is_dir(dirname($copia))) {
                    mkdir(dirname($copia), 0775, true);
                }
                copy($disco->path($ruta), $copia);
            }
            $disco->put($ruta, $nuevo);
        }
        return $motivo;
    }

    // Tolerancia: algunos rips guardan el magenta como 248,0,248 o 252,0,252
    private static function esMagenta(int $r, int $g, int $b): bool
    {
        return $r >= 240 && $g <= 16 && $b >= 240;
    }

    // Blanco (o casi): algunos sprites se guardaron con fondo blanco en vez de magenta
    private static function esBlanco(int $r, int $g, int $b): bool
    {
        return $r >= 240 && $g >= 240 && $b >= 240;
    }

    // Verde de "pantalla verde" (#00FF00 o casi): otros sprites se guardaron con ese fondo
    private static function esVerde(int $r, int $g, int $b): bool
    {
        return $r <= 40 && $g >= 200 && $b <= 40;
    }

    // Índices de los colores de fondo que se reconocen por las esquinas: blanco y verde
    private static function indicesBlanco(string $tabla): array
    {
        $indices = [];
        for ($i = 0, $n = intdiv(strlen($tabla), 3); $i < $n; $i++) {
            [$r, $g, $b] = [ord($tabla[$i * 3]), ord($tabla[$i * 3 + 1]), ord($tabla[$i * 3 + 2])];
            if (self::esBlanco($r, $g, $b) || self::esVerde($r, $g, $b)) {
                $indices[] = $i;
            }
        }
        return $indices;
    }

    // ¿Las 4 esquinas del cuadro tienen alguno de esos índices? (así se reconoce un fondo, no un blanco del dibujo)
    private static function esquinasCon(string $pixeles, int $ancho, int $alto, bool $entrelazado, array $indices): bool
    {
        if ($ancho <= 0 || $alto <= 0 || strlen($pixeles) < $ancho * $alto) {
            return false;
        }
        $filaReal = range(0, $alto - 1);
        if ($entrelazado) {
            $filaReal = [];
            foreach ([[0, 8], [4, 8], [2, 4], [1, 2]] as [$desde, $paso]) {
                for ($y = $desde; $y < $alto; $y += $paso) {
                    $filaReal[] = $y;
                }
            }
        }
        $filaGuardada = array_flip($filaReal);
        $buscados = array_flip(array_map('chr', $indices));
        foreach ([[0, 0], [$ancho - 1, 0], [0, $alto - 1], [$ancho - 1, $alto - 1]] as [$x, $y]) {
            if (! isset($buscados[$pixeles[$filaGuardada[$y] * $ancho + $x]])) {
                return false;
            }
        }
        return true;
    }

    // Índices de los colores magenta de una tabla (el exacto primero)
    private static function indicesMagenta(string $tabla): array
    {
        $exactos = $cerca = [];
        for ($i = 0, $n = intdiv(strlen($tabla), 3); $i < $n; $i++) {
            [$r, $g, $b] = [ord($tabla[$i * 3]), ord($tabla[$i * 3 + 1]), ord($tabla[$i * 3 + 2])];
            if ($r === 255 && $g === 0 && $b === 255) {
                $exactos[] = $i;
            } elseif (self::esMagenta($r, $g, $b)) {
                $cerca[] = $i;
            }
        }
        return array_merge($exactos, $cerca);
    }

    // Concatena los sub-bloques de datos que empiezan en $p; devuelve [datos, posición después del 0]
    private static function leerSubbloques(string $gif, int $p): array
    {
        $datos = '';
        $len = strlen($gif);
        while ($p < $len) {
            $tam = ord($gif[$p]);
            $datos .= substr($gif, $p + 1, $tam);
            $p += 1 + $tam;
            if ($tam === 0) {
                break;
            }
        }
        return [$datos, $p];
    }

    // Parte datos en sub-bloques de hasta 255 bytes, con el 0 final
    private static function armarSubbloques(string $datos): string
    {
        $salida = '';
        foreach (str_split($datos, 255) as $trozo) {
            if ($trozo !== '') {
                $salida .= chr(strlen($trozo)) . $trozo;
            }
        }
        return $salida . "\x00";
    }

    // Posiciones (en el orden guardado) de los píxeles con esos índices que están conectados con el borde del cuadro:
    // el fondo que quedó sin transparencia. Los encerrados dentro del sprite no se tocan (pueden ser parte del dibujo).
    private static function conectadosAlBorde(string $pixeles, int $ancho, int $alto, bool $entrelazado, array $indices): array
    {
        $total = $ancho * $alto;
        if ($ancho <= 0 || $alto <= 0 || strlen($pixeles) < $total) {
            return [];
        }
        // Fila real de cada fila guardada (GIF entrelazado: 0,8,16… / 4,12… / 2,6… / 1,3…)
        $filaReal = range(0, $alto - 1);
        if ($entrelazado) {
            $filaReal = [];
            foreach ([[0, 8], [4, 8], [2, 4], [1, 2]] as [$desde, $paso]) {
                for ($y = $desde; $y < $alto; $y += $paso) {
                    $filaReal[] = $y;
                }
            }
        }
        $filaGuardada = array_flip($filaReal);
        $buscados = array_flip(array_map('chr', $indices));
        $esBuscado = fn (int $x, int $y) => isset($buscados[$pixeles[$filaGuardada[$y] * $ancho + $x]]);

        $visto = [];
        $cola = [];
        for ($x = 0; $x < $ancho; $x++) {
            $cola[] = [$x, 0];
            $cola[] = [$x, $alto - 1];
        }
        for ($y = 0; $y < $alto; $y++) {
            $cola[] = [0, $y];
            $cola[] = [$ancho - 1, $y];
        }
        $resultado = [];
        while ($cola) {
            [$x, $y] = array_pop($cola);
            $clave = $y * $ancho + $x;
            if (isset($visto[$clave]) || ! $esBuscado($x, $y)) {
                continue;
            }
            $visto[$clave] = true;
            $resultado[] = $filaGuardada[$y] * $ancho + $x;
            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                $nx = $x + $dx;
                $ny = $y + $dy;
                if ($nx >= 0 && $ny >= 0 && $nx < $ancho && $ny < $alto && ! isset($visto[$ny * $ancho + $nx])) {
                    $cola[] = [$nx, $ny];
                }
            }
        }
        return $resultado;
    }

    // Descompresión LZW de GIF: devuelve los índices de color de los píxeles (un byte por píxel)
    public static function lzwDecodificar(int $min, string $datos): string
    {
        $clear = 1 << $min;
        $eoi = $clear + 1;
        $tam = $min + 1;
        $dict = [];
        for ($i = 0; $i < $clear; $i++) {
            $dict[$i] = chr($i);
        }
        $siguiente = $eoi + 1;
        $salida = '';
        $anterior = null;
        $acum = 0;
        $bits = 0;
        $len = strlen($datos);
        $pos = 0;
        while (true) {
            while ($bits < $tam && $pos < $len) {
                $acum |= ord($datos[$pos++]) << $bits;
                $bits += 8;
            }
            if ($bits < $tam) {
                break;
            }
            $codigo = $acum & ((1 << $tam) - 1);
            $acum >>= $tam;
            $bits -= $tam;

            if ($codigo === $clear) {
                $dict = array_slice($dict, 0, $clear);
                $siguiente = $eoi + 1;
                $tam = $min + 1;
                $anterior = null;
                continue;
            }
            if ($codigo === $eoi) {
                break;
            }
            if (isset($dict[$codigo])) {
                $entrada = $dict[$codigo];
            } elseif ($codigo === $siguiente && $anterior !== null) {
                $entrada = $anterior . $anterior[0];
            } else {
                break; // dato corrupto: se corta
            }
            $salida .= $entrada;
            if ($anterior !== null && $siguiente < 4096) {
                $dict[$siguiente++] = $anterior . $entrada[0];
                if ($siguiente === (1 << $tam) && $tam < 12) {
                    $tam++;
                }
            }
            $anterior = $entrada;
        }
        return $salida;
    }

    // Compresión LZW de GIF (la inversa de lzwDecodificar)
    public static function lzwCodificar(int $min, string $pixeles): string
    {
        $clear = 1 << $min;
        $eoi = $clear + 1;
        $tam = $min + 1;
        $siguiente = $eoi + 1;
        $dict = [];
        $salida = '';
        $acum = 0;
        $bits = 0;
        $emitir = function (int $codigo) use (&$salida, &$acum, &$bits, &$tam) {
            $acum |= $codigo << $bits;
            $bits += $tam;
            while ($bits >= 8) {
                $salida .= chr($acum & 0xFF);
                $acum >>= 8;
                $bits -= 8;
            }
        };

        $emitir($clear);
        $len = strlen($pixeles);
        if ($len === 0) {
            $emitir($eoi);
            return $salida . ($bits > 0 ? chr($acum & 0xFF) : '');
        }
        $w = $pixeles[0];
        for ($i = 1; $i < $len; $i++) {
            $k = $pixeles[$i];
            $wk = $w . $k;
            if (isset($dict[$wk])) {
                $w = $wk;
                continue;
            }
            $emitir(strlen($w) === 1 ? ord($w) : $dict[$w]);
            if ($siguiente >= 4095) {
                $emitir($clear);
                $dict = [];
                $siguiente = $eoi + 1;
                $tam = $min + 1;
            } else {
                // El decodificador agranda el código cuando su tabla llega a 2^tam (un paso después que acá)
                $dict[$wk] = $siguiente++;
                if ($siguiente > (1 << $tam) && $tam < 12) {
                    $tam++;
                }
            }
            $w = $k;
        }
        $emitir(strlen($w) === 1 ? ord($w) : $dict[$w]);
        $emitir($eoi);
        if ($bits > 0) {
            $salida .= chr($acum & 0xFF);
        }
        return $salida;
    }

    // Salta los sub-bloques de datos (tamaño + datos, hasta un 0) desde $p; devuelve la posición después del 0
    private static function saltarSubbloques(string $gif, int $p): int
    {
        $len = strlen($gif);
        while ($p < $len) {
            $tam = ord($gif[$p]);
            $p += 1 + $tam;
            if ($tam === 0) {
                break;
            }
        }
        return $p;
    }

    /**
     * Procesa los bytes de un GIF. Devuelve [nuevosBytes|null, cuadrosCambiados, motivo].
     * nuevosBytes es null si no hay nada que cambiar (sin magenta, ya transparente o no es un GIF válido).
     */
    public static function procesar(string $gif): array
    {
        if (strlen($gif) < 13 || ! in_array(substr($gif, 0, 6), ['GIF87a', 'GIF89a'], true)) {
            return [null, 0, 'no es un GIF'];
        }

        $anchoLienzo = ord($gif[6]) | (ord($gif[7]) << 8);
        $altoLienzo = ord($gif[8]) | (ord($gif[9]) << 8);
        $packed = ord($gif[10]);
        $tablaGlobal = '';
        $p = 13;
        if ($packed & 0x80) {
            $tam = 3 * (1 << (($packed & 0x07) + 1));
            $tablaGlobal = substr($gif, 13, $tam);
            $p += $tam;
        }

        $salida = substr($gif, 0, $p);
        $gcePendiente = null; // posición en $salida del GCE que corresponde al próximo cuadro
        $cambiados = 0;
        $cuadros = 0;
        $yaTransparentes = 0;
        $fondoBlanco = null; // se decide en el primer cuadro: ¿el gif tiene fondo blanco?
        $len = strlen($gif);

        while ($p < $len) {
            $byte = ord($gif[$p]);

            if ($byte === 0x3B) { // fin
                $salida .= "\x3B";
                break;
            }

            if ($byte === 0x21) { // extensión
                $etiqueta = ord($gif[$p + 1] ?? "\0");
                $fin = self::saltarSubbloques($gif, $p + 2);
                if ($etiqueta === 0xF9) {
                    $gcePendiente = strlen($salida);
                }
                $salida .= substr($gif, $p, $fin - $p);
                $p = $fin;
                continue;
            }

            if ($byte === 0x2C) { // cuadro
                $cuadros++;
                $pk = ord($gif[$p + 9] ?? "\0");
                $tabla = $tablaGlobal;
                $finDescriptor = $p + 10;
                if ($pk & 0x80) {
                    $tam = 3 * (1 << (($pk & 0x07) + 1));
                    $tabla = substr($gif, $finDescriptor, $tam);
                    $finDescriptor += $tam;
                }
                $finCuadro = self::saltarSubbloques($gif, $finDescriptor + 1); // +1: tamaño mínimo de código LZW
                $cuadro = substr($gif, $p, $finCuadro - $p);

                $magentas = self::indicesMagenta($tabla);
                $cambiadosAntes = $cambiados;
                if ($magentas) {
                    $min = ord($gif[$finDescriptor]);
                    [$datos] = self::leerSubbloques($gif, $finDescriptor + 1);
                    $pixeles = self::lzwDecodificar($min, $datos);
                    $flags = $gcePendiente !== null ? ord($salida[$gcePendiente + 3]) : 0;
                    $yaTiene = (bool) ($flags & 0x01);
                    // Índice transparente: el que ya tiene el cuadro o, si no tiene, el magenta
                    $transparente = $yaTiene ? ord($salida[$gcePendiente + 6]) : $magentas[0];
                    $aReemplazar = array_values(array_diff($magentas, [$transparente]));

                    $anchoCuadro = ord($gif[$p + 5]) | (ord($gif[$p + 6]) << 8);
                    $altoCuadro = ord($gif[$p + 7]) | (ord($gif[$p + 8]) << 8);

                    if (! $yaTiene) {
                        // Sin transparencia: todo el magenta es el fondo (así se guardan los sprites)
                        $usados = array_values(array_filter($magentas, fn ($i) => str_contains($pixeles, chr($i))));
                        $posiciones = null; // todos los píxeles de esos índices
                    } else {
                        // Ya tiene transparencia: solo los restos de fondo magenta pegados al borde
                        $posiciones = self::conectadosAlBorde($pixeles, $anchoCuadro, $altoCuadro, (bool) ($pk & 0x40), $aReemplazar);
                        $usados = $posiciones ? [true] : [];
                    }

                    if (! $usados) {
                        $yaTransparentes += $yaTiene ? 1 : 0;
                    } else {
                        $cambiados++;
                        if (! $yaTiene) {
                            // El cuadro se borra antes del siguiente solo si ocupa toda la imagen
                            // (en las animaciones optimizadas cada cuadro dibuja una parte: ahí no se cambia)
                            $completo = $anchoCuadro === $anchoLienzo && $altoCuadro === $altoLienzo;
                            if ($gcePendiente !== null) {
                                $nuevosFlags = $flags | 0x01;
                                if ($completo) {
                                    $nuevosFlags = ($nuevosFlags & ~0x1C & 0xFF) | (2 << 2);
                                }
                                $salida[$gcePendiente + 3] = chr($nuevosFlags);
                                $salida[$gcePendiente + 6] = chr($transparente);
                            } else {
                                $salida .= "\x21\xF9\x04" . chr(($completo ? (2 << 2) : 0) | 0x01) . "\x00\x00" . chr($transparente) . "\x00";
                            }
                        }

                        // Píxeles que pasan al índice transparente (se vuelve a comprimir el cuadro)
                        $antes = $pixeles;
                        if ($posiciones === null) {
                            $otros = array_values(array_filter($aReemplazar, fn ($i) => str_contains($pixeles, chr($i))));
                            if ($otros) {
                                $pixeles = strtr($pixeles, implode('', array_map('chr', $otros)), str_repeat(chr($transparente), count($otros)));
                            }
                        } else {
                            foreach ($posiciones as $pos) {
                                $pixeles[$pos] = chr($transparente);
                            }
                        }
                        if ($pixeles !== $antes) {
                            $cabecera = $finDescriptor - $p; // descriptor + tabla local
                            $cuadro = substr($cuadro, 0, $cabecera) . chr($min) . self::armarSubbloques(self::lzwCodificar($min, $pixeles));
                        }
                    }
                }

                // Fondo blanco (si el magenta no cambió nada en este cuadro): solo si las 4 esquinas son blancas, y solo el
                // blanco pegado al borde (el de adentro del sprite, como ojos o brillos, queda). Como transparente se usa
                // el que ya tenga el cuadro o un índice de la paleta que no use ningún píxel (si no hay, no se toca)
                // El blanco se toma como fondo solo si el primer cuadro del gif ya lo tiene: en una animación con fondo
                // transparente, un cuadro con las esquinas blancas es un destello del efecto, no un fondo
                $blancos = $cambiados === $cambiadosAntes && ($fondoBlanco ?? $cuadros === 1) ? self::indicesBlanco($tabla) : [];
                if ($cuadros === 1) {
                    $fondoBlanco = false;
                }
                // El índice que el cuadro ya usa como transparente no cuenta como blanco aunque en la paleta lo sea
                // (muchos gifs guardan el transparente como blanco: sus esquinas ya son transparentes, no un fondo)
                $flagsBlanco = $gcePendiente !== null ? ord($salida[$gcePendiente + 3]) : 0;
                if ($blancos && ($flagsBlanco & 0x01)) {
                    $blancos = array_values(array_diff($blancos, [ord($salida[$gcePendiente + 6])]));
                }
                if ($blancos) {
                    $min = ord($gif[$finDescriptor]);
                    [$datos] = self::leerSubbloques($gif, $finDescriptor + 1);
                    $pixeles = self::lzwDecodificar($min, $datos);
                    $anchoCuadro = ord($gif[$p + 5]) | (ord($gif[$p + 6]) << 8);
                    $altoCuadro = ord($gif[$p + 7]) | (ord($gif[$p + 8]) << 8);
                    $entrelazado = (bool) ($pk & 0x40);

                    if (self::esquinasCon($pixeles, $anchoCuadro, $altoCuadro, $entrelazado, $blancos)) {
                        if ($cuadros === 1) {
                            $fondoBlanco = true;
                        }
                        $flags = $flagsBlanco;
                        $yaTiene = (bool) ($flags & 0x01);
                        $transparente = $yaTiene ? ord($salida[$gcePendiente + 6]) : null;
                        if ($transparente === null) {
                            $usadosPixeles = count_chars($pixeles, 1);
                            for ($i = 0, $n = min(1 << ($min), intdiv(strlen($tabla), 3)); $i < $n; $i++) {
                                if (! isset($usadosPixeles[$i])) {
                                    $transparente = $i;
                                    break;
                                }
                            }
                        }
                        // Paleta llena y fondo verde: el mismo verde pasa a ser el transparente (un verde de "pantalla
                        // verde" no suele estar dentro del dibujo; con el blanco no se hace, porque sí: ojos, brillos)
                        $verdeEsquina = null;
                        foreach ($blancos as $i) {
                            if (self::esVerde(ord($tabla[$i * 3]), ord($tabla[$i * 3 + 1]), ord($tabla[$i * 3 + 2]))) {
                                $verdeEsquina = $i;
                                break;
                            }
                        }
                        if ($transparente === null && $verdeEsquina !== null) {
                            $transparente = $verdeEsquina;
                        }
                        $posiciones = $transparente !== null
                            ? self::conectadosAlBorde($pixeles, $anchoCuadro, $altoCuadro, $entrelazado, array_values(array_diff($blancos, [$transparente])))
                            : [];
                        $verdeComoTransparente = $transparente !== null && ! $yaTiene && $transparente === $verdeEsquina;

                        if ($posiciones || $verdeComoTransparente) {
                            $cambiados++;
                            if (! $yaTiene) {
                                $completo = $anchoCuadro === $anchoLienzo && $altoCuadro === $altoLienzo;
                                if ($gcePendiente !== null) {
                                    $nuevosFlags = $flags | 0x01;
                                    if ($completo) {
                                        $nuevosFlags = ($nuevosFlags & ~0x1C & 0xFF) | (2 << 2);
                                    }
                                    $salida[$gcePendiente + 3] = chr($nuevosFlags);
                                    $salida[$gcePendiente + 6] = chr($transparente);
                                } else {
                                    $salida .= "\x21\xF9\x04" . chr(($completo ? (2 << 2) : 0) | 0x01) . "\x00\x00" . chr($transparente) . "\x00";
                                }
                            }
                            if ($posiciones) {
                                foreach ($posiciones as $pos) {
                                    $pixeles[$pos] = chr($transparente);
                                }
                                $cabecera = $finDescriptor - $p;
                                $cuadro = substr($cuadro, 0, $cabecera) . chr($min) . self::armarSubbloques(self::lzwCodificar($min, $pixeles));
                            }
                        }
                    }
                }
                $gcePendiente = null;
                $salida .= $cuadro;
                $p = $finCuadro;
                continue;
            }

            // Byte inesperado: se deja el archivo como está
            return [null, 0, 'formato inesperado'];
        }

        if ($cambiados === 0) {
            return [null, 0, $yaTransparentes ? 'ya transparente' : 'sin magenta'];
        }
        // Si el original era GIF87a, las extensiones requieren GIF89a
        $salida = substr_replace($salida, 'GIF89a', 0, 6);

        return [$salida, $cambiados, "{$cambiados} de {$cuadros} cuadros"];
    }
}
