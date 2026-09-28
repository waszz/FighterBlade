<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('poderes', function (Blueprint $table) {
        $table->integer('drop_oro_multiplicador')->default(1)->after('imagen'); // Valor por defecto 1 (sin multiplicar)
        $table->decimal('drop_item_probabilidad', 5, 2)->default(0)->after('drop_oro_multiplicador'); // probabilidad en %, ej: 0.00
    });
}

public function down()
{
    Schema::table('poderes', function (Blueprint $table) {
        $table->dropColumn(['drop_oro_multiplicador', 'drop_item_probabilidad']);
    });
}
};
