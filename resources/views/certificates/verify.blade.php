@extends('layouts.newMain')

@section('title', 'Validar certificado')

@section('content')
<div class="max-w-xl mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 space-y-4 text-center">
        <h1 class="text-xl font-bold text-slate-900 m-0">Certificado válido</h1>
        <p class="text-slate-600 text-sm m-0">Código: <span class="font-mono font-semibold">{{ $certificate->codigo_validacao }}</span></p>
        <dl class="text-left text-sm space-y-2 border-t border-slate-100 pt-4 mt-4">
            <div><dt class="text-slate-500 inline">Nome:</dt> <dd class="inline font-medium text-slate-900">{{ $certificate->user->name }}</dd></div>
            <div><dt class="text-slate-500 inline">Tipo:</dt> <dd class="inline text-slate-800">{{ \App\Models\Certificate::typeLabel($certificate->tipo) }}</dd></div>
            <div><dt class="text-slate-500 inline">Evento:</dt> <dd class="inline text-slate-800">{{ $certificate->event->title }}</dd></div>
            @if($certificate->activity_id && $certificate->activity)
                <div><dt class="text-slate-500 inline">Atividade:</dt> <dd class="inline text-slate-800">{{ $certificate->activity->title }}</dd></div>
            @endif
            @if($certificate->work_id && $certificate->work)
                <div>
                    <dt class="text-slate-500 m-0">Trabalho</dt>
                    <dd class="m-0 mt-1 font-medium text-slate-900 whitespace-pre-line [overflow-wrap:anywhere] text-left">{{ $certificate->work->displayTitleForPresentationCertificate() }}</dd>
                </div>
            @endif
            <div><dt class="text-slate-500 inline">Emitido em:</dt> <dd class="inline text-slate-800">{{ $certificate->data_emissao->format('d/m/Y') }}</dd></div>
        </dl>
        <p class="m-0 pt-4 border-t border-slate-100">
            <a href="{{ route('certificates.lookup') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800 no-underline">
                Validar outro certificado
            </a>
        </p>
    </div>
</div>
@endsection
