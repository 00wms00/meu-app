@extends('layouts.app')

@section('title', 'Verificar E-mail')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Verificar E-mail</h4>

        <p class="mb-4 text-sm text-gray-600">
            Obrigado por se registrar! Antes de começar, verifique seu endereço de e-mail clicando no link que enviamos.
            Se não recebeu, podemos reenviar.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 text-sm font-medium text-green-600">
                Um novo link de verificação foi enviado para o seu e-mail.
            </div>
        @endif

        <div class="flex items-center justify-between">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-primary-button>
                    Reenviar E-mail de Verificação
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="text-sm text-gray-600 hover:text-gray-900 underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Sair
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
