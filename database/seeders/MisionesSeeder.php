<?php

namespace Database\Seeders;

use App\Models\Mision;
use App\Models\Poder;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Crea la escalera de Misiones: rivales de la carpeta "set descartados" (sets ocultos, es_enemigo = 3)
// en los escenarios de "ciudades/extras". El orden de la lista es el de la escalera (de más fácil a más difícil),
// y el nivel sube de 5 a 100 a lo largo de ella.
// Uso: php artisan db:seed --class=MisionesSeeder   (carpetas configurables con RIVALES_PATH y ESCENARIOS_PATH)
// Los rivales que ya existen se saltean, así que se puede volver a correr.
class MisionesSeeder extends SetsSeeder
{
    // carpeta => [título, tipo] — en orden de la escalera
    const RIVALES = [
        'duck'             => ['Duck King', 'fisico'],
        'cody'             => ['Cody', 'fisico'],
        'guy'              => ['Guy', 'fisico'],
        'DD'               => ['Daredevil', 'fisico'],
        'sean'             => ['Sean', 'fisico'],
        'sakura'           => ['Sakura', 'hibrido'],
        'marco'            => ['Marco', 'fisico'],
        'robert'           => ['Robert Garcia', 'fisico'],
        'joe'              => ['Joe Higashi', 'hibrido'],
        'punisher'         => ['Punisher', 'fisico'],
        'ryo'              => ['Ryo Sakazaki', 'hibrido'],
        'kim'              => ['Kim Kaphwan', 'fisico'],
        'chin'             => ['Chin Gentsai', 'fisico'],
        'shingo'           => ['Shingo Yabuki', 'hibrido'],
        'andy boga rd'     => ['Andy Bogard', 'hibrido'],
        'honda'            => ['E. Honda', 'fisico'],
        'ralf'             => ['Ralf Jones', 'fisico'],
        'jill'             => ['Jill (MvC2)', 'fisico'],
        'foxy'             => ['Foxy', 'fisico'],
        'tatsuo'           => ['Tatsuo', 'fisico'],
        'feilong'          => ['Fei Long', 'fisico'],
        'gen'              => ['Gen', 'fisico'],
        'rose'             => ['Rose', 'elemental'],
        'vega'             => ['Vega', 'fisico'],
        'vega/athena'      => ['Athena', 'elemental'],
        'vega/duo long'    => ['Duo Lon', 'hibrido'],
        'sano'             => ['Sanosuke', 'fisico'],
        'chizuru'          => ['Chizuru Kagura', 'elemental'],
        'benimaru'         => ['Benimaru', 'elemental'],
        'kula'             => ['Kula Diamond', 'elemental'],
        'goro daimon'      => ['Goro Daimon', 'fisico'],
        'chonshu'          => ['Chonshu', 'hibrido'],
        'hanzou'           => ['Hattori Hanzo', 'hibrido'],
        'hanzou/wasy'      => ['Washizuka', 'fisico'],
        'haohmaru'         => ['Haohmaru', 'fisico'],
        'genjuro'          => ['Genjuro', 'fisico'],
        'kenji'            => ['Kenji', 'fisico'],
        'jin'              => ['Jin Saotome', 'fisico'],
        'fuma'             => ['Fuuma', 'hibrido'],
        'spiderman2099'    => ['Spider-Man 2099', 'fisico'],
        'strider'          => ['Strider Hiryu', 'fisico'],
        'sabertooth'       => ['Sabretooth', 'fisico'],
        'marrow'           => ['Marrow', 'fisico'],
        'rouge'            => ['Rogue', 'hibrido'],
        'havox'            => ['Havok', 'elemental'],
        'bishop'           => ['Bishop', 'elemental'],
        'electriman'       => ['Electricman', 'elemental'],
        'crystal'          => ['Crystal', 'elemental'],
        'megaman'          => ['Mega Man', 'elemental'],
        'warmachione'      => ['War Machine', 'hibrido'],
        'talbian'          => ['Jon Talbain', 'fisico'],
        'victor'           => ['Victor', 'elemental'],
        'donovan'          => ['Donovan', 'elemental'],
        'benimaru/demetri' => ['Demitri', 'hibrido'],
        'zabel'            => ['Zabel', 'hibrido'],
        'asra'             => ['Asra', 'hibrido'],
        'hauzer'           => ['Hauzer', 'fisico'],
        'ear'              => ['Earthquake', 'fisico'],
        'tizok'            => ['Tizoc', 'fisico'],
        'maxima'           => ['Maxima', 'fisico'],
        'saisyu'           => ['Saisyu Kusanagi', 'elemental'],
        'takuma'           => ['Takuma Sakazaki', 'hibrido'],
        'basara'           => ['Basara', 'elemental'],
        'kagami'           => ['Kagami', 'elemental'],
        'super skrull'     => ['Super Skrull', 'hibrido'],
        'super skrull/mature' => ['Mature', 'elemental'],
        'goutetsu'         => ['Goutetsu', 'fisico'],
        'chonshu/sagat'    => ['Sagat', 'hibrido'],
        'legendary chunly' => ['Chun-Li Legendaria', 'fisico'],
        'dante'            => ['Dante', 'hibrido'],
        'weaponx'          => ['Weapon X', 'fisico'],
        'jean grey'        => ['Jean Grey', 'elemental'],
        'luffy'            => ['Luffy', 'fisico'],
        'dark sakura'      => ['Dark Sakura', 'hibrido'],
        'violent dan'      => ['Violent Dan', 'fisico'],
        'evil blanka'      => ['Evil Blanka', 'elemental'],
        'perfec ken'       => ['Perfect Ken', 'hibrido'],
        'violent ken'      => ['Violent Ken', 'hibrido'],
        'mukai'            => ['Mukai', 'elemental'],
        'krizald'          => ['Krizalid', 'elemental'],
        'orochi kyo'       => ['Orochi Kyo', 'elemental'],
        'orochi iori'      => ['Orochi Iori', 'elemental'],
        'evil ryu'         => ['Evil Ryu', 'hibrido'],
        'goku'             => ['Goku', 'hibrido'],
        'igniz'            => ['Igniz', 'elemental'],
        'shin akuma'       => ['Shin Akuma', 'hibrido'],
    ];

