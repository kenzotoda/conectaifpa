@extends('layouts.newMain')

@php
    use App\Models\WorkPresentation;
@endphp

@section('title', 'Certificados — '.$event->title)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-10 pb-14 space-y-8 sm:space-y-10">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-6 rounded-2xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50/35 to-white p-6 sm:p-7 shadow-md shadow-slate-200/40 ring-1 ring-slate-100/80">
        <div>
            <p class="text-sm text-slate-500 mb-2 m-0">
                <a href="{{ route('events.show', $event->id) }}" class="inline-flex text-emerald-800 font-semibold hover:underline no-underline rounded-md px-1 -mx-1 hover:bg-emerald-50 transition-colors">← Voltar ao evento</a>
            </p>
            <h1 class="text-2xl sm:text-[1.65rem] font-bold text-slate-900 tracking-tight m-0">Certificados e presença</h1>
            <p class="text-slate-600 mt-3 m-0 max-w-2xl text-sm leading-relaxed">
                Três marcações separadas — <strong>participação geral</strong>, <strong>apresentações</strong> (eventos com submissão) e <strong>atividades</strong> — cada uma com modelo de PDF próprio e as <strong>mesmas assinaturas</strong> configuradas aqui para o evento.
            </p>
            <p class="text-xs text-slate-600 m-0 mt-4 max-w-2xl leading-relaxed">Ao usar qualquer botão <strong>Salvar</strong> abaixo, a página recarrega e mostra <strong>no topo</strong> se as alterações foram gravadas (mensagem em verde).</p>
        </div>
        <div class="flex flex-wrap gap-2.5 shrink-0 sm:justify-end sm:items-start">
            <a href="{{ route('signatures.index', ['event' => $event->id]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200/90 bg-white text-slate-800 text-sm font-semibold no-underline shadow-sm hover:bg-slate-50 hover:border-slate-300 transition-colors">
                Cadastro de assinaturas
            </a>
            <a href="{{ route('events.certificates.issued', $event) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-br from-slate-800 to-slate-950 text-white text-sm font-semibold no-underline shadow-md shadow-slate-900/25 hover:from-slate-700 hover:to-slate-900 transition-colors">
                Emitidos (lista)
            </a>
        </div>
    </div>

    <nav class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-md shadow-slate-200/50 ring-1 ring-slate-100" aria-label="Seções desta página">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 m-0 mb-3">Índice</p>
        <ul class="flex flex-wrap gap-x-5 gap-y-2.5 text-sm m-0 list-none p-0 leading-snug">
            <li><a href="#cert-dados-pdf" class="text-slate-700 font-medium no-underline rounded-lg px-2 py-1 -mx-2 hover:bg-emerald-50 hover:text-emerald-900 transition-colors">1. Organização e instituição nos PDFs</a></li>
            <li><a href="#cert-assinaturas" class="text-slate-700 font-medium no-underline rounded-lg px-2 py-1 -mx-2 hover:bg-slate-100 hover:text-slate-900 transition-colors">2. Assinaturas nos PDFs</a></li>
            <li><a href="#cert-presenca-geral" class="text-slate-700 font-medium no-underline rounded-lg px-2 py-1 -mx-2 hover:bg-sky-50 hover:text-sky-900 transition-colors">3. Presença geral</a></li>
            @if($event->acceptsSubmissions())
                <li><a href="#cert-apresentacao" class="text-slate-700 font-medium no-underline rounded-lg px-2 py-1 -mx-2 hover:bg-indigo-50 hover:text-indigo-900 transition-colors">4. Apresentação de trabalhos</a></li>
            @endif
            <li><a href="#cert-atividades" class="text-slate-700 font-medium no-underline rounded-lg px-2 py-1 -mx-2 hover:bg-amber-50 hover:text-amber-900 transition-colors">@if($event->acceptsSubmissions())5.@else 4. @endif Atividades</a></li>
        </ul>
    </nav>

    @if($globalCertificatesBlockMessage)
        <div class="rounded-xl border border-amber-200/95 bg-amber-50/95 text-amber-950 px-4 py-3.5 text-sm shadow-sm ring-1 ring-amber-100/90" role="status">
            <p class="font-semibold m-0 mb-1">Para emitir qualquer certificado neste evento</p>
            <p class="m-0 leading-relaxed">{{ $globalCertificatesBlockMessage }}</p>
        </div>
    @endif

    @if (session('msg'))
        @php
            $isSavedFeedback = session()->has('saved_ok') && session('saved_ok');
            $isPartialSave = session()->has('saved_ok') && session('saved_ok') === false;
            $isGenericFlash = ! session()->has('saved_ok');
        @endphp
        <div
            @class([
                'flex gap-3 items-start rounded-xl border px-4 py-3.5 text-sm shadow-sm ring-1',
                'border-emerald-300 bg-emerald-50 text-emerald-950 ring-emerald-100' => $isSavedFeedback,
                'border-amber-200 bg-amber-50 text-amber-950 ring-amber-100' => $isPartialSave,
                'border-slate-200 bg-slate-50 text-slate-800 ring-slate-100' => $isGenericFlash,
            ])
            @if($isSavedFeedback) role="status" aria-live="polite" @endif
        >
            @if($isSavedFeedback)
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white text-xs font-bold" title="Gravado">OK</span>
            @endif
            <span class="min-w-0 leading-relaxed">{{ session('msg') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200/90 bg-red-50/95 text-red-900 px-4 py-3.5 text-sm space-y-1 shadow-sm shadow-red-900/5 ring-1 ring-red-100/80">
            @foreach ($errors->all() as $err)
                <p class="m-0">{{ $err }}</p>
            @endforeach
        </div>
    @endif

    {{-- 1 --}}
    <section id="cert-dados-pdf" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_10px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-slate-100/90">
        <div class="h-1 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500" aria-hidden="true"></div>
        <div class="p-6 sm:p-7 space-y-5">
            <div class="flex flex-wrap items-start gap-4">
                <span class="inline-flex h-11 min-w-[2.75rem] shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-600 to-teal-600 text-sm font-bold text-white shadow-md shadow-emerald-600/30 ring-[3px] ring-white">1</span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight m-0">Dados que entram nos PDFs</h2>
                    <p class="text-sm text-slate-600 m-0 mt-2 leading-relaxed">
                        Texto de <strong>organização</strong> e <strong>instituição</strong> igual em todos os certificados deste evento (participação, apresentação e atividades).
                    </p>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200/70 bg-gradient-to-b from-slate-50/80 to-white p-4 sm:p-5 shadow-inner shadow-slate-200/40">
                <form action="{{ route('events.certificates.meta', $event) }}" method="POST" class="grid sm:grid-cols-2 gap-5">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Organização (texto)</label>
                        <input type="text" name="certificate_organizer" value="{{ old('certificate_organizer', $event->certificate_organizer) }}" placeholder="{{ $event->coordinator_name }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-[3px] focus:ring-emerald-500/20">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Instituição</label>
                        <input type="text" name="certificate_institution" value="{{ old('certificate_institution', $event->certificate_institution) }}" placeholder="{{ $event->campus }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-[3px] focus:ring-emerald-500/20">
                    </div>
                    <div class="sm:col-span-2 flex flex-wrap gap-3 pt-1">
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-600 text-white text-sm font-semibold shadow-md shadow-emerald-600/25 hover:from-emerald-500 hover:to-teal-500 transition-colors">Salvar organização e instituição</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- 2 --}}
    <section id="cert-assinaturas" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_10px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-slate-100/90">
        <div class="h-1 bg-gradient-to-r from-slate-500 via-slate-600 to-slate-800" aria-hidden="true"></div>
        <div class="p-6 sm:p-7 space-y-5">
            <div class="flex flex-wrap items-start gap-4">
                <span class="inline-flex h-11 min-w-[2.75rem] shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-600 to-slate-800 text-sm font-bold text-white shadow-md shadow-slate-600/35 ring-[3px] ring-white">2</span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight m-0">Assinaturas neste evento</h2>
                    <p class="text-sm text-slate-600 m-0 mt-2 leading-relaxed">Imagens cadastradas em <a href="{{ route('signatures.index', ['event' => $event->id]) }}" class="text-emerald-700 font-semibold no-underline hover:underline">Assinaturas</a>. Marque quais entram em <strong>todos</strong> os PDFs emitidos por este evento (participação, apresentação e atividades).</p>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200/70 bg-gradient-to-b from-slate-50/80 to-white p-4 sm:p-5 shadow-inner shadow-slate-200/40 space-y-4">
                <form action="{{ route('events.certificates.signatures', $event) }}" method="POST" class="space-y-4">
                    @csrf
                    <p class="text-xs font-medium text-slate-500 m-0">Rolagem automática quando houver várias assinaturas cadastradas.</p>
                    <div class="grid sm:grid-cols-2 gap-2 max-h-[min(22rem,50svh)] overflow-y-auto overscroll-y-contain rounded-xl border border-slate-100 bg-white/70 p-3 shadow-inner">
                        @forelse($signatures as $sig)
                            <label class="flex items-start gap-3 text-sm text-slate-700 cursor-pointer rounded-lg px-2 py-2 hover:bg-white transition-colors border border-transparent hover:border-slate-100">
                                <input type="checkbox" name="assinatura_ids[]" value="{{ $sig->id }}" @checked(in_array($sig->id, $attachedSignatureIds, true)) class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                <span><span class="font-medium text-slate-900">{{ $sig->nome }}</span><br><span class="text-xs text-slate-500">{{ $sig->cargo }}</span></span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500 m-0 col-span-full py-4 text-center">Nenhuma assinatura cadastrada.</p>
                        @endforelse
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 text-sm font-semibold shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-colors">Salvar assinaturas do evento</button>
                </form>
            </div>
        </div>
    </section>

    {{-- 3 --}}
    <section id="cert-presenca-geral" class="scroll-mt-6 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_10px_40px_-18px_rgba(15,23,42,0.18)] ring-1 ring-slate-100/90">
        <div class="h-1 bg-gradient-to-r from-sky-500 via-blue-500 to-indigo-500" aria-hidden="true"></div>
        <div class="p-6 sm:p-7 space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div class="flex flex-wrap items-start gap-4 min-w-0">
                    <span class="inline-flex h-11 min-w-[2.75rem] shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 text-sm font-bold text-white shadow-md shadow-sky-500/35 ring-[3px] ring-white">3</span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900 tracking-tight m-0">Participação geral no evento</h2>
                        <p class="text-sm text-slate-600 m-0 mt-2 leading-relaxed">Presença <strong>independente</strong> de trabalho ou atividade. Apenas perfil <strong>participante</strong> inscrito. A <strong>carga horária total</strong> do certificado de participação deve ser definida nesta mesma seção (campo logo abixo). Depois das presenças, é possível emitir os certificados de participação em lote.</p>
                    </div>
                </div>
                <p class="m-0 shrink-0 rounded-full bg-sky-950/5 px-3.5 py-2 text-xs font-semibold uppercase tracking-wide text-sky-900 ring-1 ring-sky-200/70">Emitidos<br><span class="text-xl font-black normal-case tracking-normal text-sky-950">{{ $certCounts['participation'] }}</span></p>
            </div>
            <div class="rounded-xl border border-slate-200/70 bg-gradient-to-b from-white to-slate-50/40 p-4 sm:p-5 shadow-inner shadow-slate-200/30">
                <form action="{{ route('events.certificates.meta', $event) }}" method="POST" class="space-y-2">
                    @csrf
                    <div class="flex flex-wrap items-end gap-x-4 gap-y-2">
                        <div class="min-w-[min(100%,14rem)] w-full max-w-xs sm:w-auto sm:flex-1">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Carga horária total</label>
                            <input type="number" step="0.5" min="0" name="certificate_total_hours" value="{{ old('certificate_total_hours', $event->certificate_total_hours) }}" placeholder="Ex.: 20" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-normal shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-[3px] focus:ring-sky-500/20">
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-gradient-to-br from-sky-600 to-blue-600 text-white text-sm font-semibold leading-normal shadow-md shadow-sky-600/25 hover:from-sky-500 hover:to-blue-500 transition-colors shrink-0 w-full sm:w-auto border border-transparent">Salvar carga horária</button>
                    </div>
                    <p class="text-[11px] text-slate-500 m-0 leading-snug">Usada apenas no PDF de participação geral.</p>
                </form>
            </div>
            <div class="rounded-xl border border-slate-200/70 bg-white p-4 sm:p-5 shadow-sm">
                <form action="{{ route('events.certificates.presence', $event) }}" method="POST" class="space-y-4">
                    @csrf
                    <p class="text-xs font-medium text-slate-600 m-0">Lista de participantes (rolável em eventos com muitas inscrições)</p>
                    <div class="max-h-[min(32rem,58svh)] overflow-y-auto overscroll-y-contain rounded-xl border border-slate-100 bg-gradient-to-b from-slate-50/50 to-white divide-y divide-slate-100/80 shadow-inner ring-1 ring-slate-100/60">
                        @forelse($participantUsers as $u)
                            @php $p = $presences->get($u->id); @endphp
                            <label class="flex items-center gap-3 px-3 py-2.5 text-sm text-slate-800 cursor-pointer hover:bg-emerald-50/40 transition-colors">
                                <input type="checkbox" name="presente[]" value="{{ $u->id }}" @checked($p && $p->presente) class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 focus:ring-offset-0">
                                <span>{{ $u->name }} <span class="text-slate-500 text-xs font-normal">({{ $u->email }})</span></span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500 m-0 px-3 py-6 text-center">Nenhum participante inscrito.</p>
                        @endforelse
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold shadow-md shadow-slate-900/20 hover:bg-slate-800 transition-colors">Salvar presença geral</button>
                </form>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/50 px-4 py-4 sm:px-5">
                @unless($participationBatchBlock)
                    <form action="{{ route('events.certificates.generate.participation', $event) }}" method="POST" class="inline-block cert-batch-swal-confirm" data-swal-title="Gerar em lote: participação" data-swal-text="Confirma a geração em lote dos certificados de participação geral? Entram apenas participantes marcados como presentes que ainda não possuem certificado registrado.">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-600 text-white text-sm font-semibold shadow-md shadow-emerald-600/25 hover:from-emerald-500 hover:to-teal-500 transition-colors">Gerar certificados de participação</button>
                    </form>
                @else
                    <span class="text-sm text-amber-950 bg-amber-50 border border-amber-200/90 rounded-xl px-4 py-3 inline-block max-w-xl shadow-sm">{{ $participationBatchBlock }}</span>
                @endunless
            </div>
        </div>
    </section>

    @if($event->acceptsSubmissions())
        {{-- 4 --}}
        <section id="cert-apresentacao" class="scroll-mt-6 overflow-hidden rounded-2xl border border-indigo-200/70 bg-white shadow-[0_10px_40px_-16px_rgba(67,56,202,0.28)] ring-1 ring-indigo-100/60">
            <div class="h-1 bg-gradient-to-r from-indigo-500 via-violet-500 to-purple-500" aria-hidden="true"></div>
            <div class="p-6 sm:p-7 space-y-5">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="flex flex-wrap items-start gap-4 min-w-0">
                        <span class="inline-flex h-11 min-w-[2.75rem] shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-sm font-bold text-white shadow-md shadow-indigo-600/35 ring-[3px] ring-white">4</span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-slate-900 tracking-tight m-0">Apresentação de trabalho (submissão)</h2>
                            <ul class="text-sm text-slate-600 m-0 mt-3 pl-4 list-[square] space-y-1.5 marker:text-indigo-400 leading-relaxed">
                                <li><strong>Título do trabalho</strong> (uma linha) é <strong>obrigatório</strong> quando a situação for <strong>Apresentado</strong>, no formato habitual de trabalho ou artigo científico.</li>
                                <li><strong>Presença:</strong> <strong>Apresentado</strong> ou <strong>Ausente</strong> — apenas apresentações confirmadas entram na emissão em lote.</li>
                                <li><strong>Carga horária</strong> no PDF (padrão 2 h em branco).</li>
                                <li><strong>Ausente</strong> apenas registra motivo, sem certificado.</li>
                            </ul>
                            <p class="text-sm m-0 mt-3">
                                <a href="{{ route('events.presentations.manage', $event) }}" class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-indigo-800 font-semibold no-underline ring-1 ring-indigo-100 hover:bg-indigo-100 transition-colors">Cronograma de apresentações</a>
                                <span class="text-slate-500 text-sm ml-2">horário e local continuam sendo ajustados nesta página.</span>
                            </p>
                        </div>
                    </div>
                    <p class="m-0 shrink-0 rounded-full bg-indigo-950/[0.06] px-3.5 py-2 text-xs font-semibold uppercase tracking-wide text-indigo-950 ring-1 ring-indigo-100">Emitidos<br><span class="text-xl font-black normal-case tracking-normal text-indigo-950">{{ $certCounts['presentation'] }}</span></p>
                </div>

                @if($presentationWorks->isEmpty())
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 px-4 py-8 text-center text-sm text-slate-700">
                        Nenhum trabalho agendado neste momento (com apresentação salva e status compatível). Quando houver, os cartões aparecem aqui.
                    </div>
                @else
                    <form action="{{ route('events.certificates.presentation-rows', $event) }}" method="POST" class="space-y-4">
                        @csrf
                        <p class="text-xs font-medium text-slate-600 m-0 rounded-lg bg-indigo-50/50 border border-indigo-100/80 px-3 py-2.5"><strong>Título do trabalho:</strong> uma linha apenas; obrigatório quando a situação for <strong>Apresentado</strong>.</p>
                        <div class="space-y-4 max-h-[min(40rem,62svh)] overflow-y-auto overscroll-y-contain rounded-2xl border border-slate-200/60 bg-gradient-to-b from-slate-50/70 to-white p-3 sm:p-4 shadow-inner">
                            @foreach($presentationWorks as $w)
                                @php $idx = $loop->index; @endphp
                                <input type="hidden" name="rows[{{ $idx }}][work_id]" value="{{ $w->id }}">
                                @php
                                    $__storedAtt = old('rows.'.$idx.'.attendance_status', $w->presentation->attendance_status ?? WorkPresentation::ATTENDANCE_AUSENTE);
                                    $__att = $__storedAtt === WorkPresentation::ATTENDANCE_PENDENTE ? WorkPresentation::ATTENDANCE_AUSENTE : $__storedAtt;
                                @endphp
                                <article class="group overflow-hidden rounded-2xl border border-slate-200/85 bg-white shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)] ring-1 ring-slate-100 transition-shadow duration-200 hover:shadow-[0_14px_40px_-14px_rgba(67,56,202,0.14)] hover:ring-indigo-100 border-l-[3px] border-l-indigo-400">
                                    <div class="px-4 py-3.5 bg-gradient-to-r from-indigo-50/90 via-white to-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 gap-x-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-600/90 m-0">Submissão / referência</p>
                                            <p class="font-semibold text-slate-900 m-0 mt-1 [overflow-wrap:anywhere] leading-snug">{{ $w->listTitleCompact() }}</p>
                                            <p class="text-sm text-slate-600 m-0 mt-1.5">
                                                <span class="text-slate-500 font-medium">Autor principal</span> — {{ $w->submitter?->name ?? '—' }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 sm:pt-0">
                                            <a href="{{ route('works.show', $w) }}" class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl border border-emerald-200/90 bg-white text-emerald-800 text-xs font-semibold no-underline shadow-sm hover:bg-emerald-50 transition-colors">Ver trabalho</a>
                                        </div>
                                    </div>
                                    <div class="p-4 sm:p-5 space-y-4 bg-white">
                                        <div class="space-y-1.5">
                                            @php $__titleNeedsStar = $__att === WorkPresentation::ATTENDANCE_APRESENTADO; @endphp
                                            <label for="cert-title-{{ $w->id }}" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Título do trabalho
                                                @if ($__titleNeedsStar)
                                                    <abbr title="obrigatório quando Apresentado" class="text-red-600 no-underline font-bold cursor-help">*</abbr>
                                                @endif
                                            </label>
                                            <p class="text-[11px] text-slate-500 m-0 leading-snug">Mesmo texto institucional do trabalho, sem quebras de linha.</p>
                                            @php
                                                $__titleOld = old('rows.'.$idx.'.certificate_presentation_title', $w->certificate_presentation_title);
                                                $__titleOld = is_string($__titleOld) && $__titleOld !== ''
                                                    ? trim(preg_replace('/\s+/u', ' ', preg_replace('/[\r\n\t]+/', ' ', $__titleOld)))
                                                    : '';
                                            @endphp
                                            <input id="cert-title-{{ $w->id }}" type="text" name="rows[{{ $idx }}][certificate_presentation_title]" value="{{ $__titleOld }}" maxlength="1200" autocomplete="off" spellcheck="true" placeholder="Ex.: Educação intercultural e tecnologias educacionais nos cursos da área pedagógica" class="certificate-title-upper-input block w-full min-h-[2.75rem] rounded-xl border border-slate-200 bg-slate-50/30 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition focus:bg-white focus:border-indigo-500 focus:outline-none focus:ring-[3px] focus:ring-indigo-500/20">
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-start">
                                            <div class="space-y-1.5 sm:col-span-4 md:col-span-3">
                                                <label for="cert-ch-{{ $w->id }}" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Carga horária (h)</label>
                                                <input id="cert-ch-{{ $w->id }}" type="number" step="0.5" min="0" name="rows[{{ $idx }}][certificate_presentation_hours]" value="{{ old('rows.'.$idx.'.certificate_presentation_hours', $w->certificate_presentation_hours) }}" placeholder="ex.: 2" inputmode="decimal" class="block w-full min-h-[2.75rem] rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm placeholder:text-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-[3px] focus:ring-indigo-500/20">
                                                <p class="text-[11px] text-slate-500 m-0">Padrão 2 h se vazio.</p>
                                            </div>
                                            <div class="space-y-1.5 min-w-0 sm:col-span-8 md:col-span-9">
                                                <label for="cert-pres-{{ $w->id }}" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Presença na apresentação</label>
                                                <select id="cert-pres-{{ $w->id }}" name="rows[{{ $idx }}][attendance_status]" class="block w-full min-h-[2.75rem] rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-[3px] focus:ring-indigo-500/20">
                                                    <option value="{{ WorkPresentation::ATTENDANCE_APRESENTADO }}" @selected($__att === WorkPresentation::ATTENDANCE_APRESENTADO)>Apresentado</option>
                                                    <option value="{{ WorkPresentation::ATTENDANCE_AUSENTE }}" @selected($__att === WorkPresentation::ATTENDANCE_AUSENTE)>Ausente</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold shadow-md shadow-slate-900/25 hover:bg-slate-800 transition-colors">Salvar presença e CH dos trabalhos</button>
                    </form>
                @endif

                <div class="rounded-xl border border-slate-100 bg-slate-50/60 px-4 py-4 sm:px-5 space-y-2">
                    @if(blank($presentationsBatchBlock))
                        <form action="{{ route('events.certificates.generate.presentations', $event) }}" method="POST" class="inline-block cert-batch-swal-confirm" data-swal-title="Gerar em lote: apresentação de trabalhos" data-swal-text="Confirma a geração em lote dos certificados de apresentação? Inclui trabalhos marcados como Apresentado e elegíveis: emite certificados novos ou atualiza o PDF quando os dados salvos tiverem mudado.">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white text-sm font-semibold shadow-md shadow-indigo-600/25 hover:from-indigo-500 hover:to-violet-500 transition-colors">Gerar certificados de apresentação</button>
                        </form>
                        <p class="text-xs text-slate-600 m-0 leading-relaxed max-w-xl">Somente após <strong>finalizar o evento</strong>, com presença e dados completos para cada trabalho <strong>Apresentado</strong>.</p>
                    @else
                        <span class="text-sm text-amber-950 bg-amber-50 border border-amber-200/90 rounded-xl px-4 py-3 inline-block max-w-xl shadow-sm">{{ $presentationsBatchBlock }}</span>
                    @endif
                </div>
            </div>
        </section>
    @else
        <section class="overflow-hidden rounded-2xl border border-dashed border-slate-300/90 bg-gradient-to-br from-slate-50 to-white p-6 sm:p-7 shadow-inner">
            <h2 class="text-base font-bold text-slate-900 tracking-tight m-0">Apresentação de trabalhos</h2>
            <p class="text-sm text-slate-600 m-0 mt-2 leading-relaxed">Este evento <strong>não aceita submissão de trabalhos</strong>. Os certificados de apresentação e a marcação por trabalho não se aplicam. Use <strong>participação geral</strong> e <strong>atividades</strong>.</p>
        </section>
    @endif

    {{-- Atividades --}}
    <section id="cert-atividades" class="scroll-mt-6 overflow-hidden rounded-2xl border border-amber-200/80 bg-white shadow-[0_10px_40px_-18px_rgba(180,83,9,0.22)] ring-1 ring-amber-50">
        <div class="h-1 bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-500" aria-hidden="true"></div>
        <div class="p-6 sm:p-7 space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div class="flex flex-wrap items-start gap-4 min-w-0">
                    <span class="inline-flex h-11 min-w-[2.75rem] shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-md shadow-amber-500/35 ring-[3px] ring-white">{{ $event->acceptsSubmissions() ? '5' : '4' }}</span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900 tracking-tight m-0">Atividades do evento</h2>
                        <p class="text-sm text-slate-600 m-0 mt-2 leading-relaxed"><strong>Carga horária</strong> por atividade, <strong>presença completa</strong> de cada participante (salvar na lista de cada linha). A emissão de certificados de atividade é <strong>somente em lote</strong>, após <strong>finalizar o evento</strong>.</p>
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 m-0 mt-2">Tabela com cabeçalho fixo e rolagem</p>
                    </div>
                </div>
                <p class="m-0 shrink-0 rounded-full bg-amber-950/[0.07] px-3.5 py-2 text-xs font-semibold uppercase tracking-wide text-amber-950 ring-1 ring-amber-100">Emitidos<br><span class="text-xl font-black normal-case tracking-normal text-amber-950">{{ $certCounts['activity'] }}</span></p>
            </div>
            @if(blank($activitiesBatchBlock))
                <div class="rounded-xl border border-amber-100/90 bg-gradient-to-br from-amber-50/50 to-white px-4 py-4 sm:px-5">
                    <form action="{{ route('events.certificates.generate.activities-all', $event) }}" method="POST" class="inline-block cert-batch-swal-confirm" data-swal-title="Gerar em lote: certificados de atividades" data-swal-text="Confirma a geração em lote dos certificados de todas as atividades? Em uma única operação, emite certificados para participantes presentes que ainda não possuem certificado em cada atividade apta.">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-gradient-to-br from-amber-600 to-orange-600 text-white text-sm font-semibold shadow-md shadow-amber-600/25 hover:from-amber-500 hover:to-orange-500 transition-colors">Gerar certificados de atividades</button>
                    </form>
                    <p class="text-xs text-slate-700 m-0 mt-2 leading-relaxed">Só com <strong>evento finalizado</strong> e <strong>presença salva</strong> em cada atividade.</p>
                </div>
            @else
                <span class="text-sm text-amber-950 bg-amber-50 border border-amber-200/90 rounded-xl px-4 py-3 inline-block max-w-xl shadow-sm">{{ $activitiesBatchBlock }}</span>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-inner shadow-slate-200/50 max-h-[min(30rem,55svh)] overflow-auto overscroll-contain">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 z-10 text-left shadow-[0_1px_0_0_rgb(241_245_249)]">
                        <tr class="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-slate-100/80 to-slate-50">
                            <th class="px-4 py-3 font-bold text-[11px] uppercase tracking-wide text-slate-600 bg-transparent">Atividade</th>
                            <th class="px-4 py-3 font-bold text-[11px] uppercase tracking-wide text-slate-600 bg-transparent">Quando</th>
                            <th class="px-4 py-3 font-bold text-[11px] uppercase tracking-wide text-slate-600 bg-transparent whitespace-nowrap">CH (h)</th>
                            <th class="px-4 py-3 font-bold text-[11px] uppercase tracking-wide text-slate-600 bg-transparent whitespace-nowrap">Presença</th>
                            <th class="px-4 py-3 font-bold text-[11px] uppercase tracking-wide text-slate-600 bg-transparent text-right">Lista</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/90">
                        @forelse($activities as $act)
                            @php $_attDone = ($activityAttendanceCompleteById[(int) $act->id] ?? false); @endphp
                            <tr class="transition-colors hover:bg-amber-50/25">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $act->title }}</td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap tabular-nums">{{ $act->start_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-slate-700 tabular-nums">{{ $act->workload_hours ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    @if($participantUsers->isEmpty())
                                        <span class="text-xs text-slate-400 font-medium">—</span>
                                    @elseif($_attDone)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold tracking-wide text-emerald-900 shadow-sm shadow-emerald-900/5">Registrada</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-amber-950 shadow-sm shadow-amber-900/5">Pendente</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('events.certificates.activity.presence', [$event, $act]) }}" class="inline-flex items-center justify-center text-emerald-800 font-semibold text-xs no-underline rounded-lg px-2.5 py-1.5 hover:bg-emerald-50 ring-1 ring-emerald-100/80 hover:ring-emerald-200 transition-colors">Abrir lista de presença</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Nenhuma atividade cadastrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.certificate-title-upper-input').forEach(function (el) {
        function applyUpper() {
            var upper = el.value.toLocaleUpperCase('pt-BR');
            if (el.value === upper) {
                return;
            }
            var start = el.selectionStart;
            var end = el.selectionEnd;
            el.value = upper;
            if (start !== null && end !== null) {
                el.setSelectionRange(start, end);
            }
        }
        el.addEventListener('input', applyUpper);
        applyUpper();
    });

    document.querySelectorAll('form.cert-batch-swal-confirm').forEach(function (form) {
        function onConfirmSubmit(e) {
            e.preventDefault();
            var title = form.getAttribute('data-swal-title') || 'Confirmar';
            var text = form.getAttribute('data-swal-text') || '';

            function submitForm() {
                form.removeEventListener('submit', onConfirmSubmit);
                form.submit();
            }

            if (typeof window.Swal === 'undefined') {
                if (window.confirm(text || title)) {
                    submitForm();
                }
                return;
            }

            window.Swal.fire({
                icon: 'question',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: 'Sim, continuar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    confirmButton: 'swal2-confirm-rounded',
                    cancelButton: 'swal2-cancel-rounded',
                },
            }).then(function (result) {
                if (result.isConfirmed) {
                    submitForm();
                }
            });
        }

        form.addEventListener('submit', onConfirmSubmit);
    });
});
</script>
<style>
    .swal2-confirm-rounded { border-radius: 0.75rem !important; padding-left: 1.25rem !important; padding-right: 1.25rem !important; }
    .swal2-cancel-rounded { border-radius: 0.75rem !important; padding-left: 1.25rem !important; padding-right: 1.25rem !important; }
</style>
@endpush
