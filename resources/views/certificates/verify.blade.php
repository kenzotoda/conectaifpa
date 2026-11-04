@extends('layouts.newMain')

@section('title', 'Validar certificado')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 space-y-4 text-center">
        <h1 class="text-xl font-bold text-slate-900 m-0">Certificado válido</h1>
        <p class="text-slate-600 text-sm m-0">Código: <span class="font-mono font-semibold">{{ $certificate->codigo_validacao }}</span></p>
        <div class="border-t border-slate-100 pt-4 mt-4 flex flex-col sm:flex-row gap-6 text-left">
            <dl class="text-sm space-y-2 flex-1 min-w-0">
                <div><dt class="text-slate-500 inline">Nome:</dt> <dd class="inline font-medium text-slate-900">{{ $certificate->recipientDisplayName() }}</dd></div>
                <div><dt class="text-slate-500 inline">Tipo:</dt> <dd class="inline text-slate-800">{{ \App\Models\Certificate::typeLabel($certificate->tipo) }}</dd></div>
                @if(filled($certificate->recipient_role))
                    <div><dt class="text-slate-500 inline">Função:</dt> <dd class="inline text-slate-800">{{ $certificate->recipient_role }}</dd></div>
                @endif
                <div><dt class="text-slate-500 inline">Evento:</dt> <dd class="inline text-slate-800">{{ $certificate->event->title }}</dd></div>
                @if($certificate->activity_id && $certificate->activity)
                    <div><dt class="text-slate-500 inline">Atividade:</dt> <dd class="inline text-slate-800">{{ $certificate->activity->title }}</dd></div>
                    @if(!empty($sheet['activityPeriod']))
                        <div><dt class="text-slate-500 inline">Período da atividade:</dt> <dd class="inline text-slate-800">{{ $sheet['activityPeriod'] }}</dd></div>
                    @endif
                @endif
                @if($certificate->work_id && $certificate->work)
                    <div>
                        <dt class="text-slate-500 m-0">Trabalho</dt>
                        <dd class="m-0 mt-1 font-medium text-slate-900 whitespace-pre-line [overflow-wrap:anywhere] text-left">{{ $certificate->work->displayTitleForPresentationCertificate() }}</dd>
                    </div>
                @endif
                @if($sheet['hours'] !== '-')
                    <div><dt class="text-slate-500 inline">Carga horária:</dt> <dd class="inline text-slate-800">{{ $sheet['hours'] }} hora(s)</dd></div>
                @endif
                <div><dt class="text-slate-500 inline">Período:</dt> <dd class="inline text-slate-800">{{ $sheet['period'] }}</dd></div>
                <div><dt class="text-slate-500 inline">Local:</dt> <dd class="inline text-slate-800">{{ $sheet['place'] }}</dd></div>
                <div><dt class="text-slate-500 inline">Modalidade:</dt> <dd class="inline text-slate-800">{{ $sheet['modality'] }}</dd></div>
                @if(!empty($sheet['matricula']))
                    <div><dt class="text-slate-500 inline">Matrícula:</dt> <dd class="inline text-slate-800">{{ $sheet['matricula'] }}</dd></div>
                @endif
                <div><dt class="text-slate-500 inline">Presença:</dt> <dd class="inline text-slate-800">{{ $sheet['presence'] }}</dd></div>
                <div><dt class="text-slate-500 inline">Emitido em:</dt> <dd class="inline text-slate-800">{{ $certificate->data_emissao->format('d/m/Y') }}</dd></div>
            </dl>
            <div class="shrink-0 text-center">
                <img src="{{ $sheet['qrDataUri'] }}" width="120" height="120" alt="QR para validar este certificado" class="mx-auto">
                <p class="text-xs text-slate-500 mt-2 m-0">Aponte a câmera para abrir esta página.</p>
            </div>
        </div>
        <p class="m-0 pt-4 border-t border-slate-100">
            <a href="{{ route('certificates.lookup') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800 no-underline">
                Validar outro certificado
            </a>
        </p>
    </div>
</div>
@endsection