    // Premio: oro = nivel del rival × ORO_POR_NIVEL; diamantes suben parejo de DIAMANTES_PRIMERA a DIAMANTES_ULTIMA
    const ORO_POR_NIVEL = 100;
    const DIAMANTES_PRIMERA = 30;
    const DIAMANTES_ULTIMA = 300;

    // Diamantes de la misión i (0 = primera) repartidos en línea recta hasta la última
    public static function diamantes(int $i, int $total): int
    {
        return (int) round(self::DIAMANTES_PRIMERA + ($total > 1 ? $i * (self::DIAMANTES_ULTIMA - self::DIAMANTES_PRIMERA) / ($total - 1) : 0));
    }

    public function run(): void
    {
        $base = env('RIVALES_PATH', 'C:/Users/TERA/Desktop/fb/set descartados');
        $baseEscenarios = env('ESCENARIOS_PATH', 'C:/Users/TERA/Desktop/fb/ciudades/extras');
        if (! File::isDirectory($base) || ! File::isDirectory($baseEscenarios)) {
            $this->command->error('No existen las carpetas de rivales o escenarios.');
            return;
        }

        $destino = storage_path('app/public/posts');
        File::ensureDirectoryExists($destino);

        // Escenarios: se copian una vez y se reparten mezclados (se repiten si hay más misiones que escenarios)
        $escenarios = [];
        foreach (collect(File::files($baseEscenarios))->filter(fn ($f) => strtolower($f->getExtension()) === 'gif')->values() as $i => $archivo) {
            $nombre = 'escenario-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . '.gif';
            File::copy($archivo->getPathname(), "$destino/$nombre");
            $escenarios[] = $nombre;
        }
        $escenarios = (new \Random\Randomizer(new \Random\Engine\Mt19937(2026)))->shuffleArray($escenarios);

        $poderes = Poder::pluck('id', 'nombre')->mapWithKeys(fn ($id, $nombre) => [mb_strtoupper($nombre) => $id]);
        $userId = DB::table('users')->orderBy('id')->value('id');
        $total = count(self::RIVALES);

        $creados = 0;
        foreach (array_keys(self::RIVALES) as $i => $carpeta) {
            [$titulo, $tipo] = self::RIVALES[$carpeta];
            $orden = $i + 1;
            if (Mision::where('orden', $orden)->exists()) {
                continue;
            }

            $dir = "$base/$carpeta";
            if (! File::isDirectory($dir)) {
                $this->command->warn("No existe la carpeta, se saltea: $carpeta");
                continue;
            }
            $archivos = $this->archivos($dir);
            if (! $archivos['imagen'] && ! $archivos['gif']) {
                $this->command->warn("Sin imágenes, se saltea: $carpeta");
                continue;
            }

            // Nivel: sube parejo de 5 (primera misión) a 100 (última)
            $nivel = (int) round(5 + ($total > 1 ? $i * 95 / ($total - 1) : 0));

            $prefijo = 'rival-' . Str::slug($carpeta);
            $copiar = function (?string $origen, string $sufijo) use ($destino, $prefijo) {
                if (! $origen) {
                    return null;
                }
                $ext = strtolower(pathinfo($origen, PATHINFO_EXTENSION));
                $ext = $ext === 'jfif' ? 'jpg' : $ext;
                File::copy($origen, "$destino/{$prefijo}_{$sufijo}.{$ext}");
                return "{$prefijo}_{$sufijo}.{$ext}";
            };

            $imagen = $copiar($archivos['imagen'] ?? $archivos['gif'], 'img');
            $gifs = [];
            foreach (array_keys(self::RESPALDO) as $campo) {
                $gifs[$campo] = 'posts/' . ($archivos[$campo] ? $copiar($archivos[$campo], $campo) : $imagen);
            }

            $semilla = crc32('rival-' . $carpeta);
            [$stats] = $this->stats($tipo, $nivel, $semilla);

            $post = Post::create([
                'titulo'  => $titulo,
                'nivel'   => $nivel,
                'tipo'    => $tipo,
                'user_id' => $userId,
                'imagen'  => 'posts/' . $imagen,
                'stats'   => $stats,
            ] + $gifs);
            // Rival oculto: no se publica ni tiene partes
            $post->forceFill(['publicado' => false, 'es_enemigo' => Post::RIVAL_MISION, 'costo' => 0])->saveQuietly();
            $post->poderes()->attach($this->poderes($tipo, $nivel, $semilla, $poderes));

            Mision::create([
                'orden'                => $orden,
                'post_id'              => $post->id,
                'escenario'            => $escenarios[$i % count($escenarios)],
                'recompensa_oro'       => $nivel * self::ORO_POR_NIVEL,
                'recompensa_diamantes' => self::diamantes($i, $total),
            ]);
            $creados++;
        }

        $this->command->info("Misiones creadas: $creados de $total");
    }
}
