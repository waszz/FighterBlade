<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// El Comerciante Khonshu: personaje especial (es_enemigo = 5) que no pelea. Aparece a veces al terminar una
// exploración y vende partes (ver App\Support\Comerciante). Su gif viene en public/images/comerciante y se copia
// a storage (como los gifs de los sets) para que el admin lo pueda cambiar desde Personajes especiales.
// En personajes queda la oferta que le está mostrando (null = no hay comerciante).
return new class extends Migration
{
    const TITULO = 'El Comerciante Khonshu';
    const ARCHIVO = 'especial-comerciante-khonshu.gif';

    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->json('comerciante_oferta')->nullable();
        });

        if (Post::conRivales()->where('es_enemigo', Post::COMERCIANTE)->exists()) {
            return;
        }

        $destino = storage_path('app/public/posts');
        File::ensureDirectoryExists($destino);
        File::copy(public_path('images/comerciante/khonshu.gif'), $destino . '/' . self::ARCHIVO);

        $gif = 'posts/' . self::ARCHIVO;
        $post = Post::create([
            'titulo'  => self::TITULO,
            'nivel'   => 1,
            'tipo'    => 'fisico',
            'user_id' => DB::table('users')->orderBy('id')->value('id'),
            'imagen'  => $gif,
            'stats'   => ['fuerza' => 0, 'resistencia' => 0, 'ataque' => 0, 'defensa' => 0, 'velocidad' => 0, 'energia' => 0],
        ] + array_fill_keys(Post::CAMPOS_GIF, $gif));
        $post->forceFill(['publicado' => false, 'es_enemigo' => Post::COMERCIANTE, 'costo' => 0])->saveQuietly();
    }

    public function down(): void
    {
        Post::conRivales()->where('es_enemigo', Post::COMERCIANTE)->delete();
        File::delete(storage_path('app/public/posts/' . self::ARCHIVO));

        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn('comerciante_oferta');
        });
    }
};
