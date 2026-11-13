@extends('layouts.newMain')

@section('title', 'Presença: '.$activity->title)

@section('content')
@php
    $hoursLabel = $activity->workload_hours !== null
        ? rtrim(rtrim((string) $activity->workload_hours, '0'), '.').' h'
        : 'carga horária não informada';
    $typeLabel = \App\Models\Activity::typeLabels()[$activity->type] ?? $activity->type;
    $when = $activity->start_at?->format('d/m/Y H:i');
    if ($activity->end_at) {
        $when .= '–'.$activity->end_at->format('H:i');
    }
    $presentCount = $participantUsers->filter(fn ($user) => ($presences->get($user->id)?->presente))->count();
@endphp
<div class="dash-page min-h-screen">
<div class="max-w-4xl mx-auto px-4 py-8 space-y-5">
    <p class="text-sm m-0">
        <a href="{{ route('events.certificates.index', $event) }}" class="dash-link no-underline">← Certificados</a>
    </p>
    <div>
        <h1 class="dash-title text-2xl font-bold m-0">{{ $activity->title }}</h1>
        <p class="dash-text text-sm m-0 mt-1">{{ $when }} · {{ $typeLabel }} · {{ $hoursLabel }}</p>
    </div>

    @if (session('presence_msg'))
        <p class="m-0 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-950 px-3 py-2 text-sm">{{ session('presence_msg') }}</p>
    @endif

    <form id="activity-presence-form" action="{{ route('events.certificates.activity.presence.update', [$event, $activity]) }}" method="POST" class="dash-card rounded-2xl p-4 sm:p-5 space-y-4">
        @csrf
        @if($participantUsers->isEmpty())
            <p class="text-sm text-slate-600 m-0">Nenhum participante inscrito.</p>
        @else
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <input id="presence-search" type="search" placeholder="Buscar por nome ou e-mail" class="dash-field w-full sm:max-w-sm rounded-lg px-3 py-2 text-sm">
                <p id="presence-count" class="text-sm text-slate-600 m-0" data-total="{{ $participantUsers->count() }}">{{ $presentCount }} de {{ $participantUsers->count() }} presentes</p>
                <div class="flex gap-3 sm:ml-auto text-sm">
                    <button type="button" class="dash-link bg-transparent border-0 p-0 cursor-pointer text-sm font-medium underline" data-presence-mark="1">Marcar exibidos</button>
                    <button type="button" class="dash-link bg-transparent border-0 p-0 cursor-pointer text-sm font-medium underline" data-presence-mark="0">Limpar exibidos</button>
                </div>
            </div>
            <div class="max-h-[min(36rem,65vh)] overflow-y-auto rounded-lg border border-slate-200">
                <p id="presence-empty" class="hidden text-sm text-slate-500 m-0 px-3 py-6 text-center">Nenhum participante encontrado.</p>
                @foreach($participantUsers as $user)
                    @php $presence = $presences->get($user->id); @endphp
                    <label class="presence-row grid grid-cols-[auto_minmax(0,1fr)] sm:grid-cols-[auto_minmax(0,1fr)_minmax(0,16rem)] gap-x-3 items-center px-3 py-2 text-sm border-b border-slate-100 last:border-0 cursor-pointer" data-search="{{ mb_strtolower($user->name.' '.$user->email) }}">
                        <input type="checkbox" name="presente[]" value="{{ $user->id }}" @checked($presence && $presence->presente) class="dash-check w-4 h-4 rounded">
                        <span class="truncate font-medium text-slate-900">{{ $user->name }}</span>
                        <span class="truncate text-slate-500 sm:text-right col-start-2 sm:col-start-auto">{{ $user->email }}</span>
                    </label>
                @endforeach
            </div>
            <button type="submit" class="dash-btn inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold">Salvar presença</button>
        @endif
    </form>
</div>
</div>
<script>
(function () {
    const form = document.getElementById('activity-presence-form');
    if (!form) return;
    const search = document.getElementById('presence-search');
    const count = document.getElementById('presence-count');
    const empty = document.getElementById('presence-empty');
    const rows = [...form.querySelectorAll('.presence-row')];

    const refresh = () => {
        const term = (search?.value || '').trim().toLocaleLowerCase();
        let visible = 0;
        rows.forEach((row) => {
            const show = term === '' || (row.dataset.search || '').includes(term);
            row.hidden = !show;
            if (show) visible++;
        });
        if (empty) empty.hidden = visible !== 0;
        if (count) {
            const marked = form.querySelectorAll('input[name="presente[]"]:checked').length;
            count.textContent = marked + ' de ' + count.dataset.total + ' presentes';
        }
    };

    search?.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    form.querySelectorAll('[data-presence-mark]').forEach((button) => {
        button.addEventListener('click', () => {
            const mark = button.dataset.presenceMark === '1';
            rows.forEach((row) => {
                if (row.hidden) return;
                const input = row.querySelector('input[name="presente[]"]');
                if (input) input.checked = mark;
            });
            refresh();
        });
    });

    form.addEventListener('submit', () => {
        const ids = [...form.querySelectorAll('input[name="presente[]"]:checked')].map((input) => input.value);
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'presentes';
        hidden.value = ids.join(',');
        form.appendChild(hidden);
        form.querySelectorAll('input[name="presente[]"]').forEach((input) => { input.disabled = true; });
    });
})();
</script>
@endsection
