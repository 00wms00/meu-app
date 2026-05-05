@extends('layouts.app')

@section('title', 'Alertas de Preço')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🔔 Alertas de Preço</h1>
            <p class="mt-1 text-gray-600">Seja notificado quando produtos aumentarem de preço</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn-back">← Produtos</a>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif

<!-- Alertas Disparados -->
@php
    $disparadosIds = collect($disparados)->pluck('id')->toArray();
@endphp

@if(count($disparados) > 0)
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
    <h3 class="font-semibold text-red-800 mb-3">🚨 Alertas Disparados ({{ count($disparados) }})</h3>
    <div class="space-y-2">
        @foreach($disparados as $alerta)
        <div class="flex items-center justify-between bg-white rounded p-3">
            <div>
                <p class="font-medium text-gray-800">{{ $alerta->product->nome }}</p>
                <p class="text-sm text-red-600">
                    📈 Aumentou {{ number_format($alerta->variacao_percentual, 1, ',', '.') }}%
                    (R$ {{ number_format($alerta->preco_referencia, 2, ',', '.') }} → R$ {{ number_format($alerta->preco_atual, 2, ',', '.') }})
                </p>
            </div>
            <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">ACIMA DO LIMITE</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Lista de Alertas -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b bg-gray-50">
        <h2 class="text-lg font-semibold text-gray-800">📋 Seus Alertas ({{ $alertas->count() }})</h2>
    </div>
    
    @if($alertas->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço Ref.</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço Atual</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Variação</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Limite</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($alertas as $alerta)
                @php
                    $disparado = in_array($alerta->id, $disparadosIds);
                    $variacao = $alerta->variacao_percentual;
                @endphp
                <tr class="hover:bg-gray-50 {{ $disparado ? 'bg-red-50' : '' }}">
                    <td class="px-4 py-3">
                        <a href="{{ route('products.show', $alerta->product) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            {{ $alerta->product->nome }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-right">R$ {{ number_format($alerta->preco_referencia, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium">R$ {{ number_format($alerta->preco_atual, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right {{ $variacao >= 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $variacao > 0 ? '+' : '' }}{{ number_format($variacao, 1, ',', '.') }}%
                    </td>
                    <td class="px-4 py-3 text-sm text-center">
                        <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs">
                            {{ number_format($alerta->limite_alerta, 0) }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if(!$alerta->ativo)
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded-full">⏸️ Pausado</span>
                        @elseif($disparado)
                        <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">🚨 Disparado</span>
                        @else
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">✅ Monitorando</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <form action="{{ route('alertas.toggle', $alerta) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs {{ $alerta->ativo ? 'text-yellow-600' : 'text-green-600' }} hover:underline">
                                    {{ $alerta->ativo ? '⏸️' : '▶️' }}
                                </button>
                            </form>
                            <form action="{{ route('alertas.remover', $alerta) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Remover alerta?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-8 text-center text-gray-500">
        <span class="text-4xl">🔔</span>
        <p class="mt-2">Nenhum alerta configurado.</p>
        <p class="text-sm text-gray-400 mt-1">Vá até um produto e clique em "Criar Alerta de Preço".</p>
    </div>
    @endif
</div>
@endsection
