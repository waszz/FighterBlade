<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Recarga diaria de esmeraldas (ver Personaje::recargarEsmeraldasDiarias) y Poción de Oro: ahora duplica el oro de la victoria
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            // Última vez que se le completaron las esmeraldas hasta 100
            $table->timestamp('recarga_esmeraldas_en')->nullable();
        });

        // Las Pociones de Oro que ya están en los inventarios: el texto nuevo (el efecto lo da Explorar)
        DB::table('objetos')->where('nombre', 'Poción de Oro')
            ->update(['descripcion' => 'Duplica el oro de la próxima victoria']);
    }

    public function down(): void
    {
        Schema::table('personajes', function (Blueprint $table) {
            $table->dropColumn('recarga_esmeraldas_en');
        });

        DB::table('objetos')->where('nombre', 'Poción de Oro')
            ->update(['descripcion' => 'Otorga 100 de oro']);
    }
};
