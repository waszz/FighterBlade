<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('objetos', function (Blueprint $table) {
            $table->unsignedBigInteger('origen_post_id')->nullable()->after('personaje_id');
        });
    }

    public function down()
    {
        Schema::table('objetos', function (Blueprint $table) {
            $table->dropColumn('origen_post_id');
        });
    }
};

