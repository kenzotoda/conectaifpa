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
    background: var(--dash-canvas);
}
</style>
@endpush

@section('title', $event['title'])

@section('content')
<!-- ===== CONTAINER PRINCIPAL ===== -->
<div class="dash-page min-h-screen overflow-x-hidden">

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
        <div class="dash-card rounded-lg sm:rounded-xl p-6 sm:p-8 lg:p-10">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 lg:gap-8">
                <!-- Título e informações -->
                <div class="flex-1 min-w-0">
                    <h1 class="dash-title text-2xl sm:text-3xl lg:text-4xl font-bold break-words [overflow-wrap:anywhere] mb-3">
                        {{ $event->title }}
                    </h1>
                    @php
                        $locationSummary = collect([$event->venue, $event->campus, $event->building])
                            ->filter(fn ($value) => filled($value))
                            ->map(fn ($value) => trim((string) $value))
                            ->unique()
                            ->implode(' · ');
                        $modalityIsOnlineOnly = $event->modality === 'Online';
                        $modalityIsHybrid = $event->modality === 'Híbrido';
                        $seesVacancyDetails = auth()->check()
                            && auth()->user()->isCoordinator()
                            && (int) auth()->id() === (int) $event->user_id;
                        $audienceCounts = $event->audienceRegistrationCounts();
                        $viewer = auth()->user();
                        if ($viewer && $viewer->isParticipant() && $event->hasAudienceQuotas()) {
                            $viewerSideFull = $viewer->is_external_participant
                                ? $audienceCounts['external'] >= (int) $event->external_capacity
                                : $audienceCounts['internal'] >= (int) $event->internal_capacity;
                            $simpleVacancyLabel = $viewerSideFull
                                ? 'Não há vagas para o seu perfil'
                                : 'Há vagas para o seu perfil';
                        } else {
                            $spotsOpen = $event->hasAudienceQuotas()
                                ? ($audienceCounts['internal'] < (int) $event->internal_capacity || $audienceCounts['external'] < (int) $event->external_capacity)
                                : $audienceCounts['total'] < (int) $event->capacity;
                            $simpleVacancyLabel = $spotsOpen ? 'Há vagas' : 'Vagas esgotadas';
                        }
                    @endphp
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if(filled($event->category))
                            <span class="dash-badge-positive inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">{{ $event->category }}</span>
                        @endif
                        @if(filled($event->modality))
                            <span class="dash-badge inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">{{ $event->modality }}</span>
                        @endif
                        @if(filled($event->event_type))
                            <span class="dash-badge inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">{{ $event->event_type }}</span>
                        @endif
                        @if($event->natureLabel())
                            <span class="dash-badge inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">{{ $event->natureLabel() }}</span>
                        @endif
                        <span class="dash-badge inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">{{ $event->porteLabel() }}</span>
                        <span class="dash-badge inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold">{{ $event->accessLabel() }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        @if($event->scopeLabel())
                            <div><dt class="dash-muted inline">Abrangência:</dt> <dd class="dash-title inline">{{ $event->scopeLabel() }}</dd></div>
                        @endif
                        @if($event->periodicityDisplay())
                            <div><dt class="dash-muted inline">Periodicidade:</dt> <dd class="dash-title inline">{{ $event->periodicityDisplay() }}</dd></div>
                        @endif
                        <div><dt class="dash-muted inline">Porte:</dt> <dd class="dash-title inline">{{ $event->porteLabel() }} ({{ $event->audienceCapacityTotal() }} vagas)</dd></div>
                        <div><dt class="dash-muted inline">Acesso:</dt> <dd class="dash-title inline">{{ $event->accessLabel() }}</dd></div>
                    </dl>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-sm">
                        <div class="dash-panel flex gap-3 rounded-xl px-3.5 py-3 min-w-0">
                            <span class="dash-icon-well flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                <ion-icon name="calendar-outline" class="text-lg" aria-hidden="true"></ion-icon>
                            </span>
                            <div class="min-w-0">
                                <p class="dash-muted m-0 text-[11px] font-semibold uppercase tracking-wide">Quando</p>
                                <p class="dash-title m-0 mt-0.5 font-medium">
                                    {{ $event->start_date->format('d/m/Y') }}
                                    @if($event->end_date)
                                        até {{ $event->end_date->format('d/m/Y') }}
                                    @endif
                                </p>
                                <p class="dash-muted m-0 mt-0.5 text-xs">
                                    @if($event->scheduleIsComplete())
                                        {{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}
                                        às {{ \Carbon\Carbon::parse($event->end_time)->format('H:i') }}
                                    @else
                                        Horário a definir
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if($event->datetime_registration)
                            <div class="dash-panel flex gap-3 rounded-xl px-3.5 py-3 min-w-0">
                                <span class="dash-icon-well flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                    <ion-icon name="alarm-outline" class="text-lg" aria-hidden="true"></ion-icon>
                                </span>
                                <div class="min-w-0">
                                    <p class="dash-muted m-0 text-[11px] font-semibold uppercase tracking-wide">Inscrições até</p>
                                    <p id="eventRegistrationPeriod" class="dash-title m-0 mt-0.5 font-medium">{{ $event->datetime_registration->format('d/m/Y') }} às {{ $event->datetime_registration->format('H:i') }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="dash-panel flex gap-3 rounded-xl px-3.5 py-3 min-w-0 sm:col-span-2">
                            <span class="dash-icon-well flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                <ion-icon name="{{ $modalityIsOnlineOnly ? 'globe-outline' : 'location-outline' }}" class="text-lg" aria-hidden="true"></ion-icon>
                            </span>
                            <div class="min-w-0">
                                <p class="dash-muted m-0 text-[11px] font-semibold uppercase tracking-wide">{{ $modalityIsOnlineOnly ? 'Acesso' : 'Onde' }}</p>
                                @if($modalityIsOnlineOnly)
                                    <p class="dash-title m-0 mt-0.5 font-medium leading-relaxed [overflow-wrap:anywhere]">
                                        Evento online. O acesso é remoto, sem local físico para o público.
                                        @if($event->ead_link)
                                            <a href="{{ $event->ead_link }}" target="_blank" rel="noopener noreferrer" class="dash-link font-semibold">Link do ambiente</a>
                                        @endif
                                    </p>
                                @else
                                    <p class="dash-title m-0 mt-0.5 font-medium leading-relaxed [overflow-wrap:anywhere]">{{ $locationSummary }}</p>
                                    @if($modalityIsHybrid)
                                        <p class="dash-muted m-0 mt-1 text-xs leading-relaxed [overflow-wrap:anywhere]">
                                            Há participação presencial no campus e acesso remoto.
                                            @if($event->ead_link)
                                                <a href="{{ $event->ead_link }}" target="_blank" rel="noopener noreferrer" class="dash-link font-semibold">Link do ambiente online</a>
                                            @endif
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if($seesVacancyDetails)
                            @if($event->hasAudienceQuotas())
                                @php
                                    $internalRemaining = max(0, (int) $event->internal_capacity - $audienceCounts['internal']);
                                    $externalRemaining = max(0, (int) $event->external_capacity - $audienceCounts['external']);
                                @endphp
                                <div class="dash-panel rounded-xl px-3.5 py-3 min-w-0">
                                    <p class="dash-muted m-0 text-[11px] font-semibold uppercase tracking-wide">Vagas internas</p>
                                    <p class="dash-title m-0 mt-1 text-base font-semibold">{{ $internalRemaining }} restantes</p>
                                    <p class="dash-muted m-0 mt-0.5 text-xs">{{ $audienceCounts['internal'] }} de {{ (int) $event->internal_capacity }} ocupadas</p>
                                </div>
                                <div class="dash-panel rounded-xl px-3.5 py-3 min-w-0">
                                    <p class="dash-muted m-0 text-[11px] font-semibold uppercase tracking-wide">Vagas externas</p>
                                    <p class="dash-title m-0 mt-1 text-base font-semibold">{{ $externalRemaining }} restantes</p>
                                    <p class="dash-muted m-0 mt-0.5 text-xs">{{ $audienceCounts['external'] }} de {{ (int) $event->external_capacity }} ocupadas</p>
                                </div>
                            @else
                                <div class="dash-panel rounded-xl px-3.5 py-3 min-w-0 sm:col-span-2">
                                    <p class="dash-muted m-0 text-[11px] font-semibold uppercase tracking-wide">Vagas</p>
                                    <p class="dash-title m-0 mt-1 text-base font-semibold">{{ $audienceCounts['total'] }} de {{ (int) $event->capacity }} ocupadas</p>
                                </div>
                            @endif
                        @else
                            <div class="dash-panel flex gap-3 rounded-xl px-3.5 py-3 min-w-0 sm:col-span-2">
                                <span class="dash-icon-well flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                    <ion-icon name="people-outline" class="text-lg" aria-hidden="true"></ion-icon>
                                </span>
                                <div class="min-w-0">
                                    <p class="dash-muted m-0 text-[11px] font-semibold uppercase tracking-wide">Vagas</p>
                                    <p class="dash-title m-0 mt-0.5 font-medium">{{ $simpleVacancyLabel }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Área de inscrição -->
                <div id="inscricoes" class="flex-shrink-0 w-full lg:w-auto lg:min-w-[280px] flex flex-col items-stretch lg:items-end justify-center gap-3">
                    @if($event->hasAnnalsPublication())
                    <a href="{{ route('events.annals.download', $event->id) }}"
                       class="dash-link w-full lg:w-auto text-center lg:text-right inline-flex items-center justify-center gap-2 font-semibold text-sm no-underline order-first lg:order-none">
                        <ion-icon name="download-outline" class="text-lg"></ion-icon>
                        Baixar {{ $event->annalsKindLabel() }}
                    </a>
                    @endif
                    @php
                        $bloqueio = $event->registrationsBlockedReasonFor(auth()->user());
                        $alreadyRegistered = auth()->check() && $event->users()->where('users.id', auth()->id())->exists();
                    @endphp
                    @if ($alreadyRegistered)
                        <p class="dash-text text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="checkmark-circle-outline" class="text-lg text-primary-custom shrink-0 mt-0.5"></ion-icon>
                            <span>Você já está inscrito neste evento.</span>
                        </p>
                    @elseif ($bloqueio === 'finalized')
                        <p class="dash-text text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="lock-closed-outline" class="dash-muted text-lg shrink-0 mt-0.5"></ion-icon>
                            <span>Este evento foi finalizado pelo coordenador.</span>
                        </p>
                    @elseif ($bloqueio === 'ended')
                        <p class="dash-text text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="flag-outline" class="dash-muted text-lg shrink-0 mt-0.5"></ion-icon>
                            <span>O período deste evento foi encerrado.</span>
                        </p>
                    @elseif ($bloqueio === 'started')
                        <p class="dash-text text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="play-circle-outline" class="dash-muted text-lg shrink-0 mt-0.5"></ion-icon>
                            <span>Este evento está em andamento. As inscrições estão encerradas.</span>
                        </p>
                    @elseif ($bloqueio === 'deadline')
                        <p class="dash-text text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="alarm-outline" class="dash-muted text-lg shrink-0 mt-0.5"></ion-icon>
                            <span>Prazo de inscrições encerrado.</span>
                        </p>
                    @elseif ($bloqueio === 'full')
                        <p class="dash-text text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="close-circle-outline" class="dash-muted text-lg shrink-0 mt-0.5"></ion-icon>
                            <span>Inscrições indisponíveis: lotação esgotada.</span>
                        </p>
                    @elseif ($bloqueio === 'internal_full')
                        <p class="dash-text text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="close-circle-outline" class="dash-muted text-lg shrink-0 mt-0.5"></ion-icon>
                            <span>As vagas para participantes internos estão esgotadas.</span>
                        </p>
                    @elseif ($bloqueio === 'external_full')
                        <p class="dash-text text-sm m-0 inline-flex items-start gap-2 max-w-md lg:text-right lg:justify-end">
                            <ion-icon name="close-circle-outline" class="dash-muted text-lg shrink-0 mt-0.5"></ion-icon>
                            <span>As vagas para participantes externos estão esgotadas.</span>
                        </p>
                    @else
                        @php
                            $podeInscrever = !auth()->check() || auth()->user()->isParticipant();
                        @endphp
                        @if($podeInscrever)
                            <form action="/events/join/{{ $event['id'] }}" method="POST" class="w-full lg:w-auto">
                                @csrf
                                <button type="submit" id="event-submit"
                                    class="dash-btn w-full lg:w-auto min-w-[260px] inline-flex items-center justify-center gap-2.5 rounded-2xl font-semibold px-8 py-4 text-base">
                                    <ion-icon name="person-add-outline" class="text-2xl shrink-0" aria-hidden="true"></ion-icon>
                                    <span>Realizar inscrição</span>
                                </button>
                            </form>
                        @elseif(auth()->check() && auth()->user()->isCoordinator())
                            <p class="dash-panel dash-text text-sm leading-relaxed m-0 max-w-sm lg:text-right rounded-xl px-4 py-3">
                                Coordenadores não fazem inscrição em evento. Para se inscrever, entre com uma conta de participante.
                            </p>
                        @elseif(auth()->check() && auth()->user()->isReviewer())
                            <p class="dash-panel dash-text text-sm leading-relaxed m-0 max-w-sm lg:text-right rounded-xl px-4 py-3">
                                Avaliadores não fazem inscrição em evento. Para se inscrever, entre com uma conta de participante.
                            </p>
                        @endif
                    @endif
                </div>
            </div>

                    @auth
                @if(auth()->user()->isCoordinator() && auth()->id() === $event->user_id)
                    <div class="dash-rule mt-6 pt-6 border-t">
                        <h3 class="dash-title text-sm font-semibold m-0 mb-3">Coordenação</h3>
                        @if($event->isFinalized())
                            <p class="dash-text text-sm max-w-2xl mb-4">
                                Evento finalizado: não é possível editar, gerenciar inscritos ou novidades. A página permanece visível ao público e você pode excluir o evento no painel.
                            </p>
                            @include('events.partials.annals-upload-form', ['event' => $event])
                        @elseif($event->calendarEnded() || config('app.allow_early_event_finalize'))
                            @if(! $event->calendarEnded() && config('app.allow_early_event_finalize'))
                                <p class="text-sm text-amber-900 mb-3 max-w-2xl rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    Modo de teste: a finalização antes do horário de fim está ativa (<code class="text-xs bg-amber-100/80 px-1 rounded">EVENT_ALLOW_EARLY_FINALIZE</code> no <code class="text-xs bg-amber-100/80 px-1 rounded">.env</code>). Use para validar certificados e fluxos pós evento; desligue em produção.
                                </p>
                            @else
                                <p class="dash-text text-sm mb-3 max-w-2xl">
                                    O período do evento já terminou conforme data e horário de fim. Para encerrar de forma definitiva (sem edição nem gestão), finalize abaixo.
                                </p>
                            @endif
                            <div class="flex flex-wrap gap-3 items-center">
                                <form action="{{ url('/events/'.$event->id.'/finalize') }}" method="POST" class="inline"
                                      onsubmit="return confirm('Finalizar definitivamente? Você não poderá mais editar o evento, novidades ou inscritos.');">
                                    @csrf
                                    <button type="submit"
                                        class="dash-btn inline-flex items-center justify-center gap-2 font-semibold px-4 py-2.5 rounded-xl text-sm">
                                        <ion-icon name="checkmark-done-outline" class="text-lg"></ion-icon>
                                        Finalizar evento
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="dash-muted text-sm max-w-2xl">
                                Datas, horários e prazos de inscrição e de submissão podem ser ajustados na edição até a finalização. Se a mudança deixar uma atividade ou uma apresentação fora do novo período, o salvamento é recusado e a mensagem lista o que corrigir. Sem horário de início e de fim, as inscrições seguem pelo prazo de inscrição, o evento não aparece como encerrado e não pode ser finalizado, e apresentações não podem ser agendadas. Quando os dois horários existem, novas inscrições encerram na data e hora de início. Quando o período terminar, esta área mostra a opção de <span class="dash-title font-medium">finalizar</span> o evento.
                            </p>
                        @endif
                    </div>
                @endif
            @endauth
        </div>
    </section>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <section class="py-12 sm:py-16 lg:py-20 overflow-x-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-w-0">
        <div id="eventPage" class="space-y-12">

            <!-- ===== SEÇÃO: SOBRE O EVENTO ===== -->
            <div class="space-y-6">
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Sobre o Evento</h2>
                
                <div class="dash-card rounded-2xl p-6 sm:p-8 lg:p-10 overflow-hidden">
                    <div id="eventDescription" class="dash-text leading-relaxed break-words [overflow-wrap:anywhere] text-[15px] [&_p]:mb-4 [&_p:last-child]:mb-0 [&_h1]:text-2xl [&_h1]:font-semibold [&_h1]:mb-3 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:mb-2 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:mb-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:mb-4 [&_li]:mb-1">
                        {!! \Mews\Purifier\Facades\Purifier::clean($event->description) !!}
                    </div>
                </div>
            </div>

            <!-- ===== SEÇÃO: LOCALIZAÇÃO / ACESSO ===== -->
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">
                    @if($event->modality === 'Online')
                        Acesso ao evento
                    @else
                        Localização
                    @endif
                </h2>
                <div class="dash-card rounded-2xl p-6 sm:p-8 lg:p-10 min-w-0 overflow-hidden">
                    @if($event->modality === 'Online')
                        <div class="dash-text text-sm space-y-4 max-w-2xl">
                            <p class="m-0 leading-relaxed">
                                Este evento é <strong class="dash-title">100% online</strong>. Não há sede física de realização divulgada nesta página — o acesso é remoto (meet, plataforma EAD, etc.).
                            </p>
                            @if($event->ead_link)
                                <p class="m-0">
                                    <a href="{{ $event->ead_link }}" target="_blank" rel="noopener noreferrer" class="dash-link inline-flex items-center gap-1.5 font-semibold">
                                        Abrir link do ambiente <ion-icon name="open-outline" class="text-base"></ion-icon>
                                    </a>
                                </p>
                            @else
                                <p class="dash-muted m-0">O link do ambiente ainda não foi informado na ficha do evento.</p>
                            @endif
                        </div>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                            <ul class="space-y-3 text-sm">
                                <li>
                                    <span class="dash-muted block">Local fixo @if($event->modality === 'Híbrido') (presencial)@endif</span>
                                    <span class="dash-title font-semibold">{{ \App\Models\Event::FIXED_VENUE }}</span>
                                </li>
                                <li class="min-w-0 overflow-hidden">
                                    <span class="dash-muted block">Endereço</span>
                                    <span class="dash-title font-medium break-words [overflow-wrap:anywhere]">{{ \App\Models\Event::FIXED_ADDRESS }}</span>
                                </li>
                                <li>
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(\App\Models\Event::FIXED_ADDRESS) }}" target="_blank" rel="noopener noreferrer" class="dash-link inline-flex items-center gap-1 font-medium">
                                        Abrir no Google Maps <ion-icon name="open-outline" class="text-sm"></ion-icon>
                                    </a>
                                </li>
                                @if($event->modality === 'Híbrido')
                                    <li class="dash-rule pt-2 border-t">
                                        <span class="dash-muted block mb-1">Modalidade híbrida</span>
                                        <span class="dash-title">Há transmissão ou atividades online além do presencial no campus.@if($event->ead_link) Consulte também o <a href="{{ $event->ead_link }}" target="_blank" rel="noopener noreferrer" class="dash-link font-semibold">ambiente EAD</a>.@endif</span>
                                    </li>
                                @endif
                            </ul>
                            <iframe
                                src="{{ \App\Models\Event::FIXED_MAP_EMBED_URL }}"
                                class="dash-preview w-full h-72 rounded-lg"
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
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Organização e contato</h2>
                <div class="dash-card rounded-2xl p-6 sm:p-8 lg:p-10 min-w-0 overflow-hidden">
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <li>
                            <span class="dash-muted block">Organizado por</span>
                            <span id="eventCoordinator" class="dash-title font-medium">{{ $event->coordinator_name }}</span>
                        </li>
                        @if(filled($event->coordinator_phone))
                        <li>
                            <span class="dash-muted block">Contato</span>
                            <span id="eventPhone" class="dash-title font-medium">{{ $event->coordinator_phone }}</span>
                        </li>
                        @endif
                        <li class="md:col-span-2">
                            <span class="dash-muted block">Entrar em contato</span>
                            <a href="mailto:{{ $event->coordinator_email }}" id="eventEmail" class="dash-link font-medium break-all">{{ $event->coordinator_email }}</a>
                        </li>
                        @if($event->ead_link)
                        <li class="md:col-span-2">
                            <span class="dash-muted block">Ambiente EAD</span>
                            <a href="{{ $event->ead_link }}" id="eventEADLink" target="_blank" class="dash-link font-medium inline-flex items-center gap-1">
                                Acessar <ion-icon name="open-outline" class="text-sm"></ion-icon>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            <!-- ===== SEÇÃO: ATIVIDADES ===== -->
            <div class="space-y-6">
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Atividades</h2>

                <div class="dash-card rounded-2xl p-6 sm:p-8 lg:p-10">
                    @php
                        $activities = $event->activities->sortBy('start_at')->values();
                        $activitiesByDay = $activities->groupBy(function ($activity) {
                            return optional($activity->start_at)->format('Y-m-d');
                        });
                        $firstDayKey = $activitiesByDay->keys()->first();
                    @endphp
                    @if($activities->isEmpty())
                        <p class="dash-text text-sm m-0">A programação ainda não possui atividades cadastradas.</p>
                    @else
                        <div class="dash-rule border-b mb-4">
                            <div class="flex gap-2 overflow-x-auto pb-2">
                                @foreach($activitiesByDay as $dayKey => $dayActivities)
                                    @php
                                        $dayDate = \Carbon\Carbon::parse($dayKey);
                                    @endphp
                                    <button
                                        type="button"
                                        class="activity-day-tab px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors {{ $dayKey === $firstDayKey ? 'is-active' : '' }}"
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
                                        class="activity-open-modal dash-panel w-full text-left p-4 rounded-xl transition-colors hover:border-[var(--dash-brand)]"
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
                                            <p class="dash-title font-semibold m-0 break-words">{{ $activity->title }}</p>
                                            <span class="dash-muted text-sm font-semibold">{{ $timeLabel }}</span>
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
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Convidados</h2>

                <div class="dash-card rounded-2xl p-6 sm:p-8 lg:p-10 min-w-0 overflow-hidden">
                    @if($event->guests->isEmpty())
                        <p class="dash-text text-sm m-0">Nenhum convidado cadastrado neste evento ainda.</p>
                    @else
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 list-none m-0 p-0">
                            @foreach($event->guests as $guest)
                                @php
                                    $guestFn = ($guestRoleTypeLabels ?? [])[$guest->role_type] ?? null;
                                    if ($guestFn === null && filled($guest->role_type)) {
                                        $guestFn = ucfirst((string) $guest->role_type);
                                    }
                                @endphp
                                <li class="dash-panel flex gap-3 p-4 rounded-xl min-w-0">
                                    <div class="dash-avatar w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <ion-icon name="person-outline" class="text-lg" aria-hidden="true"></ion-icon>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="dash-title font-semibold m-0 text-sm sm:text-base break-words [overflow-wrap:anywhere]">
                                            {{ $guest->name }}
                                        </p>
                                        @if(filled($guestFn))
                                            <p class="dash-text text-xs sm:text-sm m-0 mt-1">{{ $guestFn }}</p>
                                        @endif
                                        @if(filled($guest->role))
                                            <p class="dash-muted text-[11px] font-semibold uppercase tracking-wide m-0 mt-3">Formação</p>
                                            <p class="dash-text text-xs sm:text-sm m-0 mt-1 leading-relaxed whitespace-pre-line break-words [overflow-wrap:anywhere]">
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
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Público-Alvo</h2>

                <div class="flex flex-wrap gap-3 min-w-0">
                    @foreach($event->target_audience as $audience)
                        @php
                            $a = is_string($audience) ? json_decode($audience, true) : $audience;
                        @endphp
                        <span class="dash-badge inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm min-w-0 max-w-full overflow-hidden">
                            <ion-icon name="checkmark" class="text-primary-custom text-base flex-shrink-0"></ion-icon>
                            <span class="min-w-0 break-words [overflow-wrap:anywhere]">{{ $a['name'] ?? $a ?? '' }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- ===== SEÇÃO: PRÉ-REQUISITOS ===== -->
            @if($event->prerequisites && count($event->prerequisites) > 0)
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Pré-Requisitos</h2>

                <ul class="space-y-3 min-w-0">
                    @foreach($event->prerequisites as $prereq)
                        @php
                            $p = is_string($prereq) ? json_decode($prereq, true) : $prereq;
                        @endphp
                        <li class="dash-card flex items-start gap-3 p-4 rounded-xl min-w-0 overflow-hidden">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary-custom mt-2 flex-shrink-0"></span>
                            <span class="dash-text text-sm leading-relaxed break-words min-w-0">{{ $p['name'] ?? $p ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($event->acceptsSubmissions() || ($acceptedWorkTypes ?? collect())->isNotEmpty())
            <div class="space-y-6 min-w-0 overflow-hidden">
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Submissões Científicas</h2>
                <div class="dash-card rounded-2xl p-6 sm:p-8 lg:p-10">
                    @if(!$event->acceptsSubmissions())
                        <div class="dash-panel rounded-xl p-4">
                            <p class="dash-text m-0">
                                Este evento está configurado para participação/aprendizado e não recebe submissão de trabalhos.
                            </p>
                        </div>
                    @else
                        @if($event->submission_deadline_at)
                            <div class="dash-panel flex flex-wrap items-start gap-3 p-4 rounded-xl mb-6">
                                <ion-icon name="calendar-clear-outline" class="dash-muted text-xl shrink-0 mt-0.5" aria-hidden="true"></ion-icon>
                                <div class="min-w-0">
                                    <p class="dash-muted text-xs font-semibold uppercase tracking-wide m-0">Prazo de submissão</p>
                                    <p class="dash-title font-medium m-0 mt-1 text-sm sm:text-base">
                                        {{ $event->submission_deadline_at->format('d/m/Y') }} às {{ $event->submission_deadline_at->format('H:i') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                        @if($event->review_deadline_at)
                            <div class="dash-panel flex flex-wrap items-start gap-3 p-4 rounded-xl mb-6">
                                <ion-icon name="hourglass-outline" class="dash-muted text-xl shrink-0 mt-0.5" aria-hidden="true"></ion-icon>
                                <div class="min-w-0">
                                    <p class="dash-muted text-xs font-semibold uppercase tracking-wide m-0">Prazo de avaliação</p>
                                    <p class="dash-title font-medium m-0 mt-1 text-sm sm:text-base">
                                        {{ $event->review_deadline_at->format('d/m/Y') }} às {{ $event->review_deadline_at->format('H:i') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if(($acceptedWorkTypes ?? collect())->isNotEmpty())
                            <div class="mb-6">
                                <h3 class="dash-title font-semibold mb-3">Tipos de trabalho aceitos</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($acceptedWorkTypes as $workType)
                                        <span class="dash-badge px-3 py-1 rounded-full text-sm">
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
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Documentos e Anexos</h2>

                <div class="dash-card rounded-2xl p-6 sm:p-8 lg:p-10">
                    @if($event->documents->isEmpty())
                        <p class="dash-text text-sm m-0">Ainda não há documentos publicados para este evento.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($event->documents as $document)
                                <div class="dash-panel p-4 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <p class="dash-title font-semibold m-0">{{ $document->title }}</p>
                                        <p class="dash-muted text-sm mt-1 mb-0">
                                            {{ $documentTypeLabels[$document->document_type] ?? 'Documento' }}
                                            • Publicado em {{ $document->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('events.documents.download', [$event->id, $document->id]) }}"
                                           class="dash-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium no-underline">
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
                <h2 class="dash-heading text-xl sm:text-2xl font-bold">Novidades</h2>

                @if($event->eventNews->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($event->eventNews as $novidade)
                            <div class="dash-card relative rounded-xl p-6 min-w-0 overflow-hidden">
                                <span class="dash-rail" aria-hidden="true"></span>
                                <div class="flex items-start gap-4 min-w-0 pl-2">
                                    <div class="dash-avatar w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <ion-icon name="megaphone-outline" class="text-lg"></ion-icon>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="dash-title font-semibold mb-1 break-words">{{ $novidade->title }}</h3>
                                        <p class="dash-text leading-relaxed text-sm break-words [overflow-wrap:anywhere]">{{ $novidade->content }}</p>
                                        <span class="dash-muted text-xs mt-2 block">{{ $novidade->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="dash-text text-sm">Este evento ainda não possui novidades.</p>
                @endif
            </div>

        </div>
    </div>
    </section>
</div>

<div id="activityDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4">
    <div class="dash-card rounded-2xl w-full max-w-lg max-h-[90vh] overflow-auto">
        <div class="dash-rule p-4 sm:p-5 border-b flex items-start justify-between gap-3">
            <h3 id="activityModalTitle" class="dash-title text-base sm:text-lg font-semibold leading-snug m-0 pr-2"></h3>
            <button type="button" id="activityModalCloseTop" class="dash-muted hover:text-[var(--dash-title)] text-2xl leading-none shrink-0" aria-label="Fechar">&times;</button>
        </div>
        <div class="p-4 sm:p-5 space-y-4 text-sm">
            <div id="activityModalSpeakersWrap" class="hidden">
                <h4 class="dash-muted text-xs font-semibold uppercase tracking-wide mb-2">Convidados</h4>
                <div id="activityModalSpeakers" class="space-y-2"></div>
            </div>

            <div>
                <h4 class="dash-muted text-xs font-semibold uppercase tracking-wide mb-1">Tipo</h4>
                <p id="activityModalType" class="dash-title text-sm m-0"></p>
            </div>
            <div>
                <h4 class="dash-muted text-xs font-semibold uppercase tracking-wide mb-1">Local</h4>
                <p id="activityModalLocation" class="dash-title text-sm m-0"></p>
            </div>
            <div>
                <h4 class="dash-muted text-xs font-semibold uppercase tracking-wide mb-1">Data e horário</h4>
                <p id="activityModalDatetime" class="dash-title text-sm m-0"></p>
            </div>
            <div id="activityModalDescriptionWrap" class="hidden">
                <h4 class="dash-muted text-xs font-semibold uppercase tracking-wide mb-1">Descrição</h4>
                <p id="activityModalDescription" class="dash-text text-sm leading-relaxed whitespace-pre-line m-0"></p>
            </div>
        </div>
        <div class="p-4 sm:px-5 sm:pb-5 pt-0 flex justify-end">
            <button type="button" id="activityModalCloseBottom" class="dash-btn-secondary px-4 py-2 rounded-lg text-sm">
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
                item.classList.remove('is-active');
            });
            tab.classList.add('is-active');

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
                    line.className = 'dash-panel p-2.5 rounded-lg';

                    const name = document.createElement('p');
                    name.className = 'dash-title font-medium text-sm m-0';
                    name.textContent = speaker?.name || 'Convidado';
                    line.appendChild(name);

                    const roleText = (speaker?.role != null && String(speaker.role).trim() !== '')
                        ? String(speaker.role).trim()
                        : '';
                    if (roleText) {
                        const fn = document.createElement('p');
                        fn.className = 'dash-muted text-xs m-0 mt-1 leading-snug';
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
