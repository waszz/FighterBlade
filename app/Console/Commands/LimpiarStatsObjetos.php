<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Objeto;

class LimpiarStatsObjetos extends Command
{
    protected $signature = 'objetos:limpiar-stats';
    protected $description = 'Limpia los stats mal guardados en la tabla objetos';

    public function handle()
    {
        $objetos = Objeto::all();
        $limpiados = 0;

        foreach ($objetos as $objeto) {
            $stats = $objeto->getOriginal('stats');

            if (is_string($stats)) {
                $stats = trim($stats, '"');
                $stats = stripslashes($stats);

                $arrayStats = json_decode($stats, true);

                if (is_array($arrayStats)) {
                    $objeto->stats = json_encode($arrayStats, JSON_UNESCAPED_UNICODE);
                    $objeto->save();
                    $limpiados++;
                }
            }
        }

        $this->info("Se limpiaron $limpiados objetos.");
    }
}
