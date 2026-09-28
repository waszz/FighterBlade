<div>
    <h1 class="text-3xl font-bold text-center mb-4">Últimas Noticias</h1>
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

                <p>{{ Str::limit(strip_tags($noticia->contenido), 200, '...') }}</p>
                <!-- Enlace a la noticia completa -->
                <a href="{{ route('news.show', $noticia->id) }}" class="text-indigo-600 hover:text-indigo-800">Ver noticia completa</a>
            </li>
        @endforeach
    </ul>
</div>