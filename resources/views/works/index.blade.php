@extends('layouts.newMain')

@section('title', 'Trabalhos do Evento')

@section('content')
<div class="dash-page min-h-screen overflow-x-hidden">
    @php
        $workTypeLabels = \App\Models\Work::workTypeLabels();
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 min-w-0">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="min-w-0 flex-1">
                <p class="dash-muted text-sm mb-1">Evento</p>
                <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl break-words [overflow-wrap:anywhere]">{{ $event->title }}</h1>
            </div>
            <a href="/dashboard"
               class="dash-btn-secondary inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold no-underline transition-colors flex-shrink-0">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Painel
            </a>
        </div>

        <div class="dash-card rounded-2xl p-5 sm:p-6 mb-6">
            <h2 class="dash-title font-semibold text-lg mb-2">Vinculação de avaliadores</h2>
            <p class="dash-text text-sm mb-4">
                @if($coordinatorActsAsReviewer)
                    @if((int) auth()->id() === (int) $event->user_id)
                        Você marcou que vai avaliar os trabalhos. A distribuição abaixo vincula só você, e o parecer que enviar já registra a decisão.
                    @else
                        O coordenador deste evento marcou que vai avaliar os trabalhos. A distribuição abaixo vincula só ele.
                    @endif
                @else
                    Depois do prazo de submissão e dentro do prazo de avaliação, selecione os trabalhos (por tipo), escolha os avaliadores e distribua.
                    Cada trabalho marcado deve ficar com no mínimo <span class="font-semibold">{{ $minReviewers }}</span> e no máximo
                    <span class="font-semibold">{{ $maxReviewers }}</span> avaliadores.
                @endif
                @if($event->review_deadline_at)
                    Prazo de avaliação: <span class="font-semibold">{{ $event->review_deadline_at->format('d/m/Y H:i') }}</span>.
                @endif
            </p>

            @if(!$distributionAllowed)
                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm mb-4">
                    @if(!$event->workSubmissionDeadlinePassed())
                        <p class="font-medium m-0 mb-1">Distribuição ainda bloqueada</p>
                        <p class="m-0">
                            Enquanto o prazo de submissão estiver aberto, os autores podem alterar os arquivos. A distribuição só é liberada depois do encerramento.
                            @if($event->submission_deadline_at)
                                Prazo de submissão: <span class="font-semibold">{{ $event->submission_deadline_at->format('d/m/Y H:i') }}</span>.
                            @endif
                        </p>
                    @else
                        <p class="font-medium m-0 mb-1">Prazo de avaliação encerrado</p>
                        <p class="m-0">
                            Não é mais possível distribuir avaliadores.
                            @if($event->review_deadline_at)
                                O prazo terminou em <span class="font-semibold">{{ $event->review_deadline_at->format('d/m/Y H:i') }}</span>.
                            @else
                                Neste evento a avaliação acompanha o fim do período.
                            @endif
                        </p>
                    @endif
                </div>
            @endif

            @if((int) auth()->id() === (int) $event->user_id)
                <form action="{{ route('events.works.coordinator-reviewer', $event->id) }}" method="POST" class="mb-4">
                    @csrf
                    <input type="hidden" name="enabled" value="0">
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="enabled" value="1"
                               class="dash-check mt-0.5 w-4 h-4 rounded"
                               @checked($coordinatorActsAsReviewer)
                               @disabled($coordinatorReviewLocked)
                               onchange="this.form.submit()">
                        <span class="text-sm text-slate-700">
                            <span class="font-medium text-slate-900">Eu mesmo vou avaliar os trabalhos</span>
                            <span class="block text-slate-500 mt-1">Marque se não houver avaliadores ou se você quiser fazer a avaliação. A marcação não vincula nenhum trabalho: a distribuição abaixo é que faz isso.</span>
                        </span>
                    </label>
                </form>
            @endif

            @if($coordinatorActsAsReviewer)
                @if(auth()->user()->canAccessReviewerArea())
                    <div class="mb-4">
                        <a href="{{ route('reviews.assigned') }}"
                           class="dash-btn-secondary inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold no-underline">
                            <ion-icon name="clipboard-outline"></ion-icon>
                            Abrir minhas avaliações
                        </a>
                    </div>
                @endif
                <form action="{{ route('events.works.reviewers.distribute', $event->id) }}" method="POST" class="space-y-4" id="distribute-coordinator-form">
                    @csrf
                    <fieldset class="space-y-4 min-w-0 border-0 p-0 m-0" @disabled(!$distributionAllowed)>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Trabalhos que você vai avaliar
                            </label>
                            @if($worksGroupedByType->isEmpty())
                                <p class="text-sm text-slate-500 m-0">Não há trabalhos elegíveis para avaliação neste momento.</p>
                            @else
                                <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
                                    @foreach($worksGroupedByType as $typeKey => $typeWorks)
                                        <div class="dash-panel rounded-xl p-3 work-type-group">
                                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                                <span class="text-sm font-semibold text-slate-900">{{ $workTypeLabels[$typeKey] ?? $typeKey }}</span>
                                                <span class="flex flex-wrap gap-1">
                                                    <button type="button" class="select-all-in-type dash-badge-positive text-xs font-semibold px-2 py-1 rounded-lg transition-colors">
                                                        Marcar todos deste tipo
                                                    </button>
                                                    <button type="button" class="deselect-all-in-type dash-badge text-xs font-semibold px-2 py-1 rounded-lg transition-colors">
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
                                                            class="dash-check coordinator-work-checkbox mt-0.5 w-4 h-4 rounded"
                                                        >
                                                        <span class="text-sm text-slate-700 min-w-0">
                                                            <span class="font-medium break-words">{{ $w->listTitleCompact(true) }}</span>
                                                            <span class="text-slate-500">, {{ $w->submitter->name ?? 'não informado' }}</span>
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
                        </div>
                        <button id="distribute-coordinator-button" type="submit"
                                class="dash-btn inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                                @disabled(!$distributionAllowed || $worksGroupedByType->isEmpty())>
                            <ion-icon name="person-outline"></ion-icon>
                            {{ (int) auth()->id() === (int) $event->user_id ? 'Vincular minha avaliação' : 'Vincular a avaliação do coordenador' }}
                        </button>
                    </fieldset>
                </form>
                @if($coordinatorReviewLocked)
                    <p class="text-sm text-slate-500 mt-4 m-0">A opção permanece marcada porque você já enviou um parecer neste evento.</p>
                @endif
                <script>
                    (function () {
                        const form = document.getElementById('distribute-coordinator-form');
                        const button = document.getElementById('distribute-coordinator-button');
                        if (!form || !button || button.disabled) {
                            return;
                        }
                        const boxes = () => form.querySelectorAll('.coordinator-work-checkbox');
                        const refresh = () => {
                            const any = [...boxes()].some((box) => box.checked);
                            button.disabled = !any;
                            button.classList.toggle('opacity-60', !any);
                            button.classList.toggle('cursor-not-allowed', !any);
                        };
                        form.querySelectorAll('.select-all-in-type').forEach((btn) => {
                            btn.addEventListener('click', () => {
                                btn.closest('.work-type-group')?.querySelectorAll('.coordinator-work-checkbox').forEach((box) => {
                                    box.checked = true;
                                });
                                refresh();
                            });
                        });
                        form.querySelectorAll('.deselect-all-in-type').forEach((btn) => {
                            btn.addEventListener('click', () => {
                                btn.closest('.work-type-group')?.querySelectorAll('.coordinator-work-checkbox').forEach((box) => {
                                    box.checked = false;
                                });
                                refresh();
                            });
                        });
                        boxes().forEach((box) => box.addEventListener('change', refresh));
                        refresh();
                    })();
                </script>
            @elseif($availableReviewers->isEmpty())
                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                    <p class="m-0">Não há avaliadores cadastrados no sistema para fazer a distribuição.</p>
                    @if((int) auth()->id() !== (int) $event->user_id)
                        <p class="m-0 mt-2">Somente quem criou o evento pode marcar a opção de avaliar os trabalhos.</p>
                    @endif
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
                                    <div class="dash-panel rounded-xl p-3 work-type-group">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                            <span class="text-sm font-semibold text-slate-900">{{ $workTypeLabels[$typeKey] ?? $typeKey }}</span>
                                            <span class="flex flex-wrap gap-1">
                                                <button type="button" class="select-all-in-type dash-badge-positive text-xs font-semibold px-2 py-1 rounded-lg transition-colors">
                                                    Marcar todos deste tipo
                                                </button>
                                                <button type="button" class="deselect-all-in-type dash-badge text-xs font-semibold px-2 py-1 rounded-lg transition-colors">
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
                                                        class="dash-check work-id-checkbox mt-0.5 w-4 h-4 rounded"
                                                    >
                                                    <span class="text-sm text-slate-700 min-w-0">
                                                        <span class="font-medium break-words">{{ $w->listTitleCompact(true) }}</span>
                                                        <span class="text-slate-500">, {{ $w->submitter->name ?? 'não informado' }}</span>
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
                                    class="dash-field w-full px-3 py-2 rounded-lg outline-none text-sm"
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
                                                class="dash-check mt-0.5 w-4 h-4 rounded"
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
                            class="dash-field w-full sm:max-w-xs px-4 py-3 rounded-xl outline-none"
                        >
                        <p class="text-xs text-slate-500 mt-1">
                            Informe um valor entre {{ $minReviewers }} e {{ $maxReviewers }}.
                        </p>
                        @error('reviewers_per_work')
                            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button id="distribute-reviewers-button" type="submit"
                            class="dash-btn inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
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
                                hint.className = 'text-xs text-amber-800 mt-1';
                                updateSummaryLabel();
                                return;
                            }

                            if (!worksOk) {
                                hint.textContent = 'Marque pelo menos um trabalho para receber avaliadores.';
                                hint.className = 'text-xs text-amber-800 mt-1';
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
                            hint.className = 'text-xs text-amber-800 mt-1';
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

        <h2 class="dash-title font-semibold text-lg mb-3">Trabalhos</h2>

        @if($works->isEmpty())
            <div class="dash-card rounded-2xl p-8 text-center">
                <h3 class="dash-title font-semibold text-lg mb-2">Nenhuma submissão ainda</h3>
                <p class="dash-muted m-0">As submissões aparecerão aqui quando os estudantes enviarem trabalhos.</p>
            </div>
        @else
            <div class="dash-card dash-table works-datatable rounded-2xl">
                <table id="worksTable" class="w-full text-sm text-left">
                    <thead class="font-semibold border-b">
                        <tr>
                            <th scope="col" class="works-col-id px-3 py-3">ID</th>
                            <th scope="col" class="works-col-title px-3 py-3">Trabalho</th>
                            <th scope="col" class="works-col-author px-3 py-3">Autor principal</th>
                            <th scope="col" class="works-col-type px-3 py-3">Tipo</th>
                            <th scope="col" class="works-col-status px-3 py-3">Status</th>
                            <th scope="col" class="works-col-reviewers px-3 py-3 text-center">Avaliadores</th>
                            <th scope="col" class="works-col-decision px-3 py-3 text-center">Decisão final</th>
                            <th scope="col" class="works-col-actions px-3 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @php $workSequence = 0; @endphp
                        @foreach($works as $work)
                            @php $workSequence++; @endphp
                            <tr class="hover:bg-slate-50/80 align-middle">
                                <td data-label="ID" class="px-3 py-3 text-slate-500 font-mono whitespace-nowrap" data-order="{{ $workSequence }}">#{{ $workSequence }}</td>
                                <td data-label="Trabalho" class="px-3 py-3 font-medium text-slate-900">
                                    {{ $work->listTitleCompact(false) }}
                                </td>
                                <td data-label="Autor principal" class="px-3 py-3 text-slate-700">
                                    {{ $work->submitter->name ?? 'não informado' }}
                                </td>
                                <td data-label="Tipo" class="px-3 py-3 text-slate-700">
                                    {{ $workTypeLabels[$work->work_type] ?? $work->work_type }}
                                </td>
                                <td data-label="Status" class="px-3 py-3 text-slate-700">
                                    {{ $work->statusLabel() }}
                                </td>
                                <td data-label="Avaliadores" class="px-3 py-3 text-center text-slate-700">
                                    {{ $work->reviewer_assignments_count }}
                                </td>
                                @php
                                    $nReviewers = (int) $work->reviewer_assignments_count;
                                    $nCompleted = (int) ($work->reviewer_assignments_completed_count ?? 0);
                                    $allReviewersResponded = $nReviewers > 0 && $nCompleted === $nReviewers;
                                    $decisionTooltip = $work->decision_at
                                        ? 'Decisão registrada em '.$work->decision_at->format('d/m/Y H:i').'. '.$work->statusLabel().($work->decisionByUser ? ' · '.$work->decisionByUser->name : '')
                                        : null;
                                @endphp
                                <td data-label="Decisão final" class="px-3 py-3 text-center align-middle">
                                    @if(!$distributionAllowed)
                                        <span class="text-xs text-slate-400 cursor-default" title="Após encerrar o prazo de submissão você verá quando todos os pareceres foram enviados.">-</span>
                                    @elseif($work->coordinatorDecisionIsTerminal())
                                        @php
                                            $regTitle = $decisionTooltip ?? ('Situação fechada para novas decisões da coordenação. '.$work->statusLabel());
                                        @endphp
                                        <span class="inline-flex items-center justify-center gap-1 rounded-full bg-slate-200/90 text-slate-900 px-2.5 py-1 text-xs font-semibold ring-1 ring-slate-300/80 whitespace-nowrap" title="{{ $regTitle }}">
                                            <ion-icon name="shield-checkmark-outline" aria-hidden="true" class="text-base shrink-0"></ion-icon>
                                            Registrada
                                        </span>
                                        @if($work->decision_at)
                                            <span class="block text-[10px] text-slate-500 mt-1 font-normal leading-tight">{{ $work->decision_at->format('d/m/Y') }}</span>
                                        @endif
                                    @elseif($work->isAwaitingAuthorCorrection())
                                        @php $encaminhamentoTitle = $decisionTooltip ?? 'Aceito com correções. O autor ainda deve enviar a versão corrigida.'; @endphp
                                        <span class="inline-flex flex-col items-center gap-0.5 max-w-[11rem] mx-auto">
                                            <span class="dash-badge inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-semibold" title="{{ $encaminhamentoTitle }}">
                                                <ion-icon name="time-outline" aria-hidden="true" class="text-base shrink-0"></ion-icon>
                                                Aguardando autor
                                            </span>
                                            <span class="text-[10px] leading-tight text-slate-500 text-center normal-case font-normal px-1">Versão corrigida</span>
                                        </span>
                                    @elseif($nReviewers === 0)
                                        <span class="text-xs text-slate-400" title="Nenhum avaliador vinculado a este trabalho.">-</span>
                                    @elseif($allReviewersResponded)
                                        <span class="dash-badge-positive inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap" title="Todos os avaliadores já enviaram o parecer. Na página Detalhes você pode registrar a decisão final da coordenação.">
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
                                <td data-label="Ações" class="px-3 py-3 text-right">
                                    <a href="{{ route('works.show', $work->id) }}"
                                       class="dash-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold no-underline transition-colors">
                                        <ion-icon name="eye-outline" class="text-base"></ion-icon>
                                        Detalhes
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
