<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title')</title>
        @include('partials.favicon')

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;900&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

        @stack('head')
        @vite(['resources/js/app.js'])
       
    </head>

    <body class="font-open-sans dash-page leading-relaxed">

    <!-- Header -->
    <header class="site-header sticky top-0 z-50">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Mobile: logo + hambúrguer. Acessibilidade no painel #mobile-menu. --}}
            <div class="flex items-center justify-between gap-3 min-w-0 w-full min-h-16">
                <div class="flex items-center gap-3 sm:gap-5 min-w-0 flex-1 md:flex-initial">
                    <a href="/" class="flex items-center gap-2 sm:gap-2.5 no-underline group min-w-0 shrink-0">
                        @include('partials.brand-mark', ['class' => 'w-9 h-9 sm:w-10 sm:h-10'])
                        <span class="site-brand font-montserrat font-bold text-base sm:text-lg md:text-xl truncate min-w-0 max-w-[11rem] sm:max-w-none">ConectaIFPA</span>
                    </a>
                    <div class="hidden md:block min-w-0">
                        @include('partials.nav-a11y-toolbar', ['placement' => 'header'])
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-1 lg:gap-2 flex-shrink-0 min-w-0">
                    <a href="/#eventos"
                       class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                        Eventos
                    </a>
                    <a href="{{ route('certificates.lookup') }}"
                       class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                        Validar certificado
                    </a>

                    @auth
                        <a href="{{ route('profile.show') }}"
                           class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                            Minha conta
                        </a>
                        @if(auth()->user()->isCoordinator())
                            <a href="/dashboard"
                               class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                                Painel
                            </a>
                            @if(auth()->user()->hasIssuedCertificates())
                                <a href="{{ route('certificates.my') }}"
                                   class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                                    Meus certificados
                                </a>
                            @endif
                        @endif
                        @if(auth()->user()->isParticipant())
                            <details class="relative">
                                <summary class="site-nav-link list-none px-4 py-2 text-sm font-medium transition-colors cursor-pointer inline-flex items-center gap-1.5">
                                    Área do participante
                                    <ion-icon name="chevron-down-outline" class="text-base"></ion-icon>
                                </summary>
                                <div class="site-menu absolute right-0 mt-2 w-56 rounded-xl p-1 z-50">
                                    <a href="/dashboard"
                                       class="site-nav-link block px-3 py-2 text-sm transition-colors">
                                        Minha Área
                                    </a>
                                    <a href="{{ route('works.my-presentation') }}"
                                       class="site-nav-link block px-3 py-2 text-sm transition-colors">
                                        Minha apresentação
                                    </a>
                                    <a href="{{ route('certificates.my') }}"
                                       class="site-nav-link block px-3 py-2 text-sm transition-colors">
                                        Certificados
                                    </a>
                                </div>
                            </details>
                        @endif
                        @if(auth()->user()->isGuest() || auth()->user()->isOrganizer())
                            <a href="/dashboard"
                               class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                                Painel
                            </a>
                            <a href="{{ route('certificates.my') }}"
                               class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                                Certificados
                            </a>
                        @endif
                        @if(auth()->user()->isReviewer())
                            <a href="/dashboard"
                               class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                                Painel
                            </a>
                            <a href="{{ route('reviews.assigned') }}"
                               class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                                Avaliações
                            </a>
                            <a href="{{ route('certificates.my') }}"
                               class="site-nav-link px-4 py-2 text-sm font-medium transition-colors">
                                Certificados
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
                        <form method="POST" action="{{ route('logout') }}" class="ml-2">
                            @csrf
                            <button type="submit"
                                    class="site-logout px-4 py-2 text-sm font-medium transition-colors"
                                    id="sair-desktop">
                                Sair
                            </button>
                        </form>
                    @endauth

                    @guest
                        <a href="/login"
                           class="site-nav-link ml-2 px-4 py-2 text-sm font-medium transition-colors">
                            Entrar
                        </a>
                        <a href="/register"
                           class="site-nav-outline px-4 py-2 text-sm font-semibold transition-colors">
                            Criar conta
                        </a>
                    @endguest
                </div>

                <button id="menu-toggle" type="button"
                        class="site-icon-btn md:hidden shrink-0 p-2.5 transition-colors touch-manipulation"
                        aria-label="Menu"
                        aria-expanded="false"
                        aria-controls="mobile-menu">
                    <ion-icon name="menu-outline" class="text-2xl pointer-events-none"></ion-icon>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <div id="mobile-menu"
                 class="md:hidden hidden overflow-hidden transition-all duration-300 ease-out">
                <div class="site-drawer-rule border-t">
                    <div class="site-drawer-tools px-4 pt-4 pb-3" aria-labelledby="mobile-a11y-section-label">
                        <p class="site-drawer-label text-xs font-semibold uppercase tracking-wide mb-3 m-0" id="mobile-a11y-section-label">
                            Acessibilidade
                        </p>
                        @include('partials.nav-a11y-toolbar', ['placement' => 'drawer'])
                    </div>
                    <div class="py-4 space-y-1">
                    <a href="/#eventos"
                       class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                        <ion-icon name="calendar-outline" class="site-nav-icon text-xl"></ion-icon>
                        Eventos
                    </a>
                    <a href="{{ route('certificates.lookup') }}"
                       class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                        <ion-icon name="shield-checkmark-outline" class="site-nav-icon text-xl"></ion-icon>
                        Validar certificado
                    </a>

                    @auth
                        <a href="{{ route('profile.show') }}"
                           class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                            <ion-icon name="person-circle-outline" class="site-nav-icon text-xl"></ion-icon>
                            Minha conta
                        </a>
                        @if(auth()->user()->isCoordinator())
                            <a href="/dashboard"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="grid-outline" class="site-nav-icon text-xl"></ion-icon>
                                Painel
                            </a>
                            @if(auth()->user()->hasIssuedCertificates())
                                <a href="{{ route('certificates.my') }}"
                                   class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                    <ion-icon name="ribbon-outline" class="site-nav-icon text-xl"></ion-icon>
                                    Meus certificados
                                </a>
                            @endif
                        @endif
                        @if(auth()->user()->isParticipant())
                            <a href="/dashboard"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="person-outline" class="site-nav-icon text-xl"></ion-icon>
                                Minha Área
                            </a>
                            <a href="{{ route('works.my-presentation') }}"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="mic-outline" class="site-nav-icon text-xl"></ion-icon>
                                Minha apresentação
                            </a>
                            <a href="{{ route('certificates.my') }}"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="ribbon-outline" class="site-nav-icon text-xl"></ion-icon>
                                Certificados
                            </a>
                        @endif
                        @if(auth()->user()->isGuest() || auth()->user()->isOrganizer())
                            <a href="/dashboard"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="grid-outline" class="site-nav-icon text-xl"></ion-icon>
                                Painel
                            </a>
                            <a href="{{ route('certificates.my') }}"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="ribbon-outline" class="site-nav-icon text-xl"></ion-icon>
                                Certificados
                            </a>
                        @endif
                        @if(auth()->user()->isReviewer())
                            <a href="/dashboard"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="grid-outline" class="site-nav-icon text-xl"></ion-icon>
                                Painel
                            </a>
                            <a href="{{ route('reviews.assigned') }}"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="clipboard-outline" class="site-nav-icon text-xl"></ion-icon>
                                Avaliações
                            </a>
                            <a href="{{ route('certificates.my') }}"
                               class="site-nav-link flex items-center gap-3 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="ribbon-outline" class="site-nav-icon text-xl"></ion-icon>
                                Certificados
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
                        <div class="site-drawer-rule pt-3 mt-3 border-t space-y-2">
                            <a href="/login"
                               class="site-nav-link flex items-center justify-center gap-2 px-4 py-3 font-medium transition-colors">
                                <ion-icon name="log-in-outline"></ion-icon>
                                Entrar
                            </a>
                            <a href="/register"
                               class="site-nav-outline flex items-center justify-center gap-2 px-4 py-3 font-semibold transition-colors">
                                <ion-icon name="person-add-outline"></ion-icon>
                                Criar conta
                            </a>
                        </div>
                    @endguest

                    @auth
                        <div class="site-drawer-rule pt-3 mt-3 border-t">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="site-logout flex items-center gap-3 w-full px-4 py-3 font-medium transition-colors text-left"
                                        id="sair-mobile">
                                    <ion-icon name="log-out-outline" class="text-xl"></ion-icon>
                                    Sair
                                </button>
                            </form>
                        </div>
                    @endauth
                    </div>
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
    <footer class="site-footer mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="flex items-center gap-2.5">
                    @include('partials.brand-mark', ['class' => 'w-10 h-10'])
                    <span class="site-brand font-montserrat font-bold text-lg">ConectaIFPA</span>
                </div>
                <div class="flex flex-wrap gap-6 text-sm">
                    <a href="/#eventos" class="site-footer-link transition-colors">
                        Eventos
                    </a>
                    <a href="{{ route('certificates.lookup') }}" class="site-footer-link transition-colors">
                        Validar certificado
                    </a>
                    <a href="/" class="site-footer-link transition-colors">
                        Início
                    </a>
                </div>
            </div>
            <div class="site-footer-rule mt-8 pt-8">
                <p class="site-footer-copy text-sm m-0">
                    © {{ date('Y') }} ConectaIFPA. Todos os direitos reservados. Feito com carinho para a comunidade universitária.
                </p>
            </div>
        </div>
    </footer>

    @stack('scripts')
    <!-- ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    @include('partials.vlibras')
</body>
</html>
