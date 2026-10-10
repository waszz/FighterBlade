<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Personajes iniciales: Goku, Wolverine, Luffy, Naruto y Spider-Man (antes Batman y Superman en lugar de Luffy y Naruto).
// Luffy y Naruto ya son sets de nivel 5: se hace una copia aparte marcada como inicial (con los mismos archivos y poderes),
// así el set normal sigue saliendo explorando. Batman y Superman dejan de ofrecerse, pero los que ya los tienen los conservan.
return new class extends Migration
{
    private const NUEVOS = ['Luffy', 'Naruto'];
    private const SACADOS = ['Batman', 'Superman'];

    public function up(): void
    {
        DB::table('posts')->where('inicial', true)->whereIn('titulo', self::SACADOS)->update(['inicial' => false]);

        foreach (self::NUEVOS as $titulo) {
            if (DB::table('posts')->where('inicial', true)->where('titulo', $titulo)->exists()) {
                continue;
            }
            $set = DB::table('posts')->where('nivel', 5)->where('titulo', $titulo)->where('inicial', false)
                ->where(fn ($q) => $q->whereNull('es_enemigo')->orWhere('es_enemigo', 0))->orderBy('id')->first();
            if (! $set) {
                continue;
            }
            $fila = (array) $set;
            unset($fila['id']);
            $fila['inicial'] = true;
            $fila['created_at'] = now();
            $fila['updated_at'] = now();
            $nuevoId = DB::table('posts')->insertGetId($fila);

            foreach (DB::table('poder_post')->where('post_id', $set->id)->get() as $p) {
                $pivote = (array) $p;
                unset($pivote['id']);
                $pivote['post_id'] = $nuevoId;
                DB::table('poder_post')->insert($pivote);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('posts')->where('inicial', true)->whereIn('titulo', self::NUEVOS)->pluck('id');
        DB::table('poder_post')->whereIn('post_id', $ids)->delete();
        DB::table('posts')->whereIn('id', $ids)->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('personajes')->whereColumn('personajes.post_id', 'posts.id'))->delete();
        DB::table('posts')->where('inicial', false)->whereIn('titulo', self::SACADOS)->where('nivel', 5)->update(['inicial' => true]);
    }
};
