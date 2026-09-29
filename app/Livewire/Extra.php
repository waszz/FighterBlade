<?php

namespace App\Livewire;

use App\Models\Buff;
use App\Models\Objeto;
use Livewire\Component;
use App\Models\Personaje;
use App\Models\MercadoPocion;
use App\Models\ExploracionRapida;
use Illuminate\Support\Facades\Artisan;

class Extra extends Component
{
    public $personaje;
    public $fraseBuff;
    public $costos = [];
    public $tipoBuff;
    public $porcentajeBuff;
    public $costoBuff = 0;
    public $superPociones = [];
    const ORIGEN_MERCADO_POCIONES = 0;
    public $reloadExtra = 0;
    public int $buffExperiencia = 0;
    public ?int $buffExpFinTimestamp = null;
    public $buffsExperienciaActivos = [];
    public $buffsOroActivos = [];
    public $buffsDropActivos = [];
    

    public function mount()
{
    
    $this->costos = [
        250 => 50,
        500=> 100,
        1500=> 150,
    ];

      $this->cargarSuperPociones();
      $this->cargarBuffs();

      // Buff seleccionado por defecto: Experiencia +5%
      $this->tipoBuff = 'xp';
      $this->porcentajeBuff = 5;
      $this->updatedPorcentajeBuff($this->porcentajeBuff);
         
}


public function comprarOro($cantidad)
{
    $costo = $this->costos[$cantidad] ?? null;

    if (!$costo || $this->personaje->diamante < $costo) {
        $this->dispatch('error', ['message' => 'No tenés suficientes esmeraldas.']);
        return;
    }

    $this->personaje->diamante -= $costo;
    $this->personaje->oro += $cantidad;
    $this->personaje->save();

     session()->flash('mensaje', '¡Compra realizada!');
     
}


public function cargarSuperPociones()
{
    $this->superPociones = MercadoPocion::whereIn('nombre', [
        'Poción de Super Fuerza',
        'Poción de Super Defensa',
        'Poción de Super Ataque',
        'Poción de Super Energia',
        'Poción de Super Resistencia',
        'Poción de Super Velocidad',
        
    ])->get();
}

public function cargarBuffs()
{
    // Borrar buffs vencidos
    Buff::where('fin', '<', now())->delete();

    $tipos = ['xp', 'oro', 'drop'];

    foreach ($tipos as $tipo) {
        $buffsActivos = Buff::with('personaje') // 👈 importante
            ->where('tipo', $tipo)
            ->where('inicio', '<=', now())
            ->where('fin', '>=', now())
            ->where(function ($q) {
                $q->where('personaje_id', $this->personaje->id)
                  ->orWhereNull('personaje_id'); // Buff global
            })
            ->orderByDesc('fin')
            ->get()
            ->map(function ($buff) {
                return [
                    'id' => $buff->id,
                    'porcentaje' => $buff->porcentaje,
                    'finTimestamp' => $buff->fin->timestamp,
                    'global' => $buff->personaje_id === null,
                    'frase' => $buff->frase,
                    'personaje' => $buff->personaje ? [  // 👈 agregado
                        'id' => $buff->personaje->id,
                        'nombre' => $buff->personaje->nombre,
                    ] : null,
                ];
            })
            ->toArray();

        // Guardar en la propiedad correspondiente
        match ($tipo) {
            'xp'   => $this->buffsExperienciaActivos = $buffsActivos,
            'oro'  => $this->buffsOroActivos = $buffsActivos,
            'drop' => $this->buffsDropActivos = $buffsActivos,
        };

        // Lanzar eventos para cada buff activo
        foreach ($buffsActivos as $buff) {
            $this->dispatch("iniciar-timer-buff-{$tipo}-{$buff['id']}", [
                'finTimestamp' => $buff['finTimestamp'],
            ]);
        }
    }
}


public function comprarSuperPocion($pocionId)
{
    $pocion = MercadoPocion::find($pocionId);

    if (!$pocion) {
        $this->dispatch('error', ['message' => '❌ Poción no encontrada.']);
        return;
    }

    if ($this->personaje->diamante < $pocion->precio) {
        $this->dispatch('error', ['message' => '💚 No tenés suficientes esmeraldas.']);
        return;
    }

    if (! $this->personaje->tieneLugar()) {
        $this->dispatch('error', ['message' => '🎒 ' . \App\Models\Personaje::MENSAJE_INVENTARIO_LLENO]);
        return;
    }

    // Descontar diamantes
    $this->personaje->diamante -= $pocion->precio;
    $this->personaje->save();

    // Crear objeto en el inventario del personaje
    Objeto::create([
        'personaje_id' => $this->personaje->id,
        'tipo' => $pocion->tipo,
        'nombre' => $pocion->nombre,
        'imagen' => $pocion->imagen,
        'nivel' => $pocion->nivel,
        'requisitos' => $pocion->requisitos,
        'stats' => $pocion->stats,
        'origen_post_id' => self::ORIGEN_MERCADO_POCIONES,
        'pocion' => $pocion->tipo === 'pocion',
        'descripcion' => $pocion->descripcion,
    ]);


     
    $this->cargarSuperPociones();
    $this->reloadExtra++;
   session()->flash('mensaje', '¡✅ Compra realizada!');
   session()->flash('error', false);
   
}



public function comprarExploracionRapida($dias)
{
    $costos = [
        3 => 300, // <- Cambialo después si querés
        5 => 600,
        7 => 900,
    ];

    if (!isset($costos[$dias])) return;

    $costo = $costos[$dias];

    if ($this->personaje->diamante < $costo) {
        session()->flash('mensaje', 'No tenés suficientes esmeraldas..');
        session()->flash('error', true);
        return;
    }

    // Verificar si ya tiene activa
    $actual = ExploracionRapida::activaPara($this->personaje->id);
    if ($actual) {
        session()->flash('mensaje', 'Ya tenés una exploración rápida activa.');
        session()->flash('error', true);
        return;
    }

    ExploracionRapida::create([
        'personaje_id' => $this->personaje->id,
        'inicio' => now(),
        'fin' => now()->addDays($dias),
    ]);

    $this->personaje->diamante -= $costo;
    $this->personaje->save();

    session()->flash('mensaje', "¡Exploración rápida activada por $dias días!");
}
public function comprarBuff()
{
    if (!isset(self::COSTO_BUFF_POR_5[$this->tipoBuff]) || !$this->porcentajeBuff) {
        session()->flash('mensaje', '⚠️ Seleccioná el tipo y cantidad del buff.');
        session()->flash('error', true);
        return;
    }

    // Los buffs comprados siempre son para todos: el tope es sobre los globales activos
    $buffActual = Buff::where('tipo', $this->tipoBuff)
        ->where('fin', '>=', now())
        ->whereNull('personaje_id')
        ->sum('porcentaje');
    $buffFaltante = 100 - $buffActual;

    if ($buffFaltante <= 0) {
        session()->flash('mensaje', 'El buff para todos de este tipo ya está al 100%.');
        session()->flash('error', true);
        return;
    }

    if ($this->porcentajeBuff > $buffFaltante) {
        $this->porcentajeBuff = $buffFaltante;
        session()->flash('mensaje', "Solo necesitás {$buffFaltante}% para llegar al 100%. Compra ajustada automáticamente.");
    }

    $costoFinal = $this->calcularCostoBuff($this->tipoBuff, $this->porcentajeBuff);

    if ($this->personaje->diamante < $costoFinal) {
        session()->flash('mensaje', 'No tenés suficientes esmeraldas.');
        session()->flash('error', true);
        return;
    }

    Buff::create([
        'personaje_id' => null,
        'tipo' => $this->tipoBuff,
        'frase' => $this->fraseBuff,
        'global' => true,
        'costo' => $costoFinal,
        'inicio' => now(),
        'fin' => now()->addHours(24),
        'porcentaje' => $this->porcentajeBuff,
        
    ]);

    $this->personaje->diamante -= $costoFinal;
    $this->personaje->save();

    session()->flash('mensaje', '¡✅ Compra realizada!');
    session()->flash('error', false);

    // Actualizar diamantes del sidebar
    $this->dispatch('statsActualizados');

    $this->cargarBuffs();
    $this->reloadExtra++;
}



// Diamantes por cada 5% según el tipo (drop más caro que oro, exp el más caro)
const COSTO_BUFF_POR_5 = ['xp' => 100, 'drop' => 75, 'oro' => 50];

private function calcularCostoBuff($tipo, $porcentaje): int
{
    $porcentaje = intval($porcentaje);
    $costo = self::COSTO_BUFF_POR_5[$tipo] ?? 0;

    return $porcentaje > 0 ? intval($porcentaje / 5 * $costo) : 0;
}

public function updatedPorcentajeBuff($valor)
{
    $this->costoBuff = $this->calcularCostoBuff($this->tipoBuff, $valor);
}

public function seleccionarTipoBuff($tipo)
{
    if (! isset(self::COSTO_BUFF_POR_5[$tipo])) {
        return;
    }

    $this->tipoBuff = $tipo;
    $this->updatedPorcentajeBuff($this->porcentajeBuff);
}

// Sube o baja el porcentaje de a 5%, entre 5% y 100%
public function cambiarPorcentajeBuff($delta)
{
    $this->porcentajeBuff = max(5, min(100, intval($this->porcentajeBuff) + (intval($delta) > 0 ? 5 : -5)));
    $this->updatedPorcentajeBuff($this->porcentajeBuff);
}

public function updatedTipoBuff()
{
    $this->updatedPorcentajeBuff($this->porcentajeBuff);
}

