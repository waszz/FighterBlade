<?php
namespace App\Livewire;

use App\Models\Clan;
use App\Models\ClanInventario;
use App\Models\Objeto;
use App\Models\Personaje;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Inventario extends Component
{
    use \Livewire\WithFileUploads;

    // Nivel mínimo para equipar pociones de drop (Poción de Búsqueda)
    const NIVEL_POCION_DROP = 20;

    public $personaje;
    public $objetoSeleccionado = null;
    public $objetoEquipado     = null;
    public $objetos            = [];
    public $precioVenta        = null;
    public $nuevoPrecioVenta;
    public $clan;
    public $inventarioClan;
    public $nuevaFrase;

    public $pociones                  = [];
    public $modalEquiparPocionAbierto = false;
    public $pocionSeleccionada        = null;
    public $mensajeErrorEquiparPocion = null;

    public $modalGuardarOroAbierto        = false;
    public $oroAGuardar                   = 0;
    public $oroARetirar                   = 0;
    public $objetosSeleccionadosParaTirar = [];
    public $modalTirarObjetosAbierto      = false;
    public $objetoParaTirarId             = null;
    public $mensajeOroObtenido            = null;
    public $mensajeErrorEquipar           = null;
    public $modalDesequiparAbierto        = false;
    public $tipoDesequipar                = null; // 'equipo', 'entrenamiento' o 'accesorio'
    public $objetoADesequipar             = null;
    public $reloadInventario              = 0;
    public $modalVenderPocionAbierto      = false;
    public $pocionParaVender              = null;
    public $precioVentaPocion             = null;
    public $mensajeErrorVentaPocion       = null;
    public $pocionesAgrupadas             = [];
    protected $listeners                  = ['actualizarInventario' => 'actualizarObjetoEquipado'];

    // Objeto del inventario de este personaje (los ids vienen del navegador: nunca buscar objetos de otros)
    private function miObjeto($objetoId): ?Objeto
    {
        return Objeto::where('personaje_id', $this->personaje->id)->find($objetoId);
    }

    public function mount($personajeId)
    {
        $this->personaje = Personaje::with('post')->findOrFail($personajeId);
        $this->cargarPocionEquipada();
        $this->cargarPociones();
        // Obtener clan del personaje por su clan_id
        $this->clan = null;
        if ($this->personaje->clan_id) {
            $this->clan = Clan::find($this->personaje->clan_id);
        }
        // Traer inventario del clan
        if ($this->clan) {
            $this->inventarioClan = ClanInventario::where('clan_id', $this->clan->id)->with('objeto')->get();
        } else {
            $this->inventarioClan = collect();
        }

        if ($this->personaje->objeto_consumible_id) {
            $this->objetoEquipado = Objeto::find($this->personaje->objeto_consumible_id);
            $this->objetoEquipado = $this->procesarObjeto($this->objetoEquipado);
        }
        // Traer objetos **excluyendo pociones**
        $this->objetos = Objeto::where('personaje_id', $this->personaje->id)
            ->where('tipo', '!=', 'pocion')
            ->get()
            ->map(function ($objeto) {
                return $this->procesarObjeto($objeto);
            });
        // Traer solo pociones
        $this->pociones = Objeto::where('personaje_id', $this->personaje->id)
            ->where('tipo', 'pocion')
            ->get()
            ->map(fn($objeto) => $this->procesarObjeto($objeto));

        $this->objetoEquipado = $this->personaje->objeto_consumible_id ? $this->procesarObjeto(Objeto::find($this->personaje->objeto_consumible_id)) : null;

        // Inicializar la frase editable
        $this->nuevaFrase = $this->personaje->frase ?? '';

        $this->agruparObjetosPorOrigen();
    }

    public function abrirModalEquiparPocion($objetoId)
    {
        $objeto = $this->miObjeto($objetoId);
        if (! $objeto) {
            $this->mensajeErrorEquiparPocion = '❌ Poción no encontrada.';
            return;
        }

        $this->pocionSeleccionada        = $this->procesarObjeto($objeto);
        $this->mensajeErrorEquiparPocion = null;
        $this->modalEquiparPocionAbierto = true;
    }

    public function obtenerStat($objeto, $clave, $default = 'N/A')
    {
        $stats = is_array($objeto->stats)
        ? $objeto->stats
        : (is_string($objeto->stats) ? json_decode($objeto->stats, true) : []);

        return $stats[$clave] ?? $default;
    }

    public function cargarPocionEquipada()
    {
        if ($this->personaje && $this->personaje->objeto_consumible_id) {
            $this->objetoEquipado = Objeto::find($this->personaje->objeto_consumible_id);
        } else {
            $this->objetoEquipado = null;
        }
    }

    // Admin: agregarse partes de cualquier set para probarlas ("Guardar nuevo objeto")
    public $modalAgregarParteAbierto = false;
    public $parteSetId               = null;
    public $parteTipo                = 'completo'; // equipo | entrenamiento | accesorio | completo
    public $parteSinRequisitos       = true;       // para probar: sin nivel ni requisitos

    public function guardarObjetoEjemplo()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->modalAgregarParteAbierto = true;
    }

    // Comparte en el chat general un objeto propio (parte de set, poción, joya o cofre)
    public function compartirEnChat($objetoId)
    {
        $objeto = $this->miObjeto($objetoId);
        if (! $objeto) {
            return;
        }
        \App\Support\ChatCompartir::objeto($this->personaje, $objeto);
        $this->dispatch('chatCompartido');
        $this->dispatch('success', ['message' => 'Compartiste ' . $objeto->nombre . ' en el chat.']);
    }

    // Cofre de la Torre: se abre y deja su contenido en el inventario
    public function abrirCofre($objetoId)
    {
        $cofre = Objeto::where('personaje_id', $this->personaje->id)->where('tipo', 'cofre')->find($objetoId);
        if (! $cofre) {
            return;
        }

        // Da hasta 3 objetos y el cofre libera su lugar: hacen falta 2 lugares libres
        if (! Personaje::find($this->personaje->id)->tieneLugar(2)) {
            $this->dispatch('error', ['message' => '🎒 Necesitás 2 lugares libres en el inventario para abrir el cofre.']);
            return;
        }

        // Abrirlo cuesta oro según su nivel (se descuenta solo si alcanza, en una sola consulta). El de bienvenida es gratis
        $costo = \App\Support\RecompensasTorre::costoCofre($cofre);
        if ($costo > 0) {
            $pagado = Personaje::whereKey($this->personaje->id)->where('oro', '>=', $costo)->decrement('oro', $costo);
            if (! $pagado) {
                $this->dispatch('error', ['message' => 'Necesitás ' . number_format($costo, 0, ',', '.') . ' de oro para abrir este cofre.']);
                return;
            }
            $this->dispatch('statsActualizados'); // el oro del panel lateral
        }

        $texto = \App\Support\RecompensasTorre::abrirCofre($cofre, Personaje::find($this->personaje->id));
        $this->objetoSeleccionado = null;
        $this->actualizarObjetos();
        $this->dispatch('success', ['message' => $texto]);
    }

    // Comprar 3 lugares más en el inventario (5000 de oro la primera vez y 1000 más cada vez, hasta 200)
    public function comprarSlots()
    {
        $pj = Personaje::where('user_id', auth()->id())->find($this->personaje->id);
        if (! $pj) {
            return;
        }
        $precio = $pj->precioProximosSlots();
        if ($precio === null) {
            $this->dispatch('error', ['message' => 'Ya tenés el máximo de ' . Personaje::SLOTS_MAX . ' lugares.']);
            return;
        }
        $suma = min(Personaje::SLOTS_POR_COMPRA, Personaje::SLOTS_MAX - $pj->capacidadInventario());

        // Se descuenta solo si alcanza (en una sola consulta, así no se paga dos veces con dos clicks)
        $pagado = Personaje::whereKey($pj->id)->where('oro', '>=', $precio)->where('slots_extra', $pj->slots_extra)
            ->update(['oro' => DB::raw('oro - ' . (int) $precio), 'slots_extra' => DB::raw('slots_extra + ' . (int) $suma)]);
        if (! $pagado) {
            $this->dispatch('error', ['message' => 'Necesitás ' . number_format($precio, 0, ',', '.') . ' de oro.']);
            return;
        }

        $this->personaje = $this->personaje->fresh();
        $this->dispatch('success', ['message' => "🎒 ¡+{$suma} lugares! Ahora tenés " . $this->personaje->capacidadInventario() . '.']);
        $this->dispatch('statsActualizados'); // el oro del panel lateral
    }

    public function cerrarModalAgregarParte()
    {
        $this->modalAgregarParteAbierto = false;
    }

    public function agregarParteAdmin()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $set = Post::conRivales()->find($this->parteSetId);
        if (! $set) {
            $this->dispatch('error', ['message' => 'Elegí un set.']);
            return;
        }

        $tipos = $this->parteTipo === 'completo' ? ['equipo', 'entrenamiento', 'accesorio'] : [$this->parteTipo];
        $decodificar = fn ($v) => is_array($v) ? $v : (json_decode($v ?? '[]', true) ?: []);

        foreach ($tipos as $tipo) {
            // Igual que un drop de pelea (Explorar::asignarRecompensas)
            $requisitos = $this->parteSinRequisitos ? [] : $decodificar($set->{'requisitos_' . $tipo});
            $imagenParte = $set->{$tipo . '_imagen'} ?: preg_replace('#^posts/#', '', (string) $set->imagen);
            Objeto::create([
                'personaje_id'             => $this->personaje->id,
                'nombre'                   => $set->{$tipo . '_nombre'} ?: (ucfirst($tipo) . ' de ' . $set->titulo),
                'tipo'                     => $tipo,
                'nivel'                    => $this->parteSinRequisitos ? 1 : $set->nivel,
                'stats'                    => $decodificar($set->{'ajustes_manuales_' . $tipo}),
                'imagen'                   => $imagenParte ?: ('default_' . $tipo . '.png'),
                'origen_post_id'           => $set->id,
                'requisitos_equipo'        => $tipo === 'equipo' ? $requisitos : [],
                'requisitos_entrenamiento' => $tipo === 'entrenamiento' ? $requisitos : [],
                'requisitos_accesorio'     => $tipo === 'accesorio' ? $requisitos : [],
                'pocion'                   => false,
                'usos_restantes'           => 1,
                'usos_totales'             => 1,
            ]);
        }

        $this->modalAgregarParteAbierto = false;
        // Recarga la lista del inventario (se carga una sola vez al abrir la sección) para que aparezcan ya
        $this->actualizarObjetos();
        $this->dispatch('success', ['message' => (count($tipos) === 3 ? 'Set completo' : ucfirst($tipos[0])) . " de {$set->titulo} agregado al inventario."]);
    }

    public function cargarObjetos()
    {
        $this->objetos = Objeto::where('personaje_id', $this->personaje->id)
            ->with('post.partes') // cargamos las partes junto con el post
            ->get()
            ->filter(function ($objeto) {
                return stripos($objeto->nombre, 'pocion') === false;
            })
            ->groupBy('origen_post_id')
            ->filter(function ($grupoObjetos, $origenPostId) {
                // Filtrar solo los grupos cuyo post tiene partes
                $post = $grupoObjetos->first()->post ?? null;
                return $post && $post->partes && $post->partes->count() > 0;
            });

        $this->pociones = Objeto::where('personaje_id', $this->personaje->id)->where('tipo', 'pocion')->get()->map(function ($objeto) {
            return $this->procesarObjeto($objeto);
        });
    }

    public function equiparPocion()
    {
        if (! $this->pocionSeleccionada) {
            return;
        }

        if ($this->personaje->nivel < ($this->pocionSeleccionada->nivel ?? 1)) {
            $this->mensajeErrorEquiparPocion = '❌ No tenés el nivel necesario para equipar esta poción.';
            return;
        }

        // Pociones de drop (Búsqueda): se pueden tener a cualquier nivel, pero se equipan desde el nivel 20
        $statsPocion = is_array($this->pocionSeleccionada->stats)
            ? $this->pocionSeleccionada->stats
            : json_decode($this->pocionSeleccionada->stats ?? '[]', true);
        if (($statsPocion['afecta'] ?? null) === 'drop_partes' && $this->personaje->nivel < self::NIVEL_POCION_DROP) {
            $this->mensajeErrorEquiparPocion = '❌ Necesitás nivel ' . self::NIVEL_POCION_DROP . ' para equipar la Poción de Búsqueda.';
            return;
        }

        if ($this->pocionSeleccionada->precio_venta) {
            $this->mensajeErrorEquiparPocion = '❌ No podés equipar una poción que está en venta.';
            return;
        }

        // Guardar poción equipada usando el campo `objeto_consumible_id`
        $this->personaje->objeto_consumible_id = $this->pocionSeleccionada->id;

        // Solo guardar los stats modificados si no existen
        if (! $this->personaje->stats_modificados) {
            $this->personaje->stats_modificados = $this->personaje->stats;
        }

        $this->personaje->save();

        // Refrescar relaciones
        $this->personaje->refresh();
        $this->pocionSeleccionada->refresh();

        // Procesar para vista
        $this->pocionSeleccionada = $this->procesarObjeto($this->pocionSeleccionada);
        $this->objetoEquipado     = $this->pocionSeleccionada;
        $this->stats              = $this->objetoEquipado->stats ?? [];

        // Mensaje de feedback
        $this->dispatch('mensaje', [
            'type' => 'success',
            'text' => '✅ Poción equipada correctamente.',
        ]);
        $this->mensajeErrorEquiparPocion = null;

        // Recargar objetos/inventario
        $this->actualizarObjetos();
        $this->reloadInventario++;

        // Cerrar modal
        $this->modalEquiparPocionAbierto = false;
    }

    public function abrirModalVenderPocion($objetoId)
    {
        $this->pocionParaVender          = $this->miObjeto($objetoId);
        $this->precioVentaPocion         = $this->pocionParaVender->precio_venta ?? null;
        $this->mensajeErrorVentaPocion   = null;
        $this->modalEquiparPocionAbierto = false; // cerrar modal equipar
        $this->modalVenderPocionAbierto  = true;
    }

    public function actualizarPrecioVentaPocion($idPocion)
    {
        $pocion = $this->miObjeto($idPocion);

        if (! $pocion) {
            $this->addError('general', 'Poción no encontrada');
            return;
        }

        if ($this->nuevoPrecioVenta < 1) {
            $this->addError('nuevoPrecioVenta', 'El precio debe ser al menos 1');
            return;
        }

        $pocion->precio_venta = $this->nuevoPrecioVenta;
        $pocion->save();
        $this->cerrarModalEquiparPocion();
        $this->cargarPociones();

        // Opcional: emitir mensaje o evento para confirmar actualización
        session()->flash('mensaje', 'Precio de venta actualizado correctamente');
    }

    public function venderPocion()
    {
        if (! $this->precioVentaPocion || $this->precioVentaPocion < 1) {
            $this->mensajeErrorVentaPocion = "El precio debe ser al menos 1.";
            return;
        }

        if (! $this->pocionSeleccionada) {
            $this->mensajeErrorVentaPocion = "No se encontró la poción seleccionada.";
            return;
        }

        $objeto               = $this->pocionSeleccionada;
        $objeto->precio_venta = $this->precioVentaPocion;
        $objeto->save();
        session()->flash('mensaje', "Poción en venta");
        $this->cerrarModalEquiparPocion(); // ✅ cerrar correctamente
        $this->cargarPociones();           // recargar lista
        $this->reloadInventario++;

    }

    public function cerrarModalEquiparPocion()
    {
        $this->modalEquiparPocionAbierto = false;
        $this->pocionSeleccionada        = null;
        $this->precioVentaPocion         = null;
        $this->mensajeErrorVentaPocion   = null;
    }
    public function desequiparPocion()
    {
        // Obtener la poción antes de quitarla del personaje
        $pocion = \App\Models\Objeto::find($this->personaje->objeto_consumible_id);

        // ❌ Ya no restauramos stats_modificados
        $this->personaje->objeto_consumible_id = null;
        $this->personaje->save();

        // Si la poción existe, devolverla al inventario
        if ($pocion) {
            $pocion->personaje_id = $this->personaje->id; // Asegura que quede asociada al personaje
            $pocion->save();
        }

        // Feedback
        session()->flash('mensaje', '✅ Poción desequipada correctamente.');

        $this->actualizarObjetos();
        $this->reloadInventario++;
        $this->modalEquiparPocionAbierto = false;
    }

    public function cancelarVentaPocion($id)
    {
        $pocion = $this->pociones->firstWhere('id', $id);
        if ($pocion) {
            $pocion->precio_venta = null;
            $pocion->save();
            $this->cerrarModalEquiparPocion();
            // Refrescar la colección o recargar la lista
            $this->cargarPociones();
        }
    }
    public function cargarPociones()
    {
        $objetosRaw = Objeto::where('personaje_id', $this->personaje->id)
            ->where('tipo', 'pocion')
            ->get();

        $pocionesProcesadas = $objetosRaw->map(function ($obj) {
            return $this->procesarObjeto($obj);
        });

        // Agrupar por tipo (ejemplo usando stats['afecta'] como categoría)
        $agrupadas = $pocionesProcesadas->groupBy(function ($pocion) {
            return $pocion->stats['afecta'] ?? 'otros';
        });

        // Ordenar cada grupo por nombre
        $agrupadasOrdenadas = $agrupadas->map(function ($grupo) {
            return $grupo->sortBy('nombre')->values();
        });

        $this->pocionesAgrupadas = $agrupadasOrdenadas;
    }

    public function eliminarPocion()
    {
        if (! $this->pocionSeleccionada) {
            return;
        }

        // Si la poción está equipada, la desvinculamos
        if ($this->personaje->objeto_consumible_id === $this->pocionSeleccionada->id) {
            $this->personaje->objeto_consumible_id = null;

            if (is_array($this->pocionSeleccionada->stats)) {
                foreach ($this->pocionSeleccionada->stats as $stat => $valor) {
                    if (isset($this->personaje->$stat)) {
                        $this->personaje->$stat -= $valor;
                    }
                }
            }
        }

        // Calcular oro ganado
        $oroGanado = $this->calcularOroPorObjeto($this->pocionSeleccionada);
        $this->personaje->oro += $oroGanado;
        $this->personaje->save();

        // Eliminar poción
        $this->pocionSeleccionada->delete();

        // Reset y refrescar
        $this->modalEquiparPocionAbierto = false;
        $this->cerrarModalEquiparPocion();
        $this->cargarPociones();
        $this->actualizarObjetos();
        $this->reloadInventario++;

        $this->mensajeOroObtenido = "🪙 Obtuviste " . number_format($oroGanado, 0, ',', '.') . " de oro.";
    }

    public function usarPocionEquipada()
    {
        if (! $this->objetoEquipado || $this->objetoEquipado->tipo !== 'pocion') {
            $this->dispatch('error', ['message' => 'No tenés una poción equipada.']);
            return;
        }

        $pocion = Objeto::find($this->objetoEquipado->id);
        if (! $pocion) {
            return;
        }

        $stats         = $pocion->stats ?? [];
        $usosRestantes = $stats['usos_restantes'] ?? 1;

        if ($usosRestantes <= 0) {
            // Ya no tiene usos, eliminarla si no se hizo antes
            $pocion->delete();
            $this->objetoEquipado                  = null;
            $this->personaje->objeto_consumible_id = null;
            $this->personaje->save();

            $this->dispatch('success', ['message' => 'La poción se terminó y fue eliminada.']);
            $this->actualizarObjetos();
            $this->reloadInventario++;
            return;
        }

        // Restar solo un uso por vez
        $usosRestantes--;
        if ($usosRestantes <= 0) {
            $pocion->delete();
            $this->objetoEquipado                  = null;
            $this->personaje->objeto_consumible_id = null;
            $this->personaje->save();

            $this->dispatch('success', ['message' => 'Usaste la última dosis de la poción. Fue eliminada.']);
        } else {
            $stats['usos_restantes'] = $usosRestantes;
            $pocion->stats           = $stats;
            $pocion->save();

            $this->dispatch('success', ['message' => "Usaste la poción. Quedan $usosRestantes usos."]);
        }

        $this->actualizarObjetos();

// Aquí actualizo $objetoEquipado para que la vista refleje que ya no hay poción equipada si se eliminó
        $this->objetoEquipado = $this->personaje->objeto_consumible_id ? $this->procesarObjeto(Objeto::find($this->personaje->objeto_consumible_id)) : null;

        $this->reloadInventario++;
    }

    // Foto del chat: se elige entre los sets desbloqueados (null = automática, la del set con el que pelea)
    public $modalFotoChatAbierto = false;

    public function abrirModalFotoChat()
    {
        $this->modalFotoChatAbierto = true;
    }

    public function cerrarModalFotoChat()
    {
        $this->modalFotoChatAbierto = false;
    }

    // Imagen propia para el chat: se recorta cuadrada al centro y se guarda chica (128px, webp)
    public $fotoChatSubida;

    public function updatedFotoChatSubida()
    {
        $this->validate(['fotoChatSubida' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:3072'], [], ['fotoChatSubida' => 'imagen']);

        $origen = @imagecreatefromstring(file_get_contents($this->fotoChatSubida->getRealPath()));
        if (! $origen) {
            $this->addError('fotoChatSubida', 'No se pudo leer la imagen.');
            return;
        }
        $lado = min(imagesx($origen), imagesy($origen));
        $x = (int) ((imagesx($origen) - $lado) / 2);
        $y = (int) ((imagesy($origen) - $lado) / 2);
        $final = imagecreatetruecolor(128, 128);
        imagealphablending($final, false); imagesavealpha($final, true);
        imagefill($final, 0, 0, imagecolorallocatealpha($final, 0, 0, 0, 127));
        imagecopyresampled($final, $origen, 0, 0, $x, $y, 128, 128, $lado, $lado);

        $personaje = Personaje::find($this->personaje->id);
        $ruta = 'chat/' . $personaje->id . '_' . time() . '.webp';
        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('chat');
        imagewebp($final, storage_path('app/public/' . $ruta), 85);

        // Borra la imagen anterior
        if ($personaje->foto_chat_propia && str_starts_with($personaje->foto_chat_propia, 'chat/')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($personaje->foto_chat_propia);
        }
        $personaje->foto_chat_propia = $ruta;
        $personaje->foto_chat_post_id = 0;
        $personaje->save();
        $this->personaje->foto_chat_propia = $ruta;
        $this->personaje->foto_chat_post_id = 0;
        $this->fotoChatSubida = null;
        $this->modalFotoChatAbierto = false;
        $this->dispatch('success', ['message' => 'Tu imagen del chat se actualizó.']);
    }

    public function elegirFotoChat($postId = null)
    {
        $personaje = Personaje::find($this->personaje->id);
        // 0 = la imagen subida (si tiene una)
        if ((string) $postId === '0') {
            if (! $personaje->foto_chat_propia) {
                return;
            }
        } elseif ($postId !== null && ! in_array((int) $postId, $personaje->idsFotosChat(), true)) {
            return; // solo sets desbloqueados
        }
        $personaje->foto_chat_post_id = $postId === null ? null : (int) $postId;
        $personaje->save();
        $this->personaje->foto_chat_post_id = $personaje->foto_chat_post_id;
        $this->modalFotoChatAbierto = false;
        $this->dispatch('success', ['message' => $postId === null ? 'Foto del chat: automática.' : 'Foto del chat actualizada.']);
    }

    public function actualizarFrase()
    {
        $this->validate([
            'nuevaFrase' => 'nullable|string|max:100',
        ]);

        $this->personaje->frase = $this->nuevaFrase;
        $this->personaje->save();

        session()->flash('mensaje', 'Frase actualizada correctamente.');
    }

    public function guardarEnClan($objetoId)
    {
        $objeto = $this->miObjeto($objetoId);

        if (! $objeto) {
            session()->flash('error', '❌ Objeto no encontrado.');
            return;
        }

        $personaje = Personaje::where('user_id', auth()->id())->first();

        if (! $personaje || ! $personaje->clan_id) {
            session()->flash('error', '❌ No perteneces a ningún clan.');
            return;
        }

        $registro = ClanInventario::where('clan_id', $personaje->clan_id)
            ->where('objeto_id', $objeto->id)
            ->first();

        if ($registro) {
            $registro->cantidad += 1;
            $registro->save();
        } else {
            ClanInventario::create([
                'clan_id'   => $personaje->clan_id,
                'objeto_id' => $objeto->id,
                'cantidad'  => 1,
            ]);
        }

        $objeto->personaje_id = null;
        $objeto->save();

        // Refrescar el inventario según el tipo
        if ($objeto->tipo === 'pocion') {
            $this->cargarPociones();
            $this->pocionSeleccionada = null;
        } else {
            $this->actualizarObjetos();
            $this->objetoSeleccionado = null;
        }

        session()->flash('mensaje', '✅ ' . ucfirst($objeto->tipo) . ' guardado en el clan.');
        $this->reloadInventario++;
    }

    protected function procesarObjeto($objeto)
    {
        if (! $objeto) {
            return null;
        }

        // Procesar stats desde JSON si es string
        if (is_string($objeto->stats)) {
            $decoded       = json_decode($objeto->stats, true);
            $objeto->stats = is_array($decoded) ? $decoded : [];
        } elseif (! is_array($objeto->stats)) {
            $objeto->stats = [];
        }

        // Procesar requisitos si son JSON strings
        $objeto->requisitos_equipo        = $this->decodeJsonField($objeto->requisitos_equipo ?? []);
        $objeto->requisitos_entrenamiento = $this->decodeJsonField($objeto->requisitos_entrenamiento ?? []);
        $objeto->requisitos_accesorio     = $this->decodeJsonField($objeto->requisitos_accesorio ?? []);

        // Agregar color según estilo (si existe)
        $estilo  = strtolower($objeto->estilo ?? '');
        $colores = [
            'fisico'    => 'text-red-600',
            'elemental' => 'text-blue-600',
            'hibrido'   => 'text-purple-600',
        ];
        $objeto->color_estilo = $colores[$estilo] ?? 'text-gray-600';

        return $objeto;
    }

    protected function decodeJsonField($field)
    {
        if (empty($field)) {
            return [];
        }

        if (is_array($field)) {
            return $field;
        }

        $req = trim($field, '"');
        $req = stripslashes($req);

        $decoded = json_decode($req, true);

        // Si sigue siendo string (doble codificación)
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function getObjetosDisponiblesProperty()
    {
        $equipadosIds = collect([
            $this->personaje->equipo_id,
            $this->personaje->entrenamiento_id,
            $this->personaje->accesorio_id,
            $this->personaje->joya_id,
        ])->filter();

        return $this->objetos->filter(fn($objeto) => ! $equipadosIds->contains($objeto->id));
    }

    public function getObjetosGuardadosProperty()
    {
        $excluirIds = array_filter([
            $this->personaje->equipo_id,
            $this->personaje->entrenamiento_id,
            $this->personaje->accesorio_id,
            $this->personaje->joya_id,
        ]);

        // Ordenados por set (nivel y nombre) y, dentro de cada set, Equipo → Entrenamiento → Accesorio; las joyas al final
        $ordenParte = ['equipo' => 0, 'entrenamiento' => 1, 'accesorio' => 2, 'joya' => 3];

        return collect($this->objetos)
            ->filter(fn($objeto) => ! in_array($objeto->id, $excluirIds))
            ->sortBy([
                fn($a, $b) => ($a->tipo === 'joya') <=> ($b->tipo === 'joya'),
                fn($a, $b) => ($a->post->nivel ?? $a->nivel ?? 0) <=> ($b->post->nivel ?? $b->nivel ?? 0),
                fn($a, $b) => strcasecmp($a->post->titulo ?? $a->nombre ?? '', $b->post->titulo ?? $b->nombre ?? ''),
                fn($a, $b) => ($a->origen_post_id ?? 0) <=> ($b->origen_post_id ?? 0),
                fn($a, $b) => ($ordenParte[$a->tipo] ?? 9) <=> ($ordenParte[$b->tipo] ?? 9),
                fn($a, $b) => $a->id <=> $b->id,
            ])
            ->values();
    }

    public function agruparObjetosPorOrigen()
    {
        $this->grupos = $this->objetos->groupBy('origen_post_id');
    }

    public function actualizarObjetos()
    {
        $this->personaje = Personaje::with(['post', 'equipo', 'entrenamiento', 'accesorio'])->find($this->personaje->id);

        $todos = Objeto::where('personaje_id', $this->personaje->id)->get()->map(function ($objeto) {
            return $this->procesarObjeto($objeto);
        });

        // Igual que al abrir el inventario: los objetos sin las pociones (las pociones se cuentan aparte; antes quedaban
        // en los dos lados y después de tirar algo cada poción ocupaba 2 lugares → "inventario lleno")
        $this->objetos  = $todos->filter(fn($objeto) => $objeto->tipo !== 'pocion')->values();
        $this->pociones = $todos->filter(fn($objeto) => $objeto->tipo === 'pocion')->values();

        $this->objetoEquipado = $this->personaje->objeto_consumible_id
        ? $this->procesarObjeto(Objeto::find($this->personaje->objeto_consumible_id))
        : null;

        // ✅ IMPORTANTE: actualizar stats también
        $this->stats = $this->objetoEquipado->stats ?? [];

        $this->agruparObjetosPorOrigen();
        $this->reloadInventario++;
    }

    public function actualizarObjetoEquipado()
    {
        $this->objetoEquipado = \App\Models\Objeto::find($this->personaje->objeto_consumible_id);
    }

    public function mostrarOpciones($objetoId)
    {
        $objeto                          = $this->procesarObjeto($this->miObjeto($objetoId));
        $this->mensajeErrorEquipar       = null;
        $this->mensajeErrorEquiparPocion = null; // si usás para pociones

        if (! $objeto) {
            return;
        }

        if ($objeto->precio_venta) {
            $this->nuevoPrecioVenta = $objeto->precio_venta;
        } else {
            $this->nuevoPrecioVenta = null;
        }

        // Abrir modal según tipo de objeto
        if ($objeto->tipo === 'pocion') {
            $this->pocionSeleccionada        = $objeto;
            $this->modalEquiparPocionAbierto = true;
            $this->objetoSeleccionado        = null;
        } else {
            $this->objetoSeleccionado        = $objeto;
            $this->modalEquiparPocionAbierto = false;
            $this->pocionSeleccionada        = null;
        }
    }

    public function actualizarPrecioVenta($objetoId)
    {
        $objeto = $this->miObjeto($objetoId);
        if (! $objeto) {
            $this->mensajeErrorEquipar = "Objeto no encontrado.";
            return;
        }
        if ($objeto->tipo === 'cofre') {
            $this->mensajeErrorEquipar = "Los cofres no se pueden vender.";
            return;
        }

        // Validar nuevo precio, por ejemplo:
        if ($this->nuevoPrecioVenta < 1) {
            $this->mensajeErrorEquipar = "El precio debe ser mayor o igual a 1.";
            return;
        }

        $objeto->precio_venta = $this->nuevoPrecioVenta;
        $objeto->save();

        // Cerrar modal
        $this->objetoSeleccionado  = null;
        $this->nuevoPrecioVenta    = null;
        $this->mensajeErrorEquipar = null;

    }

    public function cerrarModal()
    {
        $this->objetoSeleccionado = null;
    }

    public function equiparObjeto($objetoId)
    {
        $objeto = $this->procesarObjeto($this->miObjeto($objetoId));
        if (! $objeto) {
            return;
        }

        // Reset mensaje error al intentar equipar
        $this->mensajeErrorEquipar = null;

        // NUEVO: No permitir equipar si el objeto está en venta
        if ($objeto->precio_venta) {
            $this->mensajeErrorEquipar = '❌ No podés equipar un objeto que está en venta.';
            return;
        }

        if ($this->personaje->nivel < ($objeto->nivel ?? 1)) {
            $this->mensajeErrorEquipar = '❌ No tenés el nivel necesario para equipar este objeto.';
            return;
        }

        $campoRequisito = match ($objeto->tipo) {
            'equipo' => 'requisitos_equipo',
            'entrenamiento' => 'requisitos_entrenamiento',
            'accesorio' => 'requisitos_accesorio',
            default => null,
        };

        // La joya de la Torre no tiene requisitos de stats (solo el nivel, ya chequeado)
        if (! $campoRequisito && $objeto->tipo !== 'joya') {
            $this->mensajeErrorEquipar = '❌ Tipo de objeto inválido.';
            return;
        }

        $statsPersonaje = is_array($this->personaje->stats) ? $this->personaje->stats : json_decode($this->personaje->stats, true);

        foreach (($campoRequisito ? ($objeto->$campoRequisito ?? []) : []) as $stat => $valorMinimo) {
            $personajeValor = $statsPersonaje[$stat] ?? 0;
            if ($personajeValor < $valorMinimo) {
                $this->mensajeErrorEquipar = '❌ No cumples con los requisitos de ' . ucfirst($stat) . ' (' . $valorMinimo . '), tenés: ' . $personajeValor;
                return;
            }
        }

        $campoEquipadoId = match ($objeto->tipo) {
            'equipo' => 'equipo_id',
            'entrenamiento' => 'entrenamiento_id',
            'accesorio' => 'accesorio_id',
            'joya' => 'joya_id',
            default => null,
        };

        if (! $campoEquipadoId) {
            return;
        }

        // Quitar stats de objeto anterior si hay
        if ($this->personaje->$campoEquipadoId) {
            $objetoAnterior = Objeto::find($this->personaje->$campoEquipadoId);
            if ($objetoAnterior && is_array($objetoAnterior->stats)) {
                foreach ($objetoAnterior->stats as $stat => $valor) {
                    if (isset($this->personaje->$stat)) {
                        $this->personaje->$stat -= $valor;
                    }
                }
            }
        }

        // Sumar stats del nuevo objeto
        foreach ($objeto->stats as $stat => $valor) {
            if (isset($this->personaje->$stat)) {
                $this->personaje->$stat += $valor;
            }
        }

        // Guardar objeto equipado
        $this->personaje->$campoEquipadoId = $objeto->id;
        $this->personaje->save();

        // "Mis personajes": el set queda desbloqueado recién cuando tiene las 3 partes de ese set equipadas a la vez
        $partesDelSet = $this->personaje->fresh(['equipo', 'entrenamiento', 'accesorio']);
        $setCompleto = $objeto->origen_post_id
            && $partesDelSet->equipo?->origen_post_id === $objeto->origen_post_id
            && $partesDelSet->entrenamiento?->origen_post_id === $objeto->origen_post_id
            && $partesDelSet->accesorio?->origen_post_id === $objeto->origen_post_id;
        if ($setCompleto) {
    // Se mira la tabla del historial directo: la relación oculta a los personajes especiales y daba "no usado" → duplicado
    $yaUsado = \Illuminate\Support\Facades\DB::table('personaje_post_historial')
        ->where('personaje_id', $this->personaje->id)->where('post_id', $objeto->origen_post_id)->exists();
    if (! $yaUsado) {
        $this->personaje->postsUsados()->attach($objeto->origen_post_id);
    }
}

// Actualizar inventario y relaciones sin recargar todo el modelo
        $this->actualizarObjetos();
        $this->reloadInventario++;
        $this->personaje = $this->personaje->fresh(['equipo', 'entrenamiento', 'accesorio']);

        $this->objetoEquipado     = $objeto;
        $this->objetoSeleccionado = null;

       

    }

    public function abrirModalTirarObjeto($objetoId)
    {
        $this->objetosSeleccionadosParaTirar = [];
        $this->objetoParaTirarId             = $objetoId;
        $this->objetoSeleccionado            = $this->procesarObjeto($this->miObjeto($objetoId));
        $this->modalTirarObjetosAbierto      = true;
    }

// Abre modal para tirar varios
    public function abrirModalTirarObjetosSeleccionados()
    {
        if (count($this->objetosSeleccionadosParaTirar) > 0) {
            $this->objetoSeleccionado       = null; // Asegurarte de que no quede el objeto de otro modal
            $this->modalTirarObjetosAbierto = true;
        }
    }

// Cerrar modal y limpiar todo
    public function cerrarModalTirarObjetos()
    {
        $this->modalTirarObjetosAbierto      = false;
        $this->objetoParaTirarId             = null;
        $this->objetoSeleccionado            = null;
        $this->objetosSeleccionadosParaTirar = []; // limpia selección múltiple también aquí
        $this->mensajeOroObtenido            = null;
    }

    public function calcularOroPorObjeto($objeto)
    {
        if (! $objeto) {
            return 0;
        }

        $nivel = $objeto->nivel ?? 1;
        return 10 + ($nivel * 2); // Ajustá la fórmula si querés otro valor
    }

// Confirmar tirar (uno o varios)
    public function confirmarTirarObjeto()
    {
        $oroGanado = 0;

        if ($this->objetoParaTirarId) {
            $objeto = $this->miObjeto($this->objetoParaTirarId);
            if (! $objeto) {
                return;
            }

            if ($this->personaje->objeto_consumible_id === $objeto->id) {
                $this->personaje->objeto_consumible_id = null;

                if (is_array($objeto->stats)) {
                    foreach ($objeto->stats as $stat => $valor) {
                        if (isset($this->personaje->$stat)) {
                            $this->personaje->$stat -= $valor;
                        }
                    }
                }
            }

            $oroGanado = $this->calcularOroPorObjeto($objeto);
            $this->personaje->oro += $oroGanado;
            $this->personaje->save();

            $objeto->delete();
            $this->objetoParaTirarId = null;
        } else {
            $oroGanado = $this->tirarObjetosSeleccionados(); // 🔑 Modificamos este para devolver el oro ganado
        }

        $this->mensajeOroObtenido       = "🪙 Obtuviste " . number_format($oroGanado, 0, ',', '.') . " de oro.";
        $this->modalTirarObjetosAbierto = false;
        $this->objetoSeleccionado       = null;
        $this->actualizarObjetos();
        $this->reloadInventario++;
    }
    public function tirarObjetoDesdeModal($objetoId)
    {
        $this->objetoParaTirarId = $objetoId;
        $this->confirmarTirarObjeto();
        $this->reloadInventario++;
    }

    public function tirarObjetosSeleccionados()
    {
        if (empty($this->objetosSeleccionadosParaTirar)) {
            return 0;
        }

        $oroTotal = 0;

        foreach ($this->objetosSeleccionadosParaTirar as $objetoId) {
            $objeto = $this->miObjeto($objetoId);
            if ($objeto) {
                if ($this->personaje->objeto_consumible_id === $objeto->id) {
                    $this->personaje->objeto_consumible_id = null;

                    if (is_array($objeto->stats)) {
                        foreach ($objeto->stats as $stat => $valor) {
                            if (isset($this->personaje->$stat)) {
                                $this->personaje->$stat -= $valor;
                            }
                        }
                    }
                }

                $oroTotal += $this->calcularOroPorObjeto($objeto);
                $objeto->delete();
            }
        }

        $this->personaje->oro += $oroTotal;
        $this->personaje->save();

        $this->objetosSeleccionadosParaTirar = [];
        $this->modalTirarObjetosAbierto      = false;

        $this->actualizarObjetos();
        $this->cargarPociones();

        $this->reloadInventario++;

        return $oroTotal; // 🔑 Ahora devuelve el total de oro ganado

    }

    public function abrirModalDesequipar($tipo)
    {
        $this->tipoDesequipar = $tipo;

        switch ($tipo) {
            case 'equipo':
                $this->objetoADesequipar = $this->personaje->equipo;
                break;
            case 'entrenamiento':
                $this->objetoADesequipar = $this->personaje->entrenamiento;
                break;
            case 'accesorio':
                $this->objetoADesequipar = $this->personaje->accesorio;
                break;
            case 'joya':
                $this->objetoADesequipar = $this->personaje->joya;
                break;
        }

        $this->modalDesequiparAbierto = true;
    }

    public function cerrarModalDesequipar()
    {
        $this->modalDesequiparAbierto = false;
        $this->tipoDesequipar         = null;
    }

    protected function desequiparEquipoReal()
    {
        if ($this->personaje->equipo) {
            if (is_array($this->personaje->equipo->stats)) {
                foreach ($this->personaje->equipo->stats as $stat => $valor) {
                    if (isset($this->personaje->$stat)) {
                        $this->personaje->$stat -= $valor;
                    }
                }
            }
            $this->personaje->equipo_id = null;
            $this->personaje->save();
            $this->personaje = $this->personaje->fresh(['equipo', 'entrenamiento', 'accesorio']);
            $this->actualizarObjetos();
        }
    }

    protected function desequiparEntrenamientoReal()
    {
        if ($this->personaje->entrenamiento) {
            if (is_array($this->personaje->entrenamiento->stats)) {
                foreach ($this->personaje->entrenamiento->stats as $stat => $valor) {
                    if (isset($this->personaje->$stat)) {
                        $this->personaje->$stat -= $valor;
                    }
                }
            }
            $this->personaje->entrenamiento_id = null;
            $this->personaje->save();
            $this->personaje = $this->personaje->fresh(['equipo', 'entrenamiento', 'accesorio']);
            $this->actualizarObjetos();

        }
    }

    protected function desequiparAccesorioReal()
    {
        if ($this->personaje->accesorio) {
            if (is_array($this->personaje->accesorio->stats)) {
                foreach ($this->personaje->accesorio->stats as $stat => $valor) {
                    if (isset($this->personaje->$stat)) {
                        $this->personaje->$stat -= $valor;
                    }
                }
            }
            $this->personaje->accesorio_id = null;
            $this->personaje->save();
            $this->personaje = $this->personaje->fresh(['equipo', 'entrenamiento', 'accesorio']);
            $this->actualizarObjetos();
        }
    }
    public function confirmarDesequipar()
    {
        if (! $this->tipoDesequipar) {
            return;
        }

        switch ($this->tipoDesequipar) {
            case 'equipo':
                $this->desequiparEquipoReal();
                break;
            case 'entrenamiento':
                $this->desequiparEntrenamientoReal();
                break;
            case 'accesorio':
                $this->desequiparAccesorioReal();
                break;
            case 'joya':
                $this->personaje->joya_id = null;
                $this->personaje->save();
                $this->personaje = $this->personaje->fresh(['equipo', 'entrenamiento', 'accesorio', 'joya']);
                $this->actualizarObjetos();
                break;
        }

        $this->cerrarModalDesequipar();
        $this->reloadInventario++;

    }

    public function abrirModalGuardarOro()
    {
        $this->modalGuardarOroAbierto = true;
    }

    public function cerrarModalGuardarOro()
    {
        $this->modalGuardarOroAbierto = false;
    }

    public function guardarOro()
    {
        $cantidad = intval($this->oroAGuardar);

        if ($cantidad > 0 && $cantidad <= $this->personaje->oro) {
            $this->personaje->oro_guardado += $cantidad;
            $this->personaje->oro -= $cantidad;
            $this->personaje->save();
            $this->personaje->refresh();
        }

        $this->oroAGuardar            = 0;
        $this->modalGuardarOroAbierto = false;
        $this->dispatch('refreshComponent');
    }

    public function retirarOro()
    {
        $cantidad = intval($this->oroARetirar);

        if ($cantidad > 0 && $cantidad <= $this->personaje->oro_guardado) {
            $this->personaje->oro_guardado -= $cantidad;
            $this->personaje->oro += $cantidad;
            $this->personaje->save();
            $this->personaje->refresh();
        }

        $this->oroARetirar            = 0;
        $this->modalGuardarOroAbierto = false;
        $this->dispatch('refreshComponent');
    }

    public function venderObjeto($objetoId)
    {
        // Debug para verificar que llegan los datos
        // dd([
        //     'ID objeto' => $objetoId,
        //     'Precio venta' => $this->precioVenta,
        // ]);

        // 1. Validar que el precio sea válido
        if (! is_numeric($this->precioVenta) || $this->precioVenta <= 0) {
            session()->flash('error', 'Precio inválido. Por favor, ingresá un precio mayor a 0.');
            return;
        }

        // 2. Buscar el objeto por ID
        $objeto = $this->miObjeto($objetoId);

        if (! $objeto) {
            session()->flash('error', 'Objeto no encontrado.');
            return;
        }

        if ($objeto->tipo === 'cofre') {
            session()->flash('error', 'Los cofres no se pueden vender.');
            return;
        }

        // 3. Guardar el precio de venta
        $objeto->precio_venta = $this->precioVenta;
        $objeto->save();

        // 4. Limpiar la variable de precio venta para la próxima vez
        $this->precioVenta = null;

        // 5. Cerrar modal o limpiar selección
        $this->cerrarModal();

        // 6. Refrescar lista de objetos para que se actualice el estado
        $this->actualizarObjetos();
        $this->reloadInventario++;

    }

    public function quitarVentaObjeto($objetoId)
    {
        $objeto = $this->miObjeto($objetoId);
        if (! $objeto) {
            $this->dispatch('error', ['message' => 'Objeto no encontrado']);
            return;
        }

        $objeto->precio_venta = null;
        $objeto->save();

        if ($this->objetoSeleccionado && $this->objetoSeleccionado->id == $objetoId) {
            $this->objetoSeleccionado = $this->procesarObjeto($objeto);
        }

        $this->dispatch('success', ['message' => 'Objeto retirado de la venta']);
        $this->cerrarModal();
        $this->actualizarObjetos();
        $this->reloadInventario++;
    }

    public function render()
    {
        $objetosNormales = Objeto::where('personaje_id', $this->personaje->id)
            ->where('pocion', false)
            ->get();

        $grupos = $objetosNormales->groupBy('origen_post_id');

        $pocionesRaw = Objeto::where('personaje_id', $this->personaje->id)
            ->where('pocion', true)
            ->get();

        // Procesar pociones para decodificar stats, etc.
        $pociones = $pocionesRaw->map(fn($obj) => $this->procesarObjeto($obj));

        return view('livewire.inventario', [
            // Sets para la herramienta de admin (agregarse partes)
            'setsAdmin'          => $this->modalAgregarParteAbierto ? Post::conRivales()->orderBy('nivel')->orderBy('titulo')->get(['id', 'titulo', 'nivel', 'imagen', 'tipo', 'es_enemigo']) : collect(),
            'personaje'          => $this->personaje,
            'objetos'            => $this->objetos,
            'objetoEquipado'     => $this->objetoEquipado,
            'objetoSeleccionado' => $this->objetoSeleccionado,
            'objetosDisponibles' => $this->objetosDisponibles,
            'objetosGuardados'   => $this->objetosGuardados,
            'oroGuardado'        => $this->personaje->oro_guardado,
            'clan'               => $this->clan,
            'inventarioClan'     => $this->inventarioClan,

            'grupos'             => $grupos,
            'pociones'           => $pociones,
        ]);
    }

}
