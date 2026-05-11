@extends('layouts.newMain')

@section('title', 'Apresentações dos trabalhos — '.$event->title)

@section('content')
@php
    use App\Models\Work;
    $totalWorks = $works->count();
    $presentationComplete = fn ($w) => $w->presentation
        && $w->presentation->scheduled_start
        && $w->presentation->scheduled_end
        && filled(trim((string) ($w->presentation->location ?? '')));
    $scheduledCount = $works->filter($presentationComplete)->count();
    $pendingScheduleCount = $totalWorks - $scheduledCount;
    $typeLabelMap = collect($types)->pluck('label', 'value')->all();
@endphp
<div class="max-w-5xl mx-auto px-4 py-10 space-y-8">
    <div class="flex flex-col gap-2">
        <p class="text-sm text-slate-500 mb-0">
            <a href="/dashboard" class="text-emerald-700 hover:underline no-underline font-medium">← Painel</a>
            &nbsp;·&nbsp;
            <a href="{{ route('events.show', $event->id) }}" class="text-emerald-700 hover:underline no-underline">Evento</a>
        </p>
        <h1 class="text-2xl font-bold text-slate-900 m-0">Cronograma de apresentações</h1>
        <p class="text-slate-600 text-sm mt-2 m-0 max-w-3xl">
            Esta página lista somente <strong class="text-slate-800">trabalhos já aceitos na decisão final</strong>, com versão válida definida — incluindo os aprovados após correção. Cada bloco abaixo é um trabalho distinto. Para salvar, é <strong class="text-slate-800">obrigatório</strong> informar <strong class="text-slate-800">início e fim</strong> da apresentação (dentro do período do evento), o <strong class="text-slate-800">tipo</strong> e o <strong class="text-slate-800">local ou link</strong>; apenas o nome da sessão permanece opcional.
            <span class="block mt-2 text-slate-600">A <strong class="text-slate-800">presença na apresentação</strong> (apresentado/ausente), carga horária e título no certificado ficam na página <a href="{{ route('events.certificates.index', $event) }}" class="font-semibold text-emerald-800 underline underline-offset-2">Certificados e presença</a> do evento — não nesta tela.</span>
        </p>
        @if ($totalWorks > 0)
            <div class="flex flex-wrap items-center gap-2 mt-4">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 text-emerald-900 px-3 py-1 text-xs font-semibold">
                    {{ $totalWorks }} {{ $totalWorks === 1 ? 'trabalho aceito' : 'trabalhos aceitos' }}
                </span>
                @if ($scheduledCount > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-xs font-medium">
                        {{ $scheduledCount }} com horário e local completos
                    </span>
                @endif
                @if ($pendingScheduleCount > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-900 px-3 py-1 text-xs font-medium border border-amber-200/80">
                        {{ $pendingScheduleCount }} {{ $pendingScheduleCount === 1 ? 'trabalho com agendamento incompleto' : 'trabalhos com agendamento incompleto' }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    @if (session('msg'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 px-4 py-3 text-sm">{{ session('msg') }}</div>
    @endif

    @if($works->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-600 text-sm">
            Nenhum trabalho aceito com versão final definida neste momento. Quando houver decisão final válida para os trabalhos, eles aparecerão aqui.
        </div>
    @else
        <aside class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5 text-sm text-slate-700 space-y-3">
            <p class="m-0 font-semibold text-slate-900">Sobre o campo &ldquo;Nome da sessão&rdquo;</p>
            <p class="m-0 leading-relaxed">
                É um <strong>rótulo em texto livre</strong> (por exemplo, &ldquo;Comunicações orais — manhã&rdquo; ou &ldquo;Eixo temático B&rdquo;) usado para identificar o bloco da programação em que o trabalho se encaixa. Esse nome pode aparecer para os autores e em resumos do evento.
            </p>
            <p class="m-0 leading-relaxed">
                Ele é <strong>opcional</strong> porque nem todo evento organiza a grade em blocos com nome próprio; início, fim, tipo e local já definem o slot. Se você deixar em branco, o sistema não exibirá esse rótulo extra.
            </p>
        </aside>

        <div class="space-y-8">
            @foreach($works as $work)
                @php
                    $__rowShowOldInput = session()->has('errors') && (string) old('_presentation_work_row', '') === (string) $work->id;
                    $__typeSelected = $__rowShowOldInput
                        ? (string) old('presentation_type')
                        : (string) ($work->presentation->presentation_type ?? '');
                    $__sessionNameVal = $__rowShowOldInput
                        ? old('session_name', '')
                        : ($work->presentation->session_name ?? '');
                    $__locationVal = $__rowShowOldInput
                        ? old('location', '')
                        : ($work->presentation->location ?? '');
                    $__startVal = $__rowShowOldInput
                        ? old('scheduled_start')
                        : (isset($work->presentation?->scheduled_start)
                            ? \Carbon\Carbon::parse($work->presentation->scheduled_start)->format('Y-m-d\TH:i')
                            : '');
                    $__endVal = $__rowShowOldInput
                        ? old('scheduled_end')
                        : (isset($work->presentation?->scheduled_end)
                            ? \Carbon\Carbon::parse($work->presentation->scheduled_end)->format('Y-m-d\TH:i')
                            : '');
                    $__hasWindow = $work->presentation && $work->presentation->scheduled_start && $work->presentation->scheduled_end;
                    $__hasLocation = $work->presentation && filled(trim((string) ($work->presentation->location ?? '')));
                    $__scheduleComplete = $__hasWindow && $__hasLocation;
                    $__currentTypeLabel = $__typeSelected !== '' ? ($typeLabelMap[$__typeSelected] ?? $__typeSelected) : '—';
                @endphp
                <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm scroll-mt-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500" aria-hidden="true"></div>
                    <div class="pl-4 sm:pl-5">
                        <header class="border-b border-slate-100 px-4 sm:px-6 py-5 sm:py-6 space-y-3">
                            <div class="flex flex-wrap items-start justify-between gap-3 sm:gap-4">
                                <div class="flex items-start gap-3 min-w-0">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white text-sm font-bold tabular-nums shadow-sm" title="Ordem na lista">
                                        {{ $loop->iteration }}
                                    </span>
                                    <div class="min-w-0 flex-1 flex flex-col gap-3">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5">
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-900 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide">
                                                Aceito
                                            </span>
                                            <span class="text-xs text-slate-500 font-medium">
                                                {{ Work::workTypeLabels()[$work->work_type] ?? $work->work_type }}
                                            </span>
                                        </div>
                                        <h2 class="text-xl font-bold text-slate-900 m-0 break-words leading-tight tracking-tight">{{ $work->listTitleCompact() }}</h2>
                                        @php
                                            $authorLine = $work->authors->pluck('author_name')->filter()->take(5)->join(', ');
                                        @endphp
                                        @if($authorLine !== '')
                                            <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/50 px-3.5 py-3 shadow-sm">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-800/90 m-0 mb-1">Autores</p>
                                                <p class="text-[15px] font-semibold text-slate-900 m-0 leading-snug">{{ $authorLine }}{{ $work->authors->count() > 5 ? ', …' : '' }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 shrink-0">
                                    @if ($__scheduleComplete)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-900 px-2.5 py-1 text-[11px] font-semibold border border-emerald-200/90">
                                            Agendamento completo
                                        </span>
                                    @elseif ($work->presentation)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-900 px-2.5 py-1 text-[11px] font-medium border border-amber-200/90">
                                            Dados incompletos — horário e local obrigatórios
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2.5 py-1 text-[11px] font-medium border border-slate-200">
                                            Sem agendamento salvo
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($work->presentation)
                                <div class="pt-5 mt-2 border-t border-slate-100">
                                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm m-0 p-0 bg-slate-50/90 rounded-xl px-4 py-4 border border-slate-100">
                                    <div class="flex flex-col gap-0.5">
                                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 m-0">Tipo atual</dt>
                                        <dd class="m-0 text-slate-900 font-medium">{{ $__currentTypeLabel }}</dd>
                                    </div>
                                    <div class="flex flex-col gap-0.5 sm:text-right sm:items-end">
                                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 m-0">Sessão (rótulo)</dt>
                                        <dd class="m-0 text-slate-900 font-medium">{{ $work->presentation->session_name ? $work->presentation->session_name : '—' }}</dd>
                                    </div>
                                    @if($__hasWindow)
                                        <div class="sm:col-span-2 flex flex-col gap-0.5 pt-1 border-t border-slate-200/80 mt-1">
                                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 m-0">Janela agendada</dt>
                                            <dd class="m-0 text-slate-900 tabular-nums">
                                                {{ \Carbon\Carbon::parse($work->presentation->scheduled_start)->format('d/m/Y H:i') }}
                                                <span class="text-slate-400 font-normal mx-1">→</span>
                                                {{ \Carbon\Carbon::parse($work->presentation->scheduled_end)->format('d/m/Y H:i') }}
                                            </dd>
                                        </div>
                                    @endif
                                    <div class="sm:col-span-2 flex flex-col gap-0.5">
                                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 m-0">Local ou link</dt>
                                        <dd class="m-0 text-slate-800 leading-relaxed [overflow-wrap:anywhere]">{{ $__hasLocation ? $work->presentation->location : '— (obrigatório ao salvar)' }}</dd>
                                    </div>
                                </dl>
                                </div>
                            @else
                                <div class="pt-5 mt-2 border-t border-slate-100">
                                    <p class="text-sm text-slate-600 m-0 leading-relaxed rounded-xl border border-dashed border-slate-200 bg-slate-50/50 px-4 py-3.5">
                                        Ainda não há dados de apresentação salvos. Preencha o formulário abaixo e clique em <strong class="text-slate-800">Salvar</strong>.
                                    </p>
                                </div>
                            @endif
                        </header>

                        <div class="px-4 sm:px-6 py-5 sm:py-6 bg-white">
                            <p class="text-xs font-semibold text-slate-700 uppercase tracking-wide m-0 mb-4">Editar agendamento</p>
                            <form action="{{ route('works.presentation.upsert', $work) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @csrf
                                <input type="hidden" name="_presentation_work_row" value="{{ $work->id }}">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Tipo de apresentação</label>
                                    <select name="presentation_type" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm bg-white">
                                        @foreach($types as $t)
                                            <option value="{{ $t['value'] }}" @selected($__typeSelected === $t['value'])>{{ $t['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('presentation_type')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Nome da sessão <span class="font-normal text-slate-500">(opcional)</span></label>
                                    <input type="text" name="session_name" value="{{ $__sessionNameVal }}"
                                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                           maxlength="255" placeholder="Ex.: Comunicações orais — tarde">
                                    <p class="text-[11px] text-slate-500 mt-1.5 m-0 leading-relaxed">Rótulo para programação; pode ficar vazio se o evento não usar sessões nomeadas.</p>
                                    @error('session_name')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Local ou link <span class="text-red-600">*</span></label>
                                    <input type="text" name="location" value="{{ $__locationVal }}"
                                           required
                                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                           maxlength="255" placeholder="Sala / auditório ou URL da sala virtual">
                                    @error('location')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Início <span class="text-red-600">*</span></label>
                                    <input type="datetime-local" name="scheduled_start"
                                           value="{{ $__startVal }}"
                                           required
                                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                    @error('scheduled_start')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Fim <span class="text-red-600">*</span></label>
                                    <input type="datetime-local" name="scheduled_end"
                                           value="{{ $__endVal }}"
                                           required
                                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                    @error('scheduled_end')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <p class="md:col-span-2 text-[11px] text-slate-500 m-0 -mt-1">Obrigatórios. O período deve estar dentro das datas oficiais do evento; o fim não pode ser anterior ao início.</p>
                                <div class="md:col-span-2 flex flex-wrap gap-3 pt-1">
                                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 border-0">
                                        Salvar este trabalho
                                    </button>
                                </div>
                            </form>

                            @if($work->presentation && in_array($work->status, [Work::STATUS_FINAL_VALIDATED, Work::STATUS_SCHEDULED], true))
                                <form action="{{ route('works.presentation.destroy', $work) }}" method="POST" class="border-t border-slate-100 pt-5 mt-6"
                                      onsubmit="return confirm('Remover esta configuração de apresentação?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm font-medium hover:bg-red-100">
                                        Limpar dados de apresentação
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
