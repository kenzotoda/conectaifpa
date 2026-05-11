@extends('layouts.newMain')

@section('title', 'Submeter Trabalho')

@section('content')
<div class="min-h-screen bg-slate-50/50 overflow-x-hidden">
    @php
        $workTypeLabels = \App\Models\Work::workTypeLabels();
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 min-w-0">
        <div class="mb-6 min-w-0">
            <p class="text-sm text-slate-500 mb-1">Submissão para o evento</p>
            <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 break-words [overflow-wrap:anywhere]">{{ $event->title }}</h1>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 overflow-hidden">
            <x-validation-errors class="mb-4" />

            <form action="{{ route('events.works.store', $event->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de trabalho</label>
                    <select name="work_type" required
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none">
                        <option value="">Selecione...</option>
                        @foreach($workTypes as $type)
                            <option value="{{ $type }}" @selected(old('work_type') === $type)>
                                {{ $workTypeLabels[$type] ?? $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Arquivo (PDF, DOC, DOCX ou ODT — até 10MB)</label>
                    <input type="file" name="file"
                           accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text"
                           required
                           class="w-full px-4 py-3 rounded-xl border-2 border-slate-200">
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <p class="text-sm text-slate-600 mb-3 m-0">
                        <strong>Autor principal:</strong> {{ auth()->user()->name }} ({{ auth()->user()->email }}).
                    </p>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Outros autores (opcional)</label>
                    <p class="text-xs text-slate-500 mb-3 m-0">Adicione coautores preenchendo os campos abaixo.</p>
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
                            <div class="coauthor-row grid grid-cols-1 sm:grid-cols-3 gap-2 p-3 rounded-xl border border-slate-100 bg-slate-50/50">
                                <input type="text" name="coauthors_manual[{{ $idx }}][author_name]"
                                       value="{{ $row['author_name'] ?? '' }}"
                                       placeholder="Nome"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                                <input type="email" name="coauthors_manual[{{ $idx }}][author_email]"
                                       value="{{ $row['author_email'] ?? '' }}"
                                       placeholder="E-mail (opcional)"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                                <input type="text" name="coauthors_manual[{{ $idx }}][institution]"
                                       value="{{ $row['institution'] ?? '' }}"
                                       placeholder="Instituição / curso"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-coauthor-row"
                            class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-800 bg-transparent border-0 cursor-pointer p-0">
                        <ion-icon name="add-circle-outline" class="text-lg"></ion-icon>
                        Adicionar autor
                    </button>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition-colors">
                        <ion-icon name="cloud-upload-outline"></ion-icon>
                        Submeter
                    </button>
                    <a href="/dashboard"
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold no-underline transition-colors">
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
    var inputClass = 'w-full px-3 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20';
    btn.addEventListener('click', function () {
        var rows = container.querySelectorAll('.coauthor-row');
        var idx = rows.length;
        var wrap = document.createElement('div');
        wrap.className = 'coauthor-row grid grid-cols-1 sm:grid-cols-3 gap-2 p-3 rounded-xl border border-slate-100 bg-slate-50/50';
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
