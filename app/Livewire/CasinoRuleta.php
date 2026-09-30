<?php

namespace App\Livewire;

use App\Models\Objeto;
use App\Models\Personaje;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;
use Livewire\Component;

// Ruleta del casino: cuesta solo oro, no usa vidas y se juega sin límite.
// Premios: una poción normal al azar, la Poción de Oro, 3 Pociones de Esmeraldas o x20 del oro apostado (o nada)
class CasinoRuleta extends Component
{
    public $personajeId;

    const COSTO = 1000;              // oro por giro
    const MULTIPLICADOR_ORO = 20;    // premio de oro: COSTO × esto
    const POCIONES_ESMERALDA = 3;

    // Premios: peso = chance en % (suman 100). casillas = cuántas porciones de la ruleta ocupa (solo se ve)
    const PREMIOS = [
        'nada'       => ['nombre' => 'Nada',                      'peso' => 60, 'casillas' => 5, 'imagen' => null,                        'color' => '#1f2937'],
        'pocion'     => ['nombre' => 'Poción normal',             'peso' => 24, 'casillas' => 3, 'imagen' => 'images/pocion-ataque.png',   'color' => '#7c3aed'],
        'pocion_oro' => ['nombre' => 'Poción de Oro',             'peso' => 12, 'casillas' => 2, 'imagen' => 'images/pocion-oro.png',      'color' => '#b45309'],
        'esmeraldas' => ['nombre' => '3 Pociones de Esmeraldas',  'peso' => 2,  'casillas' => 1, 'imagen' => 'images/pocion-diamantes.png', 'color' => '#047857'],
        'oro_x20'    => ['nombre' => 'x20 de oro',                'peso' => 2,  'casillas' => 1, 'imagen' => 'images/oro.png',             'color' => '#ca8a04'],
    ];

    // Pociones normales (×1.5 de un stat y la de recuperación)
    const POCIONES_NORMALES = [
        ['Poción de Defensa', 'defensa', 1.5, 'pocion-defensa.png', 'Multiplica la defensa por 1.5'],
        ['Poción de Ataque', 'ataque', 1.5, 'pocion-ataque.png', 'Multiplica el ataque por 1.5'],
        ['Poción de Energía', 'energia', 1.5, 'pocion-energia.png', 'Multiplica la Energia por 1.5'],
        ['Poción de Velocidad', 'velocidad', 1.5, 'pocion-velocidad.png', 'Multiplica la velocidad por 1.5'],
        ['Poción de Fuerza', 'fuerza', 1.5, 'pocion-fuerza.png', 'Multiplica la fuerza por 1.5'],
        ['Poción de Resistencia', 'resistencia', 1.5, 'pocion-resistencia.png', 'Multiplica la Resistencia por 1.5'],
        ['Poción de Recuperacion', 'recuperacion', 15, 'pocion-recuperacion.png', 'Recuperación de 15 segundos'],
    ];

    public function mount($personaje)
    {
        $this->personajeId = $personaje->id;
    }

    // Orden de las porciones de la ruleta (intercaladas para que los premios queden repartidos)
    public static function casillas(): array
    {
        return ['nada', 'pocion', 'nada', 'pocion_oro', 'nada', 'oro_x20', 'pocion', 'nada', 'pocion_oro', 'nada', 'esmeraldas', 'pocion'];
    }

