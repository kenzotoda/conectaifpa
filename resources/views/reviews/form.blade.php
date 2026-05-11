@extends('layouts.newMain')

@section('title', 'Avaliar Trabalho')

@section('content')
<div class="min-h-screen bg-slate-50/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="mb-6">
            <p class="text-sm text-slate-500 mb-1">{{ $work->event->title }}</p>
            <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900">{{ $work->listTitleCompact() }}</h1>
            @if(!empty($isCorrectionReReview) && $isCorrectionReReview)
                <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-950 px-4 py-3 text-sm">
                    <p class="font-semibold m-0 mb-1">Reavaliação da versão corrigida</p>
                    <p class="m-0 mb-2">
                        O arquivo disponível para download é a <strong>última correção enviada pelo autor</strong> neste servidor.
                        Na reavaliação você deve apenas <strong>aceitar</strong> ou <strong>rejeitar</strong>; não use "aceitar com correções".
                    </p>
                    <p class="m-0 text-xs opacity-95">
                        O arquivo que você enviar fica só para a coordenação, separado da versão registrada pelo autor — que não é alterada.
                        Mantenha a <strong>mesma extensão</strong> do trabalho atual (ex.: PDF).
                    </p>
                </div>
            @endif
            <div class="mt-3">
                <a href="{{ route('works.download', $work->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold no-underline transition-colors">
                    <ion-icon name="download-outline"></ion-icon>
                    Baixar arquivo do trabalho
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6">
            <x-validation-errors class="mb-4" />

            <form action="{{ route('reviews.submit', $work->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Recomendação final</label>
                    <select name="recommendation" required
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none">
                        <option value="">Selecione...</option>
                        <option value="accept" @selected(old('recommendation', $review->recommendation ?? '') === 'accept')>Aceitar</option>
                        @if(empty($isCorrectionReReview) || !$isCorrectionReReview)
                            <option value="accept_with_corrections" @selected(old('recommendation', $review->recommendation ?? '') === 'accept_with_corrections')>Aceitar com correções</option>
                        @endif
                        <option value="reject" @selected(old('recommendation', $review->recommendation ?? '') === 'reject')>Rejeitar</option>
                    </select>
                </div>

                <div>
                    @if(empty($isCorrectionReReview) || ! $isCorrectionReReview)
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nota final da avaliação</label>
                        <input type="text"
                               inputmode="decimal"
                               name="score"
                               id="review-score"
                               value="{{ old('score', $review->score ?? '') }}"
                               required
                               class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none">
                        <p class="text-xs text-slate-500 mt-2">
                            Informe uma nota numérica conforme a escala definida no edital/chamada do evento (ex.: 0-5, 0-10 etc.).
                        </p>
                    @else
                        @if(($carryOverScore ?? null) !== null)
                            <label class="block text-sm font-medium text-slate-700 mb-2">Nota</label>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 mb-2">
                                <p class="text-xs text-slate-600 m-0 mb-1">Nesta rodada a nota da sua primeira avaliação é repetida automaticamente (não é editável).</p>
                                <p class="text-2xl font-bold tabular-nums text-slate-900 m-0">{{ number_format((float) $carryOverScore, 2, ',', '.') }}</p>
                            </div>
                            <input type="hidden" name="score" value="{{ old('score', number_format((float) $carryOverScore, 2, '.', '')) }}">
                        @else
                            <label class="block text-sm font-medium text-slate-700 mb-2">Nota final da avaliação</label>
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 mb-3 text-sm text-amber-950 space-y-1">
                                <p class="font-medium m-0">Nota não foi recuperada automaticamente</p>
                                <p class="m-0">Informe a <strong>mesma nota</strong> que deu na primeira avaliação. Se isso aparecer sempre, contacte o coordenador para atualizar o sistema.</p>
                            </div>
                            <input type="text"
                                   inputmode="decimal"
                                   name="score"
                                   id="review-score"
                                   value="{{ old('score', '') }}"
                                   required
                                   class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none">
                            <p class="text-xs text-slate-500 mt-2">
                                Use apenas números, conforme a escala do edital (ex.: 0–5, 0–10).
                            </p>
                        @endif
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Comentário técnico (interno)</label>
                    <textarea name="general_comment" rows="4"
                              class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none">{{ old('general_comment', $review->general_comment ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Feedback ao autor</label>
                    <textarea name="comment_to_author" rows="4"
                              class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none">{{ old('comment_to_author', $review->comment_to_author ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Arquivo para a coordenação
                    </label>
                    <p class="text-xs text-slate-500 mb-2">
                        @if(!empty($isCorrectionReReview) && $isCorrectionReReview)
                            <strong>Obrigatório nesta rodada.</strong>
                            Envie a sua versão refinada (pode ser igual à do autor com ajustes pontuais). Fica apenas à disposição da coordenação, sem alterar a versão do autor no sistema. Use a mesma extensão da submissão atual (ex.: PDF).
                        @else
                            Obrigatório se a recomendação for “Aceitar com correções”. Serve de base para o coordenador preparar o retorno ao autor. PDF, DOC, DOCX ou ODT — até 10MB.
                        @endif
                    </p>
                    <input type="file" name="feedback_file"
                           accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text"
                           class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none"
                           @if(!empty($isCorrectionReReview) && $isCorrectionReReview) required @endif>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition-colors disabled:opacity-50 disabled:pointer-events-none">
                        <ion-icon name="checkmark-done-outline"></ion-icon>
                        Enviar avaliação
                    </button>
                    <a href="{{ route('reviews.assigned') }}"
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold no-underline transition-colors">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const reviewScoreInput = document.getElementById('review-score');
    const reviewForm = reviewScoreInput ? reviewScoreInput.closest('form') : null;
    if (reviewScoreInput) {
        reviewScoreInput.addEventListener('keydown', (event) => {
            if (['e', 'E', '+', '-'].includes(event.key)) {
                event.preventDefault();
            }
        });

        reviewScoreInput.addEventListener('input', () => {
            let value = reviewScoreInput.value.replace(',', '.');
            value = value.replace(/[^0-9.]/g, '');
            const firstDot = value.indexOf('.');
            if (firstDot !== -1) {
                value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
            }
            reviewScoreInput.value = value;
        });

        reviewForm?.addEventListener('submit', () => {
            reviewScoreInput.value = reviewScoreInput.value.replace(',', '.');
        });
    }
</script>
@endpush
