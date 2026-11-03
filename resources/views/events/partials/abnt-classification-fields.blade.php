@php
    $naturezaValue = old('natureza', $natureza ?? '');
    $abrangenciaValue = old('abrangencia', $abrangencia ?? '');
    $periodicidadeValue = old('periodicidade', $periodicidade ?? '');
    $edicaoValue = old('edicao', $edicao ?? '');
    $editionVisible = $periodicidadeValue !== '' && $periodicidadeValue !== \App\Models\Event::PERIODICITY_UNIQUE;
@endphp

<div>
    <label class="form-label block text-sm font-montserrat mb-2" for="natureza">Natureza *</label>
    <select id="natureza" name="natureza" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
        <option value="">Selecione a natureza</option>
        @foreach(\App\Models\Event::NATURE_OPTIONS as $value => $label)
            <option value="{{ $value }}" @selected($naturezaValue === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('natureza')
        <p class="field-error text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="form-label block text-sm font-montserrat mb-2" for="abrangencia">Abrangência *</label>
    <select id="abrangencia" name="abrangencia" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
        <option value="">Selecione a abrangência</option>
        @foreach(\App\Models\Event::SCOPE_OPTIONS as $value => $label)
            <option value="{{ $value }}" @selected($abrangenciaValue === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('abrangencia')
        <p class="field-error text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="form-label block text-sm font-montserrat mb-2" for="periodicidade">Periodicidade *</label>
    <select id="periodicidade" name="periodicidade" class="form-input w-full px-4 py-3 rounded-lg font-open-sans" required>
        <option value="">Selecione a periodicidade</option>
        @foreach(\App\Models\Event::PERIODICITY_OPTIONS as $value => $label)
            <option value="{{ $value }}" @selected($periodicidadeValue === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('periodicidade')
        <p class="field-error text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
    @enderror
</div>

<div id="edicao-wrap" class="{{ $editionVisible ? '' : 'hidden' }}">
    <label class="form-label block text-sm font-montserrat mb-2" for="edicao">Número da edição *</label>
    <input type="number" id="edicao" name="edicao" min="1" max="999" inputmode="numeric"
        value="{{ $edicaoValue }}"
        class="form-input w-full px-4 py-3 rounded-lg font-open-sans"
        placeholder="Ex.: 2"
        @if($editionVisible) required @else disabled @endif>
    @error('edicao')
        <p class="field-error text-red-600 text-sm mt-1 m-0">{{ $message }}</p>
    @enderror
</div>

<p class="md:col-span-2 text-sm text-slate-600 m-0">
    O acesso é gratuito. O porte (micro, pequeno, médio, grande ou mega) é calculado pela soma das vagas e aparece na confirmação e na página do evento.
</p>

<script>
    window.abntPorteLabel = function (total) {
        const n = Number(total) || 0;
        if (n <= 100) return 'Microevento';
        if (n <= 500) return 'Pequeno porte';
        if (n <= 5000) return 'Médio porte';
        if (n <= 40000) return 'Grande porte';
        return 'Megaevento';
    };

    (function () {
        const periodicity = document.getElementById('periodicidade');
        const editionWrap = document.getElementById('edicao-wrap');
        const editionInput = document.getElementById('edicao');
        if (!periodicity || !editionWrap || !editionInput) return;

        const syncEdition = () => {
            const needsEdition = periodicity.value !== '' && periodicity.value !== '{{ \App\Models\Event::PERIODICITY_UNIQUE }}';
            editionWrap.classList.toggle('hidden', !needsEdition);
            editionInput.required = needsEdition;
            editionInput.disabled = !needsEdition;
        };

        periodicity.addEventListener('change', syncEdition);
    })();
</script>
