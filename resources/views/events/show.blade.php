@extends('layouts.newMain')

@section('title', $event['title'])

@section('content')
<!-- ===== CONTAINER PRINCIPAL ===== -->
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50">

    <!-- ===== BANNER COM TÍTULO ===== -->
    <section class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 py-8 sm:py-12 lg:py-16 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white text-center text-balance leading-tight">
                {{ $event->title }}
            </h1>
        </div>
    </section>


    <!-- ===== HERO SECTION COM IMAGEM E BOTÃO ===== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12 items-start">
            
            <!-- Imagem do Evento -->
            <div class="lg:col-span-2 order-2 lg:order-1">
                <div class="relative">
                    <img 
                        src="{{ asset('storage/events/' . $event->image) }}"
                        alt="{{ $event->title }}"
                        class="w-full h-auto max-h-[600px] mx-auto
                            rounded-2xl shadow-2xl
                            object-contain"
                    >
                </div>
            </div>


            <!-- Card de Inscrição -->
            <div class="lg:col-span-1 order-1 lg:order-2 sticky top-4 lg:top-8">
                <div class="bg-white rounded-2xl shadow-2xl border border-emerald-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 sm:p-8">
                        <h2 class="text-white text-lg sm:text-xl font-bold mb-6">Inscreva-se Agora</h2>
                        
                        <!-- Info Rápida -->
                        <div class="space-y-3 mb-6 pb-6 border-b border-emerald-400/30">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-emerald-50">Vagas Disponíveis:</span>
                                <span class="font-bold text-white text-lg">{{ $event->capacity - count($event->users) }}/{{ $event->capacity }}</span>
                            </div>
                            <div class="w-full bg-emerald-400/20 rounded-full h-2">
                                <div class="bg-white rounded-full h-2" style="width: {{ (count($event->users) / $event->capacity * 100) }}%"></div>
                            </div>
                        </div>

                        @if ($event->registrationClosed())
                            <span class="text-emerald-50 font-semibold inline-flex items-center gap-2">
                                <ion-icon name="alarm-outline" class="text-lg"></ion-icon>
                                Prazo de inscrição encerrado
                            </span>

                        @elseif ($event->isFull())
                            <span class="text-emerald-50 font-semibold inline-flex items-center gap-2">
                                <ion-icon name="close-circle-outline" class="text-lg"></ion-icon>
                                Vagas esgotadas
                            </span>

                        @else
                            @if(auth()->check() && auth()->user()->isParticipant())
                                <!-- Botão de Confirmação -->
                                <form action="/events/join/{{ $event['id'] }}" method="POST">
                                    @csrf
                                    <button 
                                        type="submit"
                                        id="event-submit"
                                        class="w-full flex items-center justify-center gap-2
                                            bg-white text-emerald-700 font-semibold
                                            px-6 py-3 rounded-xl shadow-lg
                                            hover:shadow-xl hover:bg-emerald-50
                                            transition-all duration-300
                                            text-sm sm:text-base"
                                    >
                                        <ion-icon name="checkmark-circle" class="text-xl"></ion-icon>
                                        <span>Confirmar Presença</span>
                                    </button>
                                </form>
                            @endif

                            @guest
                                <!-- Botão de Confirmação -->
                                <form action="/events/join/{{ $event['id'] }}" method="POST" class="space-y-3">
                                    @csrf
                                    <button 
                                        type="submit"
                                        id="event-submit"
                                        class="w-full flex items-center justify-center gap-2
                                            bg-white text-emerald-700 font-semibold
                                            px-6 py-3 rounded-xl shadow-lg
                                            hover:shadow-xl hover:bg-emerald-50
                                            transition-all duration-300
                                            text-sm sm:text-base"
                                    >
                                        <ion-icon name="checkmark-circle" class="text-xl"></ion-icon>
                                        <span>Confirmar Presença</span>
                                    </button>
                                </form>
                            @endguest
                        @endif

                    </div>

                    <!-- Info Adicional -->
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <ion-icon name="calendar-outline" class="text-emerald-600 text-lg"></ion-icon>
                            </div>
                            <div>
                                <span class="text-slate-600 text-xs">Início:</span>
                                <span class="font-semibold text-slate-900">{{ date('d/m/Y', strtotime($event['start_date'])) }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <ion-icon name="time-outline" class="text-teal-600 text-lg"></ion-icon>
                            </div>
                            <div>
                                <span class="text-slate-600 text-xs">Horário:</span>
                                <span class="font-semibold text-slate-900">
                                <span class="font-semibold text-slate-900">
                                    {{ date('H:i', strtotime($event->start_time)) }}

                                    @if($event->end_time)
                                        - {{ date('H:i', strtotime($event->end_time)) }}
                                    @endif
                                </span>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
        <div id="eventPage" class="space-y-12">

            <!-- ===== SEÇÃO: SOBRE O EVENTO ===== -->
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-emerald-600 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
                        <ion-icon name="information-circle-outline" class="text-white text-2xl"></ion-icon>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Sobre o Evento</h2>
                </div>
                
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6 sm:p-8 lg:p-10">
                    <p id="eventDescription" class="text-slate-700 leading-relaxed text-sm sm:text-base lg:text-lg mb-8">
                        {{ $event->description }}
                    </p>

                    <!-- Grid de Informações -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Card: Datas e Horários -->
                        <div class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-xl p-6 border border-emerald-200 hover:shadow-lg transition-shadow duration-300">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center">
                                    <ion-icon name="calendar-outline" class="text-white text-lg"></ion-icon>
                                </div>
                                <h3 class="font-bold text-emerald-900 text-lg">Datas e Horários</h3>
                            </div>
                            <ul class="space-y-3 text-sm text-slate-700">
                                <li>
                                    <span class="font-semibold text-emerald-700 block">Início:</span>
                                    <span id="eventStartDate" class="text-slate-600">{{ date('d/m/Y', strtotime($event['start_date'])) }}</span>
                                </li>
                                <li>
                                    <span class="font-semibold text-emerald-700 block">Prazo de Inscrição:</span>
                                    <span id="eventRegistrationPeriod" class="text-slate-600">{{ $event->datetime_registration }}</span>
                                </li>
                                <li>
                                    <span class="font-semibold text-emerald-700 block">Horários:</span>
                                    <span id="eventSchedule" class="text-slate-600">{{ $event->start_time }} às {{ $event->end_time }}</span>
                                </li>
                                <li>
                                    <span class="font-semibold text-emerald-700 block">Modalidade:</span>
                                    <span id="eventModality" class="text-slate-600">{{ $event->modality }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Card: Localização -->
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-6 border border-blue-200 hover:shadow-lg transition-shadow duration-300">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                    <ion-icon name="location-outline" class="text-white text-lg"></ion-icon>
                                </div>
                                <h3 class="font-bold text-blue-900 text-lg">Localização</h3>
                            </div>
                            <ul class="space-y-3 text-sm text-slate-700">
                                <li>
                                    <span class="font-semibold text-blue-700 block">Campus:</span>
                                    <span id="eventCampus" class="text-slate-600">{{ $event->campus }}</span>
                                </li>
                                <li>
                                    <span class="font-semibold text-blue-700 block">Bloco:</span>
                                    <span id="eventBuilding" class="text-slate-600">{{ $event->building }}</span>
                                </li>
                                <li>
                                    <span class="font-semibold text-blue-700 block">Endereço:</span>
                                    <span id="eventBuilding" class="text-slate-600">{{ $event->address }}</span>
                                </li>
                                <li>
                                    <span class="font-semibold text-blue-700 block">Local:</span>
                                    <span id="eventLocations" class="text-slate-600">{{ $event->venue }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Card: Coordenação -->
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-6 border border-amber-200 hover:shadow-lg transition-shadow duration-300">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-amber-600 rounded-lg flex items-center justify-center">
                                    <ion-icon name="person-circle-outline" class="text-white text-lg"></ion-icon>
                                </div>
                                <h3 class="font-bold text-amber-900 text-lg">Coordenação</h3>
                            </div>
                            <ul class="space-y-3 text-sm text-slate-700">
                                <li>
                                    <span class="font-semibold text-amber-700 block">Coordenador:</span>
                                    <span id="eventCoordinator" class="text-slate-600">{{ $event->coordinator_name }}</span>
                                </li>
                                <li>
                                    <span class="font-semibold text-amber-700 block">Contato:</span>
                                    <span id="eventPhone" class="text-slate-600">{{ $event->coordinator_phone }}</span>
                                </li>
                                <li>
                                    <span class="font-semibold text-amber-700 block">E-mail:</span>
                                    <span id="eventEmail" class="text-slate-600 break-all">{{ $event->coordinator_email }}</span>
                                </li>
                                <li class="flex flex-col">
                                    <span class="font-semibold text-amber-700">Ambiente EAD:</span>
                                    <a href="{{ $event->ead_link }}" id="eventEADLink" target="_blank" class="text-amber-600 hover:text-amber-800 hover:underline font-medium mt-1 inline-flex items-center gap-1">
                                    Acessar ambiente
                                    <ion-icon name="open-outline" class="text-sm"></ion-icon>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SEÇÃO: MÓDULOS ===== -->
            @if($event->modules && count($event->modules) > 0)
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <ion-icon name="book-outline" class="text-white text-2xl"></ion-icon>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Módulos</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($event->modules as $index => $module)
                        @php
                            $m = is_string($module) ? json_decode($module, true) : $module;
                        @endphp
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-6 border-2 border-purple-200 hover:shadow-lg hover:border-purple-300 transition-all duration-300 group">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-white font-bold text-lg">{{ $index + 1 }}</span>
                                </div>
                                <h3 class="font-bold text-purple-900 text-lg">{{ $m['name'] ?? 'Módulo' }}</h3>
                            </div>
                            <p class="text-slate-700 text-sm mb-3 leading-relaxed">{{ $m['description'] ?? '' }}</p>
                            <span class="text-purple-600 font-semibold text-sm"> <ion-icon name="time-outline"></ion-icon> {{ $m['hours'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- ===== SEÇÃO: PÚBLICO-ALVO ===== -->
            @if($event->target_audience && count($event->target_audience) > 0)
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-teal-600 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                        <ion-icon name="people-outline" class="text-white text-2xl"></ion-icon>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Público-Alvo</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($event->target_audience as $audience)
                        @php
                            $a = is_string($audience) ? json_decode($audience, true) : $audience;
                        @endphp
                        <div class="flex items-start gap-4 p-4 bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl border border-teal-200 hover:shadow-md hover:border-teal-300 transition-all duration-300">
                            <div class="w-6 h-6 bg-teal-600 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <ion-icon name="checkmark" class="text-white text-sm"></ion-icon>
                            </div>
                            <span class="text-slate-700 text-sm leading-relaxed">{{ $a['name'] ?? $a ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- ===== SEÇÃO: PRÉ-REQUISITOS ===== -->
            @if($event->prerequisites && count($event->prerequisites) > 0)
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-rose-600 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                        <ion-icon name="checkmark-circle-outline" class="text-white text-2xl"></ion-icon>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Pré-Requisitos</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($event->prerequisites as $prereq)
                        @php
                            $p = is_string($prereq) ? json_decode($prereq, true) : $prereq;
                        @endphp
                        <div class="flex items-start gap-4 p-4 bg-gradient-to-r from-rose-50 to-pink-50 rounded-xl border border-rose-200 hover:shadow-md hover:border-rose-300 transition-all duration-300">
                            <div class="w-6 h-6 bg-rose-600 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <ion-icon name="star" class="text-white text-sm"></ion-icon>
                            </div>
                            <span class="text-slate-700 text-sm leading-relaxed">{{ $p['name'] ?? $p ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- ===== SEÇÃO: NOVIDADES ===== -->
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-orange-600 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                        <ion-icon name="newspaper-outline" class="text-white text-2xl"></ion-icon>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Novidades</h2>
                </div>

                <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-6 sm:p-8 border-l-4 border-orange-600 shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <ion-icon name="megaphone-outline" class="text-white text-xl"></ion-icon>
                        </div>
                        <div>
                            <h3 class="font-bold text-orange-900 text-lg mb-2">Inscrições Abertas</h3>
                            <p class="text-slate-700 leading-relaxed">
                                As inscrições para este evento estão abertas até o prazo definido. Não perca a oportunidade de participar!
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>


@endsection
