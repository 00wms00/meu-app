@extends('layouts.app')

@section('title', 'Faturas de Cartão')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">💳 Faturas de Cartão</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            @if($card)
                {{ $card->bandeira }} - {{ $card->nome }} &mdash;
                <span class="font-medium text-gray-700">{{ $mes->translatedFormat('F \\d\\e Y') }}</span>
            @else
                Selecione um cartão
            @endif
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('finance.credit_cards.index') }}" class="btn-back">← Cartões</a>
        <a href="{{ route('dashboard') }}" class="btn-back">← Dashboard</a>
    </div>
</div>

{{-- Filtros --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        {{-- Cartão --}}
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Cartão</label>
            <select name="card_id" class="form-control text-sm w-full" onchange="this.form.submit()">
                @foreach($cards as $c)
                    <option value="{{ $c->id }}" {{ $cardId == $c->id ? 'selected' : '' }}>
                        {{ $c->bandeira }} - {{ $c->nome }} ({{ $c->pessoa }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Mês --}}
        <div class="w-[180px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Mês de referência</label>
            <select name="mes" class="form-control text-sm w-full" onchange="this.form.submit()">
                @foreach($meses as $m)
                    <option value="{{ $m->format('Y-m') }}" {{ $mesStr == $m->format('Y-m') ? 'selected' : '' }}>
                        {{ $m->translatedFormat('F/Y') }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn-primary text-sm px-4 py-2">Filtrar</button>
    </form>
</div>

{{-- Mensagem de sucesso --}}
@if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        {{ session('success') }}
    </div>
@endif

{{-- Card do Cartão --}}
@if($card)
<div class="bg-white rounded-xl shadow overflow-hidden mb-6">
    {{-- Header colorido --}}
    <div class="px-6 py-4 text-white" style="background: {{ $card->cor }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs opacity-75 uppercase tracking-wider">{{ $card->bandeira }}</p>
                <h2 class="text-xl font-bold">{{ $card->nome }}</h2>
            </div>
            <div class="text-right">
                <p class="text-xs opacity-75">Limite</p>
                <p class="text-lg font-bold">
                    {{ $card->limite ? 'R$ '.number_format($card->limite, 2, ',', '.') : 'Sem limite' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Informações da fatura --}}
    <div class="px-6 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Fechamento</p>
            <p class="text-sm font-semibold text-gray-800">
                {{ $card->dataFechamento($mes)->format('d/m/Y') }}
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Vencimento</p>
            <p class="text-sm font-semibold text-gray-800">
                {{ $card->dataVencimento($mes)->format('d/m/Y') }}
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Total da Fatura</p>
            <p class="text-sm font-bold text-blue-600">R$ {{ number_format($total, 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Status</p>
            @php
                $statusFatura = $card->statusFatura($mes);
            @endphp
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                {{ $statusFatura === 'aberta' ? 'bg-green-100 text-green-700' : '' }}
                {{ $statusFatura === 'fechada' ? 'bg-yellow-100 text-yellow-700' : '' }}
                {{ $statusFatura === 'vencida' ? 'bg-red-100 text-red-700' : '' }}">
                {{ ucfirst($statusFatura) }}
            </span>
        </div>
    </div>

    {{-- Barra de progresso do limite --}}
    @if($card->limite)
    @php 
        $limiteDisponivel = $card->limiteDisponivel(); 
        $limiteUsado = $card->limite - $limiteDisponivel;
        $pctLimite = $card->limite > 0 ? min(100, round(($limiteUsado / $card->limite) * 100)) : 0;
    @endphp
    <div class="px-6 pb-4">
        <div class="flex justify-between text-xs text-gray-400 mb-1">
            <span>{{ $pctLimite }}% do limite utilizado</span>
            <span>Disponível: R$ {{ number_format($limiteDisponivel, 2, ',', '.') }}</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2">
            <div class="h-2 rounded-full transition-all {{ $pctLimite >= 80 ? 'bg-red-500' : ($pctLimite >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}"
                 style="width: {{ $pctLimite }}%"></div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- Resumo rápido --}}
@if($itens->isNotEmpty())
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
        <p class="text-xs text-gray-500 uppercase tracking-wide">✅ Pago</p>
        <p class="text-xl font-bold text-green-700">R$ {{ number_format($totalPago, 2, ',', '.') }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $itens->where('is_pago', true)->count() }} lançamento(s)</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
        <p class="text-xs text-gray-500 uppercase tracking-wide">⏳ Pendente</p>
        <p class="text-xl font-bold text-yellow-700">R$ {{ number_format($totalPendente, 2, ',', '.') }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $itens->where('is_pago', false)->count() }} lançamento(s)</p>
    </div>
</div>
@endif

{{-- Tabela de Itens da Fatura --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b bg-gray-50">
        <h3 class="text-base font-semibold text-gray-800">
            📄 Lançamentos da Fatura &mdash; {{ $mes->translatedFormat('F/Y') }}
        </h3>
    </div>

    @if($itens->isEmpty())
        <div class="p-10 text-center text-gray-400">
            <p class="text-4xl mb-3">📭</p>
            <p class="text-sm font-medium">Nenhuma despesa encontrada nesta fatura.</p>
            <p class="text-xs mt-1">Adicione despesas no crédito para vê-las aqui.</p>
            <a href="{{ route('finance.expenses.index', ['mes' => $mesStr]) }}" class="inline-block mt-3 text-sm text-blue-600 hover:underline">
                → Ir para Despesas
            </a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center w-[100px]">Parcela</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center w-[100px]">Status</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-right w-[140px]">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($itens as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $item['nome'] }}</p>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                         {{ $item['parcela'] === 'à vista' ? 'bg-gray-100 text-gray-600' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ $item['parcela'] }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                         {{ $item['status'] === 'pago' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $item['status'] === 'pago' ? 'Pago' : 'Pendente' }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <span class="text-sm font-semibold text-gray-900 tabular-nums">
                                R$ {{ number_format($item['valor'], 2, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-200">
                        <td class="px-6 py-3" colspan="3">
                            <span class="text-sm font-bold text-gray-700">Total da Fatura</span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <span class="text-base font-bold text-blue-600 tabular-nums">
                                R$ {{ number_format($total, 2, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>

{{-- Links úteis --}}
<div class="mt-6 flex flex-wrap gap-3">
    <a href="{{ route('finance.expenses.index', ['mes' => $mesStr]) }}"
       class="text-sm px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm text-gray-600 hover:bg-gray-50 transition">
        📋 Ver Todas as Despesas
    </a>
    <a href="{{ route('finance.credit_cards.index') }}"
       class="text-sm px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm text-gray-600 hover:bg-gray-50 transition">
        💳 Gerenciar Cartões
    </a>
</div>

@endsection