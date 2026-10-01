{{-- Punto rojo del botón del chat (celular). El último mensaje general visto se guarda en el navegador al abrir o cerrar
     el chat (evento 'chat-visto'). El wire:key cambia con los datos: así el punto se vuelve a armar con lo nuevo --}}
<span wire:poll.15s class="pointer-events-none absolute -top-1 -right-1">
    <span wire:key="aviso-chat-{{ $privados }}-{{ $ultimoGeneral }}"
          x-data="{
              ultimo: {{ $ultimoGeneral }},
              visto: (() => { try { return +(localStorage.getItem('chat-visto') || 0) } catch (e) { return 0 } })(),
              marcar() { this.visto = this.ultimo; try { localStorage.setItem('chat-visto', this.ultimo) } catch (e) {} }
          }"
          x-init="if (! visto) marcar()"
          @chat-visto.window="marcar()"
          x-show="{{ $privados }} > 0 || ultimo > visto" x-cloak
          class="block w-3.5 h-3.5 rounded-full border-2 border-black bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.9)] animate-pulse"></span>
</span>
