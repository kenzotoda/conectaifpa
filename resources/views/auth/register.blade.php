@extends('layouts.auth')

@php
    $isCoordinatorRegister = request()->routeIs('register.coordinator');
    $isReviewerRegister = request()->routeIs('register.reviewer');
@endphp

@section('title', $isCoordinatorRegister ? 'Cadastrar Coordenador' : ($isReviewerRegister ? 'Cadastrar Avaliador' : 'Criar conta'))

@section('content')

{{-- Painel visual (esquerda) --}}
<div class="w-full min-w-0 lg:w-5/12 lg:shrink-0 auth-panel relative flex flex-col justify-center px-4 sm:px-6 md:px-8 py-10 sm:py-12 lg:py-14 xl:py-16 order-2 lg:order-1 min-h-[35vh] sm:min-h-[40vh] lg:min-h-[calc(100dvh-3.5rem)]">
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
                @if ($isCoordinatorRegister)
                    Crie sua conta de coordenador
                @elseif($isReviewerRegister)
                    Crie sua conta de avaliador
                @else
                    Faça parte da comunidade
                @endif
            </h1>
            <p class="text-white/90 text-base sm:text-lg max-w-sm">
                @if ($isCoordinatorRegister)
                    Cadastre-se como coordenador e organize eventos incríveis para sua comunidade.
                @elseif($isReviewerRegister)
                    Cadastre-se como avaliador e participe do processo científico dos eventos.
                @else
                    Cadastre-se e comece a participar dos eventos que vão transformar sua trajetória.
                @endif
            </p>
        </div>
    </div>
</div>