    #[Renderless]
    public function girar()
    {
        // Lugar para el premio más grande (3 pociones de esmeraldas)
        if (! Personaje::find($this->personajeId)?->tieneLugar(self::POCIONES_ESMERALDA)) {
            $this->dispatch('error', ['message' => '🎒 Necesitás ' . self::POCIONES_ESMERALDA . ' lugares libres en el inventario para girar la ruleta.']);
            return null;
        }

        $resultado = DB::transaction(function () {
            $personaje = Personaje::where('id', $this->personajeId)->where('user_id', Auth::id())->lockForUpdate()->first();
            if (! $personaje) {
                return ['error' => 'Personaje no encontrado.'];
            }
            if ($personaje->oro < self::COSTO) {
                return ['error' => 'No tenés suficiente oro.'];
            }

            $personaje->oro -= self::COSTO;
            $premio = $this->sortear();
            $texto = 'Nada esta vez';
            $imagen = null;

            switch ($premio) {
                case 'pocion':
                    [$nombre, $afecta, $multiplicador, $img, $descripcion] = self::POCIONES_NORMALES[array_rand(self::POCIONES_NORMALES)];
                    $this->darPocion($personaje, $nombre, $afecta, $multiplicador, $img, $descripcion);
                    $texto = '¡Ganaste una ' . $nombre . '!';
                    $imagen = 'images/' . $img;
                    break;
                case 'pocion_oro':
                    $this->darPocion($personaje, 'Poción de Oro', 'oro', 2, 'pocion-oro.png', 'Duplica el oro de la próxima victoria');
                    $texto = '¡Ganaste una Poción de Oro!';
                    $imagen = 'images/pocion-oro.png';
                    break;
                case 'esmeraldas':
                    for ($i = 0; $i < self::POCIONES_ESMERALDA; $i++) {
                        $this->darPocion($personaje, 'Poción de Esmeraldas', 'diamante', 1, 'pocion-diamantes.png', 'Otorga 100 esmeraldas');
                    }
                    $texto = '¡Ganaste ' . self::POCIONES_ESMERALDA . ' Pociones de Esmeraldas!';
                    $imagen = 'images/pocion-diamantes.png';
                    break;
                case 'oro_x20':
                    $ganado = self::COSTO * self::MULTIPLICADOR_ORO;
                    $personaje->oro += $ganado;
                    $texto = '¡x' . self::MULTIPLICADOR_ORO . '! Ganaste ' . number_format($ganado, 0, ',', '.') . ' de oro';
                    $imagen = 'images/oro.png';
                    break;
            }
            $personaje->save();

            // Una de las porciones de ese premio, para que la ruleta frene ahí
            $posiciones = array_keys(array_filter(self::casillas(), fn ($c) => $c === $premio));

            return [
                'premio'  => $premio,
                'casilla' => $posiciones[array_rand($posiciones)],
                'texto'   => $texto,
                'imagen'  => $imagen ? asset($imagen) : null,
                'oro'     => $personaje->oro,
            ];
        });

        if (isset($resultado['error'])) {
            $this->dispatch('error', ['message' => $resultado['error']]);
            return null;
        }

        return $resultado;
    }

    private function sortear(): string
    {
        $tiro = random_int(1, array_sum(array_column(self::PREMIOS, 'peso')));
        foreach (self::PREMIOS as $clave => $premio) {
            $tiro -= $premio['peso'];
            if ($tiro <= 0) {
                return $clave;
            }
        }
        return 'nada';
    }

    private function darPocion(Personaje $personaje, string $nombre, string $afecta, float $multiplicador, string $imagen, string $descripcion): void
    {
        Objeto::create([
            'personaje_id' => $personaje->id,
            'tipo'         => 'pocion',
            'nombre'       => $nombre,
            'nivel'        => 1,
            // Los usos los completa Objeto al crearla (5/5, salvo Oro y Esmeraldas)
            'stats'        => ['usos_restantes' => 1, 'usos_totales' => 1, 'multiplicador' => $multiplicador, 'afecta' => $afecta],
            'imagen'       => $imagen,
            'pocion'       => true,
            'descripcion'  => $descripcion,
            'requisitos_equipo' => [], 'requisitos_entrenamiento' => [], 'requisitos_accesorio' => [],
        ]);
    }

    public function render()
    {
        return view('livewire.casino-ruleta', [
            'oro'      => Personaje::find($this->personajeId)?->oro ?? 0,
            'premios'  => self::PREMIOS,
            'casillas' => self::casillas(),
            'costo'    => self::COSTO,
            'multiplicadorOro' => self::MULTIPLICADOR_ORO,
            'pocionesEsmeralda' => self::POCIONES_ESMERALDA,
        ]);
    }
}
