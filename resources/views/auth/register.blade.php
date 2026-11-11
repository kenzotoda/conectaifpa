@extends('layouts.auth')

@php
    $panelAccounts = [
        'register.coordinator' => ['role' => 'coordinator', 'title' => 'Cadastrar Coordenador', 'headline' => 'Crie sua conta de coordenador', 'text' => 'Cadastre-se como coordenador e organize eventos incríveis para sua comunidade.', 'button' => 'Cadastrar Coordenador'],
        'register.reviewer' => ['role' => 'reviewer', 'title' => 'Cadastrar Avaliador', 'headline' => 'Crie sua conta de avaliador', 'text' => 'Cadastre-se como avaliador e participe do processo científico dos eventos.', 'button' => 'Cadastrar Avaliador'],
        'register.guest' => ['role' => 'guest', 'title' => 'Cadastrar Convidado', 'headline' => 'Crie a conta de convidado', 'text' => 'Esta conta vê apenas os eventos em que for vinculada como convidado e os próprios certificados.', 'button' => 'Cadastrar Convidado'],
        'register.organizer' => ['role' => 'organizer', 'title' => 'Cadastrar Organizador', 'headline' => 'Crie a conta de organizador', 'text' => 'Depois de ativar o e-mail, esta conta gerencia somente os eventos em que for designada como organizador. Ela não cria outros eventos.', 'button' => 'Cadastrar Organizador'],
    ];
    $panelAccount = null;
    foreach ($panelAccounts as $routeName => $account) {
        if (request()->routeIs($routeName)) {
            $panelAccount = $account;
            break;
        }
    }
@endphp

@section('title', $panelAccount['title'] ?? 'Criar conta')

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
                @include('partials.brand-mark', ['class' => 'w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14'])
                <span class="font-outfit font-bold text-2xl sm:text-3xl text-white">ConectaIFPA</span>
            </div>
            <h1 class="font-outfit font-bold text-2xl sm:text-3xl md:text-4xl lg:text-5xl text-white leading-tight mb-3 sm:mb-4">
                {{ $panelAccount['headline'] ?? 'Faça parte da comunidade' }}
            </h1>
            <p class="text-white/90 text-base sm:text-lg max-w-sm">
                {{ $panelAccount['text'] ?? 'Cadastre-se e comece a participar dos eventos que vão transformar sua trajetória.' }}
            </p>
        </div>
    </div>
</div>

