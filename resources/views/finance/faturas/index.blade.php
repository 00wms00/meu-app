@extends('layouts.app')

@section('title', 'Faturas de Cartões')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">💳 Faturas de Cartões</h1>
        <p class="text-sm text-gray-500 mt-0.5">Despesas do cartão no mês selecionado</p>
    </div>
    <a href="{{ route('finance.credit_cards.index') }}" class="btn-back self-start sm:self-auto">← Cartões</a>
</div>

{{-- Filtros --}}
<form method="GET" class="mb-6 flex flex-wrap gap-3 items-end bg-white rounded-xl shadow p-4">
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Cartão</label>
        <select name="card_id" class="form-control text-sm" onchange="this.form.submit()">
            @foreach($cards as $c)
                <option value="{{ $c->id }}" {{ $c->id == $cardId ? 'selected' : '' }}>
                    {{ $c->nome }} &mdash; {{ $c->pessoa_label }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Mês</label>
        <select name="mes" class="form-control text-sm" onchange="this.form.submit()">
            @foreach($meses as $m)
                <option value="{{ $m->format('Y-m') }}" {{ $m->format('Y-m') === $mesStr ? 'selected' : '' }}>
                    {{ ucfirst($m->translatedFormat('F \d\e Y')) }}
                </option>
            @endforeach
        </select>
    </div>
</form>

{{-- Cabeçalho da fatura --}}
@if($card)
<div class="flex items-center gap-3 mb-4">
    <span class="inline-block w-4 h-4 rounded-full" style="background:{{ $card->cor ?? '#888' }}"></span>
    <h2 class="text-lg font-bold text-gray-800">{{ $card->nome }}</h2>
    <span class="text-sm text-gray-400">{{ ucfirst($mes->translatedFormat('F \d\e Y')) }}</span>
</div>
@endif

{{-- Tabela desktop --}}
<div class="hidden md:block bg-white rounded-xl shadow overflow-hidden">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-semibold">
                <th class="text-left px-5 py-3">Descrição</th>
                <th class="text-center px-4 py-3 w-28">Parcela</th>
                <th class="text-right px-5 py-3 w-36">Valor</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($itens as $item)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 text-gray-800">{{ $item['descricao'] }}</td>
                <td class="px-4 py-3 text-center text-gray-500">{{ $item['parcela'] }}</td>
                <td class="px-5 py-3 text-right font-medium text-gray-900">
                    R$ {{ number_format($item['valor'], 2, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="px-5 py-10 text-center text-gray-400">
                    Nenhuma despesa encontrada para este cartão neste mês.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 border-t-2 border-gray-300 font-bold">
                <td colspan="2" class="px-5 py-3 text-gray-700">Total da fatura</td>
                <td class="px-5 py-3 text-right text-gray-900">R$ {{ number_format($total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Cards mobile --}}
<div class="md:hidden space-y-3">
    @forelse($itens as $item)
    <div class="bg-white rounded-xl shadow px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="font-medium text-gray-800 truncate">{{ $item['descricao'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Parcela: {{ $item['parcela'] }}</p>
        </div>
        <span class="font-semibold text-gray-900 whitespace-nowrap">
            R$ {{ number_format($item['valor'], 2, ',', '.') }}
        </span>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow px-4 py-10 text-center text-gray-400">
        Nenhuma despesa encontrada.
    </div>
    @endforelse

    {{-- Total mobile --}}
    @if($itens->count() > 0)
    <div class="bg-gray-100 rounded-xl px-4 py-3 flex justify-between font-bold text-gray-800">
        <span>Total da fatura</span>
        <span>R$ {{ number_format($total, 2, ',', '.') }}</span>
    </div>
    @endif
</div>

@endsection
