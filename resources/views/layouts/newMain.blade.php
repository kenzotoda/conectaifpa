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
                <div class="hidden md:flex items-center space-x-8">
                    <!-- <a href="/" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Início</a> -->
                    
                    <a href="/#eventos" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Ver Eventos</a>

                    @if(auth()->check() && auth()->user()->isCoordinator())
                        <a href="/events/create"
                        class="btn-primary px-4 py-2 rounded-lg font-montserrat font-semibold no-underline">
                            Criar Evento
                        </a>
                    @endif

                    @auth
                        @if(auth()->check() && auth()->user()->isCoordinator())
                            <a href="/dashboard" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Área do Coordenador</a>
                        @endif

                        @if(auth()->check() && auth()->user()->isParticipant())
                            <a href="/dashboard" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Área do Participante</a>
                        @endif
                        

                        <form action="/logout" method="POST">
                            @csrf
                            <button type="submit" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors" id="sair">Sair</button>
                        </form>
                    @endauth

                    @guest
                        <a href="/login" class="btn-outline px-4 py-2 rounded-lg font-montserrat font-semibold no-underline">Entrar</a>
                        <a href="/register" class="btn-primary px-4 py-2 rounded-lg font-montserrat font-semibold no-underline">Criar uma conta</a>    
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
            <div id="mobile-menu" class="md:hidden hidden mt-4 pb-4 border-t border-gray-200 pt-4">
                <div class="flex flex-col space-y-3">
                    <a href="/#eventos" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Ver Eventos</a>
                    
                    @if(auth()->check() && auth()->user()->isCoordinator())
                        <a href="/events/create" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Criar Evento</a>
                    @endif

                    @auth
                        @if(auth()->check() && auth()->user()->isCoordinator())
                            <a href="/dashboard" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Área do Coordenador</a>
                        @endif

                        @if(auth()->check() && auth()->user()->isParticipant())
                            <a href="/dashboard" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Área do Participante</a>
                        @endif
                        
                        <form action="/logout" method="POST">
                            @csrf
                            <button type="submit" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors" id="sair">Sair</button>
                        </form>
                    @endauth

                    @guest
                        <a href="/login" class="btn-outline px-4 py-2 rounded-lg font-montserrat font-semibold text-center no-underline">Entrar</a>

                        <a href="/register" class="btn-primary px-4 py-2 rounded-lg font-montserrat font-semibold text-center no-underline">Criar uma conta</a>
                    @endguest
                    
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
