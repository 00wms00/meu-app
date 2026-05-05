@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h4 class="text-lg font-semibold text-gray-800 mb-6">Login</h4>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mb-4">
                <x-input-label for="password" :value="__('Senha')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mb-4 flex items-center">
                <input type="checkbox" name="remember" id="remember"
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <label for="remember" class="ms-2 text-sm text-gray-600">Lembrar-me</label>
            </div>

            <div class="flex items-center justify-between">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm text-blue-600 hover:text-blue-800 underline">
                        Esqueceu a senha?
                    </a>
                @endif

                <x-primary-button class="ms-auto">
                    Entrar
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
@endsection
