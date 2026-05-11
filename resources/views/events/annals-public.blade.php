@extends('layouts.newMain')

@section('title', 'Anais — '.$event->title)

@section('content')
@php
    $workTypeLabels = \App\Models\Work::workTypeLabels();
@endphp
<div class="min-h-screen bg-slate-50/80">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
        <a href="{{ route('events.show', $event->id) }}"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 no-underline mb-5 sm:mb-6">
            <ion-icon name="arrow-back-outline" class="text-lg shrink-0"></ion-icon>
            Página do evento
        </a>

        <header class="mb-8 sm:mb-10">
            <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 m-0 tracking-tight">Anais</h1>
            <p class="text-slate-700 text-base sm:text-lg font-medium m-0 mt-2 leading-snug break-words [overflow-wrap:anywhere]">
                {{ $event->title }}
            </p>
            @if($works->isNotEmpty())
                <p class="text-sm text-slate-500 m-0 mt-4 leading-relaxed">
                    <span class="font-semibold text-slate-700">{{ $works->count() }}</span>
                    {{ $works->count() === 1 ? 'trabalho com publicação registrada.' : 'trabalhos com publicação registrada.' }}
                </p>
            @endif
        </header>

        @if($works->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-10 sm:p-12 text-center shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mb-5">
                    <ion-icon name="book-outline" class="text-3xl"></ion-icon>
                </div>
                <p class="text-slate-800 font-semibold text-base m-0">Nenhum trabalho nesta lista ainda</p>
                <p class="text-slate-600 text-sm m-0 mt-3 max-w-md mx-auto leading-relaxed">
                    Quando a coordenação registrar a publicação nos anais, os trabalhos aparecerão aqui.
                </p>
            </div>
        @else
            <ul class="flex flex-col gap-5 sm:gap-6 m-0 p-0 list-none">
                @foreach($works as $work)
                    @php
                        $annalsTypeLabel = $workTypeLabels[$work->work_type] ?? null;
                        $annalsDisplayTitle = trim((string) ($work->title ?? '')) !== ''
                            ? trim((string) $work->title)
                            : $work->listTitleCompact();
                        $annalsShowTypeBadge = $annalsTypeLabel !== null && $annalsTypeLabel !== ''
                            && strcasecmp($annalsDisplayTitle, $annalsTypeLabel) !== 0;
                    @endphp
                    <li class="group bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all duration-200 overflow-hidden">
                        <div class="p-5 sm:p-6 flex flex-col gap-5">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <div class="min-w-0 flex-1 flex flex-col gap-3">
                                    @if($annalsShowTypeBadge)
                                        <span class="inline-flex self-start items-center rounded-full bg-indigo-50 text-indigo-900 px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-indigo-100">
                                            {{ $annalsTypeLabel }}
                                        </span>
                                    @endif
                                    <h2 class="font-montserrat font-bold text-lg sm:text-xl text-slate-900 m-0 leading-snug break-words [overflow-wrap:anywhere]">
                                        {{ $annalsDisplayTitle }}
                                    </h2>
                                    @if($work->authors->isNotEmpty())
                                        <p class="flex items-start gap-2 text-sm text-slate-600 m-0 leading-relaxed">
                                            <ion-icon name="people-outline" class="text-slate-400 text-lg shrink-0 mt-0.5"></ion-icon>
                                            <span class="min-w-0">{{ $work->authors->pluck('author_name')->filter()->implode(', ') }}</span>
                                        </p>
                                    @endif
                                </div>
                                @if($work->published_in_annals_at)
                                    <div class="shrink-0 sm:pt-0.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-slate-50 text-slate-700 px-3 py-2 text-xs font-medium ring-1 ring-slate-200/80">
                                            <ion-icon name="calendar-outline" class="text-base text-slate-500"></ion-icon>
                                            {{ $work->published_in_annals_at->format('d/m/Y') }}
                                            <span class="text-slate-400">·</span>
                                            {{ $work->published_in_annals_at->format('H:i') }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            @if($work->annals_note)
                                <div class="rounded-xl bg-slate-50/90 border border-slate-100 px-4 py-3.5">
                                    <p class="text-sm text-slate-700 m-0 whitespace-pre-line leading-relaxed">{{ $work->annals_note }}</p>
                                </div>
                            @endif

                            @if($work->annals_url)
                                <div>
                                    <a href="{{ $work->annals_url }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold no-underline shadow-sm transition-colors">
                                        <ion-icon name="open-outline" class="text-lg"></ion-icon>
                                        Ver registro / PDF
                                    </a>
                                </div>
                            @else
                                <p class="text-xs text-slate-500 m-0 flex items-start gap-2 leading-relaxed">
                                    <ion-icon name="information-circle-outline" class="text-base text-slate-400 shrink-0 mt-0.5"></ion-icon>
                                    <span>Sem link público cadastrado.</span>
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
