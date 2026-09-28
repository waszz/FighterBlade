<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\Personaje;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Bots: personajes de otros "usuarios" para pelear (PvP) y llenar el ranking.
// Cada uno usa un set inicial distinto y arranca en la ciudad inicial.
// Uso: php artisan db:seed --class=BotsSeeder   (se saltean los que ya existen)
class BotsSeeder extends Seeder
{
    const DOMINIO = 'bot.fighterblade';
    // Nivel 3: ya pasaron la protección de novatos (nivel 1 y 2), así se los puede atacar en PvP
    const NIVEL = 3;

    const NOMBRES = ['ShadowFist', 'Kaizer', 'LunaByte', 'RyuMaster99', 'Nekomata',
                     'ZeroCool', 'Valkyria', 'TurboKid', 'DarkPhoenix', 'ElCondor'];

    // Reparto de los puntos de nivel según el tipo del set
    const REPARTO = [
        'fisico'    => ['fuerza' => 0.4, 'ataque' => 0.4, 'velocidad' => 0.2],
        'elemental' => ['energia' => 0.4, 'ataque' => 0.4, 'velocidad' => 0.2],
        'hibrido'   => ['ataque' => 0.4, 'fuerza' => 0.3, 'energia' => 0.3],
    ];

    public function run(): void
    {
        $sets = Post::personajesBase()->get();
        $ciudad = Ciudad::orderBy('nivel')->first();
        $random = new \Random\Randomizer(new \Random\Engine\Mt19937(3));

        $creados = 0;
        foreach (self::NOMBRES as $i => $nombre) {
            $email = Str::slug($nombre) . '@' . self::DOMINIO;
            if (User::where('email', $email)->exists() || Personaje::where('nombre', $nombre)->exists()) {
                continue;
            }
            $set = $sets[$i % $sets->count()];

            $user = User::create([
                'name'     => $nombre,
                'email'    => $email,
                'password' => Hash::make(Str::random(32)), // nadie entra con estas cuentas
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $stats = self::statsParaNivel($set->tipo, self::NIVEL);

            $personaje = Personaje::create([
                'user_id'     => $user->id,
                'post_id'     => $set->id,
                'nombre'      => $nombre,
                'tipo'        => $set->tipo,
                'nivel'       => self::NIVEL,
                'imagen'      => $set->imagen,
                'gif'         => $set->gif,
                'ciudad_id'   => $ciudad?->id,
                'stats'       => $stats,
                'stats_base'  => $stats,
                'experiencia' => self::experienciaAlAzar(self::NIVEL, $random),
                'oro'         => $random->getInt(200, 900),
                'diamante'    => 0,
            ]);
            $personaje->forceFill(['puntos_stats' => 0])->save();

            $this->command->line("  {$nombre}: {$set->titulo} ({$set->tipo}) " . json_encode($stats));
            $creados++;
        }

        $this->command->info("Bots creados: $creados");
    }

    // Stats base 5 + los puntos de los niveles ganados (5 por nivel), repartidos según el tipo
    public static function statsParaNivel(?string $tipo, int $nivel): array
    {
        $puntos = ($nivel - 1) * 5;
        $stats = ['fuerza' => 5, 'ataque' => 5, 'velocidad' => 5, 'resistencia' => 5, 'defensa' => 5, 'energia' => 5];
        $reparto = self::REPARTO[$tipo] ?? self::REPARTO['fisico'];
        $usados = 0;
        foreach ($reparto as $stat => $fraccion) {
            $stats[$stat] += (int) floor($puntos * $fraccion);
            $usados += (int) floor($puntos * $fraccion);
        }
        $stats[array_key_first($reparto)] += $puntos - $usados;

        return $stats;
    }

    // Exp al azar dentro del nivel (sin llegar al siguiente: el nivel N va de 10000·(N-1)² a 10000·N² - 1)
    public static function experienciaAlAzar(int $nivel, \Random\Randomizer $random): int
    {
        return $random->getInt(10000 * ($nivel - 1) ** 2, 10000 * $nivel ** 2 - 1);
    }
}
