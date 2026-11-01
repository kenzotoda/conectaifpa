@extends('layouts.newMain')

@section('title', 'Assinaturas para certificados')

@section('content')
<div class="dash-page min-h-screen">
<div class="max-w-3xl mx-auto px-4 py-10 space-y-8">
    <h1 class="dash-title text-2xl font-bold m-0">Assinaturas</h1>
    <p class="dash-text text-sm m-0">Imagens usadas no rodapé dos certificados em PDF. Você pode cadastrar <strong>quantas assinaturas quiser</strong>; em cada evento, escolha quais entram na tela de certificados (mínimo uma para emitir).</p>

    @if (session('msg'))
        <div class="dash-badge-positive rounded-xl px-4 py-3 text-sm">{{ session('msg') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3 sm:gap-4">
        @isset($certificatesReturnEvent)
            <a href="{{ route('events.certificates.index', $certificatesReturnEvent) }}" class="dash-btn-soft inline-flex items-center gap-2 font-semibold text-sm no-underline rounded-xl px-4 py-2.5 transition-colors w-fit">
                <ion-icon name="arrow-back-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                Voltar aos certificados deste evento
            </a>
        @endisset
        <a href="{{ route('dashboard') }}" class="dash-btn-secondary inline-flex items-center gap-2 font-medium text-sm no-underline rounded-xl px-4 py-2.5 transition-colors w-fit">
            <ion-icon name="grid-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
            Painel
        </a>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50/90 text-amber-950 px-5 py-4 space-y-2">
        <p class="text-sm font-semibold m-0 flex items-center gap-2">
            <ion-icon name="information-circle-outline" class="text-xl shrink-0 text-amber-700"></ion-icon>
            Recomendações para ficar legível no certificado
        </p>
        <ul class="text-sm text-amber-950/90 m-0 pl-5 space-y-1.5 list-disc [overflow-wrap:anywhere]">
            <li>Use <strong>PNG com fundo transparente</strong> quando possível — combina melhor com o layout verde do PDF.</li>
            <li>Prefira só o <strong>traço da assinatura</strong> (recorte perto da assinatura). Evite foto da folha inteira: no PDF a altura máxima é <strong>≈ 64 px</strong>, então detalhes miúdos somem.</li>
            <li>Formatos aceitos: <strong>JPEG, PNG ou WebP</strong>, até <strong>2 MB</strong>.</li>
            <li>O layout do PDF reserva <strong>~42% da largura</strong> para cada assinatura na mesma linha. Com <strong>duas</strong>, ficam lado a lado; com mais, as seguintes passam para a linha de baixo (continua legível, mas evite muitas para não “apertar” o rodapé).</li>
        </ul>
        <p class="text-xs text-amber-900/75 m-0 mt-2 border-t border-amber-200/80 pt-2">
            A prévia abaixo usa a mesma altura máxima do PDF; o resultado final no arquivo pode variar levemente pelo motor de geração (DomPDF).
        </p>
    </div>

    <section class="dash-card rounded-2xl p-6 space-y-4">
        <h2 class="dash-title text-lg font-semibold m-0">Nova assinatura</h2>
        <form id="signature-form" action="{{ route('signatures.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-4">
            @csrf
            @isset($certificatesReturnEvent)
                <input type="hidden" name="return_event" value="{{ $certificatesReturnEvent->id }}">
            @endisset
            <div>
                <label class="dash-text block text-xs font-medium mb-1">Nome</label>
                <input type="text" id="sig-nome" name="nome" value="{{ old('nome') }}" required class="dash-field w-full rounded-xl px-3 py-2 text-sm" placeholder="Nome completo como no certificado">
            </div>
            <div>
                <label class="dash-text block text-xs font-medium mb-1">Cargo</label>
                <input type="text" id="sig-cargo" name="cargo" value="{{ old('cargo') }}" required placeholder="Coordenador do Evento" class="dash-field w-full rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="dash-text block text-xs font-medium mb-1">Imagem</label>
                <input type="file" id="sig-imagem" name="imagem" accept="image/jpeg,image/png,image/webp" required class="text-sm">
            </div>

            <div class="dash-panel rounded-xl p-4 space-y-2">
                <p class="dash-muted text-xs font-semibold uppercase tracking-wide m-0">Prévia — rodapé do certificado (aproximado)</p>
                <div class="dash-preview rounded-lg px-4 py-5 max-w-md mx-auto sm:mx-0">
                    <div class="border-t border-slate-200 pt-3 mt-8">
                        <p class="text-[10px] text-slate-400 m-0 mb-3">… texto do certificado acima …</p>
                    </div>
                    <div class="text-center pt-2 border-t border-slate-100">
                        <div class="inline-block max-w-[45%] align-top px-2">
                            <div class="min-h-[64px] flex items-end justify-center mb-1">
                                <img id="sig-preview-img" src="" alt="" class="hidden max-h-[64px] w-auto object-contain object-bottom mx-auto">
                                <span id="sig-preview-placeholder" class="text-xs text-slate-400 block py-4">Escolha uma imagem para ver aqui</span>
                            </div>
                            <p id="sig-preview-nome" class="font-bold text-slate-900 text-xs m-0 leading-tight">Nome</p>
                            <p id="sig-preview-cargo" class="text-slate-600 text-[11px] m-0 mt-0.5 leading-tight">Cargo</p>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="dash-btn inline-flex justify-center px-4 py-2.5 rounded-xl text-sm font-semibold w-fit">Cadastrar</button>
        </form>
    </section>

    <section class="dash-card rounded-2xl p-6">
        <h2 class="dash-title text-lg font-semibold m-0 mb-1">Cadastradas</h2>
        <p class="dash-muted text-xs m-0 mb-4">Miniatura com a mesma altura máxima usada no PDF (~64 px).</p>
        <ul class="divide-y divide-slate-100">
            @forelse($signatures as $sig)
                <li class="py-4 flex flex-col sm:flex-row gap-4 sm:items-start">
                    @if($sig->imagem_assinatura)
                        <div class="dash-panel shrink-0 rounded-lg px-3 py-2 inline-flex flex-col items-center justify-end min-w-[140px]">
                            <span class="text-[10px] text-slate-400 mb-1">No PDF</span>
                            <img src="{{ route('signatures.image', $sig) }}" alt="" class="max-h-[64px] w-auto object-contain object-bottom">
                        </div>
                    @endif
                    <div class="min-w-0 flex-1 sm:flex sm:items-start sm:justify-between sm:gap-4">
                        <div>
                            <p class="dash-title font-medium m-0">{{ $sig->nome }}</p>
                            <p class="dash-text text-sm m-0">{{ $sig->cargo }}</p>
                        </div>
                        <form action="{{ route('signatures.destroy', $sig) }}{{ isset($certificatesReturnEvent) ? '?event='.$certificatesReturnEvent->id : '' }}" method="POST" class="shrink-0 mt-3 sm:mt-0 cert-signature-delete-swal inline-block" data-swal-text="Esta assinatura será excluída do cadastro e desvinculada dos eventos. Certificados já emitidos no passado não são alterados.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-red-200 bg-white text-red-800 text-xs font-semibold hover:bg-red-50 transition-colors">
                                <ion-icon name="trash-outline" class="text-base" aria-hidden="true"></ion-icon>
                                Remover
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="dash-muted py-6 text-sm text-center">Nenhuma assinatura ainda.</li>
            @endforelse
        </ul>
        <div class="flex justify-center mt-4">{{ $signatures->links() }}</div>
    </section>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.cert-signature-delete-swal').forEach(function (form) {
        function onDeleteSubmit(e) {
            e.preventDefault();
            var text = form.getAttribute('data-swal-text') || 'Remover esta assinatura?';

            function submitForm() {
                form.removeEventListener('submit', onDeleteSubmit);
                form.submit();
            }

            if (typeof window.Swal === 'undefined') {
                if (window.confirm(text)) {
                    submitForm();
                }
                return;
            }

            window.Swal.fire({
                icon: 'warning',
                title: 'Remover assinatura?',
                text: text,
                showCancelButton: true,
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    submitForm();
                }
            });
        }

        form.addEventListener('submit', onDeleteSubmit);
    });
});
</script>
<script>
(function () {
    var fileInput = document.getElementById('sig-imagem');
    var nomeInput = document.getElementById('sig-nome');
    var cargoInput = document.getElementById('sig-cargo');
    var imgEl = document.getElementById('sig-preview-img');
    var placeholderEl = document.getElementById('sig-preview-placeholder');
    var nomeEl = document.getElementById('sig-preview-nome');
    var cargoEl = document.getElementById('sig-preview-cargo');
    var prevUrl = null;

    function setTexts() {
        nomeEl.textContent = (nomeInput && nomeInput.value.trim()) ? nomeInput.value.trim() : 'Nome';
        cargoEl.textContent = (cargoInput && cargoInput.value.trim()) ? cargoInput.value.trim() : 'Cargo';
    }

    if (fileInput && imgEl && placeholderEl) {
        fileInput.addEventListener('change', function () {
            if (prevUrl) {
                URL.revokeObjectURL(prevUrl);
                prevUrl = null;
            }
            var f = fileInput.files && fileInput.files[0];
            if (!f) {
                imgEl.classList.add('hidden');
                imgEl.removeAttribute('src');
                placeholderEl.classList.remove('hidden');
                return;
            }
            prevUrl = URL.createObjectURL(f);
            imgEl.src = prevUrl;
            imgEl.classList.remove('hidden');
            placeholderEl.classList.add('hidden');
        });
    }

    if (nomeInput) nomeInput.addEventListener('input', setTexts);
    if (cargoInput) cargoInput.addEventListener('input', setTexts);
    setTexts();
})();
</script>
@endpush
