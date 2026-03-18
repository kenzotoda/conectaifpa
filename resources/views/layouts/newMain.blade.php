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
    <header class="bg-white/95 backdrop-blur-sm border-b border-slate-200/80 sticky top-0 z-50">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2.5 no-underline group">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-primary-custom rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                        <span class="text-white font-montserrat font-black text-lg sm:text-xl">C</span>
                    </div>
                    <span class="font-montserrat font-bold text-slate-800 text-lg sm:text-xl">ConectaIFPA</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-1 lg:gap-2">
                    <a href="/#eventos"
                       class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 no-underline transition-colors">
                        Eventos
                    </a>

                    @auth
                        @if(auth()->user()->isCoordinator())
                            <a href="/dashboard"
                               class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 no-underline transition-colors">
                                Dashboard
                            </a>
                        @endif
                        @if(auth()->user()->isParticipant())
                            <a href="/dashboard"
                               class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 no-underline transition-colors">
                                Minha Área
                            </a>
                        @endif
                    @endauth

                    @if(auth()->check() && auth()->user()->isCoordinator())
                        <a href="/events/create"
                           class="ml-2 px-4 py-2 rounded-xl bg-primary-custom text-white text-sm font-semibold no-underline hover:opacity-90 transition-opacity">
                            Novo evento
                        </a>
                    @endif

                    @auth
                        <form action="/logout" method="POST" class="ml-2">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 rounded-lg text-sm font-medium text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    id="sair">
                                Sair
                            </button>
                        </form>
                    @endauth

                    @guest
                        <a href="/login"
                           class="ml-2 px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 no-underline transition-colors">
                            Entrar
                        </a>
                        <a href="/register"
                           class="px-4 py-2 rounded-xl border-2 border-primary-custom text-primary-custom text-sm font-semibold no-underline hover:bg-green-50 transition-colors">
                            Criar conta
                        </a>
                    @endguest
                </div>

                <!-- Mobile Menu Button -->
                <button id="menu-toggle" type="button"
                        class="md:hidden p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition-colors"
                        aria-label="Menu">
                    <ion-icon name="menu-outline" class="text-2xl"></ion-icon>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <div id="mobile-menu"
                 class="md:hidden hidden overflow-hidden transition-all duration-300 ease-out">
                <div class="py-4 space-y-1 border-t border-slate-200">
                    <a href="/#eventos"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-medium hover:bg-slate-100 no-underline transition-colors">
                        <ion-icon name="calendar-outline" class="text-xl text-slate-400"></ion-icon>
                        Eventos
                    </a>

                    @auth
                        @if(auth()->user()->isCoordinator())
                            <a href="/dashboard"
                               class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-medium hover:bg-slate-100 no-underline transition-colors">
                                <ion-icon name="grid-outline" class="text-xl text-slate-400"></ion-icon>
                                Dashboard
                            </a>
                        @endif
                        @if(auth()->user()->isParticipant())
                            <a href="/dashboard"
                               class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-medium hover:bg-slate-100 no-underline transition-colors">
                                <ion-icon name="person-outline" class="text-xl text-slate-400"></ion-icon>
                                Minha Área
                            </a>
                        @endif
                    @endauth

                    @if(auth()->check() && auth()->user()->isCoordinator())
                        <a href="/events/create"
                           class="flex items-center gap-3 px-4 py-3 rounded-xl bg-primary-custom text-white font-semibold no-underline hover:opacity-90 transition-opacity mt-2">
                            <ion-icon name="add-circle-outline" class="text-xl"></ion-icon>
                            Novo evento
                        </a>
                    @endif

                    @guest
                        <div class="pt-3 mt-3 border-t border-slate-200 space-y-2">
                            <a href="/login"
                               class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-slate-700 font-medium bg-slate-100 hover:bg-slate-200 no-underline transition-colors">
                                <ion-icon name="log-in-outline"></ion-icon>
                                Entrar
                            </a>
                            <a href="/register"
                               class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 border-primary-custom text-primary-custom font-semibold no-underline hover:bg-green-50 transition-colors">
                                <ion-icon name="person-add-outline"></ion-icon>
                                Criar conta
                            </a>
                        </div>
                    @endguest

                    @auth
                        <div class="pt-3 mt-3 border-t border-slate-200">
                            <form action="/logout" method="POST">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-3 w-full px-4 py-3 rounded-xl text-slate-500 font-medium hover:bg-red-50 hover:text-red-600 transition-colors text-left"
                                        id="sair">
                                    <ion-icon name="log-out-outline" class="text-xl"></ion-icon>
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
    <footer class="bg-slate-900 text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-primary-custom rounded-lg flex items-center justify-center">
                        <span class="font-montserrat font-black text-lg text-white">C</span>
                    </div>
                    <span class="font-montserrat font-bold text-lg">ConectaIFPA</span>
                </div>
                <div class="flex flex-wrap gap-6 text-sm">
                    <a href="/#eventos" class="text-slate-400 hover:text-white transition-colors no-underline">
                        Eventos
                    </a>
                    <a href="/" class="text-slate-400 hover:text-white transition-colors no-underline">
                        Início
                    </a>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-slate-700/50">
                <p class="text-slate-500 text-sm m-0">
                    © {{ date('Y') }} ConectaIFPA. Todos os direitos reservados. Feito com carinho para a comunidade universitária.
                </p>
            </div>
        </div>
    </footer>

    <!-- ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
