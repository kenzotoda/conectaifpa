@extends('layouts.newMain')

@section('title', 'Meus Trabalhos')

@section('content')
<div class="min-h-screen bg-slate-50/50">
    @php
        $workTypeLabels = \App\Models\Work::workTypeLabels();
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900">Meus Trabalhos</h1>
            <a href="/dashboard"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold no-underline transition-colors">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Painel
            </a>
        </div>

        @if($works->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <h3 class="font-semibold text-slate-900 text-lg mb-2">Nenhum trabalho submetido</h3>
                <p class="text-slate-500 m-0">Participe de um evento e realize sua primeira submissão.</p>
            </div>
        @else
            <div class="overflow-x-auto overflow-y-auto max-h-[min(75vh,52rem)] rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm text-left min-w-[44rem]">
                    <thead class="sticky top-0 z-10 bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 shadow-sm">
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
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold no-underline transition-colors">
                                                <ion-icon name="ribbon-outline" class="text-base"></ion-icon>
                                                Resultado
                                            </a>
                                        @endif
                                        <a href="{{ route('works.show', $work->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold no-underline transition-colors">
                                            <ion-icon name="eye-outline" class="text-base"></ion-icon>
                                            Detalhes
                                        </a>
                                        @if($canEdit)
                                            <a href="{{ route('works.edit', $work->id) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-semibold no-underline transition-colors">
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
