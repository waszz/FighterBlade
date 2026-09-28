<nav x-data="{ open: false }" class="bg-gray-800 border-b border-gray-700">
    <!-- Contenedor principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo y menús principales -->
            <div class="flex items-center space-x-8">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Menús principales visibles en pantalla grande -->
                <div class="hidden sm:flex space-x-6 items-center">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">Inicio</x-nav-link>
                    

                    @auth
                        @if(auth()->user()->is_admin)
                            <!-- Dropdown Administración -->
                            <div x-data="{ openAdmin: false }" class="relative">
                                <x-nav-link href="#" @click.prevent="openAdmin = !openAdmin" class="cursor-pointer flex items-center gap-2">
                                    Administración
                                    <svg :class="{'rotate-180': openAdmin}" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </x-nav-link>

                                <div x-show="openAdmin" @click.outside="openAdmin = false" x-transition class="absolute mt-2 w-48 bg-white border rounded shadow z-50">
                                    <a href="{{ route('posts.index') }}" class="block px-4 py-2 hover:bg-gray-100">Mis Personajes</a>
                                    <a href="{{ route('posts.create') }}" class="block px-4 py-2 hover:bg-gray-100">Crear Personajes</a>
                                    <a href="{{ route('personajes.especiales') }}" class="block px-4 py-2 hover:bg-gray-100">Personajes especiales</a>
                                    <a href="{{ route('news.index') }}" class="block px-4 py-2 hover:bg-gray-100">Mis Ciudades</a>
                                    <a href="{{ route('news.create') }}" class="block px-4 py-2 hover:bg-gray-100">Crear Ciudades</a>
                                    <a href="{{ route('admin.anuncios') }}" class="block px-4 py-2 hover:bg-gray-100">Anuncios</a>                                </div>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Usuario y hamburguesa -->
            <div class="flex items-center space-x-4">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                                {{ Auth::user()->name }}
                                <svg class="ml-2 w-4 h-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Cerrar Sesión
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <x-nav-link :href="route('login')">Iniciar Sesión</x-nav-link>
                    <x-nav-link :href="route('register')">Crear Cuenta</x-nav-link>
                @endauth

                <!-- Botón hamburguesa -->
                <div class="sm:hidden">
                    <button @click="open = !open" class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Menú responsive -->
    <div :class="{'block': open, 'hidden': !open}" class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('home')">Inicio</x-responsive-nav-link>
            <x-responsive-nav-link href="#sobre-nosotros">Sobre Nosotros</x-responsive-nav-link>

            <!-- Todas las mascotas en móvil -->
            <div x-data="{ openMascotasMobile: false }" class="px-4">
                <button @click="openMascotasMobile = !openMascotasMobile" class="w-full text-left text-gray-700 py-2 flex justify-between items-center">
                    Todas las Mascotas
                    <svg :class="{ 'rotate-180': openMascotasMobile }" class="w-4 h-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openMascotasMobile" class="px-4">
                    <x-responsive-nav-link href="{{ route('public.gatos') }}">🐱 Gatos</x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('public.perros') }}">🐶 Perros</x-responsive-nav-link>
                    <x-responsive-nav-link href="{{ route('public.adoptados') }}">🏡 Adoptados</x-responsive-nav-link>
                </div>
            </div>

            @auth
                @if(auth()->user()->is_admin)
                <!-- Administración en móvil -->
                <div x-data="{ openAdminMobile: false }" class="px-4">
                    <button @click="openAdminMobile = !openAdminMobile" class="w-full text-left text-gray-700 py-2 flex justify-between items-center">
                        Administración
                        <svg :class="{ 'rotate-180': openAdminMobile }" class="w-4 h-4 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openAdminMobile" class="pl-4">
                        <x-responsive-nav-link :href="route('posts.index')">Mis Personajes</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('posts.create')">Crear Personajes</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('personajes.especiales')">Personajes especiales</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('news.index')">Mis Ciudades</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('news.create')">Crear Ciudades</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.anuncios')">Anuncios</x-responsive-nav-link>                    </div>
                </div>
                @endif

                <!-- Usuario -->
                <div class="border-t border-gray-200 mt-4 pt-4 px-4">
                    <div class="text-gray-800 font-semibold">{{ Auth::user()->name }}</div>
                    <div class="text-gray-500 text-sm">{{ Auth::user()->email }}</div>
                </div>
                <x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar Sesión</x-responsive-nav-link>
                </form>
            @else
                <x-responsive-nav-link :href="route('login')">Iniciar Sesión</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('register')">Crear Cuenta</x-responsive-nav-link>
            @endauth
        </div>
    </div>
</nav>