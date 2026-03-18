@extends('layouts.newMain')

@section('title', $event['title'])

@section('content')
<!-- ===== CONTAINER PRINCIPAL ===== -->
<div class="min-h-screen bg-white overflow-x-hidden">

    @php
        $imageUrl = config('services.supabase.url') . '/storage/v1/object/public/' . config('services.supabase.bucket') . '/events/' . $event->image;
    @endphp

    <!-- ===== HERO: IMAGEM COM TÍTULO SOBREPOSTO ===== -->
    <section class="relative w-full min-h-[320px] sm:min-h-[380px] max-h-[65vh] overflow-hidden">
        <img 
            src="{{ $imageUrl }}"
            alt="{{ $event->title }}"
            class="absolute inset-0 w-full h-full object-cover object-center"
        >
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-black/20"></div>

        <!-- Botão: Abrir imagem original -->
        <a href="{{ $imageUrl }}" target="_blank" rel="noopener noreferrer"
           class="absolute top-4 right-4 z-20 flex items-center gap-2 px-3 py-2 rounded-lg bg-black/50 hover:bg-black/70 text-white text-sm font-medium backdrop-blur-sm transition-colors"
           title="Abrir imagem em tamanho original">
            <ion-icon name="expand-outline" class="text-lg"></ion-icon>
            <span class="hidden sm:inline">Ver imagem</span>
        </a>
        
        <div class="absolute inset-0 z-10 flex flex-col">
            <div class="flex-1"></div>
            <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pb-8 sm:pb-12 lg:pb-16 lg:pr-[calc(28rem+2rem)]">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold text-white tracking-tight max-w-4xl break-words [overflow-wrap:anywhere]">
                    {{ $event->title }}
                </h1>
                <p class="mt-3 text-emerald-300/90 text-sm sm:text-base font-medium">
                    {{ $event->category }} · {{ $event->modality }}
                </p>
            </div>
        </div>
    </section>

    <!-- ===== CARD DE INSCRIÇÃO (FLUTUANTE) ===== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24 sm:-mt-32 lg:-mt-40 relative z-20">
        <div class="flex justify-center lg:justify-end">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden backdrop-blur-sm">
                    <div class="bg-slate-900 p-6 sm:p-8">
                        <h2 class="text-white text-lg sm:text-xl font-bold mb-6">Inscreva-se</h2>
                        
                        <!-- Info Rápida -->
                        <div class="space-y-3 mb-6 pb-6 border-b border-slate-600">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-400">Vagas Disponíveis:</span>
                                <span class="font-bold text-white text-lg">{{ $event->capacity - count($event->users) }}/{{ $event->capacity }}</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2">
                                <div class="bg-emerald-500 rounded-full h-2 transition-all" style="width: {{ (count($event->users) / $event->capacity * 100) }}%"></div>
                            </div>
                        </div>

                        @if ($event->registrationClosed())
                            <span class="text-slate-400 font-semibold inline-flex items-center gap-2">
                                <ion-icon name="alarm-outline" class="text-lg"></ion-icon>
                                Prazo de inscrição encerrado
                            </span>

                        @elseif ($event->isFull())
                            <span class="text-slate-400 font-semibold inline-flex items-center gap-2">
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
                                            bg-emerald-500 text-white font-semibold
                                            px-6 py-3 rounded-xl
                                            hover:bg-emerald-600
                                            transition-all duration-200
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
                                            bg-emerald-500 text-white font-semibold
                                            px-6 py-3 rounded-xl
                                            hover:bg-emerald-600
                                            transition-all duration-200
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
                                <span class="font-semibold text-slate-900">{{ $event->start_date->format('d/m/Y') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <ion-icon name="time-outline" class="text-teal-600 text-lg"></ion-icon>
                            </div>
                            <div>
                                @if($event->start_time)
                                    <span class="text-slate-600 text-xs">Horário:</span>
                                    <span class="font-semibold text-slate-900">
                                        {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}
                                        @if($event->end_time)
                                            – {{ \Carbon\Carbon::parse($event->end_time)->format('H:i') }}
                                        @endif
                                    </span>
                                @else
                                    <span class="text-slate-500 italic">Horário não informado</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <section class="bg-slate-50/50 py-12 sm:py-16 lg:py-20 overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-w-0">
        <div id="eventPage" class="space-y-12">

            <!-- ===== SEÇÃO: SOBRE O EVENTO ===== -->
            <div class="space-y-6">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Sobre o Evento</h2>
                
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10 overflow-hidden">
                    <p id="eventDescription" class="text-slate-600 leading-relaxed text-base lg:text-lg mb-10 break-words [overflow-wrap:anywhere]">
                        {{ $event->description }}
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 min-w-0">
                        
                        <div class="bg-slate-50/50 rounded-xl p-6 border border-slate-200 min-w-0 overflow-hidden">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-slate-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <ion-icon name="calendar-outline" class="text-white text-lg"></ion-icon>
                                </div>
                                <h3 class="font-semibold text-slate-900">Datas e Horários</h3>
                            </div>
                            <ul class="space-y-3 text-sm">
                                <li>
                                    <span class="text-slate-500 block">Início</span>
                                    <span id="eventStartDate" class="text-slate-800 font-medium">{{ $event->start_date->format('d/m/Y') }}</span>
                                </li>
                                @if($event->end_date)
                                <li>
                                    <span class="text-slate-500 block">Término</span>
                                    <span id="eventEndDate" class="text-slate-800 font-medium">{{ $event->end_date->format('d/m/Y') }}</span>
                                </li>
                                @endif
                                @if($event->datetime_registration)
                                <li>
                                    <span class="text-slate-500 block">Prazo de Inscrição</span>
                                    <span id="eventRegistrationPeriod" class="text-slate-800 font-medium">{{ $event->datetime_registration->format('d/m/Y') }} às {{ $event->datetime_registration->format('H:i') }}</span>
                                </li>
                                @endif
                                <li>
                                    <span class="text-slate-500 block">Horários</span>
                                    <span id="eventSchedule" class="text-slate-800 font-medium">
                                        @if($event->start_time && $event->end_time)
                                            {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }} às {{ \Carbon\Carbon::parse($event->end_time)->format('H:i') }}
                                        @elseif($event->start_time)
                                            {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}
                                        @else
                                            <span class="text-slate-500 italic font-normal">Não informado</span>
                                        @endif
                                    </span>
                                </li>
                                <li>
                                    <span class="text-slate-500 block">Modalidade</span>
                                    <span id="eventModality" class="text-slate-800 font-medium">{{ $event->modality }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="bg-slate-50/50 rounded-xl p-6 border border-slate-200 min-w-0 overflow-hidden">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-slate-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <ion-icon name="location-outline" class="text-white text-lg"></ion-icon>
                                </div>
                                <h3 class="font-semibold text-slate-900">Localização</h3>
                            </div>
                            <ul class="space-y-3 text-sm">
                                <li>
                                    <span class="text-slate-500 block">Campus</span>
                                    <span id="eventCampus" class="text-slate-800 font-medium">{{ $event->campus }}</span>
                                </li>
                                <li>
                                    <span class="text-slate-500 block">Bloco</span>
                                    <span id="eventBuilding" class="text-slate-800 font-medium">{{ $event->building }}</span>
                                </li>
                                <li class="min-w-0 overflow-hidden">
                                    <span class="text-slate-500 block">Endereço</span>
                                    <span class="text-slate-800 font-medium break-words [overflow-wrap:anywhere]">{{ $event->address }}</span>
                                </li>
                                <li>
                                    <span class="text-slate-500 block">Local</span>
                                    <span id="eventLocations" class="text-slate-800 font-medium">{{ $event->venue }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="bg-slate-50/50 rounded-xl p-6 border border-slate-200 min-w-0 overflow-hidden">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-9 h-9 bg-slate-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <ion-icon name="person-circle-outline" class="text-white text-lg"></ion-icon>
                                </div>
                                <h3 class="font-semibold text-slate-900">Coordenação</h3>
                            </div>
                            <ul class="space-y-3 text-sm">
                                <li>
                                    <span class="text-slate-500 block">Coordenador</span>
                                    <span id="eventCoordinator" class="text-slate-800 font-medium">{{ $event->coordinator_name }}</span>
                                </li>
                                <li>
                                    <span class="text-slate-500 block">Contato</span>
                                    <span id="eventPhone" class="text-slate-800 font-medium">{{ $event->coordinator_phone }}</span>
                                </li>
                                <li>
                                    <span class="text-slate-500 block">E-mail</span>
                                    <a href="mailto:{{ $event->coordinator_email }}" id="eventEmail" class="text-emerald-600 hover:text-emerald-700 font-medium break-all">{{ $event->coordinator_email }}</a>
                                </li>
                                @if($event->ead_link)
                                <li>
                                    <span class="text-slate-500 block">Ambiente EAD</span>
                                    <a href="{{ $event->ead_link }}" id="eventEADLink" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-medium inline-flex items-center gap-1">
                                        Acessar <ion-icon name="open-outline" class="text-sm"></ion-icon>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SEÇÃO: MÓDULOS ===== -->
            @if($event->modules && count($event->modules) > 0)
            <div class="space-y-6">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Módulos</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 min-w-0">
                    @foreach($event->modules as $index => $module)
                        @php
                            $m = is_string($module) ? json_decode($module, true) : $module;
                        @endphp
                        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm hover:border-slate-300 transition-colors min-w-0 overflow-hidden">
                            <div class="flex items-start gap-3 mb-3 min-w-0">
                                <span class="w-8 h-8 bg-slate-900 text-white rounded-lg flex items-center justify-center text-sm font-semibold flex-shrink-0">{{ $index + 1 }}</span>
                                <h3 class="font-semibold text-slate-900 break-words min-w-0">{{ $m['name'] ?? 'Módulo' }}</h3>
                            </div>
                            <p class="text-slate-600 text-sm leading-relaxed mb-3 break-words [overflow-wrap:anywhere]">{{ $m['description'] ?? '' }}</p>
                            @if(!empty($m['hours']))
                            <span class="text-slate-500 text-sm inline-flex items-center gap-1"><ion-icon name="time-outline"></ion-icon> {{ $m['hours'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- ===== SEÇÃO: PÚBLICO-ALVO ===== -->
            @if($event->target_audience && count($event->target_audience) > 0)
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Público-Alvo</h2>

                <div class="flex flex-wrap gap-3 min-w-0">
                    @foreach($event->target_audience as $audience)
                        @php
                            $a = is_string($audience) ? json_decode($audience, true) : $audience;
                        @endphp
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 text-slate-700 text-sm min-w-0 max-w-full overflow-hidden">
                            <ion-icon name="checkmark" class="text-emerald-500 text-base flex-shrink-0"></ion-icon>
                            <span class="min-w-0 break-words [overflow-wrap:anywhere]">{{ $a['name'] ?? $a ?? '' }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- ===== SEÇÃO: PRÉ-REQUISITOS ===== -->
            @if($event->prerequisites && count($event->prerequisites) > 0)
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Pré-Requisitos</h2>

                <ul class="space-y-3 min-w-0">
                    @foreach($event->prerequisites as $prereq)
                        @php
                            $p = is_string($prereq) ? json_decode($prereq, true) : $prereq;
                        @endphp
                        <li class="flex items-start gap-3 p-4 bg-white rounded-xl border border-slate-200 min-w-0 overflow-hidden">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-2 flex-shrink-0"></span>
                            <span class="text-slate-700 text-sm leading-relaxed break-words min-w-0">{{ $p['name'] ?? $p ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- ===== SEÇÃO: NOVIDADES ===== -->
            <div class="space-y-6">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Novidades</h2>

                @if($event->eventNews->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($event->eventNews as $novidade)
                            <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm border-l-4 border-l-emerald-500 min-w-0 overflow-hidden">
                                <div class="flex items-start gap-4 min-w-0">
                                    <div class="w-10 h-10 bg-slate-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <ion-icon name="megaphone-outline" class="text-white text-lg"></ion-icon>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="font-semibold text-slate-900 mb-1 break-words">{{ $novidade->title }}</h3>
                                        <p class="text-slate-600 leading-relaxed text-sm break-words [overflow-wrap:anywhere]">{{ $novidade->content }}</p>
                                        <span class="text-slate-400 text-xs mt-2 block">{{ $novidade->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm border-l-4 border-l-emerald-500 min-w-0 overflow-hidden">
                        <div class="flex items-start gap-4 min-w-0">
                            <div class="w-10 h-10 bg-slate-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <ion-icon name="megaphone-outline" class="text-white text-lg"></ion-icon>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-slate-900 mb-1">Inscrições Abertas</h3>
                                <p class="text-slate-600 leading-relaxed text-sm break-words [overflow-wrap:anywhere]">
                                    As inscrições para este evento estão abertas até o prazo definido. Não perca a oportunidade de participar!
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
    </section>
</div>


@endsection
