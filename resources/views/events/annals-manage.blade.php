@extends('layouts.newMain')

@section('title', 'Anais do evento — '.$event->title)

@section('content')
@php
    $workTypeLabels = \App\Models\Work::workTypeLabels();
@endphp
<div class="min-h-screen bg-slate-50/80">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm text-slate-500 m-0 mb-1">
                    <a href="{{ route('dashboard') }}" class="font-semibold text-emerald-800 hover:underline">Painel</a>
                    <span class="mx-1 text-slate-400">/</span>
                    <a href="{{ route('events.show', $event->id) }}" class="text-slate-600 hover:text-emerald-800 hover:underline">{{ $event->title }}</a>
                </p>
                <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 m-0 tracking-tight">Publicação nos anais</h1>
                <p class="text-slate-600 text-sm mt-2 m-0 max-w-2xl leading-relaxed">
                    Apenas trabalhos com status <strong>Apresentado</strong> aparecem aqui para registro nos anais. Já constam também os que foram publicados anteriormente para permitir atualizar URL ou observações.
                    A <a href="{{ route('events.annals', $event->id) }}" class="font-semibold text-indigo-700 hover:text-indigo-900 no-underline underline-offset-2">página pública dos anais</a> lista o que já foi oficialmente registado neste sistema.
                </p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('events.works.index', $event->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 text-sm font-semibold no-underline hover:bg-slate-50 transition-colors">
                    <ion-icon name="documents-outline" class="text-lg"></ion-icon>
                    Trabalhos
                </a>
                @if($event->acceptsSubmissions())
                    <a href="{{ route('events.presentations.manage', $event->id) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 text-sm font-semibold no-underline hover:bg-slate-50 transition-colors">
                        <ion-icon name="videocam-outline" class="text-lg"></ion-icon>
                        Apresentações
                    </a>
                @endif
            </div>
        </div>

        @if(session('msg'))
            <div class="rounded-xl border border-emerald-200/90 bg-emerald-50/95 text-emerald-950 px-4 py-3.5 text-sm shadow-sm">{{ session('msg') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 text-red-900 px-4 py-3.5 text-sm space-y-1">
                @foreach($errors->all() as $err)
                    <p class="m-0">{{ $err }}</p>
                @endforeach
            </div>
        @endif

        @if($works->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center shadow-sm">
                <ion-icon name="book-outline" class="text-4xl text-slate-300 mx-auto mb-3 block"></ion-icon>
                <p class="text-slate-800 font-semibold m-0 mb-2">Nenhum trabalho apresentado ainda</p>
                <p class="text-slate-600 text-sm m-0 max-w-md mx-auto">Registre a apresentação como realizada na tela de <a href="{{ route('events.presentations.manage', $event->id) }}" class="font-semibold text-emerald-800 underline underline-offset-2">Apresentações</a>; depois os trabalhos passam a aparecer aqui automaticamente.</p>
            </div>
        @else
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left min-w-[44rem]">
                        <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Trabalho</th>
                                <th class="px-4 py-3 whitespace-nowrap">Tipo</th>
                                <th class="px-4 py-3 whitespace-nowrap">Situação</th>
                                <th class="px-4 py-3 min-w-[18rem]">Registro nos anais</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($works as $work)
                                @php
                                    $published = $work->status === \App\Models\Work::STATUS_PUBLISHED_ANNALS;
                                    $validated = $work->final_version_validated_at !== null;
                                @endphp
                                <tr class="align-top hover:bg-slate-50/60">
                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-900 m-0 leading-snug break-words">{{ $work->listTitleCompact(true) }}</p>
                                        <p class="text-xs text-slate-500 m-0 mt-1">{{ $work->submitter->name ?? 'Autor não informado' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">
                                        {{ $workTypeLabels[$work->work_type] ?? $work->work_type }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($published)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 text-indigo-950 px-2.5 py-1 text-[11px] font-semibold ring-1 ring-indigo-200/80">Nos anais</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 text-teal-950 px-2.5 py-1 text-[11px] font-semibold ring-1 ring-teal-200/80">Apresentado</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @unless($validated)
                                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950">
                                                <p class="font-semibold m-0 mb-1">Versão final ainda não validada</p>
                                                <p class="m-0 leading-relaxed opacity-95">Finalize a decisão da coordenação com envio da versão oficial na <a href="{{ route('works.show', $work->id) }}" class="underline font-semibold">ficha do trabalho</a>.</p>
                                            </div>
                                        @else
                                            <form action="{{ route('works.annals.publish', $work->id) }}" method="POST" class="space-y-2">
                                                @csrf
                                                <div>
                                                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">URL (opcional)</label>
                                                    <input type="url" name="annals_url"
                                                           value="{{ old('annals_url', $work->annals_url) }}"
                                                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                                           placeholder="https://...">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Observações</label>
                                                    <textarea name="annals_note" rows="2"
                                                              class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                                              placeholder="Ex.: volume, páginas, DOI">{{ old('annals_note', $work->annals_note) }}</textarea>
                                                </div>
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition-colors shadow-sm">
                                                    <ion-icon name="save-outline" class="text-base"></ion-icon>
                                                    {{ $published ? 'Salvar alterações' : 'Registrar nos anais' }}
                                                </button>
                                            </form>
                                            @if($published && $work->published_in_annals_at)
                                                <div class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                                                    <p class="text-[10px] text-slate-500 m-0">Primeiro registro em {{ $work->published_in_annals_at->format('d/m/Y H:i') }}.</p>
                                                    <form action="{{ route('works.annals.clear', $work->id) }}" method="POST"
                                                          onsubmit="return confirm('Remover apenas o registro de publicação nos anais? O trabalho volta a ficar apenas como «Apresentado», sem apagar o arquivo da versão final.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold transition-colors">
                                                            Remover dos anais
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
