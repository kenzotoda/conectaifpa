@extends('layouts.newMain')

@php
    $recommendationLabels = [
        \App\Models\Review::RECOMMENDATION_ACCEPT => 'Aceitar',
        \App\Models\Review::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS => 'Aceitar com correções',
        \App\Models\Review::RECOMMENDATION_REJECT => 'Rejeitar',
    ];
@endphp

@section('title', 'Avaliação enviada')

@section('content')
<div class="min-h-screen bg-slate-50/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="mb-6">
            <p class="text-sm text-slate-500 mb-1">{{ $work->event->title }}</p>
            <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900">{{ $work->listTitleCompact() }}</h1>
            <p class="text-sm text-slate-600 mt-2 m-0">
                Sua avaliação já foi enviada. Não é possível alterá-la.
            </p>
            @if($work->isAwaitingCorrectionReReview())
                <p class="text-sm text-indigo-900 mt-2 mb-0 p-3 rounded-xl bg-indigo-50 border border-indigo-200">
                    Este parecer refere-se à <strong>reavaliação da versão corrigida</strong> pelo autor. Outros avaliadores ou a coordenação ainda podem estar concluindo esta etapa.
                </p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 space-y-4">
            <div>
                <h2 class="font-semibold text-slate-900 text-lg mb-3">Resumo do envio</h2>
                <p class="text-sm text-slate-600 mb-1">
                    Recomendação: <span class="font-medium text-slate-900">{{ $recommendationLabels[$review->recommendation] ?? $review->recommendation }}</span>
                </p>
                <p class="text-sm text-slate-600 mb-1">
                    Nota: <span class="font-medium text-slate-900">{{ $review->score ?? '—' }}</span>
                </p>
                <p class="text-sm text-slate-600 mb-1">
                    Status da designação: <span class="font-medium text-slate-900">{{ $assignment->statusLabel() }}</span>
                </p>
                <p class="text-sm text-slate-600 m-0">
                    Enviado em:
                    <span class="font-medium text-slate-900">{{ $review->submitted_at?->format('d/m/Y H:i') ?? '—' }}</span>
                </p>
            </div>

            @if(! empty($review->refined_correction_file_path))
                <div class="pt-2 border-t border-slate-100">
                    <p class="text-sm text-slate-700 mb-2 m-0">Versão refinada enviada à coordenação (reavaliação):</p>
                    <a href="{{ route('reviews.refined-correction.download', [$work->id, $review->id]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold no-underline transition-colors">
                        <ion-icon name="download-outline"></ion-icon>
                        Baixar versão refinada
                    </a>
                </div>
            @endif

            @if($review->recommendation === \App\Models\Review::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS && !empty($review->feedback_file_path))
                <div class="pt-2 border-t border-slate-100">
                    <p class="text-sm text-slate-700 mb-2 m-0">Arquivo de feedback enviado à coordenação:</p>
                    <a href="{{ route('reviews.my-feedback.download', $work->id) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold no-underline transition-colors">
                        <ion-icon name="download-outline"></ion-icon>
                        Baixar meu arquivo de feedback
                    </a>
                </div>
            @elseif($review->recommendation === \App\Models\Review::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS)
                <p class="text-sm text-amber-800 m-0">
                    Esta avaliação foi registrada como “com correções”, mas não há arquivo de feedback associado no sistema.
                </p>
            @endif

            <div class="pt-2">
                <a href="{{ route('reviews.assigned') }}"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold no-underline transition-colors">
                    <ion-icon name="arrow-back-outline"></ion-icon>
                    Voltar às avaliações
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
