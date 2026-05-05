@extends('layouts.app')

@section('title', 'Confirmar Senha')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Área Segura</h4>

        <p class="mb-6 text-sm text-gray-600">
            Esta é uma área segura do aplicativo. Confirme sua senha antes de continuar.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-4">
                <x-input-label for="password" :value="__('Senha')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-end">
                <x-primary-button>
                    Confirmar
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
@endsection
