@extends('layouts.auth')

@section('title', request()->routeIs('register.coordinator') ? 'Cadastrar Coordenador' : 'Criar conta')

@section('content')

{{-- Painel visual (esquerda) --}}
<div class="lg:w-1/2 auth-panel relative flex flex-col justify-center px-4 sm:px-6 md:px-8 py-10 sm:py-12 lg:py-0 order-2 lg:order-1 min-h-[35vh] sm:min-h-[40vh] lg:min-h-full">
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
                @if (request()->routeIs('register.coordinator'))
                    Crie sua conta de coordenador
                @else
                    Faça parte da comunidade
                @endif
            </h1>
            <p class="text-white/90 text-base sm:text-lg max-w-sm">
                @if (request()->routeIs('register.coordinator'))
                    Cadastre-se como coordenador e organize eventos incríveis para sua comunidade.
                @else
                    Cadastre-se e comece a participar dos eventos que vão transformar sua trajetória.
                @endif
            </p>
        </div>
    </div>
</div>

{{-- Painel do formulário (direita) --}}
<div class="lg:w-1/2 flex items-start lg:items-center justify-center px-4 sm:px-6 py-6 sm:py-8 lg:py-0 order-1 lg:order-2 bg-slate-50/50 auth-safe-top auth-safe-bottom">
    <div class="w-full max-w-2xl my-4 sm:my-6">
        <div class="glass-card rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl shadow-slate-200/50 border border-white/60 p-5 sm:p-6 lg:p-8">
            <div class="flex justify-center mb-5 sm:mb-6 lg:hidden">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-primary-custom rounded-lg sm:rounded-xl flex items-center justify-center">
                        <span class="font-outfit font-black text-base sm:text-lg text-white">C</span>
                    </div>
                    <span class="font-outfit font-bold text-lg sm:text-xl text-slate-800">ConectaIFPA</span>
                </div>
            </div>

            <h2 class="font-outfit font-bold text-xl sm:text-2xl text-slate-800 mb-1">
                @if (request()->routeIs('register.coordinator'))
                    Cadastrar Coordenador
                @else
                    Criar conta
                @endif
            </h2>
            <p class="text-slate-500 text-sm mb-4 sm:mb-5">Preencha os dados abaixo</p>

            <x-validation-errors class="mb-4" />

            <form method="POST"
                action="{{ request()->routeIs('register.coordinator') ? route('register.coordinator') : route('register') }}"
                class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div class="sm:col-span-2">
                        <label for="name" class="block font-outfit font-medium text-slate-700 mb-2">Nome Completo</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')"
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all"
                            placeholder="Seu nome completo">
                    </div>

                    <div>
                        <label for="email" class="block font-outfit font-medium text-slate-700 mb-2">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all"
                            placeholder="seu@email.com">
                    </div>

                    @if (!request()->routeIs('register.coordinator'))
                        <div>
                            <label for="matricula" class="block font-outfit font-medium text-slate-700 mb-2">Matrícula</label>
                            <input id="matricula" type="text" name="matricula" value="{{ old('matricula') }}" required maxlength="12" inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all"
                                placeholder="Sua matrícula">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="curso" class="block font-outfit font-medium text-slate-700 mb-2">Curso</label>
                            <input id="curso" type="text" name="curso" value="{{ old('curso') }}" required
                                oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')"
                                class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all"
                                placeholder="Seu curso">
                        </div>
                    @else
                        <div class="sm:col-span-2"></div>
                    @endif

                    <div>
                        <label for="password" class="block font-outfit font-medium text-slate-700 mb-2">Senha</label>
                        <input id="password" type="password" name="password" required
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all"
                            placeholder="••••••••">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block font-outfit font-medium text-slate-700 mb-2">Confirmar Senha</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all"
                            placeholder="••••••••">
                    </div>
                </div>

                <input type="hidden" name="role" value="{{ request()->routeIs('register.coordinator') ? 'coordinator' : 'participant' }}">

                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="terms" required
                            class="mt-1 w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-slate-600">
                            {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline hover:text-emerald-600 text-emerald-600">'.__('Terms of Service').'</a>',
                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline hover:text-emerald-600 text-emerald-600">'.__('Privacy Policy').'</a>',
                            ]) !!}
                        </span>
                    </label>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 pt-1">
                    @if (!request()->routeIs('register.coordinator'))
                        <a href="{{ route('login') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                            Já tem conta? Entrar
                        </a>
                    @else
                        <span></span>
                    @endif
                    <button type="submit"
                        class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-primary-custom hover:bg-[#0d5a1a] text-white font-outfit font-semibold text-base transition-all duration-200 shadow-lg shadow-emerald-900/20 hover:shadow-xl hover:shadow-emerald-900/25 active:scale-[0.99]">
                        @if (request()->routeIs('register.coordinator'))
                            Cadastrar Coordenador
                        @else
                            Criar conta
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
