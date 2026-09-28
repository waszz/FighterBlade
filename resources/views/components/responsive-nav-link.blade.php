@props(['active'])

@php
// Colores claros: el menú del celular tiene fondo oscuro (los grises de Breeze no se leían)
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-yellow-400 text-start text-base font-medium text-yellow-300 bg-white/10 focus:outline-none focus:bg-white/15 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-200 hover:text-white hover:bg-white/5 hover:border-gray-400 focus:outline-none focus:text-white focus:bg-white/5 focus:border-gray-400 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
