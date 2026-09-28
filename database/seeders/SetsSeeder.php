<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Poder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Crea los sets a partir de la carpeta SETS (una subcarpeta por nivel, una por personaje con sus gifs).
// Uso: php artisan db:seed --class=SetsSeeder   (carpeta configurable con SETS_PATH en el .env)
// Los que ya existen (mismo título y nivel) se saltean, así que se puede volver a correr.
class SetsSeeder extends Seeder
{
    // 'nivel/carpeta' => [título, tipo]
    const PERSONAJES = [
        '5/alex'        => ['Alex', 'fisico'],
        '5/arrow'       => ['Green Arrow', 'fisico'],
        '5/blanka'      => ['Blanka', 'fisico'],
        '5/chang'       => ['Chang Koehan', 'fisico'],
        '5/dhalsim'     => ['Dhalsim', 'elemental'],
        '5/eiji'        => ['Eiji Kisaragi', 'hibrido'],
        '5/electro'     => ['Electro', 'elemental'],
        '5/elektra'     => ['Elektra', 'fisico'],
        '5/iron fist'   => ['Iron Fist', 'hibrido'],
        '5/moondragon'  => ['Moondragon', 'elemental'],

        '10/adon'        => ['Adon', 'fisico'],
        '10/bao'         => ['Bao', 'elemental'],
        '10/blackmasket' => ['Black Mask', 'fisico'],
        '10/bridie'      => ['Birdie', 'fisico'],
        '10/hugo'        => ['Hugo', 'fisico'],
        '10/makoto'      => ['Makoto', 'fisico'],
        '10/urien'       => ['Urien', 'hibrido'],
        '10/yang'        => ['Yang', 'fisico'],
        '10/yun'         => ['Yun', 'fisico'],
        '10/zanfield'    => ['Zangief', 'fisico'],

        '15/GentlemanGhost' => ['Gentleman Ghost', 'elemental'],
        '15/amingo'         => ['Amingo', 'elemental'],
        '15/freeman'        => ['Freeman', 'fisico'],
        '15/gwen stacy'     => ['Spider-Gwen', 'fisico'],
        '15/haw'            => ['Hawkeye', 'fisico'],
        '15/hoku'           => ['Hokutomaru', 'hibrido'],
        '15/sas'            => ['Sasquatch', 'hibrido'],
        '15/starlord'       => ['Star-Lord', 'hibrido'],
        '15/tung fu'        => ['Tung Fu Rue', 'fisico'],
        '15/yuri'           => ['Yuri Sakazaki', 'hibrido'],

        '20/Martian Manhunter' => ['Martian Manhunter', 'hibrido'],
        '20/adehleid'          => ['Adelheid', 'hibrido'],
        '20/balrog'            => ['Balrog', 'fisico'],
        '20/catwoman'          => ['Catwoman', 'fisico'],
        '20/kilowog'           => ['Kilowog', 'elemental'],
        '20/nakoruru'          => ['Nakoruru', 'hibrido'],
        '20/shumagorat'        => ['Shuma-Gorath', 'elemental'],
        '20/taskmaster'        => ['Taskmaster', 'fisico'],
        '20/vice'              => ['Vice', 'fisico'],
        '20/whip'              => ['Whip', 'fisico'],

        '25/ash'     => ['Ash Crimson', 'elemental'],
        '25/dudley'  => ['Dudley', 'fisico'],
        '25/felicia' => ['Felicia', 'fisico'],
        '25/guille'  => ['Guile', 'hibrido'],
        '25/ibuki'   => ['Ibuki', 'fisico'],
        '25/k'       => ["K'", 'hibrido'],
        '25/kraven'  => ['Kraven', 'fisico'],
        '25/lizard'  => ['Lizard', 'fisico'],
        '25/rock'    => ['Rock Howard', 'hibrido'],
        '25/ronan'   => ['Ronan', 'hibrido'],

        '30/cyborg'    => ['Cyborg', 'hibrido'],
        '30/gato'      => ['Gato', 'fisico'],
        '30/geese'     => ['Geese Howard', 'hibrido'],
        '30/hanzo'     => ['Hanzo', 'hibrido'],
        '30/iceman'    => ['Iceman', 'elemental'],
        '30/kaede'     => ['Kaede', 'elemental'],
        '30/psylock'   => ['Psylocke', 'hibrido'],
        '30/rocket'    => ['Rocket Raccoon', 'fisico'],
        '30/rolento'   => ['Rolento', 'fisico'],
        '30/sentinela' => ['Centinela', 'elemental'],

        '35/anakaris' => ['Anakaris', 'elemental'],
        '35/armor'    => ['Spider-Man Armor', 'fisico'],
        '35/gambito'  => ['Gambito', 'hibrido'],
        '35/gamorra'  => ['Gamora', 'fisico'],
        '35/hayato'   => ['Hayato', 'hibrido'],
        '35/logan'    => ['Logan', 'fisico'],
        '35/morrigan' => ['Morrigan', 'hibrido'],
        '35/oro'      => ['Oro', 'elemental'],
        '35/pyron'    => ['Pyron', 'elemental'],
        '35/terry'    => ['Terry Bogard', 'hibrido'],

        '40/archangel'       => ['Archangel', 'hibrido'],
        '40/blackwidow'      => ['Black Widow', 'fisico'],
        '40/colosus'         => ['Coloso', 'fisico'],
        '40/eagle'           => ['Eagle', 'fisico'],
        '40/falcon'          => ['Falcon', 'fisico'],
        '40/kyosuke'         => ['Kyosuke', 'hibrido'],
        '40/mujer maravilla' => ['Mujer Maravilla', 'fisico'],
        '40/ruby heart'      => ['Ruby Heart', 'hibrido'],
        '40/sandman'         => ['Sandman', 'elemental'],
        '40/shiki'           => ['Shiki', 'fisico'],

        '45/bestia'     => ['Bestia', 'fisico'],
        '45/ingrid'     => ['Ingrid', 'elemental'],
        '45/jedah'      => ['Jedah', 'elemental'],
        '45/jill'       => ['Jill Valentine', 'fisico'],
        '45/juggernaut' => ['Juggernaut', 'fisico'],
        '45/kingpin'    => ['Kingpin', 'fisico'],
        '45/mai'        => ['Mai Shiranui', 'hibrido'],
        '45/man-thing'  => ['Man-Thing', 'elemental'],
        '45/namor'      => ['Namor', 'hibrido'],
        '45/omegared'   => ['Omega Red', 'hibrido'],

        '50/acertijo'       => ['Acertijo', 'fisico'],
        '50/bison'          => ['M. Bison', 'hibrido'],
        '50/ciclope'        => ['Cíclope', 'elemental'],
        '50/cyber woo'      => ['Cyber-Woo', 'fisico'],
        '50/doctor optopus' => ['Doctor Octopus', 'fisico'],
        '50/groot'          => ['Groot', 'fisico'],
        '50/ironma'         => ['Iron Man', 'hibrido'],
        '50/necro'          => ['Necro', 'hibrido'],
        '50/puinguino'      => ['Pingüino', 'fisico'],
        '50/vanessa'        => ['Vanessa', 'fisico'],

        '55/brims'            => ['Brimstone', 'elemental'],
        '55/capitan marvel'   => ['Capitán Marvel', 'elemental'],
        '55/humantorch'       => ['Antorcha Humana', 'elemental'],
        '55/iori'             => ['Iori Yagami', 'elemental'],
        '55/kriller'          => ['Killer Croc', 'fisico'],
        '55/kyo'              => ['Kyo Kusanagi', 'elemental'],
        '55/moonknight'       => ['Moon Knight', 'fisico'],
        '55/neo dio'          => ['Neo Dio', 'hibrido'],
        '55/quasar'           => ['Quasar', 'elemental'],
        '55/soldado invierno' => ['Soldado del Invierno', 'fisico'],

        '60/azrael'       => ['Azrael', 'fisico'],
        '60/bloodwynd'    => ['Bloodwynd', 'elemental'],
        '60/chun li'      => ['Chun-Li', 'fisico'],
        '60/ghost riders' => ['Ghost Rider', 'hibrido'],
        '60/gill'         => ['Gill', 'elemental'],
        '60/gladiador'    => ['Gladiador', 'fisico'],
        '60/mech'         => ['Mech Zangief', 'fisico'],
        '60/mobius'       => ['Morbius', 'hibrido'],
        '60/shazam'       => ['Shazam', 'hibrido'],
        '60/thanos'       => ['Thanos', 'hibrido'],

        '65/Dr Fate'         => ['Doctor Fate', 'elemental'],
        '65/ares'            => ['Ares', 'fisico'],
        '65/atom'            => ['Captain Atom', 'elemental'],
        '65/capitan america' => ['Capitán América', 'fisico'],
        '65/carnage'         => ['Carnage', 'hibrido'],
        '65/clayface'        => ['Clayface', 'fisico'],
        '65/flash'           => ['Flash', 'fisico'],
        '65/mysterio'        => ['Mysterio', 'elemental'],
        '65/venom'           => ['Venom', 'fisico'],
        '65/yamazaki'        => ['Yamazaki', 'fisico'],

        '70/angel'             => ['Angel', 'hibrido'],
        '70/ant-man'           => ['Ant-Man', 'fisico'],
        '70/commando'          => ['Captain Commando', 'hibrido'],
        '70/deadpool'          => ['Deadpool', 'fisico'],
        '70/invisible woman'   => ['Mujer Invisible', 'elemental'],
        '70/joker'             => ['Joker', 'fisico'],
        '70/mister fantastico' => ['Mr. Fantástico', 'fisico'],
        '70/nick fury'         => ['Nick Fury', 'fisico'],
        '70/robin'             => ['Robin', 'fisico'],
        '70/silver samurai'    => ['Silver Samurai', 'hibrido'],

        '75/B Beyond'        => ['Batman Beyond', 'fisico'],
        '75/akuma'           => ['Akuma', 'hibrido'],
        '75/dark spiderman'  => ['Dark Spider-Man', 'fisico'],
        '75/darkseid'        => ['Darkseid', 'elemental'],
        '75/enja'            => ['Enja', 'fisico'],
        '75/hulk'            => ['Hulk', 'fisico'],
        '75/mephisto'        => ['Mephisto', 'elemental'],
        '75/ryu'             => ['Ryu', 'hibrido'],
        '75/surtur'          => ['Surtur', 'elemental'],
        '75/thor'            => ['Thor', 'hibrido'],

        '80/Luffy'        => ['Luffy', 'fisico'],
        '80/blackheart'   => ['Blackheart', 'elemental'],
        '80/cyber akuma'  => ['Cyber Akuma', 'hibrido'],
        '80/elisabeth'    => ['Elisabeth', 'elemental'],
        '80/grant'        => ['Grant', 'hibrido'],
        '80/green goblin' => ['Green Goblin', 'hibrido'],
        '80/mystique'     => ['Mystique', 'fisico'],
        '80/vision'       => ['Vision', 'elemental'],

        '85/goku ssj4'   => ['Goku SSJ4', 'hibrido'],
        '85/gouken'      => ['Gouken', 'hibrido'],
        '85/magneto'     => ['Magneto', 'elemental'],
        '85/miss marvel' => ['Ms. Marvel', 'hibrido'],
        '85/nocturno'    => ['Nocturno', 'fisico'],
        '85/silver surf' => ['Silver Surfer', 'elemental'],
        '85/storm'       => ['Storm', 'elemental'],

        '90/lilith' => ['Lilith', 'hibrido'],
        '90/remy'   => ['Remy', 'hibrido'],
        '90/rhino'  => ['Rhino', 'fisico'],
        '90/rugal'  => ['Rugal', 'hibrido'],

        '95/Dr Stronger'    => ['Doctor Strange', 'elemental'],
        '95/adam warlock'   => ['Adam Warlock', 'elemental'],
        '95/bills'          => ['Beerus', 'elemental'],
        '95/capitan marvel' => ['Capitana Marvel', 'elemental'],
        '95/la mole'        => ['La Mole', 'fisico'],
        '95/nova'           => ['Nova', 'elemental'],
        '95/odin'           => ['Odín', 'hibrido'],
        '95/orochi'         => ['Orochi', 'elemental'],
        '95/scarlet witch'  => ['Bruja Escarlata', 'elemental'],

        '100/Apocalypse'      => ['Apocalypse', 'hibrido'],
        '100/Zeno_Attendant'  => ['Asistente de Zeno', 'elemental'],
        '100/cable'           => ['Cable', 'hibrido'],
        '100/dormamu'         => ['Dormammu', 'elemental'],
        '100/dr doom'         => ['Dr. Doom', 'hibrido'],
        '100/goku daishinkan' => ['Goku Daishinkan', 'hibrido'],
        '100/profesor X'      => ['Profesor X', 'elemental'],
        '100/sentry'          => ['Sentry', 'hibrido'],
    ];

