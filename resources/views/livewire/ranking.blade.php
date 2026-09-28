<div class="w-full max-w-7xl mx-auto bg-gray-900 bg-opacity-80 rounded-xl p-3 sm:p-5 shadow-2xl text-white">
  <h2 class="text-2xl font-bold text-yellow-300 mb-4 text-center">Ranking</h2>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
    {{-- Nivel --}}
    <div class="bg-gray-800 rounded-lg p-3 flex flex-col">
      <h3 class="text-base font-semibold text-yellow-400 text-center mb-2">Nivel</h3>
      <livewire:ranking-list :ranking="$rankingNivel" tipo="Nivel" :personaje="auth()->user()->personaje" />
    </div>

    {{-- PvP --}}
    <div class="bg-gray-800 rounded-lg p-3 flex flex-col">
      <h3 class="text-base font-semibold text-yellow-400 text-center mb-2">PvP</h3>
      <livewire:ranking-list :ranking="$rankingPvp" tipo="PvP" :personaje="auth()->user()->personaje" />
    </div>

    {{-- PvE --}}
    <div class="bg-gray-800 rounded-lg p-3 flex flex-col">
      <h3 class="text-base font-semibold text-yellow-400 text-center mb-2">PvE</h3>
      <livewire:ranking-list :ranking="$rankingPve" tipo="PvE" :personaje="auth()->user()->personaje" />
    </div>

    {{-- Clanes --}}
    <div class="bg-gray-800 rounded-lg p-3 flex flex-col">
      <h3 class="text-base font-semibold text-yellow-400 text-center mb-2">Clanes</h3>
      <livewire:ranking-list :ranking="$rankingClanes" tipo="Clanes" />
    </div>

    {{-- Últimos campeones: los últimos en llegar al nivel 100 --}}
    <div class="rounded-lg p-3 flex flex-col border border-yellow-500/60 bg-gradient-to-b from-[#3a2a08] to-gray-800 shadow-[0_0_12px_rgba(250,204,21,0.25)]">
      <h3 class="text-base font-semibold text-yellow-300 text-center mb-2">👑 Últimos campeones</h3>
      <livewire:ranking-list :ranking="$rankingCampeones" tipo="Campeones" :personaje="auth()->user()->personaje" />
    </div>
  </div>
</div>
