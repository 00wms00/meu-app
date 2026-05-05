@extends('layouts.app')

@section('title', 'Esqueci a Senha')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Esqueci a Senha</h4>

        <p class="mb-6 text-sm text-gray-600">
            Esqueceu sua senha? Informe seu e-mail e enviaremos um link para redefinição.
        </p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-4">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex justify-end">
                <x-primary-button>
                    Enviar Link de Redefinição
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
@endsection
