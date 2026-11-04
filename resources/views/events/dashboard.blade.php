@extends('layouts.newMain')

@section('title', 'Painel')

@section('content')

<div class="dash-page min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">

        {{-- Card do usuário --}}
        @if(auth()->check())
        <div class="dash-card rounded-2xl p-4 sm:p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="dash-avatar w-14 h-14 sm:w-16 sm:h-16 rounded-xl flex items-center justify-center flex-shrink-0">
                        <ion-icon
                            name="{{ auth()->user()->isCoordinator() ? 'school-outline' : 'person-circle-outline' }}"
                            class="text-2xl sm:text-3xl text-white">
                        </ion-icon>
                    </div>
                    <div class="min-w-0 flex-1 overflow-hidden">
                        <h2 class="dash-title font-montserrat font-bold text-sm sm:text-lg md:text-xl break-words [overflow-wrap:anywhere]">{{ auth()->user()->name }}</h2>
                        <p class="dash-muted text-xs sm:text-sm break-all mt-0.5">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <span class="dash-badge inline-flex items-center self-start sm:self-center px-3 py-1 rounded-lg text-sm font-medium">
                    {{ auth()->user()->isCoordinator() ? 'Coordenador' : (auth()->user()->isReviewer() ? 'Avaliador' : 'Aluno') }}
                </span>
            </div>
        </div>
        @endif


        {{-- ===== COORDENADOR: Meus Eventos ===== --}}
        @if(auth()->check() && auth()->user()->isCoordinator())

        <div class="mb-10">
            <div class="flex flex-col gap-6 mb-6">
                <div>
                    <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl">Meus eventos</h1>
                    <p class="dash-text text-sm mt-1 max-w-2xl">Gerencie inscrições, trabalhos e a página pública de cada evento.</p>
                </div>
                <div class="dash-card rounded-xl p-4 sm:p-5">
                    <p class="dash-muted text-xs font-semibold uppercase tracking-wide mb-3">Cadastros de equipe</p>
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2 sm:gap-3">
                        <a href="{{ route('register.coordinator') }}"
                           class="dash-btn-soft inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors no-underline">
                            <ion-icon name="person-add-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                            Novo coordenador
                        </a>
                        <a href="{{ route('register.reviewer') }}"
                           class="dash-btn-soft inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors no-underline">
                            <ion-icon name="school-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                            Novo avaliador
                        </a>
                    </div>
                </div>
            </div>

            @if (count($events) > 0)

                <div class="grid gap-4 sm:gap-5">
                    @foreach ($events as $event)
                    <div class="dash-card dash-card-interactive rounded-2xl transition-colors">
                        <div class="p-4 sm:p-6">
                            <div class="flex flex-col gap-4 lg:gap-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex-1 min-w-0">
                                    <a href="/events/{{ $event['id'] }}" class="dash-link font-semibold text-lg transition-colors line-clamp-2 break-words no-underline block">
                                        {{ $event['title'] }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-3 flex-wrap">
                                        @php
                                            $registeredTotal = (int) ($event->registered_count ?? 0);
                                            $externalRegistered = (int) ($event->external_registered_count ?? 0);
                                            $internalRegistered = $registeredTotal - $externalRegistered;
                                            $internalQuotaLabel = $event->hasAudienceQuotas()
                                                ? $internalRegistered.'/'.(int) $event->internal_capacity
                                                : (string) $internalRegistered;
                                            $externalQuotaLabel = $event->hasAudienceQuotas()
                                                ? $externalRegistered.'/'.(int) $event->external_capacity
                                                : (string) $externalRegistered;
                                        @endphp
                                        <span class="dash-text inline-flex items-center gap-1.5 text-sm">
                                            <ion-icon name="people-outline" class="text-base shrink-0" aria-hidden="true"></ion-icon>
                                            {{ $registeredTotal }} participantes
                                        </span>
                                        <span class="dash-badge inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">
                                            Internos {{ $internalQuotaLabel }}
                                        </span>
                                        <span class="dash-badge-positive inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">
                                            Externos {{ $externalQuotaLabel }}
                                        </span>
                                        @if($event->acceptsSubmissions())
                                            <span class="dash-badge-positive inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">Aceita submissões</span>
                                        @else
                                            <span class="dash-badge inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">Sem submissão</span>
                                        @endif
                                        @if($event->isFinalized())
                                            <span class="dash-badge inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">Finalizado</span>
                                        @elseif(! $event->scheduleIsComplete())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border border-amber-200 bg-amber-50 text-amber-900">Horário a definir</span>
                                        @elseif($event->calendarEnded())
                                            <span class="dash-badge inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">Período encerrado</span>
                                        @elseif($event->calendarStarted())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border border-amber-200 bg-amber-50 text-amber-900">Em andamento</span>
                                        @else
                                            <span class="dash-badge inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium">Antes do início</span>
                                        @endif
                                    </div>
                                </div>
                                    <a href="/events/{{ $event['id'] }}"
                                       class="dash-btn inline-flex flex-wrap items-center justify-center gap-2 w-full lg:w-auto lg:shrink-0 px-4 py-3 rounded-xl text-sm font-semibold no-underline transition-colors text-center [overflow-wrap:anywhere]">
                                        <ion-icon name="arrow-forward-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                                        Abrir página do evento
                                    </a>
                                </div>

                                    @unless($event->isFinalized())
                                    <div class="dash-panel rounded-xl p-4">
                                        <p class="dash-muted text-[11px] font-semibold uppercase tracking-wider mb-3">Gestão</p>
                                        @php
                                            $submissionGestao = $event->acceptsSubmissions();
                                        @endphp
                                        {{-- Sem submissões: fluxo único em coluna (evita células vazias no grid). Com submissões: grade 2 colunas em sm+. --}}
                                        <div class="min-w-0 {{ $submissionGestao ? 'grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3' : 'flex flex-col gap-2.5 [&>a]:w-full [&>a]:justify-start lg:grid lg:grid-cols-2 lg:gap-3 xl:grid-cols-4' }}">
                                            <a href="/events/{{ $event['id'] }}/novidades"
                                               class="dash-tile group relative inline-flex flex-row items-center justify-start gap-2 px-3 py-2.5 sm:py-3 min-h-[44px] rounded-xl text-sm font-medium transition-all no-underline min-w-0">
                                                <ion-icon name="newspaper-outline" class="text-xl shrink-0" aria-hidden="true"></ion-icon>
                                                <span class="flex-1 min-w-0 text-left leading-snug break-words [overflow-wrap:anywhere]">Novidades</span>
                                            </a>
                                            @if($event->acceptsSubmissions())
                                            <a href="{{ route('events.works.index', $event->id) }}"
                                               class="dash-tile group relative inline-flex flex-row items-center justify-start gap-2 px-3 py-2.5 sm:py-3 min-h-[44px] rounded-xl text-sm font-medium transition-all no-underline min-w-0">
                                                <ion-icon name="documents-outline" class="text-xl shrink-0" aria-hidden="true"></ion-icon>
                                                <span class="flex-1 min-w-0 text-left leading-snug break-words [overflow-wrap:anywhere]">Trabalhos</span>
                                            </a>
                                            @endif
                                            @if($event->acceptsSubmissions())
                                                <a href="{{ route('events.presentations.manage', $event->id) }}"
                                                   class="dash-tile group relative inline-flex flex-row items-center justify-start gap-2 px-3 py-2.5 sm:py-3 min-h-[44px] rounded-xl text-sm font-medium transition-all no-underline min-w-0">
                                                    <ion-icon name="videocam-outline" class="text-xl shrink-0" aria-hidden="true"></ion-icon>
                                                    <span class="flex-1 min-w-0 text-left leading-snug break-words [overflow-wrap:anywhere]">Apresentações</span>
                                                </a>
                                            @endif
                                            <a href="/events/registered/{{ $event['id'] }}"
                                               class="dash-tile group relative inline-flex flex-row items-center justify-start gap-2 px-3 py-2.5 sm:py-3 min-h-[44px] rounded-xl text-sm font-medium transition-all no-underline min-w-0">
                                                <ion-icon name="people-outline" class="text-xl shrink-0" aria-hidden="true"></ion-icon>
                                                <span class="flex-1 min-w-0 text-left leading-snug break-words [overflow-wrap:anywhere]">Inscritos</span>
                                            </a>
                                            <a href="{{ route('events.certificates.index', $event->id) }}"
                                               class="dash-tile group relative inline-flex flex-row items-center justify-start gap-2 px-3 py-2.5 sm:py-3 min-h-[44px] rounded-xl text-sm font-medium transition-all no-underline min-w-0">
                                                <ion-icon name="ribbon-outline" class="text-xl shrink-0" aria-hidden="true"></ion-icon>
                                                <span class="flex-1 min-w-0 text-left leading-snug break-words [overflow-wrap:anywhere]">Certificados e presença</span>
                                            </a>
                                            <a href="/events/edit/{{ $event['id'] }}"
                                               class="dash-btn-secondary inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 sm:py-3 rounded-xl text-sm font-semibold transition-all no-underline text-center lg:justify-start [overflow-wrap:anywhere] {{ $submissionGestao ? 'sm:col-span-2 lg:col-span-1' : '' }}">
                                                <ion-icon name="create-outline" class="text-xl shrink-0" aria-hidden="true"></ion-icon>
                                                Editar evento
                                            </a>
                                        </div>
                                    </div>
                                    @endunless

                                    @if($event->isFinalized())
                                    <div class="flex flex-col gap-4 lg:grid lg:grid-cols-3 lg:items-start">
                                        <div class="lg:col-span-2">
                                            @include('events.partials.annals-upload-form', ['event' => $event])
                                        </div>
                                        <div class="flex flex-col gap-3">
                                        @if($event->acceptsSubmissions())
                                            <a href="{{ route('events.works.index', $event->id) }}"
                                               class="dash-btn-secondary inline-flex flex-wrap items-center justify-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-semibold no-underline transition-colors text-center [overflow-wrap:anywhere]">
                                                <ion-icon name="documents-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                                                Trabalhos
                                            </a>
                                        @endif
                                        <a href="/events/registered/{{ $event['id'] }}"
                                           class="dash-btn-secondary inline-flex flex-wrap items-center justify-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-semibold no-underline transition-colors text-center [overflow-wrap:anywhere]">
                                            <ion-icon name="people-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                                            Inscritos
                                        </a>
                                        <a href="{{ route('events.certificates.index', $event->id) }}"
                                           class="dash-btn-soft inline-flex flex-wrap items-center justify-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-semibold no-underline transition-colors text-center [overflow-wrap:anywhere]">
                                            <ion-icon name="ribbon-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                                            Certificados e presença
                                        </a>
                                        <a href="{{ route('events.satisfaction.results', $event) }}"
                                           class="dash-btn-secondary inline-flex flex-wrap items-center justify-center gap-2 w-full px-4 py-3 rounded-xl text-sm font-semibold no-underline transition-colors text-center [overflow-wrap:anywhere]">
                                            <ion-icon name="happy-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                                            Pesquisa de satisfação
                                        </a>
                                        </div>
                                    </div>
                                    @endif

                                    <form action="/events/{{ $event['id'] }}" method="POST"
                                          class="flex lg:justify-end"
                                          onsubmit="return confirm('Excluir este evento? Esta ação não pode ser desfeita.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex flex-wrap items-center justify-center gap-2 w-full lg:w-auto px-4 py-2.5 rounded-xl border border-red-200 bg-white text-red-700 text-sm font-medium hover:bg-red-50 hover:border-red-300 transition-colors text-center [overflow-wrap:anywhere]">
                                            <ion-icon name="trash-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                                            Excluir evento
                                        </button>
                                    </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

            @else
                <div class="dash-card rounded-2xl p-8 sm:p-12 text-center">
                    <div class="dash-icon-well w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <ion-icon name="calendar-outline" class="text-3xl" aria-hidden="true"></ion-icon>
                    </div>
                    <h3 class="dash-title font-semibold text-lg mb-2">Você ainda não tem eventos</h3>
                    <p class="dash-muted mb-6 max-w-sm mx-auto">Crie seu primeiro evento e comece a conectar pessoas.</p>
                    <a href="/events/create" class="dash-btn inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold no-underline transition-colors">
                        <ion-icon name="add-circle-outline" aria-hidden="true"></ion-icon>
                        Criar evento
                    </a>
                </div>
            @endif
        </div>

        @endif


        {{-- ===== PARTICIPANTE: Eventos que estou participando ===== --}}
        @if(auth()->check() && auth()->user()->isParticipant())

        @php
            /** Separa os eventos do participante: encerrados (finalizados pela coordenação) ficam em uma seção própria. */
            $participantActiveEvents = $eventsAsParticipant->reject(fn ($e) => $e->isFinalized())->values();
            $participantFinalizedEvents = $eventsAsParticipant->filter(fn ($e) => $e->isFinalized())->values();
        @endphp

        <div class="mb-10">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl">Eventos que estou participando</h1>
                    <p class="dash-text text-sm mt-1">Submeta trabalhos e acompanhe os prazos.</p>
                </div>
                <a href="{{ route('works.my') }}"
                   class="dash-btn-secondary inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors no-underline shrink-0">
                    <ion-icon name="document-text-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                    Meus trabalhos
                </a>
            </div>

            @if ($participantActiveEvents->isNotEmpty())

                <div class="grid gap-4 sm:gap-5">
                    @foreach ($participantActiveEvents as $event)
                    <div class="dash-card dash-card-interactive rounded-2xl overflow-hidden transition-colors">
                        <div class="p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                <div class="flex-1 min-w-0">
                                    <a href="/events/{{ $event['id'] }}" class="dash-link font-semibold transition-colors line-clamp-2 break-words no-underline block">
                                        {{ $event['title'] }}
                                    </a>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="dash-text inline-flex items-center gap-1 text-sm">
                                            <ion-icon name="people-outline" class="text-base shrink-0" aria-hidden="true"></ion-icon>
                                            {{ (int) ($event->registered_count ?? 0) }} participantes
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
                                    @php
                                        $submissionWindowOpen = $event->acceptsSubmissions()
                                            && !$event->isFinalized()
                                            && !$event->calendarEnded()
                                            && $event->workSubmissionWindowOpen();
                                        $hasUnusedWorkType = \App\Models\Work::submitterCanSubmitAnotherWork($event, auth()->id());
                                        $canSubmitWork = $submissionWindowOpen && $hasUnusedWorkType;
                                    @endphp

                                    @if($canSubmitWork)
                                        <a href="{{ route('events.works.create', $event->id) }}"
                                           class="dash-btn inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold no-underline transition-colors text-center break-words [overflow-wrap:anywhere]">
                                            <ion-icon name="cloud-upload-outline" aria-hidden="true"></ion-icon>
                                            Submeter trabalho
                                        </a>
                                    @elseif(!$event->acceptsSubmissions())
                                        <span class="dash-badge inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-center break-words [overflow-wrap:anywhere]">
                                            <ion-icon name="ban-outline" aria-hidden="true"></ion-icon>
                                            Sem submissão
                                        </span>
                                    @elseif($submissionWindowOpen && !$hasUnusedWorkType)
                                        <span class="dash-badge inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-center break-words [overflow-wrap:anywhere]">
                                            <ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>
                                            Tipos de trabalho já submetidos
                                        </span>
                                    @else
                                        <span class="dash-badge inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-center break-words [overflow-wrap:anywhere]">
                                            <ion-icon name="time-outline" aria-hidden="true"></ion-icon>
                                            Submissão encerrada
                                        </span>
                                    @endif
                                    <a href="/events/{{ $event['id'] }}"
                                       class="dash-btn-secondary inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold no-underline transition-colors text-center break-words [overflow-wrap:anywhere]">
                                        <ion-icon name="eye-outline" aria-hidden="true"></ion-icon>
                                        Ver evento
                                    </a>
                                    <form action="/events/leave/{{ $event->id }}" method="POST" class="inline"
                                          onsubmit="return confirm('Sair deste evento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-red-200 bg-white text-red-700 text-sm font-medium hover:bg-red-50 hover:border-red-300 transition-colors w-full sm:w-auto text-center break-words [overflow-wrap:anywhere]">
                                            <ion-icon name="log-out-outline" aria-hidden="true"></ion-icon>
                                            Sair do evento
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

            @elseif ($participantFinalizedEvents->isEmpty())
                <div class="dash-card rounded-2xl p-8 sm:p-12 text-center">
                    <div class="dash-icon-well w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <ion-icon name="calendar-outline" class="text-3xl" aria-hidden="true"></ion-icon>
                    </div>
                    <h3 class="dash-title font-semibold text-lg mb-2">Nenhum evento ainda</h3>
                    <p class="dash-muted mb-6 max-w-sm mx-auto">Explore os eventos disponíveis e inscreva-se nos que mais te interessam.</p>
                    <a href="/#eventos" class="dash-btn inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold no-underline transition-colors">
                        <ion-icon name="search-outline" aria-hidden="true"></ion-icon>
                        Ver eventos
                    </a>
                </div>
            @else
                {{-- Sem eventos ativos, mas ainda há histórico encerrado abaixo. --}}
                <div class="dash-card rounded-2xl p-6 sm:p-8 text-center">
                    <div class="dash-icon-well w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                        <ion-icon name="calendar-outline" class="text-2xl" aria-hidden="true"></ion-icon>
                    </div>
                    <p class="dash-text text-sm m-0">Nenhum evento em andamento agora. Veja o histórico de eventos encerrados abaixo ou
                        <a href="/#eventos" class="dash-link font-semibold no-underline hover:underline">descubra novos eventos</a>.
                    </p>
                </div>
            @endif
        </div>

        @if ($participantFinalizedEvents->isNotEmpty())
        <div class="mb-10">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
                <div>
                    <h2 class="dash-title font-montserrat font-bold text-xl sm:text-2xl m-0">Eventos encerrados</h2>
                    <p class="dash-text text-sm mt-1 m-0">Eventos finalizados pela coordenação. Acesse para conferir certificados, trabalhos e demais materiais.</p>
                </div>
                <span class="dash-badge self-start sm:self-auto inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wide">
                    <ion-icon name="lock-closed-outline" aria-hidden="true"></ion-icon>
                    {{ $participantFinalizedEvents->count() }} {{ $participantFinalizedEvents->count() === 1 ? 'evento' : 'eventos' }}
                </span>
            </div>

            <div class="grid gap-3 sm:gap-4">
                @foreach ($participantFinalizedEvents as $event)
                <div class="dash-card rounded-2xl overflow-hidden">
                    <div class="p-4 sm:p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="/events/{{ $event['id'] }}" class="dash-link font-semibold transition-colors break-words no-underline">
                                        {{ $event['title'] }}
                                    </a>
                                    <span class="dash-badge inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold">
                                        <ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>
                                        Encerrado
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-2 dash-muted text-sm">
                                    @if($event->finalized_at)
                                        <span class="inline-flex items-center gap-1">
                                            <ion-icon name="time-outline" class="text-base shrink-0" aria-hidden="true"></ion-icon>
                                            Finalizado em {{ $event->finalized_at->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
                                <a href="/events/{{ $event['id'] }}"
                                   class="dash-btn-secondary inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold no-underline transition-colors text-center break-words [overflow-wrap:anywhere]">
                                    <ion-icon name="eye-outline" aria-hidden="true"></ion-icon>
                                    Ver evento
                                </a>
                                <a href="{{ route('events.satisfaction.form', $event) }}"
                                   class="dash-btn inline-flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold no-underline transition-colors text-center break-words [overflow-wrap:anywhere]">
                                    <ion-icon name="happy-outline" aria-hidden="true"></ion-icon>
                                    {{ in_array((int) $event->id, $satisfactionAnsweredEventIds ?? [], true) ? 'Pesquisa respondida' : 'Pesquisa de satisfação' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @endif

        {{-- ===== AVALIADOR: Trabalhos designados ===== --}}
        @if(auth()->check() && auth()->user()->isReviewer())
        <div class="mb-8">
            <div class="dash-card rounded-2xl p-8 sm:p-10 text-center">
                <div class="dash-icon-well w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <ion-icon name="clipboard-outline" class="text-3xl" aria-hidden="true"></ion-icon>
                </div>
                <h3 class="dash-title font-semibold text-lg mb-2">Painel do avaliador</h3>
                <p class="dash-text mb-6 max-w-md mx-auto text-sm">Acesse os trabalhos designados e envie suas avaliações por critério.</p>
                <a href="{{ route('reviews.assigned') }}"
                   class="dash-btn inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold no-underline transition-colors">
                    <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon>
                    Ir para avaliações
                </a>
            </div>
        </div>
        @endif

    </div>
</div>

@endsection