    // Reparto (en %) de los nivel*5 puntos según el tipo: todos los del mismo nivel suman lo mismo
    const REPARTO = [
        'fisico'    => ['fuerza' => 25, 'ataque' => 25, 'velocidad' => 15, 'defensa' => 15, 'resistencia' => 15, 'energia' => 5],
        'elemental' => ['energia' => 25, 'ataque' => 25, 'velocidad' => 15, 'defensa' => 15, 'resistencia' => 15, 'fuerza' => 5],
        'hibrido'   => ['ataque' => 25, 'fuerza' => 15, 'energia' => 15, 'velocidad' => 15, 'defensa' => 15, 'resistencia' => 15],
    ];

    // Variantes para que no sean todos iguales (mueven puntos sin cambiar el total)
    const PERFILES = [
        'equilibrado' => [],
        'veloz'       => ['velocidad' => 10, 'defensa' => -5, 'resistencia' => -5],
        'tanque'      => ['velocidad' => -10, 'defensa' => 5, 'resistencia' => 5],
    ];

    // Poderes por tipo (sin los que multiplican/optimizan stats ni los de economía, para no romper el balance)
    const PODERES = [
        'fisico' => ['HEMORRAGIA', 'SANGRADO', 'ATURDIR', 'PIEL DURA', 'FRENESÍ', 'FURIA CIEGA', 'COMBO VELOZ',
                     'INTIMIDAR', 'ATAQUE DESESPERADO', 'ATAQUE TRAICIONERO', 'SUPER SENTIDOS', 'ENEMISTAD'],
        'elemental' => ['QUEMAR', 'CONGELAR', 'PARALIZAR', 'DIRECT DAMAGE', 'SUPER CARGA', 'SUPER NOVA', 'TRANCE',
                        'CONTROL CLIMATICO', 'REDUCCIÓN ELEMENTAL', 'DAMAGE ABSORV', 'TELETRANSPORTARSE', 'MÁXIMA POTENCIA'],
        'hibrido' => ['ENVENENAR', 'ESPINAS', 'ROBAR VIDA', 'REGENERAR', 'CAMUFLAJE', 'INSTINTO MEJORADO', 'ATURDIR',
                      'QUEMAR', 'HEMORRAGIA', 'SUPER CARGA', 'TRANCE', 'ENEMISTAD'],
    ];

