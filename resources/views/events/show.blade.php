@extends('layouts.newMain')

@push('head')
<style>
/* Alinhamento (Quill) */
#eventDescription .ql-align-center,
#eventDescription p.ql-align-center,
#eventDescription h1.ql-align-center,
#eventDescription h2.ql-align-center,
#eventDescription h3.ql-align-center,
#eventDescription h4.ql-align-center,
#eventDescription div.ql-align-center { text-align: center; }
#eventDescription .ql-align-right,
#eventDescription p.ql-align-right,
#eventDescription h1.ql-align-right,
#eventDescription h2.ql-align-right,
#eventDescription h3.ql-align-right,
#eventDescription h4.ql-align-right,
#eventDescription div.ql-align-right { text-align: right; }
#eventDescription .ql-align-justify,
#eventDescription p.ql-align-justify,
#eventDescription h1.ql-align-justify,
#eventDescription h2.ql-align-justify,
#eventDescription h3.ql-align-justify,
#eventDescription h4.ql-align-justify,
#eventDescription div.ql-align-justify { text-align: justify; }
#eventDescription .ql-align-left,
#eventDescription p.ql-align-left,
#eventDescription h1.ql-align-left,
#eventDescription h2.ql-align-left,
#eventDescription h3.ql-align-left,
#eventDescription h4.ql-align-left,
#eventDescription div.ql-align-left { text-align: left; }
#eventDescription align-center { display: block; text-align: center; }
/* Tamanho de fonte (Quill) */
#eventDescription .ql-size-small { font-size: 0.75em; }
#eventDescription .ql-size-large { font-size: 1.5em; }
#eventDescription .ql-size-huge { font-size: 2.5em; }
/* Fundo do banner (áreas vazias) */
.banner-bg {
    background: linear-gradient(135deg, #f1f5f9 0%, #f8fafc 60%, #ecfdf5 100%);
}
</style>
@endpush

@section('title', $event['title'])

@section('content')
<!-- ===== CONTAINER PRINCIPAL ===== -->
<div class="min-h-screen bg-white overflow-x-hidden">

    @php
        $imageUrl = config('services.supabase.url') . '/storage/v1/object/public/' . config('services.supabase.bucket') . '/events/' . $event->image;
    @endphp

    <!-- ===== HERO: BANNER (1920x1080) ===== -->
    <section class="pt-6 sm:pt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="banner-bg relative w-full min-h-[280px] aspect-video max-h-[65vh] overflow-hidden rounded-xl flex items-center justify-center">
                <img 
                    src="{{ $imageUrl }}"
                    alt="{{ $event->title }}"
                    class="relative z-10 w-full h-full object-contain object-center"
                >
            </div>
        </div>
    </section>

    <!-- ===== BOX TÍTULO + INFO + INSCRIÇÃO (estilo Even3) ===== -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-8 relative z-10">
        <div class="bg-white rounded-lg sm:rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 lg:gap-8">
                <!-- Título e informações -->
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 break-words [overflow-wrap:anywhere] mb-3">
                        {{ $event->title }}
                    </h1>
                    <div class="space-y-1.5 text-slate-600 text-sm sm:text-base">
                        <p class="flex items-center gap-2">
                            <ion-icon name="calendar-outline" class="text-slate-500 flex-shrink-0"></ion-icon>
                            <span>
                                {{ $event->start_date->format('d/m/Y') }}
                                @if($event->end_date)
                                    – {{ $event->end_date->format('d/m/Y') }}
                                @endif
                                @if($event->start_time)
                                    · {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}
                                    @if($event->end_time)
                                        – {{ \Carbon\Carbon::parse($event->end_time)->format('H:i') }}
                                    @endif
                                @endif
                            </span>
                        </p>
                        <p class="flex items-center gap-2">
                            <ion-icon name="location-outline" class="text-slate-500 flex-shrink-0"></ion-icon>
                            <span class="break-words [overflow-wrap:anywhere]">{{ $event->venue }} · {{ $event->campus }}{{ $event->building ? ' · ' . $event->building : '' }}</span>
                        </p>
                        <p class="flex items-center gap-2 text-slate-500">
                            <span>{{ $event->category }} · {{ $event->modality }}</span>
                        </p>
                    </div>
                </div>

                <!-- Área de inscrição -->
                <div id="inscricoes" class="flex-shrink-0 lg:w-auto">
                    <div class="bg-slate-900 rounded-xl p-6 min-w-[240px] max-w-sm lg:max-w-none">
                        <h2 class="text-white text-lg font-bold mb-4">Inscreva-se</h2>
                        
                        <div class="space-y-3 mb-4 pb-4 border-b border-slate-600">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-400">Vagas:</span>
                                <span class="font-bold text-white">{{ $event->capacity - count($event->users) }}/{{ $event->capacity }}</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2">
                                <div class="bg-emerald-500 rounded-full h-2 transition-all" style="width: {{ min(100, ($event->capacity > 0 ? count($event->users) / $event->capacity * 100 : 0)) }}%"></div>
                            </div>
                        </div>

                        @php
                            $bloqueio = $event->registrationsBlockedReason();
                        @endphp
                        @if ($bloqueio === 'finalized')
                            <span class="text-slate-400 font-medium inline-flex items-center gap-2 text-sm">
                                <ion-icon name="lock-closed-outline" class="text-lg"></ion-icon>
                                Este evento foi finalizado pelo coordenador.
                            </span>
                        @elseif ($bloqueio === 'ended')
                            <span class="text-slate-400 font-medium inline-flex items-center gap-2 text-sm">
                                <ion-icon name="flag-outline" class="text-lg"></ion-icon>
                                O período deste evento foi encerrado.
                            </span>
                        @elseif ($bloqueio === 'started')
                            <span class="text-slate-400 font-medium inline-flex items-center gap-2 text-sm">
                                <ion-icon name="play-circle-outline" class="text-lg"></ion-icon>
                                Este evento está em andamento. As inscrições estão encerradas.
                            </span>
                        @elseif ($bloqueio === 'deadline')
                            <span class="text-slate-400 font-medium inline-flex items-center gap-2 text-sm">
                                <ion-icon name="alarm-outline" class="text-lg"></ion-icon>
                                Prazo de inscrições encerrado
                            </span>
                        @elseif ($bloqueio === 'full')
                            <span class="text-slate-400 font-medium inline-flex items-center gap-2 text-sm">
                                <ion-icon name="close-circle-outline" class="text-lg"></ion-icon>
                                Vagas esgotadas
                            </span>
                        @else
                            @php
                                $podeInscrever = !auth()->check() || auth()->user()->isParticipant();
                            @endphp
                            @if($podeInscrever)
                                <form action="/events/join/{{ $event['id'] }}" method="POST">
                                    @csrf
                                    <button type="submit" id="event-submit"
                                        class="w-full flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-6 py-3 rounded-[32px] transition-all duration-200 text-sm sm:text-base">
                                        <ion-icon name="checkmark-circle" class="text-xl"></ion-icon>
                                        <span>Confirmar Presença</span>
                                    </button>
                                </form>
                            @elseif(auth()->check() && auth()->user()->isCoordinator())
                                <p class="text-slate-300 text-sm leading-relaxed">
                                    Contas de coordenação não podem se inscrever em eventos. Use uma conta de participante para se inscrever.
                                </p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            @auth
                @if(auth()->user()->isCoordinator() && auth()->id() === $event->user_id)
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Coordenação</h3>
                        @if($event->isFinalized())
                            <p class="text-sm text-slate-600 max-w-2xl">
                                Evento finalizado: não é possível editar, gerenciar inscritos ou novidades. A página permanece visível ao público e você pode excluir o evento no painel.
                            </p>
                        @elseif($event->calendarEnded())
                            <p class="text-sm text-slate-600 mb-3 max-w-2xl">
                                O período do evento já terminou conforme data e horário de fim. Para encerrar de forma definitiva (sem edição nem gestão), finalize abaixo.
                            </p>
                            <form action="{{ url('/events/'.$event->id.'/finalize') }}" method="POST" class="inline"
                                  onsubmit="return confirm('Finalizar definitivamente? Você não poderá mais editar o evento, novidades ou inscritos.');">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition-colors">
                                    <ion-icon name="checkmark-done-outline" class="text-lg"></ion-icon>
                                    Finalizar evento
                                </button>
                            </form>
                        @else
                            <p class="text-sm text-slate-500 max-w-2xl">
                                As datas e horários da página são informativos. O sistema usa data/hora de início para liberar o andamento e encerrar inscrições, e data/hora de fim para exibir o evento como encerrado. Você pode editar o evento a qualquer momento antes de finalizá-lo após o término do período.
                            </p>
                        @endif
                    </div>
                @endif
            @endauth
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
                    <div id="eventDescription" class="text-slate-600 leading-relaxed break-words [overflow-wrap:anywhere] text-[15px] [&_p]:mb-4 [&_p:last-child]:mb-0 [&_h1]:text-2xl [&_h1]:font-semibold [&_h1]:mb-3 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:mb-2 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:mb-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:mb-4 [&_li]:mb-1 [&_a]:text-emerald-600 [&_a]:hover:text-emerald-700">
                        {!! \Mews\Purifier\Facades\Purifier::clean($event->description) !!}
                    </div>
                </div>
            </div>

            <!-- ===== SEÇÃO: INFORMAÇÕES ===== -->
            <div class="space-y-6">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Informações</h2>
                
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10 overflow-hidden">
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
                            <span class="text-slate-500 text-sm inline-flex items-center gap-1"><ion-icon name="time-outline"></ion-icon>
                                @if(is_numeric($m['hours']))
                                    {{ (int) $m['hours'] }} horas
                                @else
                                    {{ $m['hours'] }}
                                @endif
                            </span>
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
                    <p class="text-slate-600 text-sm">Este evento ainda não possui novidades.</p>
                @endif
            </div>

        </div>
    </div>
    </section>
</div>


@endsection
