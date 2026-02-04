<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;900&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

        @vite(['resources/js/app.js'])
       
    </head>

    <body class="font-open-sans bg-white text-gray-700 leading-relaxed">

    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                 <a href="/" class="no-underline">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-primary-custom rounded-lg flex items-center justify-center">
                            <span class="text-white font-montserrat font-black text-xl">C</span>
                        </div>
                        <span class="font-montserrat font-black text-xl text-gray-800">ConectaIFPA</span>
                    </div>
                 </a>
                
                
                <!-- Navigation Desktop -->
                <div class="hidden md:flex items-center w-full justify-end gap-6">

                    <!-- Links principais -->
                    <div class="flex items-center gap-5">
                        <a href="/#eventos"
                        class="text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline transition">
                            Eventos
                        </a>

                        @auth
                            @if(auth()->user()->isCoordinator())
                                <a href="/dashboard"
                                class="text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline transition">
                                    Dashboard
                                </a>
                            @endif

                            @if(auth()->user()->isParticipant())
                                <a href="/dashboard"
                                class="text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline transition">
                                    Minha Área
                                </a>
                            @endif
                        @endauth
                    </div>

                    @if(auth()->check() && auth()->user()->isCoordinator())
                        <a href="/events/create"
                        class="px-3 py-1.5 rounded-md bg-primary-custom text-white text-sm font-semibold no-underline hover:opacity-90 transition">
                            Novo evento
                        </a>
                    @endif

                    @auth
                        <!-- Área do usuário -->
                        <div class="flex items-center gap-4 ml-4 pl-4 border-l border-gray-200">
                            <form action="/logout" method="POST">
                                @csrf
                                <button type="submit"
                                    class="text-sm font-medium text-gray-500 hover:text-red-600 transition"
                                    id="sair">
                                    Sair
                                </button>
                            </form>
                        </div>
                    @endauth

                    @guest
                        <!-- Ações -->
                        <div class="flex items-center gap-3 ml-4 pl-4 border-l border-gray-200">
                            <a href="/login"
                            class="text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline transition">
                                Entrar
                            </a>

                            <a href="/register"
                            class="px-3 py-1.5 rounded-md border border-gray-300 text-sm font-semibold text-gray-700 no-underline hover:bg-gray-100 transition">
                                Criar conta
                            </a>
                        </div>
                    @endguest
                </div>

                <!-- APLICAR LOGICA DE PERMISSÃO NO MOBILE TAMBEM -->
                
                <!-- Mobile Menu Button -->
                <button id="menu-toggle" class="md:hidden p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu"
                class="md:hidden hidden mt-4 pt-4 border-t border-gray-200 bg-white">

                <div class="flex flex-col gap-4">

                    <!-- Links principais -->
                    <a href="/#eventos"
                    class="text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline transition">
                        Eventos
                    </a>

                    @auth
                        @if(auth()->user()->isCoordinator())
                            <a href="/dashboard"
                            class="text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline transition">
                                Dashboard
                            </a>
                        @endif

                        @if(auth()->user()->isParticipant())
                            <a href="/dashboard"
                            class="text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline transition">
                                Minha Área
                            </a>
                        @endif
                    @endauth

                    @if(auth()->check() && auth()->user()->isCoordinator())
                        <a href="/events/create"
                        class="mt-2 px-4 py-2 rounded-md bg-primary-custom text-white text-sm font-semibold text-center no-underline hover:opacity-90 transition">
                            Novo evento
                        </a>
                    @endif

                    @guest
                        <div class="flex flex-col gap-2 pt-3 border-t border-gray-200">
                            <a href="/login"
                            class="text-sm font-semibold text-gray-700 hover:text-gray-900 no-underline text-center transition">
                                Entrar
                            </a>

                            <a href="/register"
                            class="px-4 py-2 rounded-md border border-gray-300 text-sm font-semibold text-gray-700 text-center no-underline hover:bg-gray-100 transition">
                                Criar conta
                            </a>
                        </div>
                    @endguest

                    @auth
                        <!-- Logout separado -->
                        <div class="pt-3 border-t border-gray-200">
                            <form action="/logout" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left text-sm font-medium text-gray-500 hover:text-red-600 transition"
                                    id="sair">
                                    Sair
                                </button>
                            </form>
                        </div>
                    @endauth

                </div>
            </div>

        </nav>
    </header>

    <main>
        @if (session('msg'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            text: "{{ session('msg') }}",
                        })
                    }
                })
            </script>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-9">
    <div class="container mx-auto text-center">
        <p class="text-gray-300 text-sm m-0">
            © 2025 ConectaIFPA. Todos os direitos reservados. Feito com carinho para a comunidade universitária.
        </p>
    </div>
</footer>

    <!-- ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
