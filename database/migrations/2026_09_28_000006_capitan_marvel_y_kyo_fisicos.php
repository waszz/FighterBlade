<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Capitán Marvel (Nv 55) y Kyo Kusanagi (Nv 55) pasan de daño elemental a físico:
// el set, los personajes que lo usan de base y las partes que ya tienen los jugadores
return new class extends Migration
{
    private const SETS = ['Capitán Marvel', 'Kyo Kusanagi'];

    private function cambiar(string $tipo): void
    {
        $ids = DB::table('posts')->whereIn('titulo', self::SETS)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        DB::table('posts')->whereIn('id', $ids)->update(['tipo' => $tipo]);
        DB::table('personajes')->whereIn('post_id', $ids)->update(['tipo' => $tipo]);
        // Las partes toman el tipo del set; solo si la columna "estilo" existe (no está en todas las bases)
        if (\Illuminate\Support\Facades\Schema::hasColumn('objetos', 'estilo')) {
            DB::table('objetos')->whereIn('origen_post_id', $ids)->whereNotNull('estilo')->update(['estilo' => $tipo]);
        }
    }

    public function up(): void
    {
        $this->cambiar('fisico');
    }

    public function down(): void
    {
        $this->cambiar('elemental');
    }
};
