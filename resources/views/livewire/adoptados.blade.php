<div class="min-h-screen py-8 px-4 bg-gray-100">
    <div class="max-w-7xl mx-auto mb-4 grid grid-cols-1 lg:grid-cols-4 gap-8">
        <div class="lg:col-span-3">
            <h1 class="text-4xl font-semibold text-center text-gray-800 mb-12">Post Adoptados</h1>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-20">
                @foreach ($posts as $post)
                    @if ($post->estado === 'adoptado') <!-- Filtrando por estado "adoptado" -->
                        <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden group">
                            <div class="relative overflow-hidden">
                                <img
                                    src="{{ asset('storage/posts/' . $post->imagen) }}"
                                    alt="{{ 'Imagen de ' . $post->titulo }}"
                                    class="w-full h-48 object-cover transform transition-transform duration-500 group-hover:scale-105"
                                >
                                <div class="absolute bottom-0 left-0 right-0 bg-pink-600 text-white text-center py-2 font-semibold uppercase">
                                    Adoptado
                                </div>
                            </div>
                            <div class="p-6 flex flex-col justify-between h-56">
                                <div>
                                    <h2 class="text-xl text-center font-semibold text-gray-800 mb-4">{{ $post->titulo }}</h2>

                                    <div class="text-center bottom-2 mb-2 left-2 bg-white bg-opacity-80 px-3 py-1 rounded-md text-xs text-gray-800 shadow-md">
                                        {{ \Carbon\Carbon::parse($post->created_at)->format('d M Y') }}
                                    </div>

                                    <p class="text-gray-600 text-sm line-clamp-4">{{ $post->descripcion }}</p>
                                </div>
                                <div class="mt-4 text-center">
                                    <a href="{{ route('posts.show', $post->id) }}"
                                       class="inline-block text-indigo-600 hover:text-indigo-800 font-medium transition duration-300 ease-in-out transform hover:scale-105">
                                        Leer más →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>