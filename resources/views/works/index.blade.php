@extends('layouts.newMain')

@section('title', 'Trabalhos do Evento')

@section('content')
<div class="min-h-screen bg-slate-50/50 overflow-x-hidden">
    @php
        $workTypeLabels = \App\Models\Work::workTypeLabels();
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 min-w-0">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-slate-500 mb-1">Evento</p>
                <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 break-words [overflow-wrap:anywhere]">{{ $event->title }}</h1>
            </div>
            <a href="/dashboard"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold no-underline transition-colors flex-shrink-0">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Painel
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-6">
            <h2 class="font-semibold text-slate-900 text-lg mb-2">Vinculação de avaliadores</h2>
            <p class="text-sm text-slate-600 mb-4">
                Após o encerramento do prazo de submissão, selecione os trabalhos (por tipo), escolha os avaliadores e distribua.
                Cada trabalho marcado deve ficar com no mínimo <span class="font-semibold">{{ $minReviewers }}</span> e no máximo
                <span class="font-semibold">{{ $maxReviewers }}</span> avaliadores.
            </p>

            @if(!$distributionAllowed)
                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm mb-4">
                    <p class="font-medium m-0 mb-1">Distribuição ainda bloqueada</p>
                    <p class="m-0">
                        Enquanto o prazo de submissão estiver aberto, os autores podem alterar os arquivos. A distribuição só é liberada depois do encerramento.
                        @if($event->submission_deadline_at)
                            Prazo de submissão: <span class="font-semibold">{{ $event->submission_deadline_at->format('d/m/Y H:i') }}</span>.
                        @endif
                    </p>
                </div>
            @endif

            @if($availableReviewers->isEmpty())
                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                    Não há avaliadores cadastrados no sistema para fazer a distribuição.
                </div>
            @else
                @php
                    $oldReviewerIds = collect(old('reviewer_ids', []))->map(fn($id) => (int) $id)->all();
                @endphp
                <form action="{{ route('events.works.reviewers.distribute', $event->id) }}" method="POST" class="space-y-4" id="distribute-reviewers-form" data-distribution-allowed="{{ $distributionAllowed ? '1' : '0' }}">
                    @csrf

                    <fieldset class="space-y-4 min-w-0 border-0 p-0 m-0" @disabled(!$distributionAllowed)>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Trabalhos que receberão avaliadores (marque por tipo)
                        </label>
                        @if($worksGroupedByType->isEmpty())
                            <p class="text-sm text-slate-500 m-0">Não há trabalhos elegíveis para distribuição neste momento.</p>
                        @else
                            <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
                                @foreach($worksGroupedByType as $typeKey => $typeWorks)
                                    <div class="rounded-xl border border-slate-200 p-3 bg-slate-50/50 work-type-group">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                            <span class="text-sm font-semibold text-slate-900">{{ $workTypeLabels[$typeKey] ?? $typeKey }}</span>
                                            <span class="flex flex-wrap gap-1">
                                                <button type="button" class="select-all-in-type text-xs font-semibold text-indigo-700 hover:text-indigo-900 px-2 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                                    Marcar todos deste tipo
                                                </button>
                                                <button type="button" class="deselect-all-in-type text-xs font-semibold text-slate-600 hover:text-slate-900 px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 transition-colors">
                                                    Desmarcar todos deste tipo
                                                </button>
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($typeWorks as $w)
                                                <label class="flex items-start gap-3 p-2 rounded-lg hover:bg-white cursor-pointer border border-transparent hover:border-slate-200 work-select-row">
                                                    <input
                                                        type="checkbox"
                                                        name="work_ids[]"
                                                        value="{{ $w->id }}"
                                                        @checked(in_array($w->id, $oldWorkIds, true))
                                                        class="work-id-checkbox mt-0.5 w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                                    >
                                                    <span class="text-sm text-slate-700 min-w-0">
                                                        <span class="font-medium break-words">{{ $w->listTitleCompact(true) }}</span>
                                                        <span class="text-slate-500"> — {{ $w->submitter->name ?? 'não informado' }}</span>
                                                        <span class="text-xs text-slate-500 block">Avaliadores atuais: {{ $w->reviewer_assignments_count }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @error('work_ids')
                            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                        @error('work_ids.*')
                            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Selecione os avaliadores (marque os nomes)
                        </label>
                        <details id="reviewer-dropdown" class="rounded-xl border-2 border-slate-200">
                            <summary id="reviewer-dropdown-summary"
                                     class="list-none cursor-pointer px-4 py-3 flex items-center justify-between text-sm text-slate-700">
                                <span>Selecionar avaliadores</span>
                                <ion-icon name="chevron-down-outline"></ion-icon>
                            </summary>
                            <div class="border-t border-slate-200 p-3 space-y-3 bg-white rounded-b-xl">
                                <input
                                    id="reviewer-search"
                                    type="text"
                                    placeholder="Buscar por nome ou e-mail..."
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:border-emerald-500 outline-none text-sm"
                                >
                                <div id="reviewer-checkboxes" class="max-h-64 overflow-y-auto space-y-2">
                                    @foreach($availableReviewers as $reviewer)
                                        <label class="reviewer-item flex items-start gap-3 p-2 rounded-lg hover:bg-slate-50 cursor-pointer"
                                               data-search="{{ strtolower($reviewer->name . ' ' . $reviewer->email) }}">
                                            <input
                                                type="checkbox"
                                                name="reviewer_ids[]"
                                                value="{{ $reviewer->id }}"
                                                @checked(in_array($reviewer->id, $oldReviewerIds, true))
                                                class="mt-0.5 w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                            >
                                            <span class="text-sm text-slate-700">
                                                <span class="font-medium">{{ $reviewer->name }}</span>
                                                <span class="text-slate-500">({{ $reviewer->email }})</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                        <p id="reviewer-selection-hint" class="text-xs text-slate-500 mt-1"></p>
                        @error('reviewer_ids')
                            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                        @error('reviewer_ids.*')
                            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reviewers_per_work" class="block text-sm font-medium text-slate-700 mb-2">
                            Quantidade de avaliadores por trabalho
                        </label>
                        <input
                            id="reviewers_per_work"
                            type="number"
                            name="reviewers_per_work"
                            min="{{ $minReviewers }}"
                            max="{{ $maxReviewers }}"
                            value="{{ old('reviewers_per_work', $minReviewers) }}"
                            required
                            class="w-full sm:max-w-xs px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none"
                        >
                        <p class="text-xs text-slate-500 mt-1">
                            Informe um valor entre {{ $minReviewers }} e {{ $maxReviewers }}.
                        </p>
                        @error('reviewers_per_work')
                            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button id="distribute-reviewers-button" type="submit"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <ion-icon name="git-network-outline"></ion-icon>
                        Distribuir avaliadores
                    </button>
                    </fieldset>
                </form>

                <script>
                    (function () {
                        const form = document.getElementById('distribute-reviewers-form');
                        const dropdownSummary = document.getElementById('reviewer-dropdown-summary');
                        const reviewersContainer = document.getElementById('reviewer-checkboxes');
                        const searchInput = document.getElementById('reviewer-search');
                        const quantityInput = document.getElementById('reviewers_per_work');
                        const submitButton = document.getElementById('distribute-reviewers-button');
                        const hint = document.getElementById('reviewer-selection-hint');
                        const distributionAllowed = form?.dataset?.distributionAllowed === '1';

                        if (!dropdownSummary || !reviewersContainer || !quantityInput || !submitButton || !hint) {
                            return;
                        }

                        const selectedWorksCount = () => document.querySelectorAll('.work-id-checkbox:checked').length;
                        const selectedReviewersCount = () => reviewersContainer.querySelectorAll('input[name="reviewer_ids[]"]:checked').length;
                        const selectedReviewers = () => reviewersContainer.querySelectorAll('input[name="reviewer_ids[]"]:checked');

                        document.querySelectorAll('.select-all-in-type').forEach((btn) => {
                            btn.addEventListener('click', () => {
                                const group = btn.closest('.work-type-group');
                                if (!group || !distributionAllowed) {
                                    return;
                                }
                                group.querySelectorAll('.work-id-checkbox').forEach((cb) => {
                                    cb.checked = true;
                                });
                                updateDistributionState();
                            });
                        });

                        document.querySelectorAll('.deselect-all-in-type').forEach((btn) => {
                            btn.addEventListener('click', () => {
                                const group = btn.closest('.work-type-group');
                                if (!group || !distributionAllowed) {
                                    return;
                                }
                                group.querySelectorAll('.work-id-checkbox').forEach((cb) => {
                                    cb.checked = false;
                                });
                                updateDistributionState();
                            });
                        });

                        document.querySelectorAll('.work-id-checkbox').forEach((cb) => {
                            cb.addEventListener('change', updateDistributionState);
                        });

                        const updateSummaryLabel = () => {
                            const selectedCount = selectedReviewersCount();
                            const requiredCount = Number(quantityInput.value || 0);
                            if (selectedCount === 0) {
                                dropdownSummary.querySelector('span').textContent = 'Selecionar avaliadores';
                                return;
                            }

                            dropdownSummary.querySelector('span').textContent = `${selectedCount} selecionado(s) de ${requiredCount || '?'}`;
                        };

                        const updateDistributionState = () => {
                            if (!distributionAllowed) {
                                submitButton.disabled = true;
                                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
                                return;
                            }

                            const worksCount = selectedWorksCount();
                            const selectedCount = selectedReviewersCount();
                            const requiredCount = Number(quantityInput.value || 0);

                            const reviewersOk = requiredCount > 0 && selectedCount === requiredCount;
                            const worksOk = worksCount > 0;
                            const isValid = reviewersOk && worksOk;

                            submitButton.disabled = !isValid;
                            submitButton.classList.toggle('opacity-60', !isValid);
                            submitButton.classList.toggle('cursor-not-allowed', !isValid);

                            if (requiredCount <= 0) {
                                hint.textContent = 'Informe a quantidade de avaliadores por trabalho.';
                                hint.className = 'text-xs text-amber-600 mt-1';
                                updateSummaryLabel();
                                return;
                            }

                            if (!worksOk) {
                                hint.textContent = 'Marque pelo menos um trabalho para receber avaliadores.';
                                hint.className = 'text-xs text-amber-600 mt-1';
                                updateSummaryLabel();
                                return;
                            }

                            if (isValid) {
                                hint.textContent = `${worksCount} trabalho(s) selecionado(s). ${selectedCount} avaliador(es) por trabalho.`;
                                hint.className = 'text-xs text-emerald-700 mt-1';
                                updateSummaryLabel();
                                return;
                            }

                            hint.textContent = `Selecione exatamente ${requiredCount} avaliador(es). Atualmente: ${selectedCount}.`;
                            hint.className = 'text-xs text-amber-600 mt-1';
                            updateSummaryLabel();
                        };

                        reviewersContainer.addEventListener('change', updateDistributionState);
                        quantityInput.addEventListener('input', updateDistributionState);

                        if (searchInput) {
                            searchInput.addEventListener('input', () => {
                                const query = searchInput.value.toLowerCase().trim();
                                const items = reviewersContainer.querySelectorAll('.reviewer-item');

                                items.forEach((item) => {
                                    const searchText = item.getAttribute('data-search') || '';
                                    item.style.display = searchText.includes(query) ? '' : 'none';
                                });
                            });
                        }

                        selectedReviewers().forEach((input) => {
                            input.closest('.reviewer-item')?.classList.add('bg-emerald-50');
                        });
                        reviewersContainer.addEventListener('change', (event) => {
                            const target = event.target;
                            if (!(target instanceof HTMLInputElement) || target.type !== 'checkbox') {
                                return;
                            }
                            target.closest('.reviewer-item')?.classList.toggle('bg-emerald-50', target.checked);
                        });

                        updateDistributionState();
                    })();
                </script>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 mb-6">
            <h2 class="font-semibold text-slate-900 text-lg mb-3">Filtrar trabalhos</h2>
            <form method="GET" action="{{ route('events.works.index', $event->id) }}"
                  class="flex flex-col sm:flex-row gap-3 sm:items-end">
                <div class="flex-1">
                    <label for="work_type_filter" class="block text-sm font-medium text-slate-700 mb-2">
                        Tipo de trabalho
                    </label>
                    <select id="work_type_filter" name="work_type"
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 outline-none">
                        <option value="">Todos os tipos</option>
                        @foreach($availableTypes as $type)
                            <option value="{{ $type }}" @selected(($selectedType ?? '') === $type)>
                                {{ $workTypeLabels[$type] ?? $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition-colors">
                        <ion-icon name="filter-outline"></ion-icon>
                        Filtrar
                    </button>
                    @if(!empty($selectedType))
                        <a href="{{ route('events.works.index', $event->id) }}"
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold no-underline transition-colors">
                            <ion-icon name="close-outline"></ion-icon>
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if($works->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <h3 class="font-semibold text-slate-900 text-lg mb-2">
                    @if(!empty($selectedType))
                        Nenhum trabalho deste tipo
                    @else
                        Nenhuma submissão ainda
                    @endif
                </h3>
                <p class="text-slate-500 m-0">
                    @if(!empty($selectedType))
                        Não há submissões para o tipo selecionado. Tente outro filtro.
                    @else
                        As submissões aparecerão aqui quando os estudantes enviarem trabalhos.
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto overflow-y-auto max-h-[min(75vh,52rem)] rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm text-left min-w-[62rem]">
                    <thead class="sticky top-0 z-10 bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 shadow-sm">
                        <tr>
                            <th scope="col" class="px-4 py-3">ID</th>
                            <th scope="col" class="px-4 py-3">Trabalho</th>
                            <th scope="col" class="px-4 py-3">Autor principal</th>
                            <th scope="col" class="px-4 py-3">Tipo</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-center">Avaliadores</th>
                            <th scope="col" class="px-4 py-3 text-center whitespace-nowrap">Decisão final</th>
                            <th scope="col" class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($works as $work)
                            <tr class="hover:bg-slate-50/80 align-top">
                                <td class="px-4 py-3 text-slate-500 font-mono whitespace-nowrap">#{{ $work->id }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 break-words [overflow-wrap:anywhere] max-w-[14rem]">
                                    {{ $work->listTitleCompact(false) }}
                                </td>
                                <td class="px-4 py-3 text-slate-700 break-words [overflow-wrap:anywhere] max-w-[12rem]">
                                    {{ $work->submitter->name ?? 'não informado' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                    {{ $workTypeLabels[$work->work_type] ?? $work->work_type }}
                                </td>
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                    {{ $work->statusLabel() }}
                                </td>
                                <td class="px-4 py-3 text-center text-slate-700">
                                    {{ $work->reviewer_assignments_count }}
                                </td>
                                @php
                                    $nReviewers = (int) $work->reviewer_assignments_count;
                                    $nCompleted = (int) ($work->reviewer_assignments_completed_count ?? 0);
                                    $allReviewersResponded = $nReviewers > 0 && $nCompleted === $nReviewers;
                                    $decisionTooltip = $work->decision_at
                                        ? 'Decisão registrada em '.$work->decision_at->format('d/m/Y H:i').' — '.$work->statusLabel().($work->decisionByUser ? ' · '.$work->decisionByUser->name : '')
                                        : null;
                                @endphp
                                <td class="px-4 py-3 text-center align-middle">
                                    @if(!$distributionAllowed)
                                        <span class="text-xs text-slate-400 cursor-default" title="Após encerrar o prazo de submissão você verá quando todos os pareceres foram enviados.">—</span>
                                    @elseif($work->coordinatorDecisionIsTerminal())
                                        @php
                                            $regTitle = $decisionTooltip ?? ('Situação fechada para novas decisões da coordenação — '.$work->statusLabel());
                                        @endphp
                                        <span class="inline-flex items-center justify-center gap-1 rounded-full bg-slate-200/90 text-slate-900 px-2.5 py-1 text-xs font-semibold ring-1 ring-slate-300/80 whitespace-nowrap" title="{{ $regTitle }}">
                                            <ion-icon name="shield-checkmark-outline" aria-hidden="true" class="text-base shrink-0"></ion-icon>
                                            Registrada
                                        </span>
                                        @if($work->decision_at)
                                            <span class="block text-[10px] text-slate-500 mt-1 font-normal leading-tight">{{ $work->decision_at->format('d/m/Y') }}</span>
                                        @endif
                                    @elseif($work->isAwaitingAuthorCorrection())
                                        @php $encaminhamentoTitle = $decisionTooltip ?? 'Aceito com correções — autor ainda deve enviar a versão corrigida.'; @endphp
                                        <span class="inline-flex flex-col items-center gap-0.5 max-w-[11rem] mx-auto">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 text-indigo-950 px-2 py-1 text-[11px] font-semibold ring-1 ring-indigo-200/80" title="{{ $encaminhamentoTitle }}">
                                                <ion-icon name="time-outline" aria-hidden="true" class="text-base shrink-0"></ion-icon>
                                                Aguardando autor
                                            </span>
                                            <span class="text-[10px] leading-tight text-slate-500 text-center normal-case font-normal px-1">Versão corrigida</span>
                                        </span>
                                    @elseif($nReviewers === 0)
                                        <span class="text-xs text-slate-400" title="Nenhum avaliador vinculado a este trabalho.">—</span>
                                    @elseif($allReviewersResponded)
                                        <span class="inline-flex items-center justify-center gap-1 rounded-full bg-emerald-100 text-emerald-900 px-2.5 py-1 text-xs font-semibold ring-1 ring-emerald-200/80 whitespace-nowrap" title="Todos os avaliadores já enviaram o parecer — na página Detalhes você pode registrar a decisão final da coordenação.">
                                            <ion-icon name="checkmark-done-outline" aria-hidden="true" class="text-base shrink-0"></ion-icon>
                                            Liberada
                                        </span>
                                    @else
                                        <span class="inline-flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-900 px-2 py-1 text-[11px] font-medium ring-1 ring-amber-200/80 whitespace-nowrap" title="Aguardando envio do parecer de um ou mais avaliadores designados ({{ $nCompleted }}/{{ $nReviewers }} concluído(s)).">
                                                Aguardando pareceres
                                            </span>
                                            <span class="text-[10px] leading-tight text-slate-500 font-mono">{{ $nCompleted }}/{{ $nReviewers }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('works.show', $work->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold no-underline transition-colors">
                                        <ion-icon name="eye-outline" class="text-base"></ion-icon>
                                        Detalhes
                                    </a>
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
