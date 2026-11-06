@extends('layouts.auth')

@section('title', 'Conta ativada')

@section('content')

<div class="lg:w-1/2 auth-panel relative flex flex-col justify-center px-4 sm:px-6 md:px-8 py-10 sm:py-12 lg:py-0 order-2 lg:order-1 min-h-[35vh] sm:min-h-[40vh] lg:min-h-[calc(100dvh-3.5rem)]">
    <div class="relative z-10 max-w-md">
        <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
            @include('partials.brand-mark', ['class' => 'w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14'])
            <span class="font-outfit font-bold text-2xl sm:text-3xl text-white">ConectaIFPA</span>
        </div>
        <h1 class="font-outfit font-bold text-2xl sm:text-3xl md:text-4xl text-white leading-tight mb-3">
            Conta ativada
        </h1>
        <p class="text-white/90 text-base sm:text-lg max-w-sm">
            O e-mail foi confirmado. Já pode usar o ConectaIFPA.
        </p>
    </div>
</div>

<div class="lg:w-1/2 flex items-start lg:items-center justify-center px-4 sm:px-6 py-6 sm:py-8 lg:py-0 order-1 lg:order-2 auth-safe-top auth-safe-bottom lg:min-h-[calc(100dvh-3.5rem)]">
    <div class="w-full max-w-md">
        <div class="glass-card rounded-2xl sm:rounded-3xl shadow-xl border border-white/60 p-5 sm:p-8 lg:p-10">
            <h2 class="font-outfit font-bold text-xl sm:text-2xl dash-title mb-2">Tudo certo</h2>
            @if ($continuesHere)
                <p class="dash-text text-sm mb-6 leading-relaxed">
                    Esta sessão já está autenticada. Siga para o painel.
                </p>
                <a href="{{ route('dashboard') }}" class="w-full inline-flex items-center justify-center py-3.5 rounded-xl dash-btn font-outfit font-semibold text-base no-underline">
                    Continuar
                </a>
            @else
                <p class="dash-text text-sm mb-6 leading-relaxed">
                    Volte para a tela em que a conta foi criada. Ela percebe a ativação e abre o acesso sozinha. Se ainda estiver parada, use o botão “Já ativei”.
                </p>
                <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center py-3.5 rounded-xl dash-btn-secondary font-outfit font-semibold text-base no-underline">
                    Entrar neste aparelho
                </a>
            @endif
        </div>
    </div>
</div>

@endsection