    // Poderes fuertes que se suman a la lista desde este nivel
    const PODERES_ALTO_NIVEL = 60;
    const PODERES_FUERTES = ['REGENERAR SUPERIOR', 'ABSORVER SALUD', 'PIEL IMPENETRABLE'];

    // Categorías de gif: campo del post => regex sobre el nombre del archivo
    const GIFS = [
        'gif_ataque'   => '/at+a?u?q|attack/',
        'gif_critico'  => '/c[rf]{1,2}i?ti?u?co/',
        'gif_defensa'  => '/d[a-z]{0,2}f[a-z]{0,2}ns|block/',
        'gif_especial' => '/esp|cial/',
        'gif_derrota'  => '/der+[eo]t|dizz/',
    ];

    // Si falta un gif, se usa el primero que exista de esta lista
    const RESPALDO = [
        'gif'          => ['gif_victoria', 'gif_ataque', 'gif_defensa'],
        'gif_ataque'   => ['gif_critico', 'gif_especial', 'gif'],
        'gif_critico'  => ['gif_ataque', 'gif_especial', 'gif'],
        'gif_especial' => ['gif_critico', 'gif_ataque', 'gif'],
        'gif_defensa'  => ['gif'],
        'gif_derrota'  => ['gif_defensa', 'gif'],
        'gif_victoria' => ['gif'],
    ];

