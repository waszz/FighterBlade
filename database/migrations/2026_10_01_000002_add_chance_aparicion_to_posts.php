<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Chance (%) de que aparezca un personaje especial. Por ahora la usa el Comerciante Khonshu (al terminar una
// exploración); se cambia desde Personajes especiales → Editar. Arranca en 10 (lo que estaba fijo en el código)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedTinyInteger('chance_aparicion')->nullable();
        });

        Post::conRivales()->where('es_enemigo', Post::COMERCIANTE)->update(['chance_aparicion' => 10]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('chance_aparicion');
        });
    }
};
