@extends('layouts.newMain')

@section('title', 'Presença — '.$activity->title)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 space-y-8">
    <p class="text-sm text-slate-500 mb-1">
        <a href="{{ route('events.certificates.index', $event) }}" class="text-emerald-700 hover:underline no-underline">← Certificados</a>
        · <a href="{{ route('events.show', $event->id) }}" class="text-emerald-700 hover:underline no-underline">Evento</a>
    </p>
    <h1 class="text-2xl font-bold text-slate-900 m-0">{{ $activity->title }}</h1>
    <p class="text-slate-600 text-sm m-0">{{ $activity->start_at?->format('d/m/Y H:i') }} @if($activity->end_at) – {{ $activity->end_at->format('H:i') }} @endif — {{ \App\Models\Activity::typeLabels()[$activity->type] ?? $activity->type }}</p>

    <p class="text-slate-600 text-xs m-0 mt-2 leading-relaxed">Depois de <strong>Salvar</strong>, confira a mensagem no topo — verde com «OK» indica que foi gravado.</p>

    @if (session('msg'))
        @php
            $isSavedFeedback = session()->has('saved_ok') && session('saved_ok');
            $isPartialSave = session()->has('saved_ok') && session('saved_ok') === false;
            $isGenericFlash = ! session()->has('saved_ok');
        @endphp
        <div
            @class([
                'flex gap-3 items-start rounded-xl border px-4 py-3 text-sm',
                'border-emerald-300 bg-emerald-50 text-emerald-950' => $isSavedFeedback,
                'border-amber-200 bg-amber-50 text-amber-950' => $isPartialSave,
                'border-slate-200 bg-slate-50 text-slate-800' => $isGenericFlash,
            ])
        >
            @if($isSavedFeedback)
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white text-xs font-bold" title="Gravado">OK</span>
            @endif
            <span class="min-w-0 leading-relaxed">{{ session('msg') }}</span>
        </div>
    @endif

    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h2 class="text-lg font-semibold text-slate-900 m-0">Carga horária (certificado)</h2>
        <form action="{{ route('events.certificates.activity.workload', [$event, $activity]) }}" method="POST" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Horas</label>
                <input type="number" step="0.25" min="0" name="workload_hours" value="{{ old('workload_hours', $activity->workload_hours) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-36">
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">Salvar</button>
        </form>
    </section>

    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h2 class="text-lg font-semibold text-slate-900 m-0">Presença dos participantes</h2>
        <form action="{{ route('events.certificates.activity.presence.update', [$event, $activity]) }}" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-2 max-h-96 overflow-y-auto border border-slate-100 rounded-xl p-3">
                @forelse($participantUsers as $u)
                    @php $p = $presences->get($u->id); @endphp
                    <label class="flex items-center gap-3 text-sm text-slate-800 py-1 border-b border-slate-50 last:border-0">
                        <input type="checkbox" name="presente[]" value="{{ $u->id }}" @checked($p && $p->presente) class="rounded border-slate-300">
                        <span>{{ $u->name }}</span>
                    </label>
                @empty
                    <p class="text-sm text-slate-500 m-0">Nenhum participante inscrito.</p>
                @endforelse
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">Salvar presença</button>
        </form>
    </section>
</div>
@endsection
