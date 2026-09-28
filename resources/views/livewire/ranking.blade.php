<div class="w-full max-w-xl mx-auto bg-gray-900 bg-opacity-80 rounded-xl p-3 sm:p-5 shadow-2xl text-white">
  <h2 class="text-2xl font-bold text-yellow-300 mb-3 text-center [text-shadow:0_2px_0_#000]">Ranking</h2>

  {{-- Pestañas: se ve una sola tabla a la vez --}}
  <div class="flex flex-wrap justify-center gap-1.5 mb-4">
    @foreach ($pestanas as $clave => [$titulo])
      <button type="button" wire:click="setPestana('{{ $clave }}')"
        class="px-3 py-1 rounded-md border-2 text-xs sm:text-sm font-semibold uppercase bg-gradient-to-b transition-all duration-100
               shadow-[inset_1px_1px_0_rgba(255,255,255,0.35),inset_-1px_-1px_0_rgba(0,0,0,0.6),0_3px_0_#000] active:translate-y-[3px] active:shadow-none hover:brightness-125
               {{ $pestana === $clave ? 'border-yellow-400 text-black from-yellow-300 to-yellow-600' : 'border-black text-yellow-400 from-neutral-700 to-black' }}">
        {{ $titulo }}
      </button>
    @endforeach
  </div>

  <div class="rounded-lg p-3 {{ $pestana === 'campeones' ? 'border border-yellow-500/60 bg-gradient-to-b from-[#3a2a08] to-gray-800 shadow-[0_0_12px_rgba(250,204,21,0.25)]' : 'bg-gray-800' }}">
    <h3 class="text-base font-semibold text-yellow-400 text-center mb-2">{{ $pestanas[$pestana][0] }}</h3>
    @if ($tipo === 'Clanes')
      <livewire:ranking-list :ranking="$ranking" :tipo="$tipo" :key="'ranking-' . $pestana" />
    @else
      <livewire:ranking-list :ranking="$ranking" :tipo="$tipo" :personaje="auth()->user()->personaje" :key="'ranking-' . $pestana" />
    @endif
  </div>
</div>
