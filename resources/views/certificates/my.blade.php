@extends('layouts.newMain')

@section('title', 'Meus certificados')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 space-y-6">
    <h1 class="text-2xl font-bold text-slate-900 m-0">Meus certificados</h1>
    <p class="text-slate-600 text-sm m-0">PDFs emitidos pelo coordenador. Validação pública pelo código.</p>

    <div class="space-y-3">
        @forelse($certificates as $c)
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="font-semibold text-slate-900 m-0">{{ \App\Models\Certificate::typeLabel($c->tipo) }}</p>
                    <p class="text-sm text-slate-600 m-0 mt-1">{{ $c->event->title }}</p>
                    <p class="text-xs text-slate-500 m-0 mt-1 font-mono">{{ $c->codigo_validacao }} · {{ $c->data_emissao->format('d/m/Y') }}</p>
                    <p class="text-xs m-0 mt-1"><a href="{{ route('certificates.verify', ['codigo' => $c->codigo_validacao]) }}" class="text-emerald-700 no-underline hover:underline" target="_blank" rel="noopener">Abrir página de validação</a></p>
                </div>
                <a href="{{ route('certificates.download', $c) }}" class="inline-flex justify-center items-center px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold no-underline hover:bg-emerald-700 shrink-0">Baixar PDF</a>
            </div>
        @empty
            <p class="text-slate-600 text-sm">Nenhum certificado disponível ainda.</p>
        @endforelse
    </div>

    <div class="flex justify-center">{{ $certificates->links() }}</div>
</div>
@endsection
