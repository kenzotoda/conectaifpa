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

          <!-- Simplified banner using custom primary color -->
          <div id="eventBanner" class="bg-[var(--primary)] text-white rounded-xl sm:rounded-2xl shadow-xl sm:shadow-2xl p-6 sm:p-8 lg:p-12 flex items-center justify-center text-center border border-green-500/30 relative">
              <div class="banner-content text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold relative z-10 font-montserrat leading-tight px-2" id="bannerText">
                  {{ $event->title }}
              </div>
          </div>


          <!-- Improved spacing and responsiveness for all content sections  -->
          <div id="home" class="page active space-y-6 sm:space-y-8 lg:space-y-10">

            <!-- Seção Sobre o Curso - Melhorada   -->
            <div class="event-info bg-white shadow-lg sm:shadow-xl rounded-xl sm:rounded-2xl p-5 sm:p-6 lg:p-8 border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
                <h2 class="section-title text-xl sm:text-2xl lg:text-3xl font-bold mb-4 sm:mb-6 flex items-center text-gray-800 font-montserrat">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-[var(--primary)] rounded-lg flex items-center justify-center mr-2 sm:mr-3 shadow-lg flex-shrink-0">
                        <ion-icon name="information-circle-outline" class="text-white text-xl sm:text-2xl"></ion-icon>
                    </div>
                    Sobre o Curso
                </h2>
                <p id="eventDescription" class="mb-6 sm:mb-8 text-gray-600 leading-relaxed text-sm sm:text-base lg:text-lg">
                    {{ $event->description }}
                </p>

              <!-- Grid de Informações - Melhorado   -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                <!-- Datas e Horários   -->
                <div class="info-card bg-gradient-to-br from-emerald-50 to-green-50 p-4 sm:p-5 lg:p-6 rounded-lg sm:rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-emerald-100 hover:border-emerald-300 hover:-translate-y-1">
                  <h3 class="font-bold mb-3 sm:mb-4 flex items-center gap-2 text-emerald-700 text-base sm:text-lg font-montserrat">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 bg-emerald-600 rounded-lg flex items-center justify-center shadow-md flex-shrink-0">
                      <ion-icon name="calendar-outline" class="text-white text-lg sm:text-xl"></ion-icon>
                    </div>
                    Datas e Horários
                  </h3>
                  <ul class="space-y-2 sm:space-y-3 text-xs sm:text-sm text-gray-700">
                    <li class="flex flex-col">
                      <span class="font-semibold text-emerald-700">Início:</span>
                      <span id="eventStartDate" class="text-gray-600 mt-1">{{ date('d/m/Y', strtotime($event['start_date'])) }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-emerald-700">Prazo de Inscrição:</span>
                      <span id="eventRegistrationPeriod" class="text-gray-600 mt-1">{{ $event->datetime_registration }}</span>
                    </li>
                    <li class="flex justify-between items-center">
                      <span class="font-semibold text-emerald-700">Vagas:</span>
                      <span id="eventSlots" class="bg-emerald-600 text-white px-2.5 sm:px-3 py-1 rounded-full text-xs font-bold">{{ $event->capacity }}</span>
                    </li>
                    <li class="flex justify-between items-center">
                      <span class="font-semibold text-emerald-700">Inscritos:</span>
                      <span id="eventRegistrations" class="bg-gray-600 text-white px-2.5 sm:px-3 py-1 rounded-full text-xs font-bold">{{ count($event->users) }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-emerald-700">Horários:</span>
                      <span id="eventSchedule" class="text-gray-600 mt-1">{{ $event->start_time }} às {{ $event->end_time }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-emerald-700">Modalidade:</span>
                      <span id="eventModality" class="text-gray-600 mt-1">{{ $event->modality }}</span>
                    </li>
                  </ul>
                </div>

                <!-- Localização   -->
                <div class="info-card bg-gradient-to-br from-blue-50 to-cyan-50 p-4 sm:p-5 lg:p-6 rounded-lg sm:rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-blue-100 hover:border-blue-300 hover:-translate-y-1">
                  <h3 class="font-bold mb-3 sm:mb-4 flex items-center gap-2 text-blue-700 text-base sm:text-lg font-montserrat">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-600 rounded-lg flex items-center justify-center shadow-md flex-shrink-0">
                      <ion-icon name="location-outline" class="text-white text-lg sm:text-xl"></ion-icon>
                    </div>
                    Localização
                  </h3>
                  <ul class="space-y-2 sm:space-y-3 text-xs sm:text-sm text-gray-700">
                    <li class="flex flex-col">
                      <span class="font-semibold text-blue-700">Campus:</span>
                      <span id="eventCampus" class="text-gray-600 mt-1">{{ $event->campus }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-blue-700">Bloco:</span>
                      <span id="eventBuilding" class="text-gray-600 mt-1">{{ $event->building }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-blue-700">Endereço:</span>
                      <span id="eventAddress" class="text-gray-600 mt-1 break-words">{{ $event->address }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-blue-700">Local:</span>
                      <span id="eventLocations" class="text-gray-600 mt-1">{{ $event->venue }}</span>
                    </li>
                  </ul>
                </div>

                <!-- Coordenação   -->
                <div class="info-card bg-gradient-to-br from-amber-50 to-orange-50 p-4 sm:p-5 lg:p-6 rounded-lg sm:rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-amber-100 hover:border-amber-300 hover:-translate-y-1">
                  <h3 class="font-bold mb-3 sm:mb-4 flex items-center gap-2 text-amber-700 text-base sm:text-lg font-montserrat">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 bg-amber-600 rounded-lg flex items-center justify-center shadow-md flex-shrink-0">
                      <ion-icon name="person-circle-outline" class="text-white text-lg sm:text-xl"></ion-icon>
                    </div>
                    Coordenação
                  </h3>
                  <ul class="space-y-2 sm:space-y-3 text-xs sm:text-sm text-gray-700">
                    <li class="flex flex-col">
                      <span class="font-semibold text-amber-700">Coodernador do Curso:</span>
                      <span id="eventCoordinator" class="text-gray-600 mt-1">{{ $event->coordinator_name }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-amber-700">Contato:</span>
                      <span id="eventPhone" class="text-gray-600 mt-1">{{ $event->coordinator_phone }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-amber-700">E-mail:</span>
                      <span id="eventEmail" class="text-gray-600 mt-1 break-all">{{ $event->coordinator_email }}</span>
                    </li>
                    <li class="flex flex-col">
                      <span class="font-semibold text-amber-700">Ambiente EAD:</span>
                      <a href="#" id="eventEADLink" target="_blank" class="text-amber-600 hover:text-amber-800 hover:underline font-medium mt-1 inline-flex items-center gap-1">
                        Acessar ambiente
                        <ion-icon name="open-outline" class="text-sm"></ion-icon>
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            

            <!-- Seção Módulos - Dinâmica -->
            <div id="modules" class="page space-y-4 sm:space-y-6 bg-white shadow-lg sm:shadow-xl rounded-xl sm:rounded-2xl p-5 sm:p-6 lg:p-8 border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
                <h2 class="section-title text-xl sm:text-2xl lg:text-3xl font-bold mb-4 sm:mb-6 flex items-center text-gray-800 font-montserrat">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center mr-2 sm:mr-3 shadow-lg flex-shrink-0">
                        <ion-icon name="book-outline" class="text-white text-xl sm:text-2xl"></ion-icon>
                    </div>
                    Módulos
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                    @foreach($event->modules as $index => $module)
                        {{-- AJUSTE: tratamento JSON --}}
                        @php
                            $m = is_string($module) ? json_decode($module, true) : $module;
                        @endphp
                        <div class="module-card group bg-gradient-to-br from-purple-50 to-indigo-50 p-4 sm:p-5 lg:p-6 rounded-lg sm:rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border-2 border-purple-100 hover:border-purple-300 hover:-translate-y-2">
                            <div class="flex items-center gap-2 sm:gap-3 mb-2 sm:mb-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-purple-600 rounded-lg flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                                    <span class="text-white font-bold text-base sm:text-lg">{{ $index + 1 }}&ordm;</span>
                                </div>
                                <h3 class="font-bold text-purple-700 text-base sm:text-lg font-montserrat">{{ $m['name'] ?? '' }}</h3>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-700 leading-relaxed">{{ $m['description'] ?? '' }}</p>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">Carga Horária: {{ $m['hours'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Seção Público-Alvo - Dinâmica -->
            <div id="targetAudience" class="page space-y-4 sm:space-y-6 bg-white shadow-lg sm:shadow-xl rounded-xl sm:rounded-2xl p-5 sm:p-6 lg:p-8 border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
              <h2 class="section-title text-xl sm:text-2xl lg:text-3xl font-bold mb-4 sm:mb-6 flex items-center text-gray-800 font-montserrat">
                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-lg flex items-center justify-center mr-2 sm:mr-3 shadow-lg flex-shrink-0">
                  <ion-icon name="people-outline" class="text-white text-xl sm:text-2xl"></ion-icon>
                </div>
                Público-Alvo
              </h2>
              <ul class="space-y-3 sm:space-y-4">
                @foreach($event->target_audience as $audience)
                  {{-- AJUSTE: tratamento JSON --}}
                  @php
                      $a = is_string($audience) ? json_decode($audience, true) : $audience;
                  @endphp
                  <li class="flex items-start gap-2 sm:gap-3 p-3 sm:p-4 bg-gradient-to-r from-teal-50 to-cyan-50 rounded-lg sm:rounded-xl border border-teal-100 hover:border-teal-300 transition-all duration-300 hover:shadow-md">
                    <div class="w-5 h-5 sm:w-6 sm:h-6 bg-teal-600 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                      <ion-icon name="checkmark" class="text-white text-xs sm:text-sm"></ion-icon>
                    </div>
                    <span class="text-xs sm:text-sm text-gray-700">{{ $a['name'] ?? $a ?? '' }}</span>
                  </li>
                @endforeach
              </ul>
            </div>

            <!-- Seção Pré-Requisitos - Dinâmica -->
            <div id="prerequisites" class="page space-y-4 sm:space-y-6 bg-white shadow-lg sm:shadow-xl rounded-xl sm:rounded-2xl p-5 sm:p-6 lg:p-8 border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
              <h2 class="section-title text-xl sm:text-2xl lg:text-3xl font-bold mb-4 sm:mb-6 flex items-center text-gray-800 font-montserrat">
                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-rose-500 to-pink-600 rounded-lg flex items-center justify-center mr-2 sm:mr-3 shadow-lg flex-shrink-0">
                  <ion-icon name="checkmark-circle-outline" class="text-white text-xl sm:text-2xl"></ion-icon>
                </div>
                Pré-Requisitos
              </h2>
              <ul class="space-y-3 sm:space-y-4">
                @foreach($event->prerequisites as $prereq)
                  {{-- AJUSTE: tratamento JSON --}}
                  @php
                      $p = is_string($prereq) ? json_decode($prereq, true) : $prereq;
                  @endphp
                  <li class="flex items-start gap-2 sm:gap-3 p-3 sm:p-4 bg-gradient-to-r from-rose-50 to-pink-50 rounded-lg sm:rounded-xl border border-rose-100 hover:border-rose-300 transition-all duration-300 hover:shadow-md">
                    <div class="w-5 h-5 sm:w-6 sm:h-6 bg-rose-600 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                      <ion-icon name="star" class="text-white text-xs sm:text-sm"></ion-icon>
                    </div>
                    <span class="text-xs sm:text-sm text-gray-700">{{ $p['name'] ?? $p ?? '' }}</span>
                  </li>
                @endforeach
              </ul>
            </div>

            <!-- Seção Novidades - Melhorada   -->
            <div id="news" class="page space-y-4 sm:space-y-6 bg-white shadow-lg sm:shadow-xl rounded-xl sm:rounded-2xl p-5 sm:p-6 lg:p-8 border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
              <h2 class="section-title text-xl sm:text-2xl lg:text-3xl font-bold mb-4 sm:mb-6 flex items-center text-gray-800 font-montserrat">
                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center mr-2 sm:mr-3 shadow-lg flex-shrink-0">
                  <ion-icon name="newspaper-outline" class="text-white text-xl sm:text-2xl"></ion-icon>
                </div>
                Novidades
              </h2>
              <div class="space-y-4 sm:space-y-5">
                <div class="news-card group bg-gradient-to-r from-orange-50 to-red-50 p-4 sm:p-5 lg:p-6 rounded-lg sm:rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border-l-4 border-orange-500 hover:border-orange-600 hover:-translate-x-1">
                  <div class="flex items-start gap-3 sm:gap-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-md group-hover:scale-110 transition-transform duration-300">
                      <ion-icon name="megaphone-outline" class="text-white text-xl sm:text-2xl"></ion-icon>
                    </div>
                    <div class="flex-1 min-w-0">
                      <h3 class="font-bold mb-1.5 sm:mb-2 text-orange-700 text-base sm:text-lg font-montserrat">Novidade 1</h3>
                      <p class="text-xs sm:text-sm text-gray-700 leading-relaxed">Inscrições abertas até 28/09/2025. Não perca a oportunidade!</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

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
