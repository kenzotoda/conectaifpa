@extends('layouts.newMain')

@php
    use App\Models\Work;
@endphp

@section('title', 'Minha apresentação')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-50/90 via-white to-slate-50/50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 pb-14">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 tracking-tight m-0">Minha apresentação</h1>
                <p class="text-sm text-slate-600 m-0 mt-2 max-w-xl leading-relaxed">Agendamentos dos seus trabalhos aprovados com data e local da sessão.</p>
            </div>
            <a href="{{ route('works.my') }}"
               class="inline-flex items-center justify-center gap-2 shrink-0 px-4 py-2.5 rounded-xl border border-slate-200/90 bg-white text-slate-800 text-sm font-semibold no-underline shadow-sm hover:bg-slate-50 hover:border-slate-300 transition-colors">
                <ion-icon name="documents-outline" class="text-lg text-emerald-600" aria-hidden="true"></ion-icon>
                Meus trabalhos
            </a>
        </div>

        @if($works->isEmpty())
            <div class="rounded-2xl border border-slate-200/90 bg-white shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)] ring-1 ring-slate-100/80 overflow-hidden text-center px-6 py-14 sm:py-16">
                <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0b6b45] text-white mb-4">
                    <ion-icon name="calendar-outline" class="text-3xl" aria-hidden="true"></ion-icon>
                </div>
                <h2 class="font-semibold text-lg text-slate-900 m-0 mb-2">Nada agendado ainda</h2>
                <p class="text-slate-600 text-sm m-0 max-w-md mx-auto leading-relaxed">Quando um trabalho seu for aceito e receber sessão e horário no cronograma do evento, os detalhes aparecerão aqui.</p>
            </div>
        @else
            <ul class="space-y-5 list-none m-0 p-0">
                @foreach($works as $work)
                    @php
                        $badgeClass = match ($work->status) {
                            Work::STATUS_SCHEDULED, Work::STATUS_PRESENTED, Work::STATUS_PUBLISHED_ANNALS => 'border-[#b7ebcf] bg-[#e7f8ef] text-[#0b6b45]',
                            Work::STATUS_ABSENT => 'border-red-200/90 bg-red-50 text-red-950 ring-red-100',
                            default => 'border-slate-200/90 bg-slate-50 text-slate-900 ring-slate-100',
                        };
                        $accentClass = match ($work->status) {
                            Work::STATUS_SCHEDULED, Work::STATUS_PRESENTED, Work::STATUS_PUBLISHED_ANNALS => 'bg-[#0b6b45]',
                            Work::STATUS_ABSENT => 'bg-red-700',
                            default => 'bg-slate-300',
                        };
                    @endphp
                    <li class="group rounded-2xl border border-slate-200/90 bg-white shadow-[0_10px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-slate-100/90 overflow-hidden transition-shadow hover:shadow-[0_16px_48px_-16px_rgba(15,23,42,0.22)]">
                        <div class="h-1 w-full {{ $accentClass }}" aria-hidden="true"></div>
                        <div class="p-5 sm:p-6 space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1 space-y-2">
                                    <a href="{{ route('events.show', $work->event_id) }}"
                                       class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-800 hover:text-emerald-950 no-underline rounded-lg px-2 py-1 -mx-2 -mt-0.5 hover:bg-emerald-50 transition-colors max-w-full [overflow-wrap:anywhere]">
                                        <ion-icon name="calendar-outline" class="text-base shrink-0 text-emerald-600" aria-hidden="true"></ion-icon>
                                        <span class="min-w-0">{{ $work->event->title }}</span>
                                    </a>
                                    <h2 class="font-semibold text-lg sm:text-xl text-slate-900 leading-snug m-0 [overflow-wrap:anywhere]">
                                        {{ $work->listTitleCompact() }}
                                    </h2>
                                </div>
                                <span class="inline-flex items-center shrink-0 rounded-full border px-3 py-1 text-xs font-bold tracking-wide ring-1 {{ $badgeClass }}">
                                    {{ $work->statusLabel() }}
                                </span>
                            </div>

                            @if($work->presentation)
                                <div class="rounded-xl border border-slate-100 bg-gradient-to-b from-slate-50/80 to-white p-4 sm:p-5 space-y-3">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0 flex items-center gap-2">
                                        <ion-icon name="videocam-outline" class="text-base text-emerald-700" aria-hidden="true"></ion-icon>
                                        Sessão de apresentação
                                    </p>
                                    <dl class="grid gap-3 sm:grid-cols-2 m-0 text-sm">
                                        <div class="flex gap-3 min-w-0">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-200/80 text-slate-500 shadow-sm">
                                                <ion-icon name="layers-outline" class="text-lg" aria-hidden="true"></ion-icon>
                                            </span>
                                            <div class="min-w-0">
                                                <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide m-0">Sessão</dt>
                                                <dd class="text-slate-900 font-medium m-0 mt-0.5 [overflow-wrap:anywhere]">{{ $work->presentation->session_name ?? '-' }}</dd>
                                            </div>
                                        </div>
                                        @if($work->presentation->scheduled_start || $work->presentation->scheduled_end)
                                            <div class="flex gap-3 min-w-0 sm:col-span-2">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-200/80 text-slate-500 shadow-sm">
                                                    <ion-icon name="time-outline" class="text-lg" aria-hidden="true"></ion-icon>
                                                </span>
                                                <div class="min-w-0">
                                                    <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide m-0">Horário</dt>
                                                    <dd class="text-slate-900 font-medium m-0 mt-0.5 tabular-nums">
                                                        @if($work->presentation->scheduled_start)
                                                            {{ \Carbon\Carbon::parse($work->presentation->scheduled_start)->format('d/m/Y H:i') }}
                                                        @else
                                                            -
                                                        @endif
                                                        @if($work->presentation->scheduled_end)
                                                            <span class="text-slate-400 font-normal"> · </span>
                                                            {{ \Carbon\Carbon::parse($work->presentation->scheduled_end)->format('H:i') }}
                                                        @endif
                                                    </dd>
                                                </div>
                                            </div>
                                        @endif
                                        @if($work->presentation->location)
                                            <div class="flex gap-3 min-w-0 sm:col-span-2">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-200/80 text-slate-500 shadow-sm">
                                                    <ion-icon name="location-outline" class="text-lg" aria-hidden="true"></ion-icon>
                                                </span>
                                                <div class="min-w-0">
                                                    <dt class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide m-0">Local</dt>
                                                    <dd class="text-slate-900 font-medium m-0 mt-0.5 [overflow-wrap:anywhere]">{{ $work->presentation->location }}</dd>
                                                </div>
                                            </div>
                                        @endif
                                    </dl>
                                </div>
                            @else
                                <p class="text-sm text-amber-800 bg-amber-50/90 border border-amber-200/80 rounded-xl px-4 py-3 m-0 ring-1 ring-amber-100/90">
                                    O trabalho está nesta etapa, mas ainda <strong>não há agendamento</strong> de sessão ou horário. Aguarde a organização do evento.
                                </p>
                            @endif

                            <div class="flex flex-wrap gap-2 pt-1">
                                <a href="{{ route('works.show', $work->id) }}"
                                   class="dash-btn inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold no-underline transition-colors">
                                    <ion-icon name="open-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                                    Ver trabalho
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
