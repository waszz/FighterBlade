<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mide los gifs de todos los sets (alto del personaje y espacio vacío) para mostrarlos a la misma escala
Artisan::command('sets:medir-gifs', function () {
    $total = 0;
    foreach (\App\Models\Post::conRivales()->get() as $post) {
        $post->gif_medidas = $post->medirGifs();
        $post->saveQuietly();
        $total++;
    }
    $this->info("Sets medidos: $total");
})->purpose('Mide los gifs de los sets para mostrarlos a la misma escala');

// Calcula los requisitos de stats de cada set (1 stat por parte) y los aplica también a las partes ya dropeadas
Artisan::command('sets:requisitos', function () {
    $total = 0;
    foreach (\App\Models\Post::with('poderes')->get() as $post) {
        $requisitos = $post->calcularRequisitos();
        foreach ($requisitos as $parte => $req) {
            $post->{'requisitos_' . $parte} = $req;
            \App\Models\Objeto::where('origen_post_id', $post->id)->where('tipo', $parte)
                ->update(['requisitos_' . $parte => json_encode($req)]);
        }
        $post->saveQuietly();
        $total++;
    }
    $this->info("Sets con requisitos: $total");
})->purpose('Calcula los requisitos de stats de los sets según sus stats y poderes');

// Reparte los bonus de cada set entre sus partes con el máximo de stats por parte según el nivel
// (5-45: 3, 50-75: 4, 80-100: 5). Solo toca los sets que se pasan; aplica también a las partes ya dropeadas
Artisan::command('sets:repartir-partes', function () {
    $total = 0;
    foreach (\App\Models\Post::all() as $post) {
        if (! $post->partesExcedenMaxStats()) {
            continue;
        }
        foreach ($post->repartirAjustes() as $parte => $ajustes) {
            $post->{'ajustes_manuales_' . $parte} = $ajustes;
            \App\Models\Objeto::where('origen_post_id', $post->id)->where('tipo', $parte)
                ->update(['stats' => json_encode($ajustes)]);
        }
        $post->saveQuietly();
        $total++;
    }
    $this->info("Sets repartidos: $total");
})->purpose('Limita los stats que da cada parte de los sets según su nivel');

// Torre: arma los pisos (nivel 5 a 100, uno por nivel). Rival: un set normal o especial de nivel cercano al del piso;
// zona: una ciudad o un escenario especial. El sorteo es fijo (misma torre para todos) salvo que se pase --semilla.
Artisan::command('torre:generar {--semilla=torre}', function () {
    $semilla = (string) $this->option('semilla');
    $azar = new \Random\Randomizer(new \Random\Engine\Mt19937(crc32($semilla)));

    $candidatos = \App\Models\Post::conRivales()
        ->where(fn ($q) => $q->whereNull('es_enemigo')->orWhereNotIn('es_enemigo', [\App\Models\Post::ENEMIGO_ESPECIAL, \App\Models\Post::VARIANTE_ZONA]))
        ->where('nivel', '>=', 3)
        ->get(['id', 'nivel', 'titulo']);
    $zonas = \App\Models\Ciudad::whereNotNull('gif')->pluck('gif')
        ->merge(\App\Models\Mision::distinct()->pluck('escenario'))
        ->filter()->unique()->values()->all();

    \Illuminate\Support\Facades\DB::table('torre_pisos')->delete();
    $usados = [];
    $piso = 0;
    $nivelAnterior = 0; // nivel del rival del piso anterior: los niveles nunca bajan al subir
    for ($nivel = \App\Models\TorrePiso::NIVEL_MINIMO; $nivel <= 100; $nivel++) {
        $piso++;
        // Rival: nivel entre el del piso anterior y el del piso (lo más cerca posible del piso), sin repetir.
        // Si no hay, el de nivel más bajo que no sea menor al anterior.
        $elegir = function (bool $permitirRepetidos) use ($candidatos, $usados, $nivel, $nivelAnterior) {
            $libres = $candidatos->filter(fn ($p) => $p->nivel >= $nivelAnterior && ($permitirRepetidos || ! isset($usados[$p->id])));
            $pool = $libres->filter(fn ($p) => $p->nivel <= $nivel && $p->nivel >= max($nivelAnterior, $nivel - 2));
            if ($pool->isEmpty()) {
                $pool = $libres->filter(fn ($p) => $p->nivel <= $nivel);
                $pool = $pool->isEmpty() ? $pool : $pool->where('nivel', $pool->max('nivel'));
            }
            if ($pool->isEmpty() && $libres->isNotEmpty()) {
                $pool = $libres->where('nivel', $libres->min('nivel'));
            }
            return $pool->values();
        };
        $pool = $elegir(false);
        if ($pool->isEmpty()) {
            $pool = $elegir(true);
        }
        $rival = $pool[$azar->getInt(0, $pool->count() - 1)];
        $usados[$rival->id] = true;
        $nivelAnterior = $rival->nivel;

        \App\Models\TorrePiso::create([
            'piso' => $piso,
            'post_id' => $rival->id,
            'escenario' => $zonas[$azar->getInt(0, count($zonas) - 1)],
            'nivel' => $nivel,
        ]);
    }
    $this->info("Torre armada: $piso pisos (nivel " . \App\Models\TorrePiso::NIVEL_MINIMO . " a 100)");
})->purpose('Arma los pisos de la Torre con rivales normales y especiales y zonas al azar');

