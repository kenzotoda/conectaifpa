@extends('layouts.newMain')

@push('head')
    @livewireStyles
@endpush

@section('title', 'Minha conta')

@section('content')
<div class="min-h-screen bg-slate-50/50 overflow-x-hidden">
    <div class="max-w-5xl mx-auto w-full min-w-0 px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8 md:mb-10">
            <div class="min-w-0">
                <p class="text-sm text-slate-500 mb-1 m-0">Conta</p>
                <h1 class="font-montserrat font-bold text-2xl sm:text-3xl text-slate-900 m-0 break-words">Minha conta</h1>
                <p class="text-slate-600 text-sm mt-2 m-0 max-w-xl">
                    Atualize nome e e-mail, altere sua senha, opcionalmente ative verificação em duas etapas e gerencie sessões.
                </p>
            </div>
            <a href="/dashboard"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-sm font-semibold no-underline transition-colors shrink-0 min-h-[44px] md:min-h-0">
                <ion-icon name="arrow-back-outline" class="text-lg shrink-0" aria-hidden="true"></ion-icon>
                Voltar ao painel
            </a>
        </div>

        {{--
          Mobile: respiro claro entre blocos Livewire (o divisor Jetstream só aparece ≥ sm).
          Desktop: comportamento igual ao Jetstream (divisória entre cards).
        --}}
        <div class="flex flex-col gap-10 sm:block">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                @livewire('profile.update-password-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                @livewire('profile.two-factor-authentication-form')

                <x-section-border />
            @endif

            @livewire('profile.logout-other-browser-sessions-form')
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @livewireScripts
@endpush
