@extends('layouts.newMain')

@section('title', 'Pesquisa de satisfação: '.$event->title)

@section('content')
<div class="dash-page min-h-screen">
<div class="max-w-xl mx-auto px-4 py-10 space-y-6">
    <p class="text-sm m-0"><a href="{{ route('dashboard') }}" class="dash-link no-underline">← Meus eventos</a></p>
    <div>
        <h1 class="dash-title text-2xl font-bold m-0">Pesquisa de satisfação</h1>
        <p class="dash-text text-sm mt-2 m-0">{{ $event->title }}</p>
        <p class="dash-muted text-sm mt-2 m-0">Quatro notas de 1 a 5. Esta pesquisa é do evento, separada do parecer dos trabalhos.</p>
    </div>

    @if (session('msg'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-950 px-4 py-3 text-sm">{{ session('msg') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <p class="m-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if($response)
        <div class="dash-card rounded-2xl p-6 space-y-3">
            <p class="font-semibold m-0">Resposta registrada</p>
            @foreach($questions as $field => $label)
                <p class="text-sm m-0"><span class="text-slate-500">{{ $label }}:</span> {{ $response->{$field} }} de 5</p>
            @endforeach
            @if(filled($response->comment))
                <p class="text-sm m-0 whitespace-pre-line">{{ $response->comment }}</p>
            @endif
        </div>
    @else
        <form method="POST" action="{{ route('events.satisfaction.store', $event) }}" class="dash-card rounded-2xl p-6 space-y-5">
            @csrf
            @foreach($questions as $field => $label)
                <fieldset>
                    <legend class="font-semibold text-slate-900 mb-2">{{ $label }}</legend>
                    <div class="flex flex-wrap gap-3">
                        @for($score = 1; $score <= 5; $score++)
                            <label class="inline-flex items-center gap-1.5 text-sm">
                                <input type="radio" name="{{ $field }}" value="{{ $score }}" @checked((int) old($field) === $score) required>
                                {{ $score }}
                            </label>
                        @endfor
                    </div>
                </fieldset>
            @endforeach
            <label class="block">
                <span class="font-semibold text-slate-900">Comentário</span>
                <textarea name="comment" rows="4" maxlength="2000" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('comment') }}</textarea>
            </label>
            <button type="submit" class="dash-btn px-4 py-2.5 rounded-xl text-sm font-semibold">Enviar pesquisa</button>
        </form>
    @endif
</div>
</div>
@endsection
