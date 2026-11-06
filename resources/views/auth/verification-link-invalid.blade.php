@extends('layouts.auth')

@section('title', 'Link de confirmação inválido')

@section('content')

<div class="lg:w-1/2 auth-panel relative flex flex-col justify-center px-4 sm:px-6 md:px-8 py-10 sm:py-12 lg:py-0 order-2 lg:order-1 min-h-[35vh] sm:min-h-[40vh] lg:min-h-[calc(100dvh-3.5rem)]">
    <div class="relative z-10">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-white/90 hover:text-white transition-colors mb-8 sm:mb-12 py-2 -mx-2 rounded-lg active:bg-white/10">
            <ion-icon name="arrow-back" class="text-lg sm:text-xl"></ion-icon>
            <span class="font-outfit font-medium text-sm sm:text-base">Voltar ao início</span>
        </a>

        <div class="max-w-md">
            <h1 class="font-outfit font-bold text-2xl sm:text-3xl md:text-4xl text-white leading-tight mb-3 sm:mb-4">
                Esse link não confirmou a conta
            </h1>
            <p class="text-white/90 text-base sm:text-lg max-w-sm">
                Ele pode ter passado de 60 minutos ou ter sido alterado no caminho.
            </p>
        </div>
    </div>
</div>

<div class="lg:w-1/2 flex items-start lg:items-center justify-center px-4 sm:px-6 py-6 sm:py-8 lg:py-0 order-1 lg:order-2 auth-safe-top auth-safe-bottom lg:min-h-[calc(100dvh-3.5rem)]">
    <div class="w-full max-w-md">
        <div class="glass-card rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl shadow-slate-200/50 border border-white/60 p-5 sm:p-6 md:p-8 lg:p-10">
            <h2 class="font-outfit font-bold text-xl sm:text-2xl dash-title mb-2">Peça outro link</h2>
            <p class="dash-text text-sm mb-6 leading-relaxed">
                Entre na conta e use “Reenviar e-mail de confirmação”. O link novo vale por 60 minutos.
            </p>

            @auth
                <a href="{{ route('verification.notice') }}" class="w-full inline-flex items-center justify-center py-3.5 rounded-xl dash-btn font-outfit font-semibold text-base no-underline">
                    Ir para a confirmação
                </a>
            @else
                <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center py-3.5 rounded-xl dash-btn font-outfit font-semibold text-base no-underline">
                    Entrar para pedir outro link
                </a>
            @endauth
        </div>
    </div>
</div>

@endsection
