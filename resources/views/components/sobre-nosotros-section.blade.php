<!-- Sección Sobre mí -->
<section id="sobre-nosotros" class="bg-gray-50 py-16 px-4">
    <div class="max-w-7xl mx-auto text-center">
        <h2 class="text-3xl font-extrabold text-gray-800 mb-8">Nuestra Historia</h2>
        <div class="lg:flex lg:items-center lg:justify-between space-y-8 lg:space-y-0">
            <!-- Imagen -->
            <div class="lg:w-1/2 overflow-hidden rounded-lg shadow-lg">
                <img
                    src="{{ asset('images/logo.png') }}" 
                    alt="Imagen del Refugio"
                    class="w-full h-72 object-cover transform transition-transform duration-500 hover:scale-105"
                >
            </div>
            
            <!-- Descripción -->
            <div class="lg:w-1/2 space-y-6">
                <p id="short-text" class="text-lg text-gray-700 leading-relaxed">
                    En nuestro refugio, creemos que todos los animales merecen una segunda oportunidad para encontrar un hogar lleno de amor y cuidado...
                </p>
                <p id="full-text" class="text-lg text-gray-700 leading-relaxed hidden">
                    En nuestro refugio, creemos que todos los animales merecen una segunda oportunidad para encontrar un hogar lleno de amor y cuidado. Desde nuestros inicios, nos hemos dedicado a proporcionar un entorno seguro y afectuoso para perros y gatos rescatados, ofreciéndoles la atención que necesitan mientras esperan ser adoptados por familias responsables. Fundado en 2010, nuestro refugio ha sido testigo de innumerables historias de superación y amor incondicional. Con el apoyo de voluntarios, donaciones y adopciones responsables, hemos logrado transformar la vida de miles de animales y conectar a cada uno de ellos con hogares que les brindan el cariño y la atención que se merecen. A través de nuestros programas educativos y eventos de adopción, buscamos concientizar a la comunidad sobre la importancia de la adopción responsable y el bienestar animal. Gracias al apoyo de todos ustedes, cada día podemos seguir adelante con nuestra misión de salvar vidas y darles una segunda oportunidad.
                </p>
                
                <!-- Botón Leer más / Leer menos -->
                <button id="toggle-button" class="text-indigo-600 hover:text-indigo-800 font-medium transition">
                    Leer más →
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Scroll suave global -->
<style>
    html {
        scroll-behavior: smooth;
    }
</style>