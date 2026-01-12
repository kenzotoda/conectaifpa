@extends('layouts.newMain')

@section('title', 'Cadastro')

@section('content')

    <x-guest-layout>
        <x-authentication-card>
            <x-slot name="logo">
                @if (request()->routeIs('register.coordinator'))
                    <div>Cadastrar Coordenador</div>
                @else
                    <div>Criar conta de aluno</div>
                @endif
            </x-slot>

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ request()->routeIs('register.coordinator') ? route('register.coordinator') : route('register') }}">
                @csrf

                <div>
                    <x-label for="name" value="{{ __('Nome Completo') }}" />
                    <x-input id="name"
                        class="block mt-1 w-full"
                        type="text"
                        name="name"
                        oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="name" />
                </div>

                <div class="mt-4">
                    <x-label for="email" value="{{ __('Email') }}" />
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                </div>

                @if (!request()->routeIs('register.coordinator'))
                    <div class="mt-4">
                        <x-label for="matricula" value="Matrícula" />
                        <x-input id="matricula"
                            class="block mt-1 w-full"
                            type="text"
                            name="matricula"
                            maxlength="12"
                            inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            :value="old('matricula')"
                            required />
                    </div>

                    <div class="mt-4">
                        <x-label for="curso" value="Curso" />
                        <x-input id="curso"
                            class="block mt-1 w-full"
                            type="text"
                            name="curso"
                            oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')"
                            :value="old('curso')"
                            required />
                    </div>
                @endif

                <div class="mt-4">
                    <x-label for="password" value="{{ __('Senha') }}" />
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                </div>

                <div class="mt-4">
                    <x-label for="password_confirmation" value="{{ __('Confirmar Senha') }}" />
                    <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>

                <input type="hidden" name="role" value="{{ request()->routeIs('register.coordinator') ? 'coordinator' : 'participant' }}">


                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <div class="mt-4">
                        <x-label for="terms">
                            <div class="flex items-center">
                                <x-checkbox name="terms" id="terms" required />

                                <div class="ms-2">
                                    {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Terms of Service').'</a>',
                                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Privacy Policy').'</a>',
                                    ]) !!}
                                </div>
                            </div>
                        </x-label>
                    </div>
                @endif

                <div class="flex items-center justify-end mt-4">
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                        {{ __('já cadastrado?') }}
                    </a>

                    <x-button class="ms-4">
                        @if (request()->routeIs('register.coordinator'))
                            {{ __('CADASTRAR COORDENADOR') }}
                        @else
                            {{ __('CRIAR CONTA') }}
                        @endif
                    </x-button>
                </div>
            </form>
        </x-authentication-card>
    </x-guest-layout>

@endsection











