<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>teste</title>

         <!-- Google Fonts   -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        @vite(['resources/js/app.js'])


        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;900&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

       
    </head>
    <body class="font-open-sans bg-white text-gray-700 leading-relaxed">

      <!-- Header   -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                  <!-- Logo   -->
                 <a href="/" class="no-underline">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-primary-custom rounded-lg flex items-center justify-center">
                            <span class="text-white font-montserrat font-black text-xl">C</span>
                        </div>
                        <span class="font-montserrat font-black text-xl text-gray-800">ConectaIFPA</span>
                    </div>
                 </a>
                
                
                  <!-- Navigation Desktop   -->
                <div class="hidden md:flex items-center space-x-8">
                    <!-- <a href="/" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Início</a>   -->
                    
                    <a href="/#eventos" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Ver Eventos</a>

                    @if(auth()->check() && auth()->user()->isCoordinator())
                        <a href="/events/create"
                        class="btn-primary px-4 py-2 rounded-lg font-montserrat font-semibold no-underline">
                            Criar Evento
                        </a>
                    @endif

                    @auth
                        <a href="/dashboard" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Meus Eventos</a>

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
                
                  <!-- Mobile Menu Button   -->
                <button class="md:hidden p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            
            <!-- ADICIONAR LOGICA DE PERMISSÃO AQUI -->
              <!-- Mobile Navigation   -->
            <div class="md:hidden mt-4 pb-4 border-t border-gray-200 pt-4">
                <div class="flex flex-col space-y-3">
                    <a href="/" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Eventos</a>
                    
                    <a href="/events/create" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Criar Eventos</a>

                    @auth
                        <a href="/dashboard" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors no-underline">Meus Eventos</a>
                        
                        <form action="/logout" method="POST">
                            @csrf
                            <button type="submit" class="font-montserrat font-semibold text-gray-700 hover:text-primary transition-colors" id="sair">Sair</button>
                        </form>
                    @endauth

                    @guest
                        <a href="/login" class="btn-outline px-4 py-2 rounded-lg font-montserrat font-semibold text-center no-underline">Entrar</a>

                        <a href="/register" class="btn-primary px-4 py-2 rounded-lg font-montserrat font-semibold text-center no-underline">Cadastrar</a>
                    @endguest
                    
                </div>
            </div>
        </nav>
    </header>

    <main>
         @if (session('msg'))
            <p class="msg">{{ session('msg') }}</p>
        @endif
         

        <!-- Redesigned mainContent with improved navigation and better responsiveness  -->
        <div id="mainContent" class="container mx-auto px-3 sm:px-4 lg:px-6 py-6 sm:py-8 lg:py-12 space-y-6 sm:space-y-8 lg:space-y-10 max-w-7xl">

          <!-- New horizontal tab navigation - cleaner and more modern  -->
          <div id="navigation-grid" class="w-full bg-white rounded-xl sm:rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <!-- Desktop Navigation  -->
            <div class="hidden sm:flex items-center border-b border-gray-200">
              <a href="#" class="nav-tab group flex-1 flex flex-col items-center justify-center py-4 px-3 lg:px-4 border-b-2 border-emerald-600 bg-emerald-50 text-emerald-700 transition-all duration-200 hover:bg-emerald-100 no-underline" data-page="home">
                <ion-icon name="home-outline" class="text-2xl lg:text-3xl mb-1.5 group-hover:scale-110 transition-transform duration-200"></ion-icon>
                <span class="text-xs lg:text-sm font-semibold text-center leading-tight">Página do Evento</span>
              </a>

              @guest
                  <a href="#" class="nav-tab group flex-1 flex flex-col items-center justify-center py-4 px-3 lg:px-4 border-b-2 border-transparent text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-300 no-underline" data-page="form">
                      <ion-icon name="document-text-outline" class="text-2xl lg:text-3xl mb-1.5 group-hover:scale-110 transition-transform duration-200"></ion-icon>
                      <span class="text-xs lg:text-sm font-semibold text-center leading-tight">Formulário</span>
                </a>
              @endguest

              @auth
                  @if(auth()->user()->isParticipant())
                      <a href="#" class="nav-tab group flex-1 flex flex-col items-center justify-center py-4 px-3 lg:px-4 border-b-2 border-transparent text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-300 no-underline" data-page="form">
                          <ion-icon name="document-text-outline" class="text-2xl lg:text-3xl mb-1.5 group-hover:scale-110 transition-transform duration-200"></ion-icon>
                          <span class="text-xs lg:text-sm font-semibold text-center leading-tight">Formulário</span>
                      </a>  
                  @endif
              @endauth

              
              <!-- PERMISSÃO APENAS PARA O CRIADOR DO EVENTO -->
              @auth
                  @if(auth()->user()->isCoordinator() && auth()->user()->id === $event->user_id)
                      <a href="#" class="nav-tab group flex-1 flex flex-col items-center justify-center py-4 px-3 lg:px-4 border-b-2 border-transparent text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-300 no-underline" data-page="participants">
                          <ion-icon name="people-outline" class="text-2xl lg:text-3xl mb-1.5 group-hover:scale-110 transition-transform duration-200"></ion-icon>
                          <span class="text-xs lg:text-sm font-semibold text-center leading-tight">Gerenciar Inscritos</span>
                      </a>    
                  @endif
              @endauth

              <!-- PERMISSÃO APENAS PARA O CRIADOR DO EVENTO -->
              @auth
                  @if(auth()->user()->isCoordinator() && auth()->user()->id === $event->user_id)
                      <a href="#" class="nav-tab group flex-1 flex flex-col items-center justify-center py-4 px-3 lg:px-4 border-b-2 border-transparent text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-300 no-underline" data-page="customize">
                          <ion-icon name="color-palette-outline" class="text-2xl lg:text-3xl mb-1.5 group-hover:scale-110 transition-transform duration-200"></ion-icon>
                          <span class="text-xs lg:text-sm font-semibold text-center leading-tight">Personalizar Evento</span>
                      </a>      
                  @endif
              @endauth
              
              <!-- PERMISSÃO APENAS PARA O CRIADOR DO EVENTO -->
              @auth
                  @if(auth()->user()->isCoordinator() && auth()->user()->id === $event->user_id)
                      <a href="#" class="nav-tab group flex-1 flex flex-col items-center justify-center py-4 px-3 lg:px-4 border-b-2 border-transparent text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-300 no-underline" data-page="news">
                          <ion-icon name="newspaper-outline" class="text-2xl lg:text-3xl mb-1.5 group-hover:scale-110 transition-transform duration-200"></ion-icon>
                          <span class="text-xs lg:text-sm font-semibold text-center leading-tight">Novidades</span>
                      </a>      
                  @endif
              @endauth

            <!-- PERMISSÃO APENAS PARA O PARTICIPANTE QUE SE INSCREVEU NO EVENTO -->
            @auth
                @if(\DB::table('event_user')->where('event_id', $event->id)->where('user_id', auth()->user()->id)->exists())
                    <a href="#" class="nav-tab group flex-1 flex flex-col items-center justify-center py-4 px-3 lg:px-4 border-b-2 border-transparent text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-emerald-600 hover:border-emerald-300 no-underline" data-page="myArea">
                        <ion-icon name="person-outline" class="text-2xl lg:text-3xl mb-1.5 group-hover:scale-110 transition-transform duration-200"></ion-icon>
                        <span class="text-xs lg:text-sm font-semibold text-center leading-tight">Minha Área</span>
                    </a>                    
                @endif
            @endauth


            </div>

            <!-- REPETIR A LÓGICA DE PERMISSÃO AQUI NA NAVEGAÇÃO MOBILE -->
             
            <!-- Mobile Navigation - Horizontal Scroll  -->
            <div class="sm:hidden overflow-x-auto scrollbar-hide">
              <div class="flex min-w-max border-b border-gray-200">
                <a class="nav-tab group flex flex-col items-center justify-center py-3 px-4 min-w-[100px] border-b-2 border-emerald-600 bg-emerald-50 text-emerald-700 transition-all duration-200 no-underline" data-page="home">
                  <ion-icon name="home-outline" class="text-2xl mb-1 group-active:scale-95 transition-transform duration-200"></ion-icon>
                  <span class="text-xs font-semibold text-center leading-tight whitespace-nowrap">Página do Evento</span>
                </a>

                <a class="nav-tab group flex flex-col items-center justify-center py-3 px-4 min-w-[100px] border-b-2 border-transparent text-gray-600 transition-all duration-200 active:bg-gray-50 no-underline" data-page="form">
                  <ion-icon name="document-text-outline" class="text-2xl mb-1 group-active:scale-95 transition-transform duration-200"></ion-icon>
                  <span class="text-xs font-semibold text-center leading-tight whitespace-nowrap">Formulário</span>
                </a>

                <a class="nav-tab group flex flex-col items-center justify-center py-3 px-4 min-w-[100px] border-b-2 border-transparent text-gray-600 transition-all duration-200 active:bg-gray-50 no-underline" data-page="participants">
                  <ion-icon name="people-outline" class="text-2xl mb-1 group-active:scale-95 transition-transform duration-200"></ion-icon>
                  <span class="text-xs font-semibold text-center leading-tight whitespace-nowrap">Inscritos</span>
                </a>

                <a class="nav-tab group flex flex-col items-center justify-center py-3 px-4 min-w-[100px] border-b-2 border-transparent text-gray-600 transition-all duration-200 active:bg-gray-50 no-underline" data-page="customize">
                  <ion-icon name="color-palette-outline" class="text-2xl mb-1 group-active:scale-95 transition-transform duration-200"></ion-icon>
                  <span class="text-xs font-semibold text-center leading-tight whitespace-nowrap">Personalizar</span>
                </a>

                <a class="nav-tab group flex flex-col items-center justify-center py-3 px-4 min-w-[100px] border-b-2 border-transparent text-gray-600 transition-all duration-200 active:bg-gray-50 no-underline" data-page="news">
                  <ion-icon name="newspaper-outline" class="text-2xl mb-1 group-active:scale-95 transition-transform duration-200"></ion-icon>
                  <span class="text-xs font-semibold text-center leading-tight whitespace-nowrap">Novidades</span>
                </a>

                <a class="nav-tab group flex flex-col items-center justify-center py-3 px-4 min-w-[100px] border-b-2 border-transparent text-gray-600 transition-all duration-200 active:bg-gray-50 no-underline" data-page="myArea">
                  <ion-icon name="person-outline" class="text-2xl mb-1 group-active:scale-95 transition-transform duration-200"></ion-icon>
                  <span class="text-xs font-semibold text-center leading-tight whitespace-nowrap">Minha Área</span>
                </a>
              </div>
            </div>
          </div>




          <div id="editEvent">

          </div>

          
        </div>

    </main>

    <!-- Footer   -->
    <footer class="bg-gray-800 text-white py-9">
    <div class="container mx-auto text-center px-4">
        <p class="text-gray-300 text-sm m-0">
            © 2025 ConectaIFPA. Todos os direitos reservados. Feito com ❤️ para a comunidade universitária.
        </p>
    </div>
</footer>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
     <!-- Add custom CSS for hiding scrollbar on mobile navigation  -->
    <style>
      .scrollbar-hide::-webkit-scrollbar {
        display: none;
      }
      .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
      }
    </style>
</body>
</html>
