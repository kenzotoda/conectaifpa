@extends('layouts.newMain')

@section('title', 'Contas criadas')

@section('content')
<div class="dash-page min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="dash-title font-montserrat font-bold text-2xl sm:text-3xl">Contas criadas</h1>
                <p class="dash-text text-sm mt-1">Convidados, organizadores, avaliadores e coordenadores criados por você.</p>
            </div>
            <a href="/dashboard" class="dash-btn-secondary inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold no-underline">Voltar ao painel</a>
        </div>

        <div class="flex flex-wrap gap-2 mb-8">
            <a href="{{ route('register.guest') }}" class="dash-btn-soft inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium no-underline">Novo convidado</a>
            <a href="{{ route('register.organizer') }}" class="dash-btn-soft inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium no-underline">Novo organizador</a>
            <a href="{{ route('register.reviewer') }}" class="dash-btn-soft inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium no-underline">Novo avaliador</a>
            <a href="{{ route('register.coordinator') }}" class="dash-btn-soft inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium no-underline">Novo coordenador</a>
        </div>

        @foreach ($groups as $role => $label)
            <section class="mb-8">
                <h2 class="dash-title font-semibold text-lg mb-3">{{ $label }}</h2>
                @php $rows = $accounts->get($role, collect()); @endphp
                @if ($rows->isEmpty())
                    <p class="dash-muted text-sm m-0">Nenhuma conta ainda.</p>
                @else
                    <div class="dash-card rounded-2xl divide-y divide-slate-100">
                        @foreach ($rows as $account)
                            <div class="px-4 py-3 sm:px-5">
                                <p class="dash-title font-medium m-0">{{ $account->name }}</p>
                                <p class="dash-muted text-sm m-0 break-all">{{ $account->email }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
@endsection