{{-- Painel do formulário. No desktop: card largo e grelha compacta para evitar rolagem vertical. --}}
<div class="w-full min-w-0 lg:w-7/12 lg:shrink-0 flex flex-col items-stretch justify-start lg:justify-center px-4 sm:px-6 lg:px-8 xl:px-10 py-6 sm:py-8 lg:py-4 order-1 lg:order-2  auth-safe-top auth-safe-bottom lg:min-h-[calc(100dvh-3.5rem)]">
    <div class="w-full max-w-xl min-w-0 mx-auto my-0 sm:my-1 lg:my-0 lg:max-w-none">
        <div class="glass-card rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl shadow-slate-200/50 border border-white/60 p-5 sm:p-6 lg:p-5 xl:p-6 min-w-0 w-full max-w-full [overflow-wrap:anywhere]">
            <div class="flex justify-center mb-5 sm:mb-6 lg:hidden">
                <div class="flex items-center gap-2">
                    @include('partials.brand-mark', ['class' => 'w-9 h-9 sm:w-10 sm:h-10'])
                    <span class="font-outfit font-bold text-lg sm:text-xl dash-title">ConectaIFPA</span>
                </div>
            </div>

            <h2 class="font-outfit font-bold text-xl sm:text-2xl lg:text-xl dash-title mb-1 lg:leading-tight">
                {{ $panelAccount['title'] ?? 'Criar conta' }}
            </h2>
            <p class="dash-muted text-sm mb-4 sm:mb-5 lg:mb-3">Preencha os dados abaixo</p>

            <x-validation-errors class="mb-4 lg:mb-3" />

            <form method="POST"
                action="{{ $panelAccount ? url()->current() : route('register') }}"
                class="space-y-4 lg:space-y-3">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 sm:gap-x-5 sm:gap-y-4 lg:gap-x-5 lg:gap-y-3 min-w-0">
                    <div class="sm:col-span-2 md:col-span-1 min-w-0">
                        <label for="name" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Nome Completo</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')"
                            class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base"
                            placeholder="Seu nome completo">
                    </div>

                    <div class="sm:col-span-2 md:col-span-1 min-w-0">
                        <label for="email" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base"
                            placeholder="seu@email.com">
                    </div>

                    @if (!$panelAccount)
                        <div class="sm:col-span-2 min-w-0">
                            <label id="participant_profile_label" for="participant_profile_trigger" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Perfil de participação</label>
                            <div class="profile-picker" data-profile-picker>
                                <select id="participant_profile" name="participant_profile" required class="profile-picker-native" tabindex="-1" aria-hidden="true">
                                    <option value="">Selecione</option>
                                    <option value="discente" @selected(old('participant_profile') === 'discente')>Discente</option>
                                    <option value="docente" @selected(old('participant_profile') === 'docente')>Docente</option>
                                    <option value="tecnico_administrativo" @selected(old('participant_profile') === 'tecnico_administrativo')>Técnico administrativo</option>
                                    <option value="empreendedor" @selected(old('participant_profile') === 'empreendedor')>Empreendedor</option>
                                    <option value="estudante_escola_publica" @selected(old('participant_profile') === 'estudante_escola_publica')>Estudante de escola pública</option>
                                    <option value="startup" @selected(old('participant_profile') === 'startup')>Startup</option>
                                    <option value="organizacao_social" @selected(old('participant_profile') === 'organizacao_social')>Organização social</option>
                                    <option value="comunidade_geral" @selected(old('participant_profile') === 'comunidade_geral')>Comunidade em geral</option>
                                    <option value="ifpa_outro_campus" @selected(old('participant_profile') === 'ifpa_outro_campus')>Discente, docente ou técnico de outro campus</option>
                                </select>
                                <button type="button" id="participant_profile_trigger" class="profile-picker-trigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="participant_profile_menu">
                                    <span class="profile-picker-value">Selecione</span>
                                    <svg class="profile-picker-chevron" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M5.5 7.5 10 12l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <div id="participant_profile_menu" class="profile-picker-menu" role="listbox" hidden>
                                    <p class="profile-picker-group">IFPA Belém</p>
                                    <button type="button" class="profile-picker-option" role="option" data-value="discente">Discente</button>
                                    <button type="button" class="profile-picker-option" role="option" data-value="docente">Docente</button>
                                    <button type="button" class="profile-picker-option" role="option" data-value="tecnico_administrativo">Técnico administrativo</button>
                                    <p class="profile-picker-group">Comunidade externa</p>
                                    <button type="button" class="profile-picker-option" role="option" data-value="ifpa_outro_campus">Discente, docente ou técnico de outro campus</button>
                                    <button type="button" class="profile-picker-option" role="option" data-value="empreendedor">Empreendedor</button>
                                    <button type="button" class="profile-picker-option" role="option" data-value="estudante_escola_publica">Estudante de escola pública</button>
                                    <button type="button" class="profile-picker-option" role="option" data-value="startup">Startup</button>
                                    <button type="button" class="profile-picker-option" role="option" data-value="organizacao_social">Organização social</button>
                                    <button type="button" class="profile-picker-option" role="option" data-value="comunidade_geral">Comunidade em geral</button>
                                </div>
                            </div>
                            <p class="text-[11px] dash-muted mt-1.5 m-0 leading-relaxed">Internos são discentes, docentes e técnicos do IFPA Belém. Quem é de outro campus entra na comunidade externa.</p>
                        </div>

                        <div id="participant-matricula-fields" class="sm:col-span-2 md:col-span-1 min-w-0 hidden">
                            <label for="matricula" id="matricula-label" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Matrícula (IFPA Belém)</label>
                            <input id="matricula" type="text" name="matricula" value="{{ old('matricula') }}" maxlength="20" inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 20)"
                                class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base"
                                placeholder="Somente números">
                        </div>

                        <div id="participant-campus-fields" class="sm:col-span-2 md:col-span-1 min-w-0 hidden">
                            <label for="campus-ifpa" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Campus</label>
                            <select id="campus-ifpa" name="institution" disabled data-app-select-search="Pesquisar campus"
                                class="hidden w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base bg-white">
                                <option value="">Selecione o campus</option>
                                @foreach (\App\Support\IfpaCampuses::otherThanBelem() as $campus)
                                    <option value="IFPA - {{ $campus }}" @selected(old('institution') === 'IFPA - '.$campus)>{{ $campus }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="participant-bond-fields" class="sm:col-span-2 md:col-span-1 min-w-0 hidden">
                            <label for="ifpa_bond" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Vínculo</label>
                            <select id="ifpa_bond" name="ifpa_bond" disabled
                                class="hidden w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base bg-white">
                                <option value="">Selecione</option>
                                <option value="discente" @selected(old('ifpa_bond') === 'discente')>Discente</option>
                                <option value="docente" @selected(old('ifpa_bond') === 'docente')>Docente</option>
                                <option value="tecnico_administrativo" @selected(old('ifpa_bond') === 'tecnico_administrativo')>Técnico administrativo</option>
                            </select>
                        </div>

                        <div id="participant-organization-fields" class="sm:col-span-2 md:col-span-1 min-w-0 hidden">
                            <label for="institution" id="institution-label" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Organização</label>
                            <input id="institution" type="text" name="institution" value="{{ old('institution') }}"
                                class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base"
                                placeholder="">
                        </div>

                        <div id="participant-detail-fields" class="sm:col-span-2 md:col-span-1 min-w-0 hidden">
                            <label for="curso" id="curso-label" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Curso</label>
                            <select id="curso-discente" name="curso" disabled data-app-select-search="Pesquisar curso"
                                class="hidden w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base bg-white">
                                <option value="">Selecione o curso</option>
                                @foreach (\App\Support\IfpaBelemCourses::grouped() as $group => $courses)
                                    <optgroup label="{{ $group }}">
                                        @foreach ($courses as $course)
                                            <option value="{{ $course }}" @selected(old('curso') === $course)>{{ $course }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <input id="curso" type="text" name="curso" value="{{ old('curso') }}"
                                class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base"
                                placeholder="">
                        </div>
                    @endif

                    <div class="min-w-0">
                        <label for="password" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Senha</label>
                        <input id="password" type="password" name="password" required
                            class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base"
                            placeholder="••••••••">
                    </div>

                    <div class="min-w-0">
                        <label for="password_confirmation" class="block font-outfit font-medium dash-title mb-2 lg:mb-1.5 text-sm">Confirmar Senha</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full min-w-0 px-4 py-3 lg:py-2.5 rounded-xl dash-field outline-none text-base"
                            placeholder="••••••••">
                    </div>
                </div>

                <input type="hidden" name="role" value="{{ $panelAccount['role'] ?? 'participant' }}">

                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <label class="flex items-start gap-3 cursor-pointer min-w-0">
                        <input type="checkbox" name="terms" required
                            class="dash-check mt-1 w-4 h-4 shrink-0 rounded">
                        <span class="text-sm lg:text-xs dash-text min-w-0 flex-1 leading-snug [overflow-wrap:anywhere] break-words">
                            {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="dash-link underline">'.__('Terms of Service').'</a>',
                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="dash-link underline">'.__('Privacy Policy').'</a>',
                            ]) !!}
                        </span>
                    </label>
                @endif

                <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:gap-x-4 sm:gap-y-3 lg:gap-y-2 min-w-0">
                    @if (!$panelAccount)
                        <a href="{{ route('login') }}" class="dash-link text-sm font-medium">
                            Já tem conta? Entrar
                        </a>
                    @else
                        <span class="hidden sm:block sm:flex-1 min-w-0"></span>
                    @endif
                    <button type="submit"
                        class="w-full sm:w-auto sm:shrink-0 px-8 py-3.5 lg:py-2.5 lg:px-6 rounded-xl dash-btn font-outfit font-semibold text-base lg:text-sm">
                        {{ $panelAccount['button'] ?? 'Criar conta' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(!$panelAccount)
<style>
    .profile-picker { position: relative; }
    .profile-picker-native {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    .profile-picker-trigger {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        min-height: 3rem;
        padding: 0.75rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fff;
        color: #1e293b;
        font-size: 16px;
        line-height: 1.4;
        text-align: left;
        cursor: pointer;
    }
    .profile-picker-trigger:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
    }
    .profile-picker-trigger.is-invalid {
        border-color: #dc2626;
    }
    .profile-picker-value {
        min-width: 0;
        overflow-wrap: anywhere;
    }
    .profile-picker-value.is-placeholder { color: #64748b; }
    .profile-picker-chevron {
        width: 1.1rem;
        height: 1.1rem;
        flex-shrink: 0;
        color: #64748b;
    }
    .profile-picker.is-open .profile-picker-chevron { transform: rotate(180deg); }
    .profile-picker-menu {
        position: absolute;
        z-index: 40;
        left: 0;
        right: 0;
        top: calc(100% + 0.25rem);
        max-height: 20rem;
        overflow: auto;
        padding: 0.3rem 0 0.4rem;
        border: 2px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.14);
    }
    .profile-picker-menu.is-up {
        top: auto;
        bottom: calc(100% + 0.25rem);
    }
    .profile-picker-group {
        margin: 0.15rem 0 0;
        padding: 0.45rem 1rem 0.2rem;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .profile-picker-group + .profile-picker-group,
    .profile-picker-option + .profile-picker-group {
        margin-top: 0.35rem;
        border-top: 1px solid #f1f5f9;
    }
    .profile-picker-option {
        display: block;
        width: 100%;
        padding: 0.55rem 1rem;
        border: 0;
        background: transparent;
        color: #0f172a;
        font-size: 0.95rem;
        line-height: 1.35;
        text-align: left;
        cursor: pointer;
        overflow-wrap: anywhere;
    }
    .profile-picker-option:hover,
    .profile-picker-option:focus {
        outline: none;
        background: #ecfdf5;
    }
    .profile-picker-option.is-selected {
        background: #d1fae5;
        color: #065f46;
        font-weight: 600;
    }
    html.a11y-high-contrast .profile-picker-trigger,
    html.a11y-high-contrast .profile-picker-menu,
    html.a11y-high-contrast .profile-picker-option {
        background: #fff !important;
        color: #000 !important;
        border-color: #000 !important;
    }
    html.a11y-high-contrast .profile-picker-option.is-selected {
        outline: 2px solid #000;
        font-weight: 700;
    }
</style>
<script>
(function () {
    var picker = document.querySelector('[data-profile-picker]');
    var profile = document.getElementById('participant_profile');
    var trigger = document.getElementById('participant_profile_trigger');
    var menu = document.getElementById('participant_profile_menu');
    var valueLabel = picker ? picker.querySelector('.profile-picker-value') : null;
    var optionButtons = menu ? menu.querySelectorAll('.profile-picker-option') : [];

    function selectedLabel() {
        var selected = profile && profile.options[profile.selectedIndex];
        if (!selected || !selected.value) return '';
        return selected.text;
    }

    function paintSelection() {
        var current = profile ? profile.value : '';
        var label = selectedLabel();
        if (valueLabel) {
            valueLabel.textContent = label || 'Selecione';
            valueLabel.classList.toggle('is-placeholder', !label);
        }
        optionButtons.forEach(function (button) {
            var active = button.getAttribute('data-value') === current;
            button.classList.toggle('is-selected', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function placeMenu() {
        menu.classList.remove('is-up');
        menu.style.maxHeight = '';
        var rect = trigger.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom - 16;
        var spaceAbove = Math.max(160, rect.top - 72);
        var openUp = spaceBelow < 360 && spaceAbove > spaceBelow;
        menu.classList.toggle('is-up', openUp);
        menu.style.maxHeight = (openUp ? spaceAbove : Math.max(160, spaceBelow)) + 'px';
    }

    function setOpen(open) {
        if (!picker || !menu || !trigger) return;
        if (open) placeMenu();
        picker.classList.toggle('is-open', open);
        menu.hidden = !open;
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (picker && profile && trigger && menu) {
        paintSelection();
        trigger.addEventListener('click', function () {
            setOpen(menu.hidden);
        });
        optionButtons.forEach(function (button) {
            button.addEventListener('pointerdown', function (event) {
                event.preventDefault();
                event.stopPropagation();
                profile.value = button.getAttribute('data-value') || '';
                profile.dispatchEvent(new Event('change', { bubbles: true }));
                trigger.classList.remove('is-invalid');
                paintSelection();
                setOpen(false);
                trigger.focus();
            });
        });
        document.addEventListener('click', function (event) {
            if (!picker.contains(event.target)) setOpen(false);
        });
        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setOpen(false);
            if (event.key === 'ArrowDown' && menu.hidden) {
                event.preventDefault();
                setOpen(true);
            }
        });
        profile.addEventListener('invalid', function (event) {
            event.preventDefault();
            trigger.classList.add('is-invalid');
            setOpen(true);
            trigger.focus();
        });
    }

    var matriculaWrap = document.getElementById('participant-matricula-fields');
    var campusWrap = document.getElementById('participant-campus-fields');
    var bondWrap = document.getElementById('participant-bond-fields');
    var organizationWrap = document.getElementById('participant-organization-fields');
    var detailWrap = document.getElementById('participant-detail-fields');
    var matricula = document.getElementById('matricula');
    var matriculaLabel = document.getElementById('matricula-label');
    var campus = document.getElementById('campus-ifpa');
    var bond = document.getElementById('ifpa_bond');
    var institution = document.getElementById('institution');
    var institutionLabel = document.getElementById('institution-label');
    var curso = document.getElementById('curso');
    var cursoSelect = document.getElementById('curso-discente');
    var cursoLabel = document.getElementById('curso-label');
    if (!profile || !matriculaWrap || !campusWrap || !bondWrap || !organizationWrap || !detailWrap) return;

    var organizationCopy = {
        empreendedor: ['Empreendimento', 'Nome da empresa ou da atividade'],
        estudante_escola_publica: ['Escola', 'Nome da escola pública'],
        startup: ['Startup', 'Nome da startup'],
        organizacao_social: ['Organização', 'Nome da organização social'],
        comunidade_geral: ['Organização ou referência', 'Opcional']
    };
    var detailCopy = {
        discente: ['Curso', 'Seu curso no IFPA Belém'],
        docente: ['Área de atuação', 'Ex.: Informática'],
        tecnico_administrativo: ['Setor ou lotação', 'Ex.: Coordenação de pesquisa'],
        estudante_escola_publica: ['Curso ou série', 'Ex.: 2 ano do ensino medio']
    };
    var bondCopy = {
        discente: ['Curso', 'Curso no campus'],
        docente: ['Área de atuação', 'Ex.: Informática'],
        tecnico_administrativo: ['Setor ou lotação', 'Ex.: Coordenação de pesquisa']
    };
    var detailProfiles = ['discente', 'docente', 'tecnico_administrativo', 'estudante_escola_publica', 'ifpa_outro_campus'];
    var organizationProfiles = ['empreendedor', 'estudante_escola_publica', 'startup', 'organizacao_social', 'comunidade_geral'];

    function setField(input, wrap, visible, required) {
        wrap.classList.toggle('hidden', !visible);
        setControl(input, visible, required);
    }

    function controlShell(input) {
        return (input.__appSelect && input.__appSelect.root) || input;
    }

    function setControl(input, visible, required) {
        if (!input) return;
        input.disabled = !visible;
        var shell = controlShell(input);
        if (shell !== input) {
            input.classList.remove('hidden');
            input.hidden = false;
        }
        shell.classList.toggle('hidden', !visible);
        shell.hidden = !visible;
        if (!visible) shell.style.setProperty('display', 'none', 'important');
        else shell.style.removeProperty('display');
        if (visible && required) {
            input.setAttribute('required', 'required');
        } else {
            input.removeAttribute('required');
            if (!visible) input.value = '';
        }
    }

    function sync() {
        var value = profile.value;
        var otherCampus = value === 'ifpa_outro_campus';
        var showMatricula = value === 'discente' || otherCampus;
        var showOrganization = organizationProfiles.indexOf(value) !== -1;
        var showDetail = detailProfiles.indexOf(value) !== -1;
        var organizationRequired = showOrganization && value !== 'comunidade_geral';
        var showCourseSelect = showDetail && value === 'discente';
        var showCourseText = showDetail && value !== 'discente';
        var bondValue = bond ? bond.value : '';
        var detailText = otherCampus ? (bondCopy[bondValue] || ['Curso, área ou setor', 'Conforme o vínculo']) : detailCopy[value];

        setField(matricula, matriculaWrap, showMatricula, value === 'discente');
        setField(campus, campusWrap, otherCampus, true);
        setField(bond, bondWrap, otherCampus, true);
        setField(institution, organizationWrap, showOrganization, organizationRequired);
        detailWrap.classList.toggle('hidden', !showDetail);
        setControl(cursoSelect, showCourseSelect, true);
        setControl(curso, showCourseText, true);

        if (matriculaLabel) {
            matriculaLabel.textContent = otherCampus ? 'Matrícula (opcional)' : 'Matrícula (IFPA Belém)';
        }
        if (showOrganization && organizationCopy[value]) {
            institutionLabel.textContent = organizationCopy[value][0] + (organizationRequired ? '' : ' (opcional)');
            institution.placeholder = organizationCopy[value][1];
        }
        if (cursoLabel) {
            cursoLabel.setAttribute('for', showCourseSelect ? 'curso-discente' : 'curso');
        }
        if (showDetail && detailText && cursoLabel) {
            cursoLabel.textContent = detailText[0];
        }
        if (showCourseText && detailText) {
            curso.placeholder = detailText[1];
        }
    }

    profile.addEventListener('change', sync);
    if (bond) bond.addEventListener('change', sync);
    sync();

    var selectUiTries = 0;
    function syncWhenCourseSelectIsReady() {
        if (!cursoSelect || cursoSelect.__appSelect || selectUiTries > 40) {
            sync();
            return;
        }
        selectUiTries += 1;
        setTimeout(syncWhenCourseSelectIsReady, 50);
    }
    syncWhenCourseSelectIsReady();
})();
</script>
@endif

@endsection