    public function run(): void
    {
        $base = env('SETS_PATH', 'C:/Users/TERA/Desktop/SETS');
        if (! File::isDirectory($base)) {
            $this->command->error("No existe la carpeta de sets: $base");
            return;
        }

        $destino = storage_path('app/public/posts');
        File::ensureDirectoryExists($destino);

        $poderes = Poder::pluck('id', 'nombre')->mapWithKeys(fn ($id, $nombre) => [mb_strtoupper($nombre) => $id]);
        $userId = DB::table('users')->orderBy('id')->value('id');

        $creados = 0;
        foreach (File::directories($base) as $dirNivel) {
            $nivel = (int) basename($dirNivel);

            foreach (File::directories($dirNivel) as $dirPj) {
                $clave = $nivel . '/' . basename($dirPj);
                if (! isset(self::PERSONAJES[$clave])) {
                    $this->command->warn("Sin datos en el seeder, se saltea: $clave");
                    continue;
                }
                [$titulo, $tipo] = self::PERSONAJES[$clave];

                if (Post::where('titulo', $titulo)->where('nivel', $nivel)->exists()) {
                    continue;
                }

                $archivos = $this->archivos($dirPj);
                if (! $archivos['imagen'] && ! $archivos['gif']) {
                    $this->command->warn("Sin imágenes, se saltea: $clave");
                    continue;
                }

                $prefijo = 'set-' . Str::slug($clave);
                $copiar = function (?string $origen, string $sufijo) use ($destino, $prefijo) {
                    if (! $origen) {
                        return null;
                    }
                    $ext = strtolower(pathinfo($origen, PATHINFO_EXTENSION));
                    $ext = $ext === 'jfif' ? 'jpg' : $ext;
                    $nombre = "{$prefijo}_{$sufijo}.{$ext}";
                    File::copy($origen, "$destino/$nombre");
                    return $nombre;
                };

                $imagen = $copiar($archivos['imagen'] ?? $archivos['gif'], 'img');
                $gifs = [];
                foreach (array_keys(self::RESPALDO) as $campo) {
                    // Sin ningún gif: se usa la imagen
                    $gifs[$campo] = $archivos[$campo] ? 'posts/' . $copiar($archivos[$campo], $campo) : 'posts/' . $imagen;
                }

                $semilla = crc32($clave);
                [$stats, $ajustes] = $this->stats($tipo, $nivel, $semilla);

                $post = Post::create([
                    'titulo'   => $titulo,
                    'nivel'    => $nivel,
                    'tipo'     => $tipo,
                    'user_id'  => $userId,
                    'imagen'   => 'posts/' . $imagen,
                    'stats'    => $stats,
                    'stats_equipo'        => array_fill_keys(array_keys($stats), 0),
                    'stats_entrenamiento' => array_fill_keys(array_keys($stats), 0),
                    'stats_accesorio'     => array_fill_keys(array_keys($stats), 0),
                    'ajustes_manuales_equipo'        => $ajustes['equipo'],
                    'ajustes_manuales_entrenamiento' => $ajustes['entrenamiento'],
                    'ajustes_manuales_accesorio'     => $ajustes['accesorio'],
                    'equipo_nombre'        => "Equipo de $titulo",
                    'entrenamiento_nombre' => "Entrenamiento de $titulo",
                    'accesorio_nombre'     => "Accesorio de $titulo",
                    'equipo_imagen'        => $imagen,
                    'entrenamiento_imagen' => $imagen,
                    'accesorio_imagen'     => $imagen,
                    'requisitos_equipo'        => [],
                    'requisitos_entrenamiento' => [],
                    'requisitos_accesorio'     => [],
                ] + $gifs);

                // Campos fuera de $fillable
                $post->forceFill(['publicado' => true, 'costo' => $nivel * 200, 'es_enemigo' => false])->save();

                $post->poderes()->attach($this->poderes($tipo, $nivel, $semilla, $poderes));

                // Requisitos de stats según sus stats y poderes (1 por parte)
                foreach ($post->load('poderes')->calcularRequisitos() as $parte => $req) {
                    $post->{'requisitos_' . $parte} = $req;
                }
                // Bonus de cada parte con el máximo de stats según el nivel (5-45: 3, 50-75: 4, 80-100: 5)
                foreach ($post->repartirAjustes() as $parte => $ajustes) {
                    $post->{'ajustes_manuales_' . $parte} = $ajustes;
                }
                $post->saveQuietly();
                $creados++;
            }
        }

        $this->command->info("Sets creados: $creados");
    }

