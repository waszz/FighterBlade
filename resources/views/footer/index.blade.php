<footer class="bg-gray-900 text-gray-200 py-8 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Redes Sociales y Contacto -->
        <div class="flex flex-col md:flex-row justify-between items-center text-center md:text-left gap-8 mb-8">
            
            <!-- Redes Sociales -->
            <div>
                <h2 class="text-xl font-bold mb-4">Redes Sociales</h2>
                <div class="flex flex-wrap justify-center md:justify-start gap-4 text-gray-400 text-xl">
                    <a href="https://www.instagram.com/_jenifer.uy?igsh=MW1wc2V5OG42NnFiag%3D%3D&utm_source=qr" class="hover:text-gray-300" title="Instagram" target="_blank">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.tiktok.com/@jenifer.uy?_t=ZM-8vnw1ZLG0t3&_r=1" class="hover:text-gray-300" title="TikTok" target="_blank">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="https://www.facebook.com/tuusuario" class="hover:text-gray-300" title="Facebook" target="_blank">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="mailto:ferruiz017@gmail.com" class="hover:text-gray-300" title="Correo">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <!-- Información de Contacto -->
            <div>
                <h2 class="text-xl font-bold mb-4">Contacto</h2>
                <ul class="text-gray-400 space-y-2">
                    <li><i class="fas fa-phone mr-2 text-pink-300"></i><a href="tel:+59891234567">+598 (tu número)</a></li>
                    <li><i class="fas fa-map-marker-alt mr-2 text-pink-300"></i>Montevideo, Uruguay</li>
                    <li><i class="fas fa-envelope mr-2 text-pink-300"></i><a href="mailto:">tucorreo@correo.com</a></li>
                </ul>
            </div>
        </div>

        <!-- Logo -->
        <div class="flex flex-col md:flex-row items-center md:justify-between text-center md:text-left gap-4 mb-4">
            <p class="text-gray-400 text-sm max-w-sm">
                Desarrollado por MasDigital.
            </p>
            <a href="https://masdigital.com.uy/" target="_blank">
                <img src="{{ asset('storage/images/logo.png') }}" alt="Logo" class="h-12 w-auto">
            </a>
        </div>

        <!-- Derechos -->
        <div class="border-t border-gray-700 pt-6 text-center text-sm text-gray-500">
            © {{ date('Y') }} Tu Refugio. Todos los derechos reservados.
        </div>
    </div>
</footer>