@php
    $kindOptions = \App\Models\Event::annalsKindOptions();
    $fieldId = 'annals-'.$event->id;
@endphp
<div class="dash-panel rounded-xl p-4 flex flex-col gap-3">
    <p class="dash-muted text-[11px] font-semibold uppercase tracking-wider m-0">Anais ou e-book</p>
    @if($event->hasAnnalsPublication())
        <p class="dash-title text-sm m-0">
            Publicado: <strong>{{ $event->annalsKindLabel() }}</strong>
            @if($event->annals_uploaded_at)
                em {{ $event->annals_uploaded_at->format('d/m/Y') }} às {{ $event->annals_uploaded_at->format('H:i') }}
            @endif
        </p>
        <p class="dash-text text-xs m-0 break-words [overflow-wrap:anywhere]">{{ $event->annals_file_name }}</p>
        <a href="{{ route('events.annals.download', $event->id) }}"
           class="dash-link inline-flex items-center gap-2 text-sm font-semibold no-underline">
            <ion-icon name="download-outline" class="text-lg" aria-hidden="true"></ion-icon>
            Baixar PDF
        </a>
    @else
        <p class="dash-text text-sm m-0">Envie um PDF dos anais ou do e-book. Qualquer pessoa poderá baixar.</p>
    @endif

    <form action="{{ route('events.annals.store', $event->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3">
        @csrf
        <div>
            <label for="{{ $fieldId }}-kind" class="dash-text block text-xs font-semibold mb-1">Tipo</label>
            <select id="{{ $fieldId }}-kind" name="annals_kind" required data-app-select="native"
                    class="dash-field form-input w-full rounded-lg px-3 py-2.5 text-sm">
                @foreach($kindOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('annals_kind', $event->annals_kind) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="{{ $fieldId }}-file" class="dash-text block text-xs font-semibold mb-1">
                {{ $event->hasAnnalsPublication() ? 'Novo PDF' : 'Arquivo PDF' }}
            </label>
            <input id="{{ $fieldId }}-file" type="file" name="file" accept="application/pdf,.pdf" required
                   class="dash-field block w-full rounded-lg px-3 py-2 text-sm">
            <p class="dash-muted text-[11px] m-0 mt-1">Somente PDF, até 40 MB.</p>
        </div>
        <button type="submit"
                class="dash-btn inline-flex items-center justify-center gap-2 w-full min-h-[2.75rem] px-4 py-3 rounded-xl text-sm font-semibold">
            <ion-icon name="save-outline" class="text-lg" aria-hidden="true"></ion-icon>
            {{ $event->hasAnnalsPublication() ? 'Salvar novo arquivo' : 'Salvar publicação' }}
        </button>
    </form>

    @if($event->hasAnnalsPublication())
        <form action="{{ route('events.annals.destroy', $event->id) }}" method="POST"
              onsubmit="return confirm('Remover o PDF publicado? Ele deixa de ficar disponível para download.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full text-sm font-medium text-red-700 hover:text-red-900 py-1">
                Remover publicação
            </button>
        </form>
    @endif
</div>
