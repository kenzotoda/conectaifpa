@php
    $internalFloor = (int) ($internalRegistered ?? 0);
    $externalFloor = (int) ($externalRegistered ?? 0);
    $showRegistered = $internalRegistered !== null && $externalRegistered !== null;
@endphp

<div id="audience-capacity-fields" class="dash-panel md:col-span-2 rounded-xl p-4 sm:p-5">
    <div class="mb-4">
        <p class="text-sm font-montserrat font-semibold text-slate-900 m-0">Distribuição das vagas</p>
        <p class="text-sm text-slate-600 mt-1 mb-0">
            A capacidade total é a soma das duas categorias.
            Internos: discente, docente e técnico administrativo do IFPA.
            Externos: empreendedor, estudante de escola pública, startup, organização social e comunidade em geral.
        </p>
    </div>

    @if($showRegistered && empty($quotasAlreadySaved))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
            Este evento ainda usa apenas a capacidade total. Ao salvar, a divisão abaixo passa a valer nas novas inscrições.
        </div>
    @endif

    @if($showRegistered)
        <p class="text-sm text-slate-700 mb-4 m-0">
            Inscritos agora: <strong>{{ $internalRegistered }} internos</strong> e <strong>{{ $externalRegistered }} externos</strong>.
            Cada limite novo precisa ser igual ou maior do que a quantidade já inscrita na categoria. Para reduzir, remova participantes antes de salvar.
        </p>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="form-label block text-sm font-montserrat mb-2" for="capacity">
                Capacidade total *
            </label>
            <input
                id="capacity"
                name="capacity"
                type="number"
                inputmode="numeric"
                class="form-input w-full px-4 py-3 rounded-lg font-open-sans @error('capacity') border-red-500 @enderror"
                placeholder="Ex: 600"
                min="1"
                value="{{ $capacityValue }}"
                required
            >
            @error('capacity')
                <p class="field-error text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label block text-sm font-montserrat mb-2" for="internal_capacity">
                Vagas internas *
            </label>
            <input
                id="internal_capacity"
                name="internal_capacity"
                type="number"
                inputmode="numeric"
                class="form-input w-full px-4 py-3 rounded-lg font-open-sans @error('internal_capacity') border-red-500 @enderror"
                placeholder="Ex: 300"
                min="{{ $internalFloor }}"
                data-floor="{{ $internalFloor }}"
                value="{{ $internalValue }}"
                @if($showRegistered) title="Mínimo de {{ $internalFloor }}, igual aos internos já inscritos" @endif
                required
            >
            @error('internal_capacity')
                <p class="field-error text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label block text-sm font-montserrat mb-2" for="external_capacity">
                Vagas externas *
            </label>
            <input
                id="external_capacity"
                name="external_capacity"
                type="number"
                inputmode="numeric"
                class="form-input w-full px-4 py-3 rounded-lg font-open-sans @error('external_capacity') border-red-500 @enderror"
                placeholder="Ex: 300"
                min="{{ $externalFloor }}"
                data-floor="{{ $externalFloor }}"
                value="{{ $externalValue }}"
                @if($showRegistered) title="Mínimo de {{ $externalFloor }}, igual aos externos já inscritos" @endif
                required
            >
            @error('external_capacity')
                <p class="field-error text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p id="audience-capacity-hint" class="text-sm text-slate-600 m-0" aria-live="polite"></p>
        <button type="button" id="audience-capacity-split" class="btn-outline px-4 py-2 rounded-lg text-sm shrink-0">
            Dividir igualmente
        </button>
    </div>
</div>

@once
    <script>
        (function () {
            var capacity = document.getElementById('capacity');
            var internal = document.getElementById('internal_capacity');
            var external = document.getElementById('external_capacity');
            var hint = document.getElementById('audience-capacity-hint');
            var split = document.getElementById('audience-capacity-split');
            if (!capacity || !internal || !external || !hint) {
                return;
            }

            function floorOf(input) {
                var floor = parseInt(input.getAttribute('data-floor') || '0', 10);
                return Number.isFinite(floor) && floor > 0 ? floor : 0;
            }

            function parseValue(input) {
                if (input.value === '') {
                    return null;
                }
                var value = Number(input.value);
                return Number.isInteger(value) ? value : NaN;
            }

            function refresh() {
                var total = parseValue(capacity);
                var internalValue = parseValue(internal);
                var externalValue = parseValue(external);

                hint.className = 'text-sm m-0';

                if (total === null || internalValue === null || externalValue === null) {
                    hint.textContent = 'Preencha a capacidade total e as duas categorias. A soma precisa ser igual à capacidade.';
                    hint.classList.add('dash-muted');
                    return;
                }

                if (Number.isNaN(total) || Number.isNaN(internalValue) || Number.isNaN(externalValue)) {
                    hint.textContent = 'Use apenas números inteiros.';
                    hint.classList.add('text-amber-800');
                    return;
                }

                var sum = internalValue + externalValue;
                if (sum === total) {
                    hint.textContent = internalValue + ' internas + ' + externalValue + ' externas = ' + total + ' vagas.';
                    hint.classList.add('text-primary-custom');
                    return;
                }

                hint.textContent = 'A soma está em ' + sum + '. Ela precisa ser igual à capacidade total de ' + total + '.';
                hint.classList.add('text-amber-800');
            }

            function splitEvenly() {
                var total = parseValue(capacity);
                if (total === null || Number.isNaN(total) || total < 1) {
                    refresh();
                    return;
                }

                var internalFloor = floorOf(internal);
                var externalFloor = floorOf(external);
                if (total < internalFloor + externalFloor) {
                    internal.value = String(internalFloor);
                    external.value = String(externalFloor);
                    refresh();
                    return;
                }

                var free = total - internalFloor - externalFloor;
                var externalExtra = Math.floor(free / 2);
                var internalExtra = free - externalExtra;
                internal.value = String(internalFloor + internalExtra);
                external.value = String(externalFloor + externalExtra);
                refresh();
            }

            capacity.addEventListener('input', refresh);
            internal.addEventListener('input', refresh);
            external.addEventListener('input', refresh);
            if (split) {
                split.addEventListener('click', splitEvenly);
            }
            refresh();
        })();
    </script>
@endonce
