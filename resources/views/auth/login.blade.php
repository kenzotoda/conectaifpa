@extends('layouts.auth')

@section('title', 'Entrar')

@section('content')

{{-- Painel visual (esquerda) --}}
<div class="lg:w-1/2 auth-panel relative flex flex-col justify-center px-4 sm:px-6 md:px-8 py-10 sm:py-12 lg:py-0 order-2 lg:order-1 min-h-[35vh] sm:min-h-[40vh] lg:min-h-screen">
    {{-- Blobs decorativos --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-40 h-40 sm:w-56 sm:h-56 lg:w-64 lg:h-64 bg-white/10 blob float-slow"></div>
        <div class="absolute bottom-1/4 right-1/4 w-32 h-32 sm:w-40 sm:h-40 lg:w-48 lg:h-48 bg-white/15 blob float-slower"></div>
        <div class="absolute top-1/2 right-1/3 w-24 h-24 sm:w-28 sm:h-28 lg:w-32 lg:h-32 bg-white/5 blob float-reverse"></div>
    </div>

    <div class="relative z-10">
        <a href="/" class="inline-flex items-center gap-2 text-white/90 hover:text-white transition-colors mb-8 sm:mb-12 py-2 -mx-2 rounded-lg active:bg-white/10">
            <ion-icon name="arrow-back" class="text-lg sm:text-xl"></ion-icon>
            <span class="font-outfit font-medium text-sm sm:text-base">Voltar ao início</span>
        </a>

        <div class="max-w-md">
            <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                <div class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 bg-white/20 backdrop-blur-sm rounded-xl sm:rounded-2xl flex items-center justify-center flex-shrink-0">
                    <span class="font-outfit font-black text-xl sm:text-2xl text-white">C</span>
                </div>
                <span class="font-outfit font-bold text-2xl sm:text-3xl text-white">ConectaIFPA</span>
            </div>
            <h1 class="font-outfit font-bold text-2xl sm:text-3xl md:text-4xl lg:text-5xl text-white leading-tight mb-3 sm:mb-4">
                Conecte-se à comunidade
            </h1>
            <p class="text-white/90 text-base sm:text-lg max-w-sm">
                Acesse sua conta e participe dos eventos que transformam sua jornada acadêmica.
            </p>
        </div>
    </div>
</div>

{{-- Painel do formulário (direita) --}}
<div class="lg:w-1/2 flex items-start lg:items-center justify-center px-4 sm:px-6 py-6 sm:py-8 lg:py-0 order-1 lg:order-2 bg-slate-50/50 auth-safe-top auth-safe-bottom">
    <div class="w-full max-w-md">
        <div class="glass-card rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl shadow-slate-200/50 border border-white/60 p-5 sm:p-6 md:p-8 lg:p-10">
            <div class="flex justify-center mb-6 sm:mb-8 lg:hidden">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-primary-custom rounded-lg sm:rounded-xl flex items-center justify-center">
                        <span class="font-outfit font-black text-base sm:text-lg text-white">C</span>
                    </div>
                    <span class="font-outfit font-bold text-lg sm:text-xl text-slate-800">ConectaIFPA</span>
                </div>
            </div>

            <h2 class="font-outfit font-bold text-xl sm:text-2xl text-slate-800 mb-1 sm:mb-2">Entrar</h2>
            <p class="text-slate-500 text-sm mb-4 sm:mb-6">Preencha seus dados para acessar</p>

            <x-validation-errors class="mb-4" />

            @session('status')
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-medium">
                    {{ $value }}
                </div>
            @endsession

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block font-outfit font-medium text-slate-700 mb-2">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all"
                        placeholder="seu@email.com">
                </div>

                <div>
                    <label for="password" class="block font-outfit font-medium text-slate-700 mb-2">Senha</label>
                    <input id="password" type="password" name="password" required
                        class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all"
                        placeholder="••••••••">
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <label class="flex items-center gap-2 cursor-pointer min-h-[44px]">
                        <input type="checkbox" name="remember"
                            class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-slate-600">Lembrar de mim</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors py-2">
                            Esqueceu a senha?
                        </a>
                    @endif
                </div>

                <button type="submit"
                    class="w-full py-3.5 rounded-xl bg-primary-custom hover:bg-[#0d5a1a] text-white font-outfit font-semibold text-base transition-all duration-200 shadow-lg shadow-emerald-900/20 hover:shadow-xl hover:shadow-emerald-900/25 active:scale-[0.99]">
                    Entrar
                </button>
            </form>

            <p class="mt-6 text-center text-slate-600 text-sm">
                Não tem conta?
                <a href="{{ route('register') }}" class="font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">Criar conta</a>
            </p>
        </div>
    </div>
</div>

@endsection
