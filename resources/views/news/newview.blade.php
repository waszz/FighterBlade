<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nueva Vista de Noticias
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Aquí mostramos las noticias de la manera que prefieras -->
                <h1 class="text-3xl font-bold text-center mb-4">Últimas Noticias - Nueva Vista</h1>
                <ul>
                    @foreach ($news as $noticia)
                        <li class="border-b py-4">
                            <h2 class="font-semibold text-lg">{{ $noticia->titulo }}</h2>

                            <!-- Mostrar la imagen si existe -->
                            @if($noticia->imagen)
                                <div class="my-3">
                                    <img src="{{ asset('storage/news/' . $noticia->imagen) }}" alt="{{ $noticia->titulo }}" class="w-full h-auto rounded-lg">
                                </div>
                            @endif

                            <p>{{ $noticia->contenido }}</p>
                            <a href="{{ route('news.show', $noticia->id) }}" class="text-indigo-600 hover:text-indigo-800">Ver noticia completa</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

</x-app-layout>