    // Clasifica los archivos de la carpeta de un personaje en imagen + gifs por categoría
    protected function archivos(string $dir): array
    {
        $resultado = ['imagen' => null] + array_fill_keys(array_keys(self::RESPALDO), null);
        $sinCategoria = [];
        $caras = [];

        // Los nombres sin números primero (así "especial.gif" gana sobre "especial 2.gif")
        $files = collect(File::files($dir))->sortBy(fn ($f) => [preg_match('/\d/', $f->getFilename()), $f->getFilename()]);

        foreach ($files as $file) {
            $ruta = $file->getPathname();
            $ext = strtolower($file->getExtension());
            $nombre = mb_strtolower($file->getFilenameWithoutExtension());

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'jfif', 'webp'])) {
                $resultado['imagen'] ??= $ruta;
                continue;
            }
            if ($ext !== 'gif') {
                continue;
            }

            $categorias = [];
            foreach (self::GIFS as $campo => $regex) {
                if (preg_match($regex, $nombre)) {
                    $categorias[] = $campo;
                }
            }
            foreach (preg_split('/[^\p{L}]+/u', $nombre, -1, PREG_SPLIT_NO_EMPTY) as $palabra) {
                if (preg_match('/^v\p{L}*t\p{L}*(ia|ai|ias|as)$/u', $palabra)) {
                    $categorias[] = 'gif_victoria';
                }
                if (preg_match('/^p\p{L}*s\p{L}*j\p{L}*$/u', $palabra)) {
                    $categorias[] = 'gif';
                }
            }

            if (! $categorias) {
                if (str_contains($nombre, 'cara')) {
                    $caras[] = $ruta;
                } else {
                    $sinCategoria[] = $ruta;
                }
                continue;
            }
            foreach ($categorias as $campo) {
                $resultado[$campo] ??= $ruta;
            }
        }

        // Gif de personaje sin nombre reconocible (ej. "sf-blanka.gif")
        $resultado['gif'] ??= $sinCategoria[0] ?? null;
        // Sin foto: la cara en gif o el gif del personaje
        $resultado['imagen'] ??= $caras[0] ?? $resultado['gif'];

        foreach (self::RESPALDO as $campo => $opciones) {
            foreach ($opciones as $opcion) {
                $resultado[$campo] ??= $resultado[$opcion];
            }
        }
        // Si solo hay un gif de otra categoría, que sirva para todo
        $unGif = collect($resultado)->except('imagen')->filter()->first();
        foreach (array_keys(self::RESPALDO) as $campo) {
            $resultado[$campo] ??= $unGif;
        }

        return $resultado;
    }

    // Reparte nivel*5 puntos según el tipo y el perfil, y los divide entre equipo/entrenamiento/accesorio
    protected function stats(string $tipo, int $nivel, int $semilla): array
    {
        $pesos = self::REPARTO[$tipo];
        $perfiles = array_values(self::PERFILES);
        foreach ($perfiles[$semilla % count($perfiles)] as $stat => $delta) {
            $pesos[$stat] += $delta;
        }

        // Método del mayor resto: el total queda exacto
        $puntos = $nivel * 5;
        $valores = [];
        $restos = [];
        foreach ($pesos as $stat => $peso) {
            $exacto = $puntos * $peso / 100;
            $valores[$stat] = (int) floor($exacto);
            $restos[$stat] = $exacto - $valores[$stat];
        }
        arsort($restos);
        foreach (array_slice(array_keys($restos), 0, $puntos - array_sum($valores)) as $stat) {
            $valores[$stat]++;
        }

        $orden = ['fuerza', 'resistencia', 'ataque', 'defensa', 'velocidad', 'energia'];
        $ajustes = ['equipo' => [], 'entrenamiento' => [], 'accesorio' => []];
        $stats = [];
        foreach ($orden as $stat) {
            $v = $valores[$stat];
            $tercio = intdiv($v, 3);
            $resto = $v % 3;
            $ajustes['equipo'][$stat]        = $tercio + ($resto > 0 ? 1 : 0);
            $ajustes['entrenamiento'][$stat] = $tercio + ($resto > 1 ? 1 : 0);
            $ajustes['accesorio'][$stat]     = $tercio;
            $stats[$stat] = 5 + $v;
        }

        return [$stats, $ajustes];
    }

    // 1 poder hasta nivel 30, 2 hasta 65, 3 desde 70
    protected function poderes(string $tipo, int $nivel, int $semilla, $poderes): array
    {
        $lista = self::PODERES[$tipo];
        if ($nivel >= self::PODERES_ALTO_NIVEL) {
            $lista = array_merge($lista, self::PODERES_FUERTES);
        }
        $lista = array_values(array_filter($lista, fn ($nombre) => isset($poderes[$nombre])));

        $random = new \Random\Randomizer(new \Random\Engine\Mt19937($semilla));
        $cantidad = $nivel < 35 ? 1 : ($nivel < 70 ? 2 : 3);

        return array_map(fn ($nombre) => $poderes[$nombre], array_slice($random->shuffleArray($lista), 0, $cantidad));
    }
}
