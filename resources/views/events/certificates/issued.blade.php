@extends('layouts.newMain')

@section('title', 'Certificados emitidos — '.$event->title)

@section('content')
<div class="dash-page min-h-screen">
<div class="max-w-6xl mx-auto px-4 py-10 space-y-6">
    <p class="text-sm m-0"><a href="{{ route('events.certificates.index', $event) }}" class="dash-link no-underline">← Voltar</a></p>
    <h1 class="dash-title text-2xl font-bold m-0">Certificados emitidos</h1>

    <div class="dash-card dash-table overflow-x-auto rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Data</th>
                    <th class="px-4 py-3 font-medium">Participante</th>
                    <th class="px-4 py-3 font-medium">Tipo</th>
                    <th class="px-4 py-3 font-medium">Código</th>
                    <th class="px-4 py-3 font-medium text-right">PDF</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $c)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2 whitespace-nowrap">{{ $c->data_emissao->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $c->user->name }}</td>
                        <td class="px-4 py-2">{{ \App\Models\Certificate::typeLabel($c->tipo) }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $c->codigo_validacao }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('certificates.download', $c) }}" class="dash-link font-medium no-underline">Baixar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Nenhum certificado emitido ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">{{ $certificates->links() }}</div>
</div>
</div>
@endsection
