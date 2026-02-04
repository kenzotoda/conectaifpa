@extends('layouts.newMain')

@section('title', 'Cadastro')

@section('content')

<div class="min-h-[calc(100vh-160px)] flex items-center justify-center px-4">
    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl p-8">

        

        <!-- Título / Logo -->
        <div class="text-center mb-4">
            @if (request()->routeIs('register.coordinator'))
                <h1 class="text-xl font-montserrat font-bold text-gray-800">
                    Cadastrar Coordenador
                </h1>
            @else
                <h1 class="text-xl font-montserrat font-bold text-gray-800">
                    Criar conta de aluno
                </h1>
            @endif
        </div>

        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <i class="bi bi-person-plus-fill text-6xl"></i>
        </div>

        <x-validation-errors class="mb-4" />

        <form method="POST"
            action="{{ request()->routeIs('register.coordinator') ? route('register.coordinator') : route('register') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Linha 1 --}}
                <div>
                    <x-label for="name" value="Nome Completo" />
                    <x-input id="name"
                            class="block mt-1 w-full"
                            type="text"
                            name="name"
                            oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s]/g, '')"
                            :value="old('name')"
                            required
                            autofocus />
                </div>

                <div>
                    <x-label for="email" value="Email" />
                    <x-input id="email"
                            class="block mt-1 w-full"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required />
                </div>

                {{-- Linha 2 (somente aluno) --}}
                @if (!request()->routeIs('register.coordinator'))
                    <div>
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

                    <div>
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

                {{-- Linha 3 --}}
                <div>
                    <x-label for="password" value="Senha" />
                    <x-input id="password"
                            class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required />
                </div>

                <div>
                    <x-label for="password_confirmation" value="Confirmar Senha" />
                    <x-input id="password_confirmation"
                            class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required />
                </div>

            </div>

            <input type="hidden"
                name="role"
                value="{{ request()->routeIs('register.coordinator') ? 'coordinator' : 'participant' }}">

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="mt-4">
                    <label class="flex items-start">
                        <x-checkbox name="terms" required />
                        <span class="ml-2 text-sm text-gray-600">
                            {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline hover:text-primary">'.__('Terms of Service').'</a>',
                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline hover:text-primary">'.__('Privacy Policy').'</a>',
                            ]) !!}
                        </span>
                    </label>
                </div>
            @endif

            <div class="flex items-center mt-6 
                {{ request()->routeIs('register.coordinator') ? 'justify-end' : 'justify-between' }}">
                
                @if (!request()->routeIs('register.coordinator'))
                    <a href="{{ route('login') }}"
                        class="text-sm text-gray-600 hover:text-primary underline">
                        Já cadastrado?
                    </a>
                @endif

                <x-button>
                    @if (request()->routeIs('register.coordinator'))
                        CADASTRAR COORDENADOR
                    @else
                        CRIAR CONTA
                    @endif
                </x-button>
            </div>

        </form>


    </div>
</div>

@endsection
