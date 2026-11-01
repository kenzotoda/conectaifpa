@extends('layouts.newMain')

@section('title', 'Meus Trabalhos')

@section('content')
<div class="dash-page min-h-screen">
    @php
        $workTypeLabels = \App\Models\Work::workTypeLabels();
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="flex items-center justify-between mb-6">
            <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl">Meus Trabalhos</h1>
            <a href="/dashboard"
               class="dash-btn-secondary inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold no-underline transition-colors">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Painel
            </a>
        </div>

        @if($works->isEmpty())
            <div class="dash-card rounded-2xl p-8 text-center">
                <h3 class="dash-title font-semibold text-lg mb-2">Nenhum trabalho submetido</h3>
                <p class="dash-muted m-0">Participe de um evento e realize sua primeira submissão.</p>
            </div>
        @else
            <div class="dash-card dash-table overflow-x-auto overflow-y-auto max-h-[min(75vh,52rem)] rounded-2xl">
                <table class="w-full text-sm text-left min-w-[44rem]">
                    <thead class="sticky top-0 z-10 font-semibold border-b">
                        <tr>
                            <th scope="col" class="px-4 py-3">Evento</th>
                            <th scope="col" class="px-4 py-3">Trabalho</th>
                            <th scope="col" class="px-4 py-3">Tipo</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($works as $work)
                            @php
                                $hasFinalResult = in_array($work->status, [
                                    \App\Models\Work::STATUS_FINAL_VALIDATED,
                                    \App\Models\Work::STATUS_ACCEPTED_WITH_CORRECTIONS,
                                    \App\Models\Work::STATUS_REJECTED,
                                    \App\Models\Work::STATUS_CONFLICT,
                                ], true);
                                $canEdit = $work->status === \App\Models\Work::STATUS_SUBMITTED
                                    && $work->event->workSubmissionWindowOpen();
                            @endphp
                            <tr class="hover:bg-slate-50/80 align-top">
                                <td class="px-4 py-3 text-slate-600 max-w-[14rem] break-words [overflow-wrap:anywhere]">
                                    {{ $work->event->title }}
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 break-words [overflow-wrap:anywhere]">
                                    {{ $work->listTitleCompact() }}
                                </td>
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                    {{ $workTypeLabels[$work->work_type] ?? $work->work_type }}
                                </td>
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                    {{ $work->statusLabel() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        @if($hasFinalResult)
                                            <a href="{{ route('works.show', $work->id) }}"
                                               class="dash-btn-secondary inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold no-underline transition-colors">
                                                <ion-icon name="ribbon-outline" class="text-base"></ion-icon>
                                                Resultado
                                            </a>
                                        @endif
                                        <a href="{{ route('works.show', $work->id) }}"
                                           class="dash-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold no-underline transition-colors">
                                            <ion-icon name="eye-outline" class="text-base"></ion-icon>
                                            Detalhes
                                        </a>
                                        @if($canEdit)
                                            <a href="{{ route('works.edit', $work->id) }}"
                                               class="dash-btn-soft inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold no-underline transition-colors">
                                                <ion-icon name="create-outline" class="text-base"></ion-icon>
                                                Editar
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($works->hasPages())
                <div class="mt-4">
                    {{ $works->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
