@extends('layouts.newMain')

@section('title', 'IFPA Eventos')

@section('content')

<div class="min-h-[calc(100vh-160px)] flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">

        <!-- Logo -->
        <div class="flex justify-center mb-10">
            <i class="bi bi-lock-fill text-6xl"></i>
        </div>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600 text-center">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}">
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

            <div class="mt-4">
                <x-label for="password" value="Senha" />
                <x-input id="password"
                         class="block mt-1 w-full"
                         type="password"
                         name="password"
                         required />
            </div>

            <div class="flex items-center justify-between mt-4">
                <label class="flex items-center">
                    <x-checkbox name="remember" />
                    <span class="ml-2 text-sm text-gray-600">Lembre de mim</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-sm text-primary hover:underline"
                       href="{{ route('password.request') }}">
                        Esqueceu a senha?
                    </a>
                @endif
            </div>

            <div class="mt-6">
                <x-button class="w-full justify-center">
                    Entrar
                </x-button>
            </div>
        </form>

    </div>
</div>

@endsection
