@extends('layouts.newMain')

@section('title', 'Submeter Trabalho')

@section('content')
<div class="dash-page min-h-screen overflow-x-hidden">
    @php
        $workTypeLabels = \App\Models\Work::workTypeLabels();
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 min-w-0">
        <div class="mb-6 min-w-0">
            <p class="dash-muted text-sm mb-1">Submissão para o evento</p>
            <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl break-words [overflow-wrap:anywhere]">{{ $event->title }}</h1>
        </div>

        <div class="dash-card rounded-2xl p-5 sm:p-6 overflow-hidden">
            <x-validation-errors class="mb-4" />

            <form action="{{ route('events.works.store', $event->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="dash-title block text-sm font-medium mb-2">Tipo de trabalho</label>
                    <select name="work_type" required
                            class="dash-field w-full px-4 py-3 rounded-xl outline-none">
                        <option value="">Selecione...</option>
                        @foreach($workTypes as $type)
                            <option value="{{ $type }}" @selected(old('work_type') === $type)>
                                {{ $workTypeLabels[$type] ?? $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="dash-title block text-sm font-medium mb-2">Arquivo (PDF, DOC, DOCX ou ODT, até 10MB)</label>
                    <input type="file" name="file"
                           accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text"
                           required
                           class="dash-field w-full px-4 py-3 rounded-xl">
                </div>

                <div class="dash-rule pt-2 border-t">
                    <p class="dash-text text-sm mb-3 m-0">
                        <strong class="dash-title">Autor principal:</strong> {{ auth()->user()->name }} ({{ auth()->user()->email }}).
                    </p>
                    <label class="dash-title block text-sm font-medium mb-1">Outros autores (opcional)</label>
                    <p class="dash-muted text-xs mb-3 m-0">Adicione coautores preenchendo os campos abaixo.</p>
                    @php
                        $manualRows = old('coauthors_manual');
                        if (!is_array($manualRows)) {
                            $manualRows = [['author_name' => '', 'author_email' => '', 'institution' => '']];
                        }
                        if (count($manualRows) === 0) {
                            $manualRows[] = ['author_name' => '', 'author_email' => '', 'institution' => ''];
                        }
                    @endphp
                    <div id="coauthors-manual-container" class="space-y-3">
                        @foreach($manualRows as $idx => $row)
                            <div class="coauthor-row dash-panel grid grid-cols-1 sm:grid-cols-3 gap-2 p-3 rounded-xl">
                                <input type="text" name="coauthors_manual[{{ $idx }}][author_name]"
                                       value="{{ $row['author_name'] ?? '' }}"
                                       placeholder="Nome"
                                       class="dash-field w-full px-3 py-2 rounded-lg text-sm outline-none">
                                <input type="email" name="coauthors_manual[{{ $idx }}][author_email]"
                                       value="{{ $row['author_email'] ?? '' }}"
                                       placeholder="E-mail (opcional)"
                                       class="dash-field w-full px-3 py-2 rounded-lg text-sm outline-none">
                                <input type="text" name="coauthors_manual[{{ $idx }}][institution]"
                                       value="{{ $row['institution'] ?? '' }}"
                                       placeholder="Instituição / curso"
                                       class="dash-field w-full px-3 py-2 rounded-lg text-sm outline-none">
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-coauthor-row"
                            class="dash-link mt-3 inline-flex items-center gap-1.5 text-sm font-medium bg-transparent border-0 cursor-pointer p-0">
                        <ion-icon name="add-circle-outline" class="text-lg"></ion-icon>
                        Adicionar autor
                    </button>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="dash-btn inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold">
                        <ion-icon name="cloud-upload-outline"></ion-icon>
                        Submeter
                    </button>
                    <a href="/dashboard"
                       class="dash-btn-secondary inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold no-underline">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    var container = document.getElementById('coauthors-manual-container');
    var btn = document.getElementById('add-coauthor-row');
    if (!container || !btn) return;
    var inputClass = 'dash-field w-full px-3 py-2 rounded-lg text-sm outline-none';
    btn.addEventListener('click', function () {
        var rows = container.querySelectorAll('.coauthor-row');
        var idx = rows.length;
        var wrap = document.createElement('div');
        wrap.className = 'coauthor-row dash-panel grid grid-cols-1 sm:grid-cols-3 gap-2 p-3 rounded-xl';
        wrap.innerHTML =
            '<input type="text" name="coauthors_manual[' + idx + '][author_name]" placeholder="Nome" class="' + inputClass + '">' +
            '<input type="email" name="coauthors_manual[' + idx + '][author_email]" placeholder="E-mail (opcional)" class="' + inputClass + '">' +
            '<input type="text" name="coauthors_manual[' + idx + '][institution]" placeholder="Instituição / curso" class="' + inputClass + '">';
        container.appendChild(wrap);
    });
})();
</script>
@endpush
@endsection
