@extends('layouts.newMain')

@section('title', 'Detalhes do Trabalho')

@section('content')
<div class="min-h-screen bg-slate-50/50">
    @php
        $workTypeLabels = \App\Models\Work::workTypeLabels();
        $Wr = \App\Models\WorkReviewer::class;
        $Rv = \App\Models\Review::class;
        $W = \App\Models\Work::class;
        $Wp = \App\Models\WorkPresentation::class;
        $presentationTypeLabels = [
            $Wp::TYPE_ORAL => 'Apresentação oral',
            $Wp::TYPE_POSTER => 'Pôster',
            $Wp::TYPE_ONLINE => 'Online',
        ];
        $recommendationLabels = [
            $Rv::RECOMMENDATION_ACCEPT => 'Aprovar',
            $Rv::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS => 'Aprovar com correções',
            $Rv::RECOMMENDATION_REJECT => 'Reprovar',
        ];
        $reviewAssignmentStatusBadgeClass = [
            $Wr::STATUS_ASSIGNED => 'bg-slate-100 text-slate-900 ring-1 ring-slate-200 shadow-sm',
            $Wr::STATUS_IN_PROGRESS => 'bg-sky-50 text-sky-950 ring-1 ring-sky-200 shadow-sm',
            $Wr::STATUS_COMPLETED => 'bg-emerald-50 text-emerald-950 ring-1 ring-emerald-200 shadow-sm',
        ];
        $recommendationBadgeClassByKey = [
            $Rv::RECOMMENDATION_ACCEPT => 'bg-emerald-50 text-emerald-950 ring-1 ring-emerald-200 shadow-sm',
            $Rv::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS => 'bg-amber-50 text-amber-950 ring-1 ring-amber-300/80 shadow-sm',
            $Rv::RECOMMENDATION_REJECT => 'bg-red-50 text-red-950 ring-1 ring-red-200 shadow-sm',
        ];
        if ($work->isAwaitingCorrectionReReview()) {
            $workStatusBadgeClass = 'bg-indigo-50 text-indigo-950 ring-1 ring-indigo-200/90 shadow-sm';
        } else {
            $workStatusBadgeClass = match ($work->status) {
                $W::STATUS_SUBMITTED => 'bg-slate-100 text-slate-900 ring-1 ring-slate-300/80 shadow-sm',
                $W::STATUS_UNDER_REVIEW => 'bg-amber-50 text-amber-950 ring-1 ring-amber-200 shadow-sm',
                $W::STATUS_APPROVED_FINAL => 'bg-sky-50 text-sky-950 ring-1 ring-sky-200 shadow-sm',
                $W::STATUS_ACCEPTED_WITH_CORRECTIONS => 'bg-orange-50 text-orange-950 ring-1 ring-orange-200 shadow-sm',
                $W::STATUS_REJECTED => 'bg-red-50 text-red-950 ring-1 ring-red-200 shadow-sm',
                $W::STATUS_DESK_REJECTED => 'bg-red-50 text-red-950 ring-1 ring-red-200 shadow-sm',
                $W::STATUS_FINAL_VALIDATED => 'bg-emerald-50 text-emerald-950 ring-1 ring-emerald-200 shadow-sm',
                $W::STATUS_SCHEDULED => 'bg-cyan-50 text-cyan-950 ring-1 ring-cyan-200 shadow-sm',
                $W::STATUS_PRESENTED => 'bg-green-50 text-green-950 ring-1 ring-green-200 shadow-sm',
                $W::STATUS_ABSENT => 'bg-rose-50 text-rose-950 ring-1 ring-rose-200 shadow-sm',
                $W::STATUS_PUBLISHED_ANNALS => 'bg-violet-50 text-violet-950 ring-1 ring-violet-200 shadow-sm',
                $W::STATUS_CONFLICT => 'bg-amber-100 text-amber-950 ring-1 ring-amber-300 shadow-sm',
                $W::STATUS_WITHDRAWAL_REQUESTED => 'bg-slate-200 text-slate-900 ring-1 ring-slate-400/40 shadow-sm',
                $W::STATUS_WITHDRAWN => 'bg-slate-200 text-slate-800 ring-1 ring-slate-300 shadow-sm',
                $W::STATUS_CANCELLED => 'bg-slate-200 text-slate-800 ring-1 ring-slate-300 shadow-sm',
                default => 'bg-slate-100 text-slate-900 ring-1 ring-slate-200 shadow-sm',
            };
        }
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 pb-12">
        @php
            $canReviewerDownloadWork = !($isAssignedReviewer ?? false) || $work->event->workSubmissionDeadlinePassed();
            $evt = $work->event;
            $downloadIsOfficial = filled($work->final_version_file_path) && $work->final_version_validated_at !== null;
        @endphp
        <header class="mb-8 rounded-2xl border border-slate-200/90 bg-white shadow-[0_1px_6px_rgba(15,23,42,0.07)] overflow-hidden">
            <div class="px-5 py-5 sm:px-6 sm:py-6">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-2 flex-wrap">
                    <a href="{{ route('events.show', $evt->id) }}" class="inline-flex items-center gap-1.5 font-semibold text-slate-600 hover:text-emerald-700 transition-colors no-underline">
                        <ion-icon name="calendar-outline" class="text-emerald-600 text-base shrink-0" aria-hidden="true"></ion-icon>
                        <span class="truncate max-w-[min(100vw-4rem,28rem)] sm:max-w-none">{{ $evt->title }}</span>
                    </a>
                </p>
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-5">
                    <div class="min-w-0 flex-1">
                        <h1 class="font-montserrat font-bold text-2xl sm:text-3xl lg:text-[1.875rem] text-slate-900 leading-tight [overflow-wrap:anywhere] m-0">{{ $work->listTitleCompact($isCoordinator) }}</h1>
                        <p class="mt-3 m-0">
                            <span class="inline-flex items-center rounded-lg px-3 py-1 text-sm font-semibold {{ $workStatusBadgeClass }}">{{ $work->statusLabel() }}</span>
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 shrink-0 w-full xl:w-auto xl:max-w-[22rem]">
                        <div class="flex flex-wrap gap-2 justify-start xl:justify-end">
                            @if($canReviewerDownloadWork)
                                <a href="{{ route('works.download', $work->id) }}" id="work-download-link" aria-describedby="work-download-hint"
                                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold no-underline transition-colors shadow-sm ring-1 ring-indigo-700/25">
                                    <ion-icon name="download-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                                    Baixar arquivo
                                </a>
                            @else
                                <span class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-500 text-sm font-semibold cursor-not-allowed ring-1 ring-slate-200"
                                      title="O download para avaliação só é liberado após o encerramento do prazo de submissão." aria-describedby="work-download-hint">
                                    <ion-icon name="time-outline" class="text-lg shrink-0"></ion-icon>
                                    Baixar (após o prazo)
                                </span>
                            @endif
                            <a href="{{ $isCoordinator ? route('events.works.index', $work->event_id) : (auth()->user()->isReviewer() ? route('reviews.assigned') : route('works.my')) }}"
                               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white text-slate-800 text-sm font-semibold no-underline transition-colors hover:bg-slate-50 shadow-sm ring-1 ring-slate-200">
                                <ion-icon name="arrow-back-outline" class="text-lg shrink-0"></ion-icon>
                                Voltar
                            </a>
                        </div>
                        <p id="work-download-hint" class="text-[11px] sm:text-xs text-slate-600 m-0 leading-relaxed xl:text-right border-t border-slate-100 xl:border-0 xl:pt-0 pt-3 mt-1 xl:mt-0">
                            @if(! $canReviewerDownloadWork)
                                Para <strong>avaliadores</strong>, o arquivo do trabalho só pode ser baixado <strong>depois do encerramento do prazo de submissão</strong>. Autor e coordenação continuam com acesso ao download da versão atual, quando existir arquivo.
                            @elseif($downloadIsOfficial)
                                Versão oficial anexada pela coordenação na aceitação.
                            @else
                                Arquivo enviado pelo autor (vigente no sistema).
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <div class="xl:grid xl:grid-cols-12 xl:gap-8 xl:items-start space-y-8 xl:space-y-0">
            <div class="xl:col-span-8 flex min-w-0 flex-col gap-6 sm:gap-8">
            @if($work->isAwaitingCorrectionReReview())
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-950 px-4 py-4 text-sm shadow-sm ring-1 ring-indigo-100">
                    <p class="font-bold m-0 mb-2 flex items-center gap-2"><ion-icon name="sync-outline" class="text-xl shrink-0"></ion-icon> Reavaliação das correções</p>
                    @if(auth()->id() === $work->submitter_user_id)
                        <p class="m-0">
                            Sua versão corrigida foi enviada em
                            <span class="font-medium">{{ $work->correction_submitted_at?->format('d/m/Y H:i') }}</span>.
                            Os avaliadores estão analisando as correções; o status acima indica esta etapa.
                        </p>
                    @elseif(!empty($isCoordinator) && $isCoordinator)
                        <p class="m-0">
                            O autor enviou a versão corrigida em
                            <span class="font-medium">{{ $work->correction_submitted_at?->format('d/m/Y H:i') }}</span>.
                            Aguarde os pareceres dos avaliadores (aceitar ou rejeitar a versão corrigida) para registrar a decisão final.
                        </p>
                    @elseif(!empty($isAssignedReviewer) && $isAssignedReviewer)
                        <p class="m-0">
                            O arquivo do trabalho é a <strong>versão corrigida</strong> pelo autor. Ao avaliar, use apenas <strong>aceitar</strong> ou <strong>rejeitar</strong> nesta rodada.
                        </p>
                    @else
                        <p class="m-0">Este trabalho está na etapa de reavaliação da versão corrigida pelo autor.</p>
                    @endif
                </div>
            @endif

            <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900/5 text-emerald-600 shrink-0">
                            <ion-icon name="library-outline" class="text-xl" aria-hidden="true"></ion-icon>
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Sobre esta submissão</h2>
                            <p class="text-[11px] font-medium text-slate-500 m-0 mt-0.5">Tipo, nota e andamento atual</p>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6">
                        <div class="grid gap-8 sm:grid-cols-2 sm:gap-10">
                            <div class="space-y-2">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0">Tipo de trabalho</p>
                                <p class="text-lg font-bold text-slate-900 leading-snug m-0">{{ $workTypeLabels[$work->work_type] ?? $work->work_type }}</p>
                            </div>
                            @php
                                $viewerSeesProvisionalFinalScore = !empty($isCoordinator) || !empty($isAssignedReviewer);
                                $participantSeesFinalScore = $work->final_score !== null
                                    && ($work->final_score_is_manual || $viewerSeesProvisionalFinalScore);
                            @endphp
                            <div class="space-y-2 sm:text-right sm:justify-self-end sm:w-full">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0 sm:text-right">Nota final</p>
                                @if($participantSeesFinalScore)
                                    <p class="text-3xl font-bold tabular-nums tracking-tight text-slate-900 m-0 sm:text-right">{{ number_format((float) $work->final_score, 2, ',', '.') }}</p>
                                    <p class="text-xs font-medium text-slate-600 m-0 leading-relaxed sm:text-right">{{ $work->final_score_is_manual ? 'Definida pela coordenação.' : 'Média provisória dos pareceres (aguardando decisão oficial).' }}</p>
                                @elseif($work->final_score !== null && ! $viewerSeesProvisionalFinalScore)
                                    <p class="text-base font-medium text-slate-700 m-0 sm:text-right leading-snug max-w-[20rem] sm:max-w-none sm:ml-auto">Ainda não há nota oficial — será exibida quando a coordenação registrar a decisão.</p>
                                @else
                                    <p class="text-base font-medium text-slate-700 m-0 sm:text-right leading-snug">Aguardando pareceres.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>

            @if($work->decision_at)
                <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6 border-b border-emerald-100 bg-gradient-to-r from-emerald-50/75 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100/70 text-emerald-700 shrink-0">
                            <ion-icon name="ribbon-outline" class="text-xl" aria-hidden="true"></ion-icon>
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Resultado da avaliação</h2>
                            <p class="text-[11px] font-medium text-slate-500 m-0 mt-0.5">Decisão da coordenação e situação do trabalho</p>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6 space-y-6">
                        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200/80 px-4 py-3 flex flex-wrap items-center gap-3">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 shrink-0">Situação atual</span>
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold shadow-sm {{ $workStatusBadgeClass }}">{{ $work->statusLabel() }}</span>
                        </div>

                        @if($work->coordinatorDecisionIsTerminal())
                            <div class="space-y-2 rounded-xl border border-emerald-100 bg-emerald-50/35 px-4 py-3 ring-1 ring-emerald-100/80">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-950/85 m-0">Decisão registrada em {{ $work->decision_at->format('d/m/Y H:i') }}@if($work->decisionByUser)<span class="font-medium normal-case text-emerald-900"> · {{ $work->decisionByUser->name }}</span>@endif</p>
                                @if($work->status === \App\Models\Work::STATUS_REJECTED || $work->status === \App\Models\Work::STATUS_DESK_REJECTED)
                                    <p class="text-sm font-medium text-emerald-950 m-0 leading-relaxed">O trabalho foi <strong>reprovado</strong> nesta decisão da coordenação.</p>
                                @else
                                    <p class="text-sm font-medium text-emerald-950 m-0 leading-relaxed">O trabalho foi <strong>aceito</strong> nesta decisão{{ $work->final_version_source === \App\Models\Work::FINAL_VERSION_SOURCE_CORRECTED ? ', com a versão corrigida aprovada após reavaliação.' : '.' }}</p>
                                @endif
                            </div>
                        @elseif($work->isAwaitingAuthorCorrection())
                            <div class="space-y-2 rounded-xl border border-amber-200/90 bg-amber-50/50 px-4 py-3 ring-1 ring-amber-100">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-950 m-0">Encaminhamento registrado em {{ $work->decision_at->format('d/m/Y H:i') }}@if($work->decisionByUser)<span class="font-medium normal-case text-amber-950"> · {{ $work->decisionByUser->name }}</span>@endif</p>
                                <p class="text-sm font-medium text-amber-950 m-0 leading-relaxed"><strong>Aceito com correções.</strong> O autor deve enviar a versão ajustada até
                                    @if($work->correction_deadline_at)<span class="font-bold tabular-nums">{{ $work->correction_deadline_at->format('d/m/Y H:i') }}</span>@else o prazo indicado.@endif
                                </p>
                                <p class="text-xs text-amber-900/90 m-0 leading-relaxed">Enquanto a nova versão não for enviada, não há nova decisão nem reavaliação.</p>
                            </div>
                        @elseif($work->isAwaitingCorrectionReReview())
                            <div class="space-y-2 rounded-xl border border-indigo-200/90 bg-indigo-50/50 px-4 py-3 ring-1 ring-indigo-100">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-950 m-0">Encaminhamento anterior em {{ $work->decision_at->format('d/m/Y H:i') }}</p>
                                <p class="text-sm font-medium text-indigo-950 m-0 leading-relaxed">A coordenação havia solicitado <strong>correções</strong>. A versão corrigida foi enviada
                                    @if($work->correction_submitted_at)em <span class="font-bold tabular-nums">{{ $work->correction_submitted_at->format('d/m/Y H:i') }}</span>@endif.</p>
                                @if(! empty($allReviewersCompleted))
                                    <p class="text-xs font-semibold text-indigo-900 m-0">Os avaliadores concluíram a análise da versão corrigida — a próxima etapa é a decisão definitiva (<strong>aceitar</strong> ou <strong>reprovar</strong>) pela coordenação.</p>
                                @else
                                    <p class="text-xs font-medium text-indigo-900 m-0">Os avaliadores estão na <strong>reavaliação</strong> do arquivo corrigido. A decisão final (aceitar ou reprovar) só aparece quando todos enviarem o parecer nesta rodada.</p>
                                @endif
                            </div>
                        @else
                            <div class="rounded-xl border border-slate-200 bg-slate-50/90 px-4 py-3 ring-1 ring-slate-200/80">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-600 m-0">Registro da coordenação em {{ $work->decision_at->format('d/m/Y H:i') }}@if($work->decisionByUser)<span class="font-medium normal-case text-slate-800"> · {{ $work->decisionByUser->name }}</span>@endif</p>
                                <p class="text-sm font-medium text-slate-800 m-0 mt-2 leading-relaxed">Acompanhe o status atual acima. Outras ações podem estar condicionadas ao fluxo do evento ou a novos pareceres.</p>
                            </div>
                        @endif

                        @if($work->presentation)
                            <div class="space-y-4">
                                <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0 flex items-center gap-2">
                                    <ion-icon name="mic-outline" class="text-base text-slate-500"></ion-icon>
                                    Detalhes da apresentação
                                </h3>
                                <dl class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Modalidade</dt>
                                        <dd class="mt-1.5 m-0 text-base font-semibold text-slate-900">{{ $presentationTypeLabels[$work->presentation->presentation_type] ?? ucfirst($work->presentation->presentation_type) }}</dd>
                                    </div>
                                    @if($work->presentation->session_name)
                                        <div>
                                            <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sessão</dt>
                                            <dd class="mt-1.5 m-0 text-base font-semibold text-slate-900">{{ $work->presentation->session_name }}</dd>
                                        </div>
                                    @endif
                                    @if($work->presentation->scheduled_start || $work->presentation->scheduled_end)
                                        <div class="sm:col-span-2 rounded-xl bg-white px-4 py-3 ring-1 ring-slate-200/90">
                                            <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 block">Horário</dt>
                                            <dd class="m-0 text-sm font-semibold text-slate-900 tabular-nums">
                                                @if($work->presentation->scheduled_start)
                                                    <time datetime="{{ $work->presentation->scheduled_start }}">{{ \Carbon\Carbon::parse($work->presentation->scheduled_start)->format('d/m/Y H:i') }}</time>
                                                @endif
                                                @if($work->presentation->scheduled_end)
                                                    @if($work->presentation->scheduled_start)<span class="text-slate-400 font-normal mx-1">até</span>@endif
                                                    <time datetime="{{ $work->presentation->scheduled_end }}">{{ \Carbon\Carbon::parse($work->presentation->scheduled_end)->format('d/m/Y H:i') }}</time>
                                                @endif
                                            </dd>
                                        </div>
                                    @endif
                                    @if($work->presentation->location)
                                        <div class="sm:col-span-2">
                                            <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Local ou link</dt>
                                            <dd class="mt-1.5 m-0 text-sm font-semibold text-slate-900 leading-relaxed [overflow-wrap:anywhere]">{{ $work->presentation->location }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif
                    </div>
                </article>
            @endif

            @if(filled($work->abstract))
                <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900/5 text-emerald-600 shrink-0">
                            <ion-icon name="reader-outline" class="text-xl" aria-hidden="true"></ion-icon>
                        </span>
                        <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Resumo</h2>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="text-[15px] leading-[1.7] text-slate-800 font-medium whitespace-pre-line m-0 max-w-none">{{ $work->abstract }}</p>
                    </div>
                </article>
            @endif

            <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900/5 text-emerald-600 shrink-0">
                            <ion-icon name="people-outline" class="text-xl" aria-hidden="true"></ion-icon>
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Autores</h2>
                            <p class="text-[11px] font-medium text-slate-500 m-0 mt-0.5">Créditos e contato na submissão</p>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6 space-y-4">
                        @foreach($work->authors as $author)
                            <div class="rounded-xl border border-slate-200/90 bg-gradient-to-br from-white to-slate-50/70 px-4 py-4 ring-1 ring-slate-200/50 shadow-sm">
                                <div class="flex flex-wrap items-baseline gap-2 gap-y-1">
                                    <p class="text-base font-bold text-slate-900 m-0"><span class="text-slate-500 font-semibold">{{ $author->author_order }}.</span> {{ $author->author_name }}</p>
                                    @if($author->is_main_author)
                                        <span class="inline-flex items-center rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-900 ring-1 ring-emerald-200/90">Autor principal</span>
                                    @endif
                                </div>
                                @if($author->author_email)
                                    <p class="mt-2 m-0 flex items-center gap-2 text-sm text-slate-700">
                                        <ion-icon name="mail-outline" class="text-lg text-slate-500 shrink-0"></ion-icon>
                                        <a href="mailto:{{ $author->author_email }}" class="font-semibold text-indigo-700 hover:text-indigo-900 no-underline [overflow-wrap:anywhere]">{{ $author->author_email }}</a>
                                    </p>
                                @endif
                                @if($author->institution)
                                    <p class="mt-2 m-0 text-sm text-slate-700"><span class="font-bold text-slate-600">Instituição:</span> {{ $author->institution }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>

            @if($work->decision_at && $work->reviews->whereNotNull('submitted_at')->isNotEmpty())
                <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900/5 text-emerald-600 shrink-0">
                            <ion-icon name="chatbubbles-outline" class="text-xl" aria-hidden="true"></ion-icon>
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Pareceres enviados ao autor</h2>
                            <p class="text-[11px] font-medium text-slate-500 m-0 mt-0.5">Texto público registrado pelo avaliador (sem comentários internos)</p>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6 space-y-4">
                        @foreach($work->reviews->whereNotNull('submitted_at') as $reviewSub)
                            @php
                                $pubRecClass = $recommendationBadgeClassByKey[$reviewSub->recommendation] ?? 'bg-slate-100 text-slate-900 ring-1 ring-slate-200 shadow-sm';
                                $pubRecLabel = $recommendationLabels[$reviewSub->recommendation] ?? 'Não informado';
                            @endphp
                            <div class="rounded-xl border border-slate-200/90 bg-white px-4 py-4 shadow-sm ring-1 ring-slate-200/50">
                                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                                    <p class="text-base font-bold text-slate-900 m-0">{{ $reviewSub->reviewer->name ?? 'Avaliador' }}</p>
                                    <span class="inline-flex items-center rounded-lg px-3 py-1 text-[11px] font-bold {{ $pubRecClass }}">{{ $pubRecLabel }}</span>
                                </div>
                                @if($reviewSub->comment_to_author)
                                    <p class="text-[15px] leading-relaxed text-slate-800 font-medium whitespace-pre-line m-0 border-t border-slate-100 pt-4">{{ $reviewSub->comment_to_author }}</p>
                                @else
                                    <p class="text-sm text-slate-500 m-0 border-t border-slate-100 pt-4">Nenhum comentário ao autor neste parecer.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @endif

            @if($work->author_feedback)
                <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6 border-b border-violet-100 bg-gradient-to-r from-violet-50/55 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100/70 text-violet-700 shrink-0">
                            <ion-icon name="megaphone-outline" class="text-xl"></ion-icon>
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Mensagem da coordenação</h2>
                            <p class="text-[11px] font-medium text-slate-500 m-0 mt-0.5">Feedback público sobre o trabalho</p>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="text-[15px] leading-[1.65] font-medium text-slate-800 whitespace-pre-line m-0">{{ $work->author_feedback }}</p>
                    </div>
                </article>
            @endif

            @if($work->coordinator_feedback_file_path && (auth()->id() === $work->submitter_user_id || $isCoordinator))
                <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6 border-b border-indigo-100 bg-gradient-to-r from-indigo-50/70 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100/75 text-indigo-700 shrink-0">
                            <ion-icon name="folder-open-outline" class="text-xl"></ion-icon>
                        </span>
                        <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Arquivo de correções consolidadas</h2>
                    </div>
                    <div class="p-5 sm:p-6 space-y-4">
                        <p class="text-sm leading-relaxed text-slate-700 font-medium m-0">Documento reunindo orientações dos avaliadores preparado pela coordenação.</p>
                        <a href="{{ route('works.coordinator-feedback.download', $work->id) }}"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold no-underline transition-colors shadow-sm ring-1 ring-indigo-700/20">
                            <ion-icon name="download-outline" class="text-lg"></ion-icon>
                            Baixar arquivo
                        </a>
                    </div>
                </article>
            @endif

            @if($work->presentation && ! $work->decision_at)
                <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 sm:px-6 border-b border-cyan-100 bg-gradient-to-r from-cyan-50/50 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100/70 text-cyan-800 shrink-0">
                            <ion-icon name="mic-outline" class="text-xl"></ion-icon>
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Apresentação prevista</h2>
                            <p class="text-[11px] font-medium text-slate-500 m-0 mt-0.5">Enquanto não há decisão final registrada, estes são os dados de agendamento atuais</p>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6 space-y-4">
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Modalidade</dt>
                                <dd class="mt-1.5 font-semibold text-slate-900 m-0">{{ $presentationTypeLabels[$work->presentation->presentation_type] ?? ucfirst($work->presentation->presentation_type) }}</dd>
                            </div>
                            @if($work->presentation->session_name)
                                <div>
                                    <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sessão</dt>
                                    <dd class="mt-1.5 font-semibold text-slate-900 m-0">{{ $work->presentation->session_name }}</dd>
                                </div>
                            @endif
                        </dl>
                        @if($work->presentation->scheduled_start || $work->presentation->scheduled_end)
                            <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 m-0">Horário</p>
                                <p class="font-semibold text-slate-900 text-sm tabular-nums m-0">
                                    @if($work->presentation->scheduled_start)<time>{{ \Carbon\Carbon::parse($work->presentation->scheduled_start)->format('d/m/Y H:i') }}</time>@endif
                                    @if($work->presentation->scheduled_end)<span class="text-slate-400 font-normal mx-1">→</span><time>{{ \Carbon\Carbon::parse($work->presentation->scheduled_end)->format('d/m/Y H:i') }}</time>@endif
                                </p>
                            </div>
                        @endif
                        @if($work->presentation->location)
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Local ou link</p>
                                <p class="mt-1.5 font-semibold text-slate-900 text-sm leading-relaxed m-0 [overflow-wrap:anywhere]">{{ $work->presentation->location }}</p>
                            </div>
                        @endif
                    </div>
                </article>
            @endif

            @if(auth()->id() === $work->submitter_user_id)
                @php
                    $isSubmitted = $work->status === \App\Models\Work::STATUS_SUBMITTED;
                    $canEditSubmission = $isSubmitted && $work->event->workSubmissionWindowOpen();
                    $isCorrectionRequired = $work->status === \App\Models\Work::STATUS_ACCEPTED_WITH_CORRECTIONS;
                    $correctionDeadlinePassed = $work->correction_deadline_at
                        ? \Carbon\Carbon::now()->greaterThanOrEqualTo(\Carbon\Carbon::parse($work->correction_deadline_at))
                        : false;
                    $canSubmitCorrection = $isCorrectionRequired && $work->correction_deadline_at && !$correctionDeadlinePassed;
                    $awaitingCorrectionReReview = $work->isAwaitingCorrectionReReview();
                @endphp

                <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
                    <h2 class="font-semibold text-slate-900 text-lg mb-3">Ações do autor</h2>
                    @if($awaitingCorrectionReReview)
                        <p class="text-sm text-slate-600 m-0">
                            Sua versão corrigida já foi recebida. Não é possível enviar outro arquivo até a coordenação concluir esta rodada de avaliação.
                        </p>
                    @elseif($canEditSubmission)
                        <p class="text-sm text-slate-600 mb-3">
                            Antes do prazo de submissão, você pode editar e substituir o arquivo.
                        </p>
                        <a href="{{ route('works.edit', $work->id) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold no-underline transition-colors">
                            <ion-icon name="create-outline"></ion-icon>
                            Editar submissão
                        </a>
                    @elseif($canSubmitCorrection)
                        <p class="text-sm text-slate-600 mb-3">
                            Seu trabalho precisa de correções. Envie a nova versão até
                            <span class="font-semibold">{{ $work->correction_deadline_at->format('d/m/Y H:i') }}</span>.
                        </p>
                        <form action="{{ route('works.submit-correction', $work->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <p class="text-xs text-slate-500 m-0">Formatos aceitos: PDF, DOC, DOCX ou ODT (até 10MB).</p>
                            <input type="file" name="file"
                                   accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text"
                                   required
                                   class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none">
                            <textarea name="correction_change_log" rows="3"
                                      class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none"
                                      placeholder="Descreva o que foi ajustado (opcional).">{{ old('correction_change_log') }}</textarea>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors">
                                <ion-icon name="cloud-upload-outline"></ion-icon>
                                Enviar versão corrigida
                            </button>
                        </form>
                    @elseif($isCorrectionRequired && $correctionDeadlinePassed)
                        <p class="text-sm text-red-700 m-0">
                            O prazo para envio da versão corrigida foi encerrado.
                        </p>
                    @else
                        <p class="text-sm text-slate-600 m-0">
                            Nenhuma ação disponível para o autor neste status.
                        </p>
                    @endif
                </div>
            @endif

            </div>{{-- xl: principal --}}

            <aside class="xl:col-span-4 flex min-w-0 flex-col gap-6 sm:gap-8 xl:sticky xl:top-[5.25rem] xl:self-start mt-10 xl:mt-0 pb-8 xl:pb-0">
                <div class="rounded-2xl border border-slate-200/90 bg-white shadow-[0_1px_5px_rgba(15,23,42,0.06)] overflow-hidden ring-1 ring-slate-200/40">
                    <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <ion-icon name="compass-outline" class="text-xl text-emerald-600 shrink-0" aria-hidden="true"></ion-icon>
                        <h3 class="font-semibold text-slate-900 text-sm m-0 tracking-tight">Contexto</h3>
                    </div>
                    <div class="p-4 sm:p-5 space-y-5 text-sm">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2 m-0">Evento</p>
                            <a href="{{ route('events.show', $work->event_id) }}" class="font-semibold text-indigo-700 hover:text-indigo-900 no-underline leading-snug block [overflow-wrap:anywhere]">{{ $work->event->title }}</a>
                        </div>
                        <div class="h-px bg-slate-100"></div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2 m-0">Período do evento</p>
                            <p class="font-bold text-slate-900 tabular-nums m-0 text-[15px] leading-snug">
                                {{ $work->event->start_date?->format('d/m/Y') ?? '—' }}
                                <span class="text-slate-400 font-normal mx-0.5">—</span>
                                {{ ($work->event->end_date ?? $work->event->start_date)?->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>
                        @if($work->event->acceptsSubmissions() && $work->event->submission_deadline_at)
                            <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600 mb-1.5 m-0 flex items-center gap-1">
                                    <ion-icon name="alarm-outline" class="text-base"></ion-icon>
                                    Submissões de trabalho
                                </p>
                                <p class="font-bold text-slate-900 tabular-nums text-[13px] m-0 leading-snug">{{ $work->event->submission_deadline_at->format('d/m/Y H:i') }}</p>
                                <p class="text-[11px] text-slate-600 mt-2 m-0 leading-relaxed">{{ $work->event->workSubmissionDeadlinePassed() ? 'Prazo encerrado — avaliações em curso conforme cada evento.' : 'O autor ainda pode ajustar o arquivo conforme esta data.' }}</p>
                            </div>
                        @endif
                        @if($work->presentation && ($work->presentation->scheduled_start || $work->presentation->scheduled_end || $work->presentation->location))
                            <div class="rounded-xl border border-cyan-100 bg-cyan-50/35 px-4 py-3 ring-1 ring-cyan-200/60">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-cyan-950/80 mb-2 m-0">Apresentação</p>
                                @if($work->presentation->scheduled_start)
                                    <p class="font-semibold text-slate-900 m-0 tabular-nums text-[13px]">{{ \Carbon\Carbon::parse($work->presentation->scheduled_start)->format('d/m/Y H:i') }}@if($work->presentation->scheduled_end)<span class="font-normal text-slate-400 mx-1">→</span>{{ \Carbon\Carbon::parse($work->presentation->scheduled_end)->format('H:i') }}@endif</p>
                                @endif
                                @if($work->presentation->session_name)
                                    <p class="text-[12px] text-slate-700 mt-2 m-0 font-medium">{{ $work->presentation->session_name }}</p>
                                @endif
                                @if($work->presentation->location)
                                    <p class="text-[11px] text-slate-600 mt-2 m-0 leading-relaxed">{{ $work->presentation->location }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>{{-- xl:grid page --}}

            @if($isCoordinator)
            <div class="mt-8 flex flex-col gap-6 border-t border-slate-200/70 pt-8 sm:mt-10 sm:gap-8 sm:pt-10">
                <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_4px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3.5 sm:px-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900/5 text-emerald-600 shrink-0">
                            <ion-icon name="school-outline" class="text-xl" aria-hidden="true"></ion-icon>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Avaliadores vinculados</h2>
                            <p class="text-[11px] font-medium text-slate-500 m-0 mt-0.5">Designação deste trabalho</p>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5">
                        @if($work->isAwaitingCorrectionReReview())
                            <div class="rounded-xl border border-indigo-200 bg-indigo-50/95 px-4 py-3 text-sm text-indigo-950 shadow-sm ring-1 ring-indigo-100">
                                <p class="font-bold m-0 flex items-center gap-2"><ion-icon name="git-branch-outline" class="text-lg shrink-0"></ion-icon> Rodada de reavaliação</p>
                                <p class="m-0 mt-2 leading-relaxed opacity-95">Os avaliadores emitiram parecer sobre a <strong>versão corrigida</strong> (aceitar ou rejeitar). A decisão final só depois que todos concluírem.</p>
                            </div>
                        @endif
                        <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200/80 px-4 py-3">
                            <p class="text-sm font-medium text-slate-700 m-0 leading-relaxed">
                                Para incluir ou trocar avaliadores em lote, use a lista de trabalhos do evento.
                            </p>
                            <a href="{{ route('events.works.index', $work->event_id) }}"
                               class="inline-flex items-center gap-2 mt-3 text-sm font-semibold text-indigo-700 hover:text-indigo-900 no-underline">
                                <ion-icon name="open-outline" class="text-lg shrink-0"></ion-icon>
                                Abrir gerenciar trabalhos do evento
                            </a>
                        </div>
                        @forelse($work->reviewerAssignments as $assignment)
                            <div class="flex flex-col gap-4 rounded-xl border border-slate-200/90 bg-gradient-to-br from-white to-slate-50/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between ring-1 ring-slate-200/50 shadow-sm">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-900/5 text-slate-600">
                                        <ion-icon name="person-circle-outline" class="text-2xl" aria-hidden="true"></ion-icon>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0">Avaliador</p>
                                        <p class="text-base font-bold text-slate-900 m-0 mt-1 leading-snug [overflow-wrap:anywhere]">{{ $assignment->reviewer->name ?? 'Avaliador' }}</p>
                                        @if($assignment->assigned_at)
                                            <p class="text-xs font-medium text-slate-600 m-0 mt-1 tabular-nums">Designado em {{ $assignment->assigned_at->format('d/m/Y H:i') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <span class="inline-flex w-fit shrink-0 items-center rounded-lg px-3 py-1.5 text-xs font-bold {{ $reviewAssignmentStatusBadgeClass[$assignment->status] ?? 'bg-slate-100 text-slate-900 ring-1 ring-slate-200 shadow-sm' }}">
                                    {{ $assignment->statusLabel() }}
                                </span>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-5 py-8 text-center">
                                <ion-icon name="people-outline" class="text-3xl text-slate-400 mb-2" aria-hidden="true"></ion-icon>
                                <p class="font-semibold text-slate-800 m-0 text-sm">Nenhum avaliador vinculado</p>
                                <p class="text-xs text-slate-600 mt-2 m-0 max-w-xs mx-auto">Use a página de trabalhos do evento para distribuir avaliadores a esta submissão.</p>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="bg-white rounded-2xl border border-slate-200/90 shadow-[0_1px_4px_rgba(15,23,42,0.06)] overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-3.5 sm:px-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900/5 text-emerald-600 shrink-0">
                            <ion-icon name="clipboard-outline" class="text-xl" aria-hidden="true"></ion-icon>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-semibold text-slate-900 text-base sm:text-lg m-0 tracking-tight">Avaliações recebidas</h2>
                            <p class="text-[11px] font-medium text-slate-500 m-0 mt-0.5">Pareceres e notas registrados neste trabalho</p>
                        </div>
                    </div>
                    <div class="p-5 sm:p-6 space-y-5">
                        @if($work->isAwaitingCorrectionReReview())
                            <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-950 ring-1 ring-indigo-100">
                                <p class="font-bold m-0">Versão corrigida</p>
                                <p class="m-0 mt-1 leading-relaxed">Os itens desta lista referem-se ao arquivo após as correções do autor.</p>
                            </div>
                        @endif
                        @if($hasReviewConflict)
                            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-950 shadow-sm ring-1 ring-amber-200 flex gap-3 items-start">
                                <ion-icon name="warning-outline" class="text-2xl text-amber-600 shrink-0 mt-0.5" aria-hidden="true"></ion-icon>
                                <p class="m-0 leading-relaxed"><strong>Conflito entre pareceres.</strong> A decisão final deve ser registrada pela coordenação na seção ao final da página.</p>
                            </div>
                        @endif
                        @forelse($work->reviews as $review)
                            @php
                                $reviewRecBadgeClass = $recommendationBadgeClassByKey[$review->recommendation] ?? 'bg-slate-100 text-slate-900 ring-1 ring-slate-200 shadow-sm';
                                $reviewRecLabel = $recommendationLabels[$review->recommendation] ?? 'Sem recomendação';
                            @endphp
                            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-200/55">
                                <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/95 px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm ring-1 ring-slate-200/90">
                                            <ion-icon name="person-circle-outline" class="text-2xl" aria-hidden="true"></ion-icon>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0">Avaliador</p>
                                            <p class="text-base font-bold text-slate-900 m-0 mt-1 [overflow-wrap:anywhere]">{{ $review->reviewer->name ?? 'Avaliador' }}</p>
                                            @if($review->submitted_at)
                                                <p class="text-xs font-semibold text-slate-600 m-0 mt-1 tabular-nums flex items-center gap-1">
                                                    <ion-icon name="calendar-outline" class="text-base text-slate-500"></ion-icon>
                                                    Enviado em {{ $review->submitted_at->format('d/m/Y H:i') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="inline-flex w-fit shrink-0 items-center rounded-lg px-3 py-1.5 text-xs font-bold {{ $reviewRecBadgeClass }}">
                                        {{ $reviewRecLabel }}
                                    </span>
                                </div>
                                <div class="space-y-4 p-4 sm:p-5">
                                    <div class="flex flex-wrap items-end gap-x-8 gap-y-2">
                                        <div>
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0">Nota atribuída</p>
                                            @if($review->score !== null)
                                                <p class="mt-1.5 mb-0 text-3xl font-bold tabular-nums tracking-tight text-slate-900">{{ number_format((float) $review->score, 2, ',', '.') }}</p>
                                            @else
                                                <p class="mt-2 mb-0 text-sm font-semibold text-slate-600">Não informada neste parecer</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 ring-1 ring-slate-100">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0 flex items-center gap-1.5">
                                            <ion-icon name="chatbubble-ellipses-outline" class="text-base"></ion-icon>
                                            Feedback ao autor
                                        </p>
                                        <p class="mt-3 m-0 text-[15px] leading-relaxed font-medium text-slate-800">{{ $review->comment_to_author ?: 'Sem texto de feedback ao autor neste parecer.' }}</p>
                                    </div>
                                    @if($review->general_comment)
                                        <div class="rounded-xl border border-amber-200/90 bg-amber-50/50 px-4 py-3 ring-1 ring-amber-200/70">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-900/90 m-0 flex items-center gap-1.5">
                                                <ion-icon name="shield-checkmark-outline" class="text-base"></ion-icon>
                                                Visível apenas à coordenação
                                            </p>
                                            <p class="mt-2 mb-0 text-sm leading-relaxed font-medium text-amber-950/95">{{ $review->general_comment }}</p>
                                        </div>
                                    @endif
                                    @if($review->refined_correction_file_path && $work->event->workSubmissionDeadlinePassed())
                                        <div class="flex flex-wrap items-center gap-2 pt-1">
                                            <a href="{{ route('reviews.refined-correction.download', [$work->id, $review->id]) }}"
                                               class="inline-flex flex-1 min-w-[12rem] items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold no-underline transition-colors shadow-sm ring-1 ring-violet-700/20 sm:flex-none">
                                                <ion-icon name="document-outline" class="text-lg shrink-0"></ion-icon>
                                                Versão refinada (reavaliação)
                                            </a>
                                        </div>
                                    @elseif($review->refined_correction_file_path)
                                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-900 flex gap-2 items-start">
                                            <ion-icon name="time-outline" class="text-lg shrink-0 text-amber-700"></ion-icon>
                                            A versão refinada ficará disponível após o fim do prazo de submissão de trabalhos.
                                        </div>
                                    @endif
                                    @if($review->feedback_file_path && $work->event->workSubmissionDeadlinePassed())
                                        <div class="flex flex-wrap items-center gap-2 pt-1">
                                            <a href="{{ route('reviews.feedback.download', [$work->id, $review->id]) }}"
                                               class="inline-flex flex-1 min-w-[12rem] items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold no-underline transition-colors shadow-sm ring-1 ring-indigo-700/20 sm:flex-none">
                                                <ion-icon name="document-attach-outline" class="text-lg shrink-0"></ion-icon>
                                                Baixar arquivo de feedback
                                            </a>
                                        </div>
                                    @elseif($review->feedback_file_path)
                                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-900 flex gap-2 items-start">
                                            <ion-icon name="time-outline" class="text-lg shrink-0 text-amber-700"></ion-icon>
                                            O arquivo ficará disponível para download após o fim do prazo de submissão de trabalhos.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-5 py-10 text-center">
                                <ion-icon name="clipboard-outline" class="text-3xl text-slate-400 mb-3" aria-hidden="true"></ion-icon>
                                <p class="font-semibold text-slate-800 m-0 text-sm">Nenhum parecer enviado ainda</p>
                                <p class="text-xs text-slate-600 mt-2 m-0">Quando um avaliador submeter o formulário de avaliação, o resultado aparecerá aqui.</p>
                            </div>
                        @endforelse
                    </div>
                </article>

                @if(!empty($showCoordinatorDecisionForm) && $showCoordinatorDecisionForm)
                @php
                    $isDecisionPostCorrectionRound = $work->isAwaitingCorrectionReReview();
                    $decisionStatusOld = old('status', '');
                    $showOfficialBlockInit = $decisionStatusOld === \App\Models\Work::STATUS_APPROVED_FINAL
                        || $errors->has('official_final_file');
                    $showCorrectionsBlockInit = ! $isDecisionPostCorrectionRound
                        && (
                            $decisionStatusOld === \App\Models\Work::STATUS_ACCEPTED_WITH_CORRECTIONS
                            || $errors->has('correction_deadline_at')
                            || $errors->has('coordinator_feedback_file')
                        );
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
                    <h2 class="font-semibold text-slate-900 text-lg mb-3">Decisão final da coordenação</h2>

                    <div class="mb-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm space-y-2">
                        <p class="font-semibold m-0">Resumo do fluxo dos arquivos</p>
                        <ul class="list-disc ml-5 m-0 space-y-1 text-slate-700">
                            <li><strong>Submissão do autor</strong> — arquivo inicial conservado pelo sistema como referência histórica.</li>
                            <li><strong>Correções e parecer em arquivo</strong> — quando o avaliador indica correções ou na reavaliação, há arquivos anexados que ficam só para uso da coordenação (junto com eventual nova versão que o próprio autor enviou).</li>
                            <li><strong>Correções para o autor</strong> — ao marcar “Aceito com correções”, você pode anexar o documento com as orientações que o participante deve seguir.</li>
                            <li><strong>Versão oficial final</strong> — ao marcar “Aceito”, você envia o PDF (ou DOC/ODT) que deve valer para apresentações e para os anais; é essa cópia que o sistema passa a oferecer no download principal após a aprovação.</li>
                        </ul>
                    </div>

                    @if($work->isAwaitingCorrectionReReview())
                        <div class="mb-4 p-3 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-950 text-sm space-y-2">
                            <p class="font-medium m-0">Decisão após correção e parecer dos avaliadores</p>
                            <p class="m-0 mt-1">Somente os avaliadores que tinham “Aceitar com correções” foram reconvocados para esta rodada. Você registra aqui <strong>Aceito</strong> ou <strong>Rejeitado</strong>. Se aceitar, é obrigatório enviar também a <strong>versão oficial final</strong> neste formulário.</p>
                        </div>
                    @else
                        <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-950 text-sm space-y-2">
                            <p class="font-medium m-0 mb-1">Primeira decisão da coordenação</p>
                            <p class="m-0"><strong>Aceito</strong> → anexe a versão oficial final que deve valer nos anais e na apresentação (pode ser o mesmo arquivo da submissão ou uma versão ajustada por você).</p>
                            <p class="m-0 mt-2"><strong>Aceito com correções</strong> → preencha o prazo ao autor e envie o consolidado ao participante no campo indicado mais abixo; o envio da <strong>versão oficial final</strong> ficará obrigatório apenas depois, quando você aceitar na rodada após a correção pelos avaliadores e pelo autor.</p>
                            <p class="m-0 mt-2"><strong>Rejeitado</strong> → não é necessário arquivo oficial.</p>
                        </div>
                    @endif
                    <form id="work_coordinator_decision_form"
                          action="{{ route('works.decision', $work->id) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="space-y-4"
                          data-post-correction="{{ $isDecisionPostCorrectionRound ? '1' : '0' }}">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2" for="work_decision_status">Status final</label>
                            <select name="status"
                                    id="work_decision_status"
                                    required
                                    class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none">
                                <option value="">Selecione...</option>
                                <option value="approved_final" @selected($decisionStatusOld === 'approved_final')>Aceito</option>
                                @if(! $isDecisionPostCorrectionRound)
                                    <option value="accepted_with_corrections" @selected($decisionStatusOld === 'accepted_with_corrections')>Aceito com correções</option>
                                @endif
                                <option value="rejected" @selected($decisionStatusOld === 'rejected')>Rejeitado</option>
                            </select>
                            @error('status')
                                <p class="text-sm text-red-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>
                        <div id="work_decision_block_official"
                             class="rounded-xl border border-violet-200 bg-violet-50/60 p-4 space-y-2 {{ $showOfficialBlockInit ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-slate-900">Versão oficial final <span class="text-red-600">*</span> <span class="text-slate-500 font-normal">(obrigatória se a decisão for “Aceito”)</span></label>
                            <p class="text-xs text-slate-600 m-0">Este é o arquivo que o sistema usará principalmente quando o trabalho estiver aceito — por exemplo nos downloads aos participantes e para referência de apresentação e anais.</p>
                            <input type="file"
                                   name="official_final_file"
                                   id="work_decision_official_final_file"
                                   accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text"
                                   class="w-full px-4 py-3 rounded-xl border-2 border-violet-200 focus:border-violet-500 outline-none bg-white"
                                   @disabled(! $showOfficialBlockInit)>
                            @error('official_final_file')
                                <p class="text-sm text-red-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 space-y-3">
                            @if($computedFinalScoreAverage !== null)
                                <p class="text-xs text-slate-600 m-0">
                                    Referência — média dos pareceres:
                                    <span class="font-semibold">{{ number_format($computedFinalScoreAverage, 2, ',', '.') }}</span>
                                    <span class="text-slate-500">(você deve informar a nota final registrada)</span>
                                </p>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2" for="coordinator_final_score">
                                    Nota final do trabalho <span class="text-red-600">*</span>
                                </label>
                                <input type="number"
                                       id="coordinator_final_score"
                                       name="coordinator_final_score"
                                       step="0.01"
                                       min="0"
                                       max="999.99"
                                       inputmode="decimal"
                                       required
                                       value="{{ old('coordinator_final_score', $work->final_score !== null ? number_format((float) $work->final_score, 2, '.', '') : ($computedFinalScoreAverage !== null ? number_format($computedFinalScoreAverage, 2, '.', '') : '')) }}"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none">
                                @error('coordinator_final_score')
                                    <p class="text-sm text-red-600 mt-1 mb-0">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        @if(! $isDecisionPostCorrectionRound)
                            <div id="work_decision_block_corrections"
                                 class="space-y-4 {{ $showCorrectionsBlockInit ? '' : 'hidden' }}">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2" for="work_decision_correction_deadline">Prazo para o autor enviar a versão corrigida <span class="text-red-600">*</span></label>
                                    <input type="datetime-local"
                                           id="work_decision_correction_deadline"
                                           name="correction_deadline_at"
                                           value="{{ old('correction_deadline_at', $work->correction_deadline_at ? $work->correction_deadline_at->format('Y-m-d\TH:i') : '') }}"
                                           class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none"
                                           @disabled(! $showCorrectionsBlockInit)>
                                    @error('correction_deadline_at')
                                        <p class="text-sm text-red-600 mt-1 mb-0">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="rounded-xl border border-sky-200 bg-sky-50/50 p-4 space-y-2">
                                    <label class="block text-sm font-medium text-slate-900" for="work_decision_coordinator_feedback_file">
                                        Arquivo de correções para o aluno <span class="text-red-600">*</span>
                                    </label>
                                    <p class="text-xs text-slate-600 m-0">
                                        Obrigatório ao escolher <strong>Aceito com correções</strong> (PDF, DOC, DOCX ou ODT — até 10MB), salvo se já existir um arquivo anexado em encaminhamento anterior. Orienta o que o autor deve ajustar — <strong>não substitui</strong> a versão oficial final, que será exigida depois quando você registrar o aceite definitivo.
                                    </p>
                                    <input type="file"
                                           id="work_decision_coordinator_feedback_file"
                                           name="coordinator_feedback_file"
                                           accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text"
                                           class="w-full px-4 py-3 rounded-xl border-2 border-sky-200 focus:border-sky-500 outline-none bg-white"
                                           @disabled(! $showCorrectionsBlockInit)>
                                    @error('coordinator_feedback_file')
                                        <p class="text-sm text-red-600 mt-1 mb-0">{{ $message }}</p>
                                    @enderror
                                    @if($work->coordinator_feedback_file_path)
                                        <div class="mt-2 flex items-center gap-2">
                                            <ion-icon name="checkmark-circle" class="text-emerald-600"></ion-icon>
                                            <span class="text-xs text-slate-600">
                                                Já existe um arquivo anexado.
                                                <a href="{{ route('works.coordinator-feedback.download', $work->id) }}"
                                                   class="text-indigo-700 hover:text-indigo-800 font-semibold no-underline">
                                                    Baixar arquivo atual
                                                </a>.
                                                Selecionar um novo arquivo o substituirá.
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2" for="work_decision_author_feedback">Feedback ao autor (texto)</label>
                            <textarea id="work_decision_author_feedback"
                                      name="author_feedback"
                                      rows="4"
                                      class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none">{{ old('author_feedback', $work->author_feedback) }}</textarea>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition-colors">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                            Salvar decisão
                        </button>
                    </form>
                </div>
                @elseif($work->reviewerAssignments->isNotEmpty())
                    @if($work->coordinatorCanRegisterNewDecision() && ! $work->event->workSubmissionDeadlinePassed())
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
                            <h2 class="font-semibold text-slate-900 text-lg mb-3">Decisão final</h2>
                            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
                                <p class="font-medium m-0 mb-1">Aguardando o fim do prazo de submissão</p>
                                <p class="m-0">
                                    Enquanto os participantes ainda podem alterar o arquivo da submissão, a decisão final, o feedback ao autor e o envio de arquivos consolidados ficam bloqueados.
                                    @if($work->event->submission_deadline_at)
                                        Prazo de submissão:
                                        <span class="font-semibold">{{ $work->event->submission_deadline_at->format('d/m/Y H:i') }}</span>.
                                    @endif
                                </p>
                            </div>
                        </div>
                    @elseif($work->event->workSubmissionDeadlinePassed() && $work->coordinatorCanRegisterNewDecision() && ! $allReviewersCompleted)
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
                            <h2 class="font-semibold text-slate-900 text-lg mb-3">Decisão final</h2>
                            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
                                <p class="font-medium m-0 mb-1">Aguardando avaliadores</p>
                                <p class="m-0">
                                    @if($work->isAwaitingCorrectionReReview())
                                        Nesta rodada <strong>só precisam reenviar parecer os avaliadores que tinham recomendado “Aceitar com correções”</strong>. Quem havia apenas “Aceitar” fica dispensado; quando todos obrigados concluirem, você poderá registrar a decisão definitiva — com envio obrigatório da <strong>versão oficial</strong> ao aceitar o trabalho.
                                    @else
                                        A <strong>nota final</strong> e a <strong>decisão</strong> só podem ser registradas depois que <strong>todos</strong> os avaliadores designados enviarem o parecer deste trabalho.
                                    @endif
                                </p>
                            </div>
                        </div>
                    @elseif($work->event->workSubmissionDeadlinePassed() && $work->isAwaitingAuthorCorrection())
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
                            <h2 class="font-semibold text-slate-900 text-lg mb-3">Decisão</h2>
                            <div class="p-4 rounded-xl bg-sky-50 border border-sky-200 text-sky-950 text-sm">
                                <p class="font-medium m-0 mb-1">Aguardando nova versão do autor</p>
                                <p class="m-0">
                                    Você já registrou <strong>aceito com correções</strong>. O formulário para nova decisão só reaparecerá quando o autor enviar o arquivo na data limite (@if($work->correction_deadline_at)<span class="font-bold tabular-nums">{{ $work->correction_deadline_at->format('d/m/Y H:i') }}</span>@else defina um prazo no encaminhamento anterior @endif).
                                </p>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
                    <h2 class="font-semibold text-slate-900 text-lg mb-3">Excluir trabalho</h2>
                    <p class="text-sm text-slate-600 mb-4">
                        Remove o registro do trabalho, as avaliações vinculadas e os arquivos no armazenamento. Esta ação não pode ser desfeita.
                    </p>
                    <form action="{{ route('works.destroy', $work->id) }}" method="POST"
                          onsubmit="return confirm('Confirma a exclusão deste trabalho? Os arquivos e os dados da submissão serão removidos de forma definitiva.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold transition-colors">
                            <ion-icon name="trash-outline"></ion-icon>
                            Excluir trabalho
                        </button>
                    </form>
                </div>

                @if($isCoordinator)
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 text-sm text-emerald-950">
                        <p class="font-semibold m-0 mb-2">Fluxo após aprovação</p>
                        <ul class="list-disc ml-5 m-0 space-y-2">
                            @if($work->status === \App\Models\Work::STATUS_FINAL_VALIDATED && $work->final_version_source)
                                <li class="list-none -ml-5 mb-2 text-emerald-900 leading-snug">{{ $work->canonicalFinalVersionDescription() }}</li>
                            @endif
                            @if($work->event->acceptsSubmissions())
                                <li>
                                    <a class="text-emerald-800 underline font-medium" href="{{ route('events.presentations.manage', $work->event_id) }}">Organizar local, data e horário das apresentações dos trabalhos aceitos</a>
                                    <span class="text-emerald-900/90"> — use o painel do evento para ver todos os trabalhos neste estágio.</span>
                                    @if($work->presentation && in_array($work->status, [\App\Models\Work::STATUS_SCHEDULED, \App\Models\Work::STATUS_PRESENTED, \App\Models\Work::STATUS_ABSENT], true))
                                        <span class="block mt-1.5 text-emerald-900/90">A <strong class="font-semibold text-emerald-950">presença na apresentação</strong>, a CH e o título no certificado são registrados em <a href="{{ route('events.certificates.index', $work->event_id) }}" class="font-semibold text-emerald-950 underline">Certificados e presença</a>.</span>
                                    @endif
                                </li>
                                <li>
                                    <a class="text-emerald-800 underline font-medium" href="{{ route('events.annals.manage', $work->event_id) }}">Publicação nos anais</a>
                                    <span class="text-emerald-900/90"> — após marcar a apresentação como realizada, use a tela única do painel para registrar URL e observações dos trabalhos elegíveis.</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
            @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('work_coordinator_decision_form');
    if (!form) return;
    var sel = document.getElementById('work_decision_status');
    var blockOfficial = document.getElementById('work_decision_block_official');
    var blockCorr = document.getElementById('work_decision_block_corrections');
    var fileOfficial = document.getElementById('work_decision_official_final_file');
    var corrDeadline = document.getElementById('work_decision_correction_deadline');
    var fileCorr = document.getElementById('work_decision_coordinator_feedback_file');
    var postCorrection = form.getAttribute('data-post-correction') === '1';

    function setDisabled(el, on) {
        if (!el) return;
        el.disabled = !!on;
        if (on && el.type === 'file') {
            try { el.value = ''; } catch (e) {}
        }
    }

    function sync() {
        var v = sel ? sel.value : '';
        var showOfficial = v === 'approved_final';
        var showCorr = !postCorrection && v === 'accepted_with_corrections';

        if (blockOfficial) {
            blockOfficial.classList.toggle('hidden', !showOfficial);
            setDisabled(fileOfficial, !showOfficial);
        }
        if (blockCorr) {
            blockCorr.classList.toggle('hidden', !showCorr);
            setDisabled(corrDeadline, !showCorr);
            setDisabled(fileCorr, !showCorr);
        }
    }

    if (sel) {
        sel.addEventListener('change', sync);
    }
    sync();
})();
</script>
@endpush
