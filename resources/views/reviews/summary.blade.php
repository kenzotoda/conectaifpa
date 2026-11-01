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
<div class="dash-page min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="mb-6">
            <p class="dash-muted text-sm mb-1">{{ $work->event->title }}</p>
            <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl">{{ $work->listTitleCompact() }}</h1>
            <p class="dash-text text-sm mt-2 m-0">
                Sua avaliação já foi enviada. Não é possível alterá-la.
            </p>
            @if($work->isAwaitingCorrectionReReview())
                <p class="dash-panel dash-text text-sm mt-2 mb-0 p-3 rounded-xl">
                    Este parecer refere-se à <strong>reavaliação da versão corrigida</strong> pelo autor. Outros avaliadores ou a coordenação ainda podem estar concluindo esta etapa.
                </p>
            @endif
        </div>

        <div class="dash-card rounded-2xl p-5 sm:p-6 space-y-4">
            <div>
                <h2 class="dash-title font-semibold text-lg mb-3">Resumo do envio</h2>
                <p class="dash-text text-sm mb-1">
                    Recomendação: <span class="dash-title font-medium">{{ $recommendationLabels[$review->recommendation] ?? $review->recommendation }}</span>
                </p>
                <p class="dash-text text-sm mb-1">
                    Nota: <span class="dash-title font-medium">{{ $review->score ?? '—' }}</span>
                </p>
                <p class="dash-text text-sm mb-1">
                    Status da designação: <span class="dash-title font-medium">{{ $assignment->statusLabel() }}</span>
                </p>
                <p class="dash-text text-sm m-0">
                    Enviado em:
                    <span class="dash-title font-medium">{{ $review->submitted_at?->format('d/m/Y H:i') ?? '—' }}</span>
                </p>
            </div>

            @if(! empty($review->refined_correction_file_path))
                <div class="dash-rule pt-2 border-t">
                    <p class="dash-text text-sm mb-2 m-0">Versão refinada enviada à coordenação (reavaliação):</p>
                    <a href="{{ route('reviews.refined-correction.download', [$work->id, $review->id]) }}"
                       class="dash-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold no-underline">
                        <ion-icon name="download-outline"></ion-icon>
                        Baixar versão refinada
                    </a>
                </div>
            @endif

            @if($review->recommendation === \App\Models\Review::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS && !empty($review->feedback_file_path))
                <div class="dash-rule pt-2 border-t">
                    <p class="dash-text text-sm mb-2 m-0">Arquivo de feedback enviado à coordenação:</p>
                    <a href="{{ route('reviews.my-feedback.download', $work->id) }}"
                       class="dash-btn inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold no-underline">
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
                   class="dash-btn-secondary inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold no-underline">
                    <ion-icon name="arrow-back-outline"></ion-icon>
                    Voltar às avaliações
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
