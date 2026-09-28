<x-app-layout>
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                 <!-- Botón para volver -->
                 <a href="{{ route('home') }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition duration-300 ease-in-out transform hover:scale-105 hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7M4 12h16"></path>
                    </svg>
                    Volver
                </a>
                <!-- Título de la noticia -->
                <h1 class="text-3xl font-bold mb-4 text-center">{{ $news->titulo }}</h1>

                <!-- Mostrar la imagen principal si existe -->
                @if($news->imagen)
                    <div class="my-6 flex justify-center">
                        <img src="{{ asset('storage/news/' . $news->imagen) }}" 
                            alt="{{ $news->titulo }}" 
                            class="max-w-3xl w-full h-auto rounded-xl shadow-md">
                    </div>
                @endif

                <!-- Contenido de la noticia -->
                <p class="text-lg mb-6">{{ $news->contenido }}</p>

                <!-- Galería de imágenes -->
                           
            @if($news->imagenes->count() > 0)
                <h3 class="text-2xl font-semibold mb-4">Mas fotos de esta Noticia</h3>
                <div 
                    x-data="{ modalOpen: false, imageUrl: '' }"
                    @keydown.escape.window="modalOpen = false"
                >
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        @foreach ($news->imagenes as $imagen)
                            <div class="relative group cursor-pointer" @click="modalOpen = true; imageUrl = '{{ asset('storage/news/gallery/' . $imagen->ruta) }}'">
                                <img src="{{ asset('storage/news/gallery/' . $imagen->ruta) }}" alt="Imagen" class="w-full h-32 object-cover rounded-lg shadow-md hover:scale-105 transition-transform duration-200">
                            </div>
                        @endforeach
                    </div>

                    <!-- Modal -->
                    <div
                        x-show="modalOpen"
                        class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-75"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                    >
                        <div class="max-w-4xl mx-auto p-4" @click.away="modalOpen = false">
                            <img :src="imageUrl" alt="Imagen grande" class="w-full max-h-[80vh] object-contain rounded shadow-lg">
                            <button @click="modalOpen = false" class="mt-4 text-white bg-red-600 px-4 py-2 rounded hover:bg-red-700">Cerrar</button>
                        </div>
                    </div>
                </div>
            @endif
               
            </div>
        </div>
    
    </div>
</x-app-layout>