// Zona inicial (El Comienzo): 3 variantes de cada set de nivel 5 — Black (tira el Equipo), normal (Entrenamiento)
// y Gold (Accesorio). Black y Gold usan copias de los gifs con un tinte CSS (filtro_gif). Se puede volver a correr.
Artisan::command('zona:variantes', function () {
    $variantes = [
        ['prefijo' => 'Black', 'parte' => 'equipo',        'filtro' => 'brightness(0.45) contrast(1.4) saturate(0.4)'],
        ['prefijo' => null,    'parte' => 'entrenamiento', 'filtro' => null],
        ['prefijo' => 'Gold',  'parte' => 'accesorio',     'filtro' => 'sepia(1) saturate(4) hue-rotate(-15deg) brightness(1.1)'],
    ];

    // Borra las variantes anteriores (y sus copias de gifs)
    foreach (\App\Models\Post::conRivales()->where('es_enemigo', \App\Models\Post::VARIANTE_ZONA)->get() as $vieja) {
        foreach (\App\Models\Post::CAMPOS_GIF as $campo) {
            if ($vieja->$campo && str_starts_with($vieja->$campo, 'posts/var-')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($vieja->$campo);
            }
        }
        $vieja->poderes()->detach();
        $vieja->forceDelete();
    }

    $sets = \App\Models\Post::where('nivel', 5)->with('poderes')->orderBy('titulo')->get();
    $creadas = 0;
    foreach ($sets as $set) {
        foreach ($variantes as $v) {
            $post = $set->replicate(['variante_de_post_id', 'variante_parte', 'filtro_gif']);
            $post->titulo = $v['prefijo'] ? $v['prefijo'] . ' ' . $set->titulo : $set->titulo;
            // Black y Gold: copia de cada gif (así el tinte no afecta al set original, que comparte la ruta)
            if ($v['filtro']) {
                foreach (\App\Models\Post::CAMPOS_GIF as $campo) {
                    if (! $set->$campo || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($set->$campo)) {
                        continue;
                    }
                    $destino = 'posts/var-' . \Illuminate\Support\Str::slug($set->titulo) . '-' . strtolower($v['prefijo']) . '-' . $campo . '.' . pathinfo($set->$campo, PATHINFO_EXTENSION);
                    \Illuminate\Support\Facades\Storage::disk('public')->copy($set->$campo, $destino);
                    $post->$campo = $destino;
                }
                // Las medidas y ajustes van por nombre de animación, no por ruta: sirven igual
            }
            $post->forceFill([
                'es_enemigo' => \App\Models\Post::VARIANTE_ZONA,
                'publicado' => false,
                'inicial' => false,
                'variante_de_post_id' => $set->id,
                'variante_parte' => $v['parte'],
                'filtro_gif' => $v['filtro'],
            ]);
            $post->saveQuietly(); // sin volver a medir: se copian las medidas del set
            $post->poderes()->sync($set->poderes->pluck('id'));
            $creadas++;
        }
    }
    $this->info("Variantes creadas: $creadas (" . $sets->count() . " sets × 3)");
})->purpose('Crea las variantes Black / normal / Gold de los sets de nivel 5 para la zona inicial');
