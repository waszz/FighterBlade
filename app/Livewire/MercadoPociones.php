<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MercadoPocion;
use App\Models\Objeto;
use App\Models\Personaje;

class MercadoPociones extends Component
{
    public $pociones;
    public $personaje;
    const ORIGEN_MERCADO_POCIONES = 0;
    public $mensajeTemporal;
    public $reloadMercadoPocion = 0;
    public $esError = false;
    public $cantidades = [];
    protected $listeners = ['actualizarCantidad' => '$refresh'];

    public function mount($personajeId)
    {
        $this->personaje = Personaje::findOrFail($personajeId);
        $this->cargarPociones();
    }

   public function cargarPociones()
{
    $this->pociones = MercadoPocion::all();

    foreach ($this->pociones as $pocion) {
        // Si ya hay cantidad seteada por el usuario, no la pisamos
        $this->cantidades[$pocion->id] = $this->cantidades[$pocion->id] ?? 1;
    }
}



public function incrementarCantidad($pocionId)
{
    $cantidades = $this->cantidades;
    $actual = $cantidades[$pocionId] ?? 1;
    $cantidades[$pocionId] = $actual + 1;
    $this->cantidades = $cantidades;
}


public function disminuirCantidad($pocionId)
{
    $cantidades = $this->cantidades;
    $actual = $cantidades[$pocionId] ?? 1;
    $cantidades[$pocionId] = max(1, $actual - 1);
    $this->cantidades = $cantidades;
}
public function updatedCantidades($value, $key)
{
    // $key tiene formato 'cantidades.17' por ejemplo
    if ($value < 1) {
        $this->cantidades[explode('.', $key)[1]] = 1;
    }
}

public function comprarPocion($pocionId)
{
    $pocion = MercadoPocion::findOrFail($pocionId);

    // ✅ Leer la cantidad ingresada (por defecto 1)
    $cantidad = intval($this->cantidades[$pocionId] ?? 1);
    if ($cantidad < 1) {
        $cantidad = 1;
    }

    // 🚫 Restricción por nivel
    if ($pocion->nombre === 'Poción de Búsqueda' && $this->personaje->nivel < 20) {
        $this->mensajeTemporal = 'Debes tener nivel 20 para comprar la Poción de Búsqueda.';
        $this->esError = true;
        $this->dispatch('ocultar-mensaje');
        return;
    }

    // 🎒 Cada poción ocupa un lugar del inventario
    if (! $this->personaje->tieneLugar($cantidad)) {
        $libres = $this->personaje->lugaresLibres();
        $this->mensajeTemporal = $libres > 0
            ? "Solo te quedan {$libres} lugares libres en el inventario."
            : \App\Models\Personaje::MENSAJE_INVENTARIO_LLENO;
        $this->esError = true;
        $this->dispatch('ocultar-mensaje');
        return;
    }

    $moneda = $pocion->moneda ?? 'oro';
    $precioTotal = $pocion->precio * $cantidad;

    // 💰 Validación de recursos
    if ($moneda === 'oro') {
        if ($this->personaje->oro < $precioTotal) {
            $this->mensajeTemporal = 'No tienes suficiente oro.';
            $this->esError = true;
            $this->dispatch('ocultar-mensaje');
            return;
        }
        $this->personaje->oro -= $precioTotal;
    } elseif ($moneda === 'diamante') {
        if ($this->personaje->diamante < $precioTotal) {
            $this->mensajeTemporal = 'No tienes suficientes esmeraldas.';
            $this->esError = true;
            $this->dispatch('ocultar-mensaje');
            return;
        }
        $this->personaje->diamante -= $precioTotal;
    }

    $this->personaje->save();

    // ✅ Crear múltiples objetos
    for ($i = 0; $i < $cantidad; $i++) {
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
    }

    // ✅ Resetear la cantidad visual
    $this->cantidades[$pocionId] = 1;

    $this->mensajeTemporal = "¡Compraste $cantidad poción" . ($cantidad > 1 ? 'es' : '') . " correctamente!";
    $this->esError = false;
    $this->dispatch('ocultar-mensaje');

    $this->cargarPociones();
    $this->reloadMercadoPocion++;
}





    public function render()
    {
        return view('livewire.mercado-pociones');
    }
}