    // ===== Cosméticos: efectos de chat, efectos de nombre y auras (catálogo en App\Support\Cosmeticos) =====
    public string $tipoCosmetico = 'chat';

    public function comprarCosmetico(string $clave)
    {
        $item = \App\Support\Cosmeticos::CATALOGO[$clave] ?? null;
        if (! $item) {
            return;
        }
        $personaje = Personaje::find($this->personaje->id);
        if (in_array($clave, $personaje->cosmeticosComprados(), true)) {
            session()->flash('mensaje', 'Ya tenés ese cosmético.');
            session()->flash('error', true);
            return;
        }
        if ($personaje->diamante < $item['precio']) {
            session()->flash('mensaje', 'No tenés suficientes esmeraldas.');
            session()->flash('error', true);
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($personaje, $clave, $item) {
            $personaje->diamante -= $item['precio'];
            // Se equipa solo al comprarlo
            $personaje->{\App\Support\Cosmeticos::TIPOS[$item['tipo']]['columna']} = $clave;
            $personaje->save();
            \Illuminate\Support\Facades\DB::table('personaje_cosmeticos')->insert([
                'personaje_id' => $personaje->id, 'clave' => $clave, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $this->personaje = $personaje->fresh();
        $this->dispatch('statsActualizados');
        session()->flash('mensaje', "¡Compraste {$item['nombre']}! Ya lo tenés puesto.");
    }

    public function equiparCosmetico(string $clave)
    {
        $item = \App\Support\Cosmeticos::CATALOGO[$clave] ?? null;
        $personaje = Personaje::find($this->personaje->id);
        if (! $item || ! in_array($clave, $personaje->cosmeticosComprados(), true)) {
            return;
        }
        $personaje->{\App\Support\Cosmeticos::TIPOS[$item['tipo']]['columna']} = $clave;
        $personaje->save();
        $this->personaje = $personaje->fresh();
        $this->dispatch('statsActualizados');
    }

    public function quitarCosmetico(string $tipo)
    {
        $columna = \App\Support\Cosmeticos::TIPOS[$tipo]['columna'] ?? null;
        if (! $columna) {
            return;
        }
        $personaje = Personaje::find($this->personaje->id);
        $personaje->$columna = null;
        $personaje->save();
        $this->personaje = $personaje->fresh();
        $this->dispatch('statsActualizados');
    }

    public function render()
    {
        return view('livewire.extra', [
            'cosmeticosComprados' => Personaje::find($this->personaje->id)?->cosmeticosComprados() ?? [],
        ]);
    }
}
