@extends('layouts.newMain')

@section('title', $event->hasAnnalsPublication() ? $event->annalsKindLabel().' | '.$event->title : 'Anais | '.$event->title)

@section('content')
<div class="min-h-screen bg-slate-50/80">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
        <a href="{{ route('events.show', $event->id) }}"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 no-underline mb-5 sm:mb-6">
            <ion-icon name="arrow-back-outline" class="text-lg shrink-0"></ion-icon>
            Página do evento
        </a>

        <header class="mb-8">
            <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 m-0 tracking-tight">
                {{ $event->hasAnnalsPublication() ? $event->annalsKindLabel() : 'Anais ou e-book' }}
            </h1>
            <p class="text-slate-700 text-base sm:text-lg font-medium m-0 mt-2 leading-snug break-words [overflow-wrap:anywhere]">
                {{ $event->title }}
            </p>
        </header>

        @if($event->hasAnnalsPublication())
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <p class="text-sm text-slate-600 m-0 leading-relaxed">
                    Arquivo publicado pela coordenação
                    @if($event->annals_uploaded_at)
                        em {{ $event->annals_uploaded_at->format('d/m/Y') }} às {{ $event->annals_uploaded_at->format('H:i') }}.
                    @else
                        .
                    @endif
                </p>
                <p class="text-sm font-medium text-slate-800 m-0 mt-2 break-words [overflow-wrap:anywhere]">{{ $event->annals_file_name }}</p>
                <a href="{{ route('events.annals.download', $event->id) }}"
                   class="dash-btn mt-6 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold no-underline">
                    <ion-icon name="download-outline" class="text-lg" aria-hidden="true"></ion-icon>
                    Baixar PDF
                </a>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 p-10 sm:p-12 text-center shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mb-5">
                    <ion-icon name="book-outline" class="text-3xl"></ion-icon>
                </div>
                <p class="text-slate-800 font-semibold text-base m-0">Ainda não há PDF publicado</p>
                <p class="text-slate-600 text-sm m-0 mt-3 max-w-md mx-auto leading-relaxed">
                    Depois de finalizar o evento, a coordenação envia o PDF dos anais ou do e-book. Quando isso acontecer, o download aparece aqui.
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
