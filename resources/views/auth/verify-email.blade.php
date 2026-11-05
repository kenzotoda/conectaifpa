@extends('layouts.auth')

@section('title', 'Confirme seu e-mail')

@section('content')

<div class="lg:w-1/2 auth-panel relative flex flex-col justify-center px-4 sm:px-6 md:px-8 py-10 sm:py-12 lg:py-0 order-2 lg:order-1 min-h-[35vh] sm:min-h-[40vh] lg:min-h-[calc(100dvh-3.5rem)]">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-40 h-40 sm:w-56 sm:h-56 lg:w-64 lg:h-64 bg-white/10 blob float-slow"></div>
        <div class="absolute bottom-1/4 right-1/4 w-32 h-32 sm:w-40 sm:h-40 lg:w-48 lg:h-48 bg-white/15 blob float-slower"></div>
        <div class="absolute top-1/2 right-1/3 w-24 h-24 sm:w-28 sm:h-28 lg:w-32 lg:h-32 bg-white/5 blob float-reverse"></div>
    </div>

    <div class="relative z-10">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-white/90 hover:text-white transition-colors mb-8 sm:mb-12 py-2 -mx-2 rounded-lg active:bg-white/10">
            <ion-icon name="arrow-back" class="text-lg sm:text-xl"></ion-icon>
            <span class="font-outfit font-medium text-sm sm:text-base">Voltar ao início</span>
        </a>

        <div class="max-w-md">
            <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                @include('partials.brand-mark', ['class' => 'w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14'])
                <span class="font-outfit font-bold text-2xl sm:text-3xl text-white">ConectaIFPA</span>
            </div>
            <h1 class="font-outfit font-bold text-2xl sm:text-3xl md:text-4xl lg:text-5xl text-white leading-tight mb-3 sm:mb-4">
                Falta confirmar o e-mail
            </h1>
            <p class="text-white/90 text-base sm:text-lg max-w-sm">
                Enviamos um link de confirmação. Sem esse passo, a conta fica parada nesta tela.
            </p>
        </div>
    </div>
</div>

<div class="lg:w-1/2 flex items-start lg:items-center justify-center px-4 sm:px-6 py-6 sm:py-8 lg:py-0 order-1 lg:order-2 auth-safe-top auth-safe-bottom lg:min-h-[calc(100dvh-3.5rem)]">
    <div class="w-full max-w-md">
        <div class="glass-card rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl shadow-slate-200/50 border border-white/60 p-5 sm:p-6 md:p-8 lg:p-10">
            <div class="flex justify-center mb-6 sm:mb-8 lg:hidden">
                <div class="flex items-center gap-2">
                    @include('partials.brand-mark', ['class' => 'w-9 h-9 sm:w-10 sm:h-10'])
                    <span class="font-outfit font-bold text-lg sm:text-xl dash-title">ConectaIFPA</span>
                </div>
            </div>

            <h2 class="font-outfit font-bold text-xl sm:text-2xl dash-title mb-1 sm:mb-2">Confirme seu e-mail</h2>
            <p class="dash-text text-sm mb-4 sm:mb-6 leading-relaxed">
                O link foi enviado para
                <strong class="dash-title">{{ auth()->user()->email }}</strong>.
                Abra essa caixa, inclusive o spam, e procure uma mensagem de
                <strong class="dash-title">{{ config('mail.from.address') }}</strong>
                com o assunto “Confirme seu e-mail no ConectaIFPA”.
            </p>

            @if (session('status') === 'verification-link-sent')
                <div class="dash-badge-positive mb-4 p-3 rounded-xl text-sm font-medium" role="status">
                    Enviamos outro link para {{ auth()->user()->email }}. Se não aparecer em alguns minutos, olhe o spam.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="space-y-3">
                @csrf
                <button type="submit" class="w-full py-3.5 rounded-xl dash-btn font-outfit font-semibold text-base">
                    Reenviar e-mail de confirmação
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full py-3.5 rounded-xl dash-btn-secondary font-outfit font-semibold text-base">
                    Sair da conta
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
