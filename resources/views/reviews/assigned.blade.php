@extends('layouts.newMain')

@section('title', 'Avaliações Designadas')

@section('content')
<div class="min-h-screen bg-slate-50/50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900">Minhas Avaliações</h1>
            <a href="/dashboard"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold no-underline transition-colors">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Painel
            </a>
        </div>

        @if($assignments->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <h3 class="font-semibold text-slate-900 text-lg mb-2">Nenhum trabalho designado</h3>
                <p class="text-slate-500 m-0">Quando um coordenador vincular você como avaliador, os trabalhos aparecerão aqui.</p>
            </div>
        @else
            <div class="overflow-x-auto overflow-y-auto max-h-[min(75vh,52rem)] rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm text-left min-w-[56rem]">
                    <thead class="sticky top-0 z-10 bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 shadow-sm">
                        <tr>
                            <th scope="col" class="px-4 py-3">Evento</th>
                            <th scope="col" class="px-4 py-3">Trabalho</th>
                            <th scope="col" class="px-4 py-3">Sua designação</th>
                            <th scope="col" class="px-4 py-3 max-w-[18rem]">Acesso ao trabalho</th>
                            <th scope="col" class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                            @foreach($assignments as $assignment)
                            @php
                                $eventModel = $assignment->work->event;
                                $submissionDeadline = $eventModel->submission_deadline_at;
                                $canReviewNow = $eventModel->workSubmissionDeadlinePassed();
                                $evaluationWindowOpen = $eventModel->reviewersEvaluationWindowOpen();
                                $canSendNewReview = $canReviewNow && $evaluationWindowOpen;
                                $myReview = $assignment->work->reviews->first();
                                $reviewCompleted = $myReview && $myReview->submitted_at;
                                $workCorrRound = $assignment->work->isAwaitingCorrectionReReview();
                                $needsMyCorrReview = $workCorrRound && ! $reviewCompleted;
                                $canDownloadWork = $canReviewNow && ($evaluationWindowOpen || $reviewCompleted);
                            @endphp
                            <tr class="hover:bg-slate-50/80 align-top">
                                <td class="px-4 py-3 text-slate-600 max-w-[12rem] break-words [overflow-wrap:anywhere]">
                                    {{ $assignment->work->event->title }}
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 break-words [overflow-wrap:anywhere] max-w-[14rem]">
                                    {{ $assignment->work->listTitleCompact() }}
                                    @if($workCorrRound)
                                        <span class="block mt-1.5 text-[0.7rem] font-semibold uppercase tracking-wide text-indigo-700">
                                            Reavaliação — versão corrigida
                                            @if(! $needsMyCorrReview && $assignment->status === \App\Models\WorkReviewer::STATUS_COMPLETED)
                                                <span class="block mt-1 font-normal normal-case text-emerald-800">Sua avaliação nesta rodada não é obrigatória (parecer anterior já era “aceitar” sem correções).</span>
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                    {{ $assignment->statusLabel() }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 text-xs max-w-[18rem] align-top">
                                    @if($canSendNewReview)
                                        <span class="block text-emerald-800 font-semibold text-sm">Pode baixar e avaliar</span>
                                        <span class="block text-slate-500 mt-1 leading-snug">
                                            Submissões encerradas e dentro do período do evento (até
                                            <strong class="text-slate-700">{{ $eventModel->calendarEndAt()->format('d/m/Y H:i') }}</strong>).
                                        </span>
                                    @elseif($canReviewNow && ! $evaluationWindowOpen)
                                        <span class="block text-rose-800 font-semibold text-sm">Prazo de avaliação encerrado</span>
                                        <span class="block text-slate-600 mt-1 leading-snug">
                                            O período do evento já terminou
                                            @if($reviewCompleted)
                                                — você pode ver o parecer enviado.
                                            @else
                                                — não é mais possível enviar avaliação para este trabalho.
                                            @endif
                                        </span>
                                    @else
                                        <span class="block text-amber-800 font-semibold text-sm">Aguardando fim das submissões</span>
                                        @if($submissionDeadline)
                                            <span class="block text-slate-600 mt-1 leading-snug">
                                                Enquanto autores puderem alterar arquivos, o download e o envio do parecer ficam bloqueados.
                                                Liberação prevista após <strong class="text-slate-800">{{ $submissionDeadline->format('d/m/Y') }} às {{ $submissionDeadline->format('H:i') }}</strong>.
                                            </span>
                                        @else
                                            <span class="block text-slate-600 mt-1 leading-snug">
                                                Este evento ainda não tem data de encerramento das submissões cadastrada. A coordenação precisa definir esse prazo para liberar as avaliações.
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        @if($canDownloadWork)
                                            <a href="{{ route('works.download', $assignment->work_id) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold no-underline transition-colors">
                                                <ion-icon name="download-outline" class="text-base"></ion-icon>
                                                Baixar
                                            </a>
                                        @else
                                            @php
                                                $dlBlockedTitle = ! $canReviewNow
                                                    ? 'Disponível após o encerramento do prazo de submissão dos autores.'
                                                    : (! $reviewCompleted
                                                        ? 'O período do evento encerrou. Não é mais possível baixar para nova avaliação.'
                                                        : '');
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-200 text-slate-500 text-xs font-medium cursor-not-allowed"
                                                  title="{{ $dlBlockedTitle ?: 'Download indisponível.' }}">
                                                <ion-icon name="time-outline" class="text-base"></ion-icon>
                                                Baixar
                                            </span>
                                        @endif
                                        @if($reviewCompleted)
                                            <a href="{{ route('reviews.form', $assignment->work_id) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-xs font-semibold no-underline transition-colors">
                                                <ion-icon name="eye-outline" class="text-base"></ion-icon>
                                                {{ $needsMyCorrReview ? 'Ver parecer' : 'Ver avaliação' }}
                                            </a>
                                        @elseif($canSendNewReview)
                                            <a href="{{ route('reviews.form', $assignment->work_id) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold no-underline transition-colors">
                                                <ion-icon name="clipboard-outline" class="text-base"></ion-icon>
                                                {{ $needsMyCorrReview ? 'Avaliar correções' : 'Avaliar' }}
                                            </a>
                                        @else
                                            @php
                                                $blockedTitle = ! $canReviewNow
                                                    ? 'A avaliação só pode ser enviada depois que o prazo de submissão dos autores terminar.'
                                                    : 'O período do evento encerrou. Não é mais possível enviar avaliações.';
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-200 text-slate-500 text-xs font-medium cursor-not-allowed"
                                                  title="{{ $blockedTitle }}">
                                                <ion-icon name="time-outline" class="text-base"></ion-icon>
                                                Bloqueado
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($assignments->hasPages())
                <div class="mt-4">
                    {{ $assignments->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
