<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoderesTable extends Migration
{
    public function up(): void
    {
        Schema::create('poderes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade'); // relación con posts
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();

            $table->float('porcentaje_danio_directo')->nullable();
            $table->string('stat_base_danio')->nullable();

            $table->float('porcentaje_regeneracion')->nullable();
            $table->string('stat_base_regeneracion')->nullable();

            $table->float('porcentaje_reduccion_danio')->nullable();
            $table->string('tipo_danio_reducido')->nullable();

            $table->float('porcentaje_devolucion_danio')->nullable();

            $table->float('chance_estado')->nullable();
            $table->string('estado_aplicado')->nullable();

            $table->float('modificador_stat')->nullable();
            $table->string('stat_afectado')->nullable();
            $table->integer('duracion_efecto')->nullable();

            $table->boolean('anula_camuflaje')->default(false);
            $table->boolean('evita_ser_atacado')->default(false);
            $table->boolean('reducir_tiempo_recuperacion')->default(false);
            $table->boolean('reducir_tiempo_viaje')->default(false);
            $table->boolean('ataque_extra_por_empate')->default(false);
            $table->boolean('ataque_extra_por_derrota')->default(false);
            $table->boolean('transformacion')->default(false);
            $table->string('tipo_transformacion')->nullable();

            $table->string('estado_aplicado_en_ronda')->nullable();
            $table->float('chance_activar_estado')->nullable();

            $table->boolean('optimiza_stats')->default(false);
            $table->json('stats_a_optimizar')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poderes');
    }
}