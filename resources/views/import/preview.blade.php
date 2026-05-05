@extends('layouts.app')

@section('title', 'Confirmar Importação')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">📋 Confirmar Dados da Nota</h1>
        <p class="mt-1 text-gray-600">Revise os dados antes de salvar</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Estabelecimento -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">🏦 Estabelecimento</h2>
            <dl class="grid grid-cols-1 gap-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nome</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $data['nome_estabelecimento'] ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">CNPJ</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $data['cnpj'] ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Endereço</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $data['endereco_estabelecimento'] ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Dados da Nota -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">📄 Dados da Nota</h2>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Número / Série</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $data['numero'] ?? 'N/A' }} (Série: {{ $data['serie'] ?? 'N/A' }})</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Emissão</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if(isset($data['data_emissao']))
                            @if($data['data_emissao'] instanceof \Carbon\Carbon)
                                {{ $data['data_emissao']->format('d/m/Y H:i:s') }}
                            @else
                                {{ $data['data_emissao'] }}
                            @endif
                        @else
                            N/A
                        @endif
                    </dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Chave de Acesso</dt>
                    <dd class="mt-1 text-xs text-gray-500 font-mono">{{ isset($data['chave']) ? chunk_split($data['chave'], 4, ' ') : 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Itens -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-800">🛒 Itens da Compra</h2>
            </div>
            @if(isset($data['itens']) && count($data['itens']) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Produto</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Qtde</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Unid.</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Vl. Unit.</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Vl. Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['itens'] as $item)
                        <tr class="border-b">
                            <td class="py-3 px-4 text-sm">{{ $item['nome'] ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-sm text-center">{{ isset($item['quantidade']) ? number_format($item['quantidade'], 3, ',', '.') : '0' }}</td>
                            <td class="py-3 px-4 text-sm text-center">{{ $item['unidade'] ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-sm text-right">R$ {{ isset($item['valor_unitario']) ? number_format($item['valor_unitario'], 2, ',', '.') : '0,00' }}</td>
                            <td class="py-3 px-4 text-sm text-right font-semibold">R$ {{ isset($item['valor_total']) ? number_format($item['valor_total'], 2, ',', '.') : '0,00' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-6 text-center text-gray-500">Nenhum item encontrado.</div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Totais -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">💰 Totais</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Qtd. Itens</dt>
                    <dd class="text-sm font-medium">{{ $data['total_itens'] ?? 0 }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Valor Total</dt>
                    <dd class="text-sm font-medium">R$ {{ isset($data['valor_total']) ? number_format($data['valor_total'], 2, ',', '.') : '0,00' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Descontos</dt>
                    <dd class="text-sm font-medium text-red-600">R$ {{ isset($data['descontos']) ? number_format($data['descontos'], 2, ',', '.') : '0,00' }}</dd>
                </div>
                <div class="flex justify-between pt-3 border-t">
                    <dt class="text-sm font-semibold">Valor Pago</dt>
                    <dd class="text-lg font-bold text-green-600">R$ {{ isset($data['valor_pago']) ? number_format($data['valor_pago'], 2, ',', '.') : '0,00' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Pagamento</dt>
                    <dd class="text-sm font-medium">{{ $data['forma_pagamento'] ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Ações -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('import.store') }}" method="POST">
                @csrf
                <div class="space-y-3">
                    {{-- btn-success já incluía estilo base; sem prefixo 'btn' redundante --}}
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white text-lg font-semibold rounded-md transition">
                        ✅ Confirmar e Salvar Nota
                    </button>
                    {{-- btn-outline-secondary já incluía estilo base --}}
                    <a href="{{ route('import.create') }}"
                       class="block w-full text-center inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">
                        ← Voltar e Corrigir
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