{{-- Painel do formulário — desktop: card largo + grelha compacta para evitar rolagem vertical --}}
<div class="w-full min-w-0 lg:w-7/12 lg:shrink-0 flex flex-col items-stretch justify-start lg:justify-center px-4 sm:px-6 lg:px-8 xl:px-10 py-6 sm:py-8 lg:py-4 order-1 lg:order-2 bg-slate-50/50 auth-safe-top auth-safe-bottom lg:min-h-[calc(100dvh-3.5rem)]">
    <div class="w-full max-w-xl min-w-0 mx-auto my-0 sm:my-1 lg:my-0 lg:max-w-none">
        <div class="glass-card rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl shadow-slate-200/50 border border-white/60 p-5 sm:p-6 lg:p-5 xl:p-6 min-w-0 w-full max-w-full [overflow-wrap:anywhere]">
            <div class="flex justify-center mb-5 sm:mb-6 lg:hidden">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-primary-custom rounded-lg sm:rounded-xl flex items-center justify-center">
                        <span class="font-outfit font-black text-base sm:text-lg text-white">C</span>
                    </div>
                    <span class="font-outfit font-bold text-lg sm:text-xl text-slate-800">ConectaIFPA</span>
                </div>
            </div>

            <h2 class="font-outfit font-bold text-xl sm:text-2xl lg:text-xl text-slate-800 mb-1 lg:leading-tight">
                @if ($isCoordinatorRegister)
                    Cadastrar Coordenador
                @elseif($isReviewerRegister)
                    Cadastrar Avaliador
                @else
                    Criar conta
                @endif
            </h2>
            <p class="text-slate-500 text-sm mb-4 sm:mb-5 lg:mb-3">Preencha os dados abaixo</p>

            <x-validation-errors class="mb-4 lg:mb-3" />

            <form method="POST"
                action="{{ $isCoordinatorRegister ? route('register.coordinator') : ($isReviewerRegister ? route('register.reviewer') : route('register')) }}"
                class="space-y-4 lg:space-y-3">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 sm:gap-x-5 sm:gap-y-4 lg:gap-x-5 lg:gap-y-3 min-w-0">
                    <div class="sm:col-span-2 md:col-span-1 min-w-0">
                        <label for="name" class="block font-outfit font-medium text-slate-700 mb-2 lg:mb-1.5 text-sm">Nome Completo</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')"
                            class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all text-base"
                            placeholder="Seu nome completo">
                    </div>

                    <div class="sm:col-span-2 md:col-span-1 min-w-0">
                        <label for="email" class="block font-outfit font-medium text-slate-700 mb-2 lg:mb-1.5 text-sm">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all text-base"
                            placeholder="seu@email.com">
                    </div>

                    @if (!$isCoordinatorRegister && !$isReviewerRegister)
                        <div class="sm:col-span-2 min-w-0 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 lg:py-2 lg:px-3">
                            <input type="hidden" name="is_external_participant" value="0">
                            <label class="flex items-start gap-2.5 cursor-pointer m-0 min-w-0 lg:items-center">
                                <input type="checkbox" name="is_external_participant" id="is_external_participant" value="1"
                                    class="mt-0.5 lg:mt-0 w-4 h-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    {{ in_array(old('is_external_participant'), ['1', 1, true], true) ? 'checked' : '' }}>
                                <span class="text-xs sm:text-sm text-slate-700 leading-snug min-w-0 flex-1 [overflow-wrap:anywhere]">
                                    <span class="font-semibold text-slate-800">Sou aluno(a) externo(a).</span><span class="text-slate-600"> Estudo em outra instituição (fora do campus Belém do IFPA).</span>
                                </span>
                            </label>
                        </div>

                        <div id="participant-ifpa-fields" class="sm:col-span-2 md:col-span-1 min-w-0">
                            <label for="matricula" class="block font-outfit font-medium text-slate-700 mb-2 lg:mb-1.5 text-sm">Matrícula (IFPA Belém)</label>
                            <input id="matricula" type="text" name="matricula" value="{{ old('matricula') }}" required maxlength="12" inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all text-base"
                                placeholder="12 dígitos">
                        </div>

                        <div id="participant-external-fields" class="sm:col-span-2 md:col-span-1 min-w-0 hidden">
                            <label for="institution" class="block font-outfit font-medium text-slate-700 mb-2 lg:mb-1.5 text-sm">Instituição de origem</label>
                            <input id="institution" type="text" name="institution" value="{{ old('institution') }}"
                                class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all text-base"
                                placeholder="Ex.: Universidade Federal do Pará">
                        </div>

                        <div class="sm:col-span-2 md:col-span-1 min-w-0">
                            <label for="curso" class="block font-outfit font-medium text-slate-700 mb-2 lg:mb-1.5 text-sm">Curso</label>
                            <input id="curso" type="text" name="curso" value="{{ old('curso') }}" required
                                oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')"
                                class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all text-base"
                                placeholder="Seu curso">
                        </div>
                    @endif

                    <div class="min-w-0">
                        <label for="password" class="block font-outfit font-medium text-slate-700 mb-2 lg:mb-1.5 text-sm">Senha</label>
                        <input id="password" type="password" name="password" required
                            class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all text-base"
                            placeholder="••••••••">
                    </div>

                    <div class="min-w-0">
                        <label for="password_confirmation" class="block font-outfit font-medium text-slate-700 mb-2 lg:mb-1.5 text-sm">Confirmar Senha</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all text-base"
                            placeholder="••••••••">
                    </div>
                </div>

                <input type="hidden" name="role" value="{{ $isCoordinatorRegister ? 'coordinator' : ($isReviewerRegister ? 'reviewer' : 'participant') }}">

                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <label class="flex items-start gap-3 cursor-pointer min-w-0">
                        <input type="checkbox" name="terms" required
                            class="mt-1 w-4 h-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm lg:text-xs text-slate-600 min-w-0 flex-1 leading-snug [overflow-wrap:anywhere] break-words">
                            {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline hover:text-emerald-600 text-emerald-600">'.__('Terms of Service').'</a>',
                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline hover:text-emerald-600 text-emerald-600">'.__('Privacy Policy').'</a>',
                            ]) !!}
                        </span>
                    </label>
                @endif

                <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:gap-x-4 sm:gap-y-3 lg:gap-y-2 min-w-0">
                    @if (!$isCoordinatorRegister && !$isReviewerRegister)
                        <a href="{{ route('login') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                            Já tem conta? Entrar
                        </a>
                    @else
                        <span class="hidden sm:block sm:flex-1 min-w-0"></span>
                    @endif
                    <button type="submit"
                        class="w-full sm:w-auto sm:shrink-0 px-8 py-3.5 lg:py-2.5 lg:px-6 rounded-xl bg-primary-custom hover:bg-[#0d5a1a] text-white font-outfit font-semibold text-base lg:text-sm transition-all duration-200 shadow-lg shadow-emerald-900/20 hover:shadow-xl hover:shadow-emerald-900/25 active:scale-[0.99]">
                        @if ($isCoordinatorRegister)
                            Cadastrar Coordenador
                        @elseif($isReviewerRegister)
                            Cadastrar Avaliador
                        @else
                            Criar conta
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(!$isCoordinatorRegister && !$isReviewerRegister)
<script>
(function () {
    var cb = document.getElementById('is_external_participant');
    var ifpa = document.getElementById('participant-ifpa-fields');
    var ext = document.getElementById('participant-external-fields');
    var mat = document.getElementById('matricula');
    var inst = document.getElementById('institution');
    var curso = document.getElementById('curso');
    if (!cb || !ifpa || !ext) return;
    function sync() {
        var external = cb.checked;
        ifpa.classList.toggle('hidden', external);
        ext.classList.toggle('hidden', !external);
        if (mat) {
            mat.disabled = external;
            if (external) {
                mat.removeAttribute('required');
                mat.value = '';
            } else {
                mat.setAttribute('required', 'required');
            }
        }
        if (inst) {
            inst.disabled = !external;
            if (external) {
                inst.setAttribute('required', 'required');
            } else {
                inst.removeAttribute('required');
                inst.value = '';
            }
        }
        if (curso) {
            curso.placeholder = external ? 'Seu curso na instituição de origem' : 'Seu curso no IFPA Belém';
        }
    }
    cb.addEventListener('change', sync);
    sync();
})();
</script>
@endif

@endsection
