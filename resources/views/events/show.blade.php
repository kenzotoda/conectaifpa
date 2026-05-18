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
        $imageUrl = config('services.supabase.url') . '/storage/v1/object/public/' . config('services.supabase.bucket_events') . '/events/' . $event->image;
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
                        @php
                            $locationSummary = collect([$event->venue, $event->campus, $event->building])
                                ->filter(fn ($value) => filled($value))
                                ->map(fn ($value) => trim((string) $value))
                                ->unique()
                                ->implode(' · ');
                            $modalityIsOnlineOnly = $event->modality === 'Online';
                            $modalityIsHybrid = $event->modality === 'Híbrido';
                        @endphp
                        <p class="flex items-center gap-2">
                            <ion-icon name="calendar-outline" class="text-slate-500 flex-shrink-0"></ion-icon>
                            <span>
                                {{ $event->start_date->format('d/m/Y') }}
                                @if($event->end_date)
                                    até {{ $event->end_date->format('d/m/Y') }}
                                @endif
                                @if($event->start_time)
                                    · {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}
                                    @if($event->end_time)
                                        às {{ \Carbon\Carbon::parse($event->end_time)->format('H:i') }}
                                    @endif
                                @endif
                            </span>
                        </p>
                        @if($modalityIsOnlineOnly)
                            <p class="flex items-start gap-2">
                                <ion-icon name="globe-outline" class="text-slate-500 flex-shrink-0 mt-0.5"></ion-icon>
                                <span class="break-words [overflow-wrap:anywhere]">
                                    <span class="font-medium text-slate-800">Evento online</span>
                                    — não há local físico de realização ao público; o acesso é remoto.
                                    @if($event->ead_link)
                                        <a href="{{ $event->ead_link }}" target="_blank" rel="noopener noreferrer" class="text-emerald-700 hover:text-emerald-800 font-semibold ml-1">Link do ambiente</a>
                                    @endif
                                </span>
                            </p>
                        @else
                            <p class="flex items-center gap-2">
                                <ion-icon name="location-outline" class="text-slate-500 flex-shrink-0"></ion-icon>
                                <span class="break-words [overflow-wrap:anywhere]">{{ $locationSummary }}</span>
                            </p>
                            @if($modalityIsHybrid)
                                <p class="flex items-start gap-2 m-0 text-slate-600 text-sm">
                                    <ion-icon name="globe-outline" class="text-slate-500 flex-shrink-0 mt-0.5"></ion-icon>
                                    <span class="[overflow-wrap:anywhere]">Modalidade <strong class="text-slate-800">híbrida</strong>: há participação presencial no campus e acesso remoto.@if($event->ead_link) <a href="{{ $event->ead_link }}" target="_blank" rel="noopener noreferrer" class="text-emerald-700 hover:text-emerald-800 font-semibold">Link do ambiente online</a>@endif</span>
                                </p>
                            @endif
                        @endif
                        <p class="flex items-center gap-2 text-slate-500">
                            <span>{{ $event->category }} · {{ $event->modality }}</span>
                        </p>
                        @if($event->event_type)
                            <p class="flex items-center gap-2">
                                <ion-icon name="pricetag-outline" class="text-slate-500 flex-shrink-0"></ion-icon>
                                <span>
                                    <span class="text-slate-500">Tipo de evento:</span>
                                    <span class="text-slate-700 font-medium">{{ $event->event_type }}</span>
                                </span>
                            </p>
                        @endif
                        @if($event->datetime_registration)
                            <p class="flex items-center gap-2">
                                <ion-icon name="alarm-outline" class="text-slate-500 flex-shrink-0"></ion-icon>
                                <span>
                                    <span class="text-slate-500">Prazo de inscrição:</span>
                                    <span id="eventRegistrationPeriod" class="text-slate-700 font-medium">{{ $event->datetime_registration->format('d/m/Y') }} às {{ $event->datetime_registration->format('H:i') }}</span>
                                </span>
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Área de inscrição -->
                <div id="inscricoes" class="flex-shrink-0 w-full lg:w-auto lg:min-w-[280px] flex flex-col items-stretch lg:items-end justify-center gap-3">
                    @if($event->accepts_submissions)
                    <a href="{{ route('events.annals', $event->id) }}"
                       class="w-full lg:w-auto text-center lg:text-right inline-flex items-center justify-center gap-2 text-indigo-700 hover:text-indigo-900 font-semibold text-sm no-underline order-first lg:order-none">
                        <ion-icon name="book-outline" class="text-lg"></ion-icon>
                        Anais do evento
                    </a>
                    @endif
                    @php
                        $bloqueio = $event->registrationsBlockedReason();
                    @endphp
                    @if ($bloqueio === 'finalized')
                        <p class="text-slate-600 text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="lock-closed-outline" class="text-lg text-slate-500 shrink-0 mt-0.5"></ion-icon>
                            <span>Este evento foi finalizado pelo coordenador.</span>
                        </p>
                    @elseif ($bloqueio === 'ended')
                        <p class="text-slate-600 text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="flag-outline" class="text-lg text-slate-500 shrink-0 mt-0.5"></ion-icon>
                            <span>O período deste evento foi encerrado.</span>
                        </p>
                    @elseif ($bloqueio === 'started')
                        <p class="text-slate-600 text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="play-circle-outline" class="text-lg text-slate-500 shrink-0 mt-0.5"></ion-icon>
                            <span>Este evento está em andamento. As inscrições estão encerradas.</span>
                        </p>
                    @elseif ($bloqueio === 'deadline')
                        <p class="text-slate-600 text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="alarm-outline" class="text-lg text-slate-500 shrink-0 mt-0.5"></ion-icon>
                            <span>Prazo de inscrições encerrado.</span>
                        </p>
                    @elseif ($bloqueio === 'full')
                        <p class="text-slate-600 text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="close-circle-outline" class="text-lg text-slate-500 shrink-0 mt-0.5"></ion-icon>
                            <span>Inscrições indisponíveis: lotação esgotada.</span>
                        </p>
                    @else
                        @php
                            $podeInscrever = !auth()->check() || auth()->user()->isParticipant();
                        @endphp
                        @if($podeInscrever)
                            <form action="/events/join/{{ $event['id'] }}" method="POST" class="w-full lg:w-auto">
                                @csrf
                                <button type="submit" id="event-submit"
                                    class="w-full lg:w-auto min-w-[260px] inline-flex items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold px-8 py-4 text-base shadow-lg shadow-emerald-500/25 border border-emerald-400/30 transition-all duration-200 hover:shadow-xl hover:shadow-emerald-500/30 hover:-translate-y-0.5 active:translate-y-0">
                                    <ion-icon name="person-add-outline" class="text-2xl shrink-0" aria-hidden="true"></ion-icon>
                                    <span>Realizar inscrição</span>
                                </button>
                            </form>
                        @elseif(auth()->check() && auth()->user()->isCoordinator())
                            <p class="text-slate-500 text-sm leading-relaxed m-0 max-w-sm lg:text-right border border-slate-200 rounded-xl px-4 py-3 bg-slate-50">
                                Coordenadores não fazem inscrição em evento. Para se inscrever, entre com uma conta de participante.
                            </p>
                        @elseif(auth()->check() && auth()->user()->isReviewer())
                            <p class="text-slate-500 text-sm leading-relaxed m-0 max-w-sm lg:text-right border border-slate-200 rounded-xl px-4 py-3 bg-slate-50">
                                Avaliadores não fazem inscrição em evento. Para se inscrever, entre com uma conta de participante.
                            </p>
                        @endif
                    @endif
                </div>
            </div>

                    @auth
                @if(auth()->user()->isCoordinator() && auth()->id() === $event->user_id)
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <h3 class="text-sm font-semibold text-slate-900 m-0 mb-3">Coordenação</h3>
                        @if($event->isFinalized())
                            <p class="text-sm text-slate-600 max-w-2xl">
                                Evento finalizado: não é possível editar, gerenciar inscritos ou novidades. A página permanece visível ao público e você pode excluir o evento no painel.
                            </p>
                        @elseif($event->calendarEnded() || config('app.allow_early_event_finalize'))
                            @if(! $event->calendarEnded() && config('app.allow_early_event_finalize'))
                                <p class="text-sm text-amber-900 mb-3 max-w-2xl rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    Modo de teste: a finalização antes do horário de fim está ativa (<code class="text-xs bg-amber-100/80 px-1 rounded">EVENT_ALLOW_EARLY_FINALIZE</code> no <code class="text-xs bg-amber-100/80 px-1 rounded">.env</code>). Use para validar certificados e fluxos pós evento; desligue em produção.
                                </p>
                            @else
                                <p class="text-sm text-slate-600 mb-3 max-w-2xl">
                                    O período do evento já terminou conforme data e horário de fim. Para encerrar de forma definitiva (sem edição nem gestão), finalize abaixo.
                                </p>
                            @endif
                            <div class="flex flex-wrap gap-3 items-center">
                                <form action="{{ url('/events/'.$event->id.'/finalize') }}" method="POST" class="inline"
                                      onsubmit="return confirm('Finalizar definitivamente? Você não poderá mais editar o evento, novidades ou inscritos.');">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition-colors">
                                        <ion-icon name="checkmark-done-outline" class="text-lg"></ion-icon>
                                        Finalizar evento
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-sm text-slate-500 max-w-2xl">
                                Na edição, <span class="text-slate-700 font-medium">datas e horários do evento</span> e <span class="text-slate-700 font-medium">prazo de inscrição</span> não podem ser alterados como no cadastro original, para evitar conflitos com inscrições e certificados. O <span class="text-slate-700 font-medium">prazo de submissão de trabalhos</span> só permite preenchimento até ser definido pela primeira vez; depois também fica fixo. Se precisar mudar calendários ou outros prazos já bloqueados, é mais seguro <span class="text-slate-700 font-medium">criar outro evento</span>. O que você pode mudar são os <span class="text-slate-700 font-medium">dados liberados pelo formulário de edição</span> (por exemplo título, descrição, imagem de capa, capacidade, modalidade, contatos da coordenação). Após a data e hora de início, novas inscrições de participantes encerram. Quando o período do evento terminar, esta área mostrará a opção de <span class="text-slate-700 font-medium">finalizar</span> o evento de forma definitiva.
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

            <!-- ===== SEÇÃO: LOCALIZAÇÃO / ACESSO ===== -->
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">
                    @if($event->modality === 'Online')
                        Acesso ao evento
                    @else
                        Localização
                    @endif
                </h2>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10 min-w-0 overflow-hidden">
                    @if($event->modality === 'Online')
                        <div class="text-sm text-slate-700 space-y-4 max-w-2xl">
                            <p class="m-0 leading-relaxed">
                                Este evento é <strong class="text-slate-900">100% online</strong>. Não há sede física de realização divulgada nesta página — o acesso é remoto (meet, plataforma EAD, etc.).
                            </p>
                            @if($event->ead_link)
                                <p class="m-0">
                                    <a href="{{ $event->ead_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-emerald-600 hover:text-emerald-700 font-semibold">
                                        Abrir link do ambiente <ion-icon name="open-outline" class="text-base"></ion-icon>
                                    </a>
                                </p>
                            @else
                                <p class="text-slate-500 m-0">O link do ambiente ainda não foi informado na ficha do evento.</p>
                            @endif
                        </div>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                            <ul class="space-y-3 text-sm">
                                <li>
                                    <span class="text-slate-500 block">Local fixo @if($event->modality === 'Híbrido') (presencial)@endif</span>
                                    <span class="text-slate-800 font-semibold">{{ \App\Models\Event::FIXED_VENUE }}</span>
                                </li>
                                <li class="min-w-0 overflow-hidden">
                                    <span class="text-slate-500 block">Endereço</span>
                                    <span class="text-slate-800 font-medium break-words [overflow-wrap:anywhere]">{{ \App\Models\Event::FIXED_ADDRESS }}</span>
                                </li>
                                <li>
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(\App\Models\Event::FIXED_ADDRESS) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium">
                                        Abrir no Google Maps <ion-icon name="open-outline" class="text-sm"></ion-icon>
                                    </a>
                                </li>
                                @if($event->modality === 'Híbrido')
                                    <li class="pt-2 border-t border-slate-100">
                                        <span class="text-slate-500 block mb-1">Modalidade híbrida</span>
                                        <span class="text-slate-800">Há transmissão ou atividades online além do presencial no campus.@if($event->ead_link) Consulte também o <a href="{{ $event->ead_link }}" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:text-emerald-700 font-semibold">ambiente EAD</a>.@endif</span>
                                    </li>
                                @endif
                            </ul>
                            <iframe
                                src="{{ \App\Models\Event::FIXED_MAP_EMBED_URL }}"
                                class="w-full h-72 rounded-lg border border-slate-200"
                                style="border:0;"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                allowfullscreen
                                title="Mapa do {{ \App\Models\Event::FIXED_VENUE }}"
                            ></iframe>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ===== SEÇÃO: ORGANIZAÇÃO E CONTATO ===== -->
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Organização e contato</h2>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10 min-w-0 overflow-hidden">
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <li>
                            <span class="text-slate-500 block">Organizado por</span>
                            <span id="eventCoordinator" class="text-slate-800 font-medium">{{ $event->coordinator_name }}</span>
                        </li>
                        @if(filled($event->coordinator_phone))
                        <li>
                            <span class="text-slate-500 block">Contato</span>
                            <span id="eventPhone" class="text-slate-800 font-medium">{{ $event->coordinator_phone }}</span>
                        </li>
                        @endif
                        <li class="md:col-span-2">
                            <span class="text-slate-500 block">Entrar em contato</span>
                            <a href="mailto:{{ $event->coordinator_email }}" id="eventEmail" class="text-emerald-600 hover:text-emerald-700 font-medium break-all">{{ $event->coordinator_email }}</a>
                        </li>
                        @if($event->ead_link)
                        <li class="md:col-span-2">
                            <span class="text-slate-500 block">Ambiente EAD</span>
                            <a href="{{ $event->ead_link }}" id="eventEADLink" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-medium inline-flex items-center gap-1">
                                Acessar <ion-icon name="open-outline" class="text-sm"></ion-icon>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            <!-- ===== SEÇÃO: ATIVIDADES ===== -->
            <div class="space-y-6">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Atividades</h2>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10">
                    @php
                        $activities = $event->activities->sortBy('start_at')->values();
                        $activitiesByDay = $activities->groupBy(function ($activity) {
                            return optional($activity->start_at)->format('Y-m-d');
                        });
                        $firstDayKey = $activitiesByDay->keys()->first();
                    @endphp
                    @if($activities->isEmpty())
                        <p class="text-slate-600 text-sm m-0">A programação ainda não possui atividades cadastradas.</p>
                    @else
                        <div class="border-b border-slate-200 mb-4">
                            <div class="flex gap-2 overflow-x-auto pb-2">
                                @foreach($activitiesByDay as $dayKey => $dayActivities)
                                    @php
                                        $dayDate = \Carbon\Carbon::parse($dayKey);
                                    @endphp
                                    <button
                                        type="button"
                                        class="activity-day-tab px-4 py-2 rounded-lg border text-sm font-medium whitespace-nowrap transition-colors {{ $dayKey === $firstDayKey ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }}"
                                        data-day="{{ $dayKey }}"
                                    >
                                        {{ $dayDate->locale('pt_BR')->translatedFormat('l, d/m') }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        @foreach($activitiesByDay as $dayKey => $dayActivities)
                            <div class="activity-day-panel space-y-3 {{ $dayKey !== $firstDayKey ? 'hidden' : '' }}" data-day-panel="{{ $dayKey }}">
                                @foreach($dayActivities as $activity)
                                    @php
                                        $timeLabel = ($activity->start_at ? $activity->start_at->format('H:i') : 'a definir')
                                            . ($activity->end_at ? ' às '.$activity->end_at->format('H:i') : '');
                                    @endphp
                                    <button
                                        type="button"
                                        class="activity-open-modal w-full text-left p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition-colors"
                                        data-title="{{ $activity->title }}"
                                        data-type="{{ ($activityTypeLabels ?? [])[$activity->type] ?? ucfirst($activity->type) }}"
                                        data-location="{{ $activity->location ?? 'Não informado' }}"
                                        data-datetime="{{ $activity->start_at?->format('d/m/Y H:i') }}{{ $activity->end_at ? ' às '.$activity->end_at->format('H:i') : '' }}"
                                        data-description="{{ $activity->description ?? '' }}"
                                        @php
                                            $modalGuests = $activity->eventGuests;
                                            if ($modalGuests->isEmpty() && $activity->guest) {
                                                $modalGuests = collect([$activity->guest]);
                                            }
                                            $speakersForModal = $modalGuests->map(function ($g) {
                                                $roleLabel = ($guestRoleTypeLabels ?? [])[$g->role_type] ?? null;
                                                if ($roleLabel === null && filled($g->role_type)) {
                                                    $roleLabel = ucfirst((string) $g->role_type);
                                                }

                                                return [
                                                    'name' => $g->name,
                                                    'role' => $roleLabel,
                                                ];
                                            })->values()->all();
                                            if ($speakersForModal === []) {
                                                $speakersForModal = $activity->speakers ?? [];
                                            }
                                        @endphp
                                        data-speakers="{{ json_encode($speakersForModal, JSON_UNESCAPED_UNICODE) }}"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="font-semibold text-slate-900 m-0 break-words">{{ $activity->title }}</p>
                                            <span class="text-sm text-slate-600 font-semibold">{{ $timeLabel }}</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- ===== SEÇÃO: CONVIDADOS ===== -->
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Convidados</h2>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10 min-w-0 overflow-hidden">
                    @if($event->guests->isEmpty())
                        <p class="text-slate-600 text-sm m-0">Nenhum convidado cadastrado neste evento ainda.</p>
                    @else
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 list-none m-0 p-0">
                            @foreach($event->guests as $guest)
                                @php
                                    $guestFn = ($guestRoleTypeLabels ?? [])[$guest->role_type] ?? null;
                                    if ($guestFn === null && filled($guest->role_type)) {
                                        $guestFn = ucfirst((string) $guest->role_type);
                                    }
                                @endphp
                                <li class="flex gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/80 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-slate-900 flex items-center justify-center flex-shrink-0">
                                        <ion-icon name="person-outline" class="text-white text-lg" aria-hidden="true"></ion-icon>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-slate-900 m-0 text-sm sm:text-base break-words [overflow-wrap:anywhere]">
                                            {{ $guest->name }}
                                        </p>
                                        @if(filled($guestFn))
                                            <p class="text-slate-600 text-xs sm:text-sm m-0 mt-1">{{ $guestFn }}</p>
                                        @endif
                                        @if(filled($guest->role))
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 m-0 mt-3">Formação</p>
                                            <p class="text-slate-600 text-xs sm:text-sm m-0 mt-1 leading-relaxed whitespace-pre-line break-words [overflow-wrap:anywhere]">
                                                {{ $guest->role }}
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

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

            @if($event->acceptsSubmissions() || ($acceptedWorkTypes ?? collect())->isNotEmpty())
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Submissões Científicas</h2>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10">
                    @if(!$event->acceptsSubmissions())
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-slate-700 m-0">
                                Este evento está configurado para participação/aprendizado e não recebe submissão de trabalhos.
                            </p>
                        </div>
                    @else
                        @if($event->submission_deadline_at)
                            <div class="flex flex-wrap items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 mb-6">
                                <ion-icon name="calendar-clear-outline" class="text-slate-600 text-xl shrink-0 mt-0.5" aria-hidden="true"></ion-icon>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 m-0">Prazo de submissão</p>
                                    <p class="text-slate-800 font-medium m-0 mt-1 text-sm sm:text-base">
                                        {{ $event->submission_deadline_at->format('d/m/Y') }} às {{ $event->submission_deadline_at->format('H:i') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if(($acceptedWorkTypes ?? collect())->isNotEmpty())
                            <div class="mb-6">
                                <h3 class="font-semibold text-slate-900 mb-3">Tipos de trabalho aceitos</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($acceptedWorkTypes as $workType)
                                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                                            {{ \App\Models\Work::workTypeLabels()[$workType] ?? $workType }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            @endif

            <!-- ===== SEÇÃO: DOCUMENTOS E ANEXOS ===== -->
            <div class="space-y-6">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 border-l-4 border-emerald-500 pl-4">Documentos e Anexos</h2>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 lg:p-10">
                    @if($event->documents->isEmpty())
                        <p class="text-slate-600 text-sm m-0">Ainda não há documentos publicados para este evento.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($event->documents as $document)
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900 m-0">{{ $document->title }}</p>
                                        <p class="text-sm text-slate-600 mt-1 mb-0">
                                            {{ $documentTypeLabels[$document->document_type] ?? 'Documento' }}
                                            • Publicado em {{ $document->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('events.documents.download', [$event->id, $document->id]) }}"
                                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium no-underline transition-colors">
                                            <ion-icon name="download-outline"></ion-icon>
                                            Baixar
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

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

<div id="activityDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-auto shadow-2xl">
        <div class="p-4 sm:p-5 border-b border-slate-200 flex items-start justify-between gap-3">
            <h3 id="activityModalTitle" class="text-base sm:text-lg font-semibold text-slate-900 leading-snug m-0 pr-2"></h3>
            <button type="button" id="activityModalCloseTop" class="text-slate-400 hover:text-slate-600 text-2xl leading-none shrink-0" aria-label="Fechar">&times;</button>
        </div>
        <div class="p-4 sm:p-5 space-y-4 text-sm">
            <div id="activityModalSpeakersWrap" class="hidden">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Convidados</h4>
                <div id="activityModalSpeakers" class="space-y-2"></div>
            </div>

            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Tipo</h4>
                <p id="activityModalType" class="text-slate-800 text-sm m-0"></p>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Local</h4>
                <p id="activityModalLocation" class="text-slate-800 text-sm m-0"></p>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Data e horário</h4>
                <p id="activityModalDatetime" class="text-slate-800 text-sm m-0"></p>
            </div>
            <div id="activityModalDescriptionWrap" class="hidden">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Descrição</h4>
                <p id="activityModalDescription" class="text-slate-600 text-sm leading-relaxed whitespace-pre-line m-0"></p>
            </div>
        </div>
        <div class="p-4 sm:px-5 sm:pb-5 pt-0 flex justify-end">
            <button type="button" id="activityModalCloseBottom" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.activity-day-tab');
    const panels = document.querySelectorAll('.activity-day-panel');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const day = tab.dataset.day;
            tabs.forEach((item) => {
                item.classList.remove('bg-slate-900', 'text-white', 'border-slate-900');
                item.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
            });
            tab.classList.add('bg-slate-900', 'text-white', 'border-slate-900');
            tab.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.dayPanel !== day);
            });
        });
    });

    const modal = document.getElementById('activityDetailModal');
    const closeTop = document.getElementById('activityModalCloseTop');
    const closeBottom = document.getElementById('activityModalCloseBottom');

    const modalTitle = document.getElementById('activityModalTitle');
    const modalType = document.getElementById('activityModalType');
    const modalLocation = document.getElementById('activityModalLocation');
    const modalDatetime = document.getElementById('activityModalDatetime');
    const modalDescription = document.getElementById('activityModalDescription');
    const modalDescriptionWrap = document.getElementById('activityModalDescriptionWrap');
    const speakersWrap = document.getElementById('activityModalSpeakersWrap');
    const speakersContainer = document.getElementById('activityModalSpeakers');

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    closeTop?.addEventListener('click', closeModal);
    closeBottom?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.querySelectorAll('.activity-open-modal').forEach((button) => {
        button.addEventListener('click', () => {
            modalTitle.textContent = button.dataset.title || '';
            modalType.textContent = button.dataset.type || 'Não informado';
            modalLocation.textContent = button.dataset.location || 'Não informado';
            modalDatetime.textContent = button.dataset.datetime || 'Não informado';

            const description = button.dataset.description || '';
            modalDescription.textContent = description;
            modalDescriptionWrap.classList.toggle('hidden', description.trim() === '');

            let speakers = [];
            const speakersRaw = button.getAttribute('data-speakers') || '[]';
            try {
                speakers = JSON.parse(speakersRaw);
            } catch (error) {
                speakers = [];
            }

            speakersContainer.innerHTML = '';
            if (Array.isArray(speakers) && speakers.length > 0) {
                speakers.forEach((speaker) => {
                    const line = document.createElement('div');
                    line.className = 'p-2.5 rounded-lg border border-slate-200 bg-slate-50';

                    const name = document.createElement('p');
                    name.className = 'font-medium text-slate-800 text-sm m-0';
                    name.textContent = speaker?.name || 'Convidado';
                    line.appendChild(name);

                    const roleText = (speaker?.role != null && String(speaker.role).trim() !== '')
                        ? String(speaker.role).trim()
                        : '';
                    if (roleText) {
                        const fn = document.createElement('p');
                        fn.className = 'text-xs text-slate-600 m-0 mt-1 leading-snug';
                        fn.textContent = roleText;
                        line.appendChild(fn);
                    }

                    speakersContainer.appendChild(line);
                });
                speakersWrap.classList.remove('hidden');
            } else {
                speakersWrap.classList.add('hidden');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });
});
</script>


@endsection
