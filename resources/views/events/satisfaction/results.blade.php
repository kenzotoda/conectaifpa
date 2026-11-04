@extends('layouts.newMain')

@section('title', 'Pesquisa de satisfação: '.$event->title)

@section('content')
<div class="dash-page min-h-screen">
<div class="max-w-3xl mx-auto px-4 py-10 space-y-6">
    <p class="text-sm m-0"><a href="{{ route('dashboard') }}" class="dash-link no-underline">← Meus eventos</a></p>
    <div>
        <h1 class="dash-title text-2xl font-bold m-0">Pesquisa de satisfação</h1>
        <p class="dash-text text-sm mt-2 m-0">{{ $event->title }}</p>
        <p class="dash-muted text-sm mt-2 m-0">Médias de 1 a 5. Os comentários aparecem sem o nome de quem escreveu. Esta tela não é o parecer dos trabalhos.</p>
    </div>

    @if (session('msg'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-950 px-4 py-3 text-sm">{{ session('msg') }}</div>
    @endif

    <div class="dash-card rounded-2xl p-6 space-y-3">
        <p class="m-0 text-sm">Respostas: <strong>{{ $count }}</strong> de {{ $enrolled }} participante(s) inscrito(s).</p>
        @foreach($questions as $field => $label)
            <p class="m-0 text-sm"><span class="text-slate-500">{{ $label }}:</span> {{ $averages[$field] ?? '-' }}</p>
        @endforeach
    </div>

    <div class="dash-card rounded-2xl p-6 space-y-3">
        <h2 class="text-lg font-bold m-0">Comentários</h2>
        @forelse($comments as $comment)
            <p class="text-sm m-0 whitespace-pre-line border-t border-slate-100 pt-3">{{ $comment }}</p>
        @empty
            <p class="text-sm text-slate-500 m-0">Nenhum comentário ainda.</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('events.satisfaction.invite', $event) }}">
        @csrf
        @if($event->satisfaction_invited_at)
            <p class="text-sm text-slate-600 m-0">Convite enviado em {{ $event->satisfaction_invited_at->format('d/m/Y H:i') }}.</p>
        @else
            <button type="submit" class="dash-btn px-4 py-2.5 rounded-xl text-sm font-semibold">Enviar convite aos inscritos</button>
        @endif
    </form>
</div>
</div>
@endsection
