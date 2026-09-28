{{-- Fila de iconos: tipo de daño y poderes. Al pasar el mouse o tocarlos muestran nombre y descripción --}}
@if ($tipoIconos || collect($poderesIconos)->isNotEmpty())
<div class="flex flex-wrap items-center justify-center gap-1 mb-1.5">
    @if ($tipoIconos)
        <x-icono-tipo :tipo="$tipoIconos" tam="w-7 h-7" class="cursor-pointer" />
    @endif
    @foreach ($poderesIconos ?? [] as $poder)
        <x-icono-poder :poder="$poder" tam="w-7 h-7" class="cursor-pointer" />
    @endforeach
</div>
@endif
