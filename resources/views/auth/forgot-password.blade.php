@extends('layouts.newMain')

@section('title', 'Esqueceu a senha')

@section('content')

<div class="min-h-[calc(100vh-160px)] flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">

        <!-- Ícone / Título -->
        <div class="flex flex-col items-center mb-6">
            <i class="bi bi-envelope-lock-fill text-5xl text-primary mb-3"></i>

            <h1 class="text-xl font-montserrat font-bold text-gray-800 text-center">
                Esqueceu sua senha?
            </h1>
        </div>

        <!-- Texto explicativo -->
        <div class="mb-4 text-sm text-gray-600 text-center">
            {{ __('Sem problemas. Informe seu endereço de e-mail e enviaremos um link para redefinir sua senha.') }}
        </div>

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600 text-center">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div>
                <x-label for="email" value="Email" />
                <x-input id="email"
                         class="block mt-1 w-full"
                         type="email"
                         name="email"
                         :value="old('email')"
                         required
                         autofocus />
            </div>

            <div class="mt-6">
                <x-button class="w-full justify-center">
                    {{ __('Enviar Link') }}
                </x-button>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}"
                   class="text-sm text-gray-600 hover:text-primary underline">
                    Voltar para o login
                </a>
            </div>
        </form>

    </div>
</div>

@endsection
