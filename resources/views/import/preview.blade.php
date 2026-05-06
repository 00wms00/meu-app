@extends('layouts.app')

@section('title', 'Confirmar Importação')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">📋Confirmar Dados da Nota</h1>
        <p class="mt-1 text-gray-600">Revise os dados antes de salvar</p>
    </div>
</div>

{{-- Banner de detecção de combustível --}}
@if(!empty($data['is_combustivel']))
<div class="mb-4 flex items-start gap-3 bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded-lg">
    <span class="text-xl">⛽</span>
    <div>
        <p class="font-semibold">NFC-e de combustível detectada!</p>
        <p class="text-sm">Detectamos <strong>{{ $data['fuel']['nome_produto'] }}</strong>
            ({{ number_format($data['fuel']['litros'] ?? 0, 3, ',', '.') }} L
            &bull; R$ {{ number_format($data['fuel']['valor'], 2, ',', '.') }})
            no posto <strong>{{ $data['fuel']['posto'] }}</strong>.
            Escolha abaixo se quer salvar como compra de mercado ou como abastecimento de veículo.
        </p>
    </div>
</div>
@endif

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
            <form action="{{ route('import.store') }}" method="POST" id="form-import">
                @csrf

                {{-- SELECT: destino da nota --}}
                <div class="mb-4">
                    <label for="destino" class="block text-sm font-medium text-gray-700 mb-1">Destino da nota</label>
                    <select name="destino" id="destino" class="form-control"
                        onchange="toggleVehicleSelect(this.value)">
                        <option value="mercado">&#127978; Mercado / Supermercado</option>
                        @if(!empty($data['is_combustivel']))
                        <option value="veiculo" selected>&#9981; Veículo (abastecimento)</option>
                        @else
                        <option value="veiculo">&#9981; Veículo (abastecimento)</option>
                        @endif
                    </select>
                </div>

                {{-- Bloco veículo: só aparece quando destino=veiculo --}}
                <div id="vehicle-select-wrap" class="space-y-4 mb-4 {{ empty($data['is_combustivel']) ? 'hidden' : '' }}">

                    {{-- Select de veículo --}}
                    <div>
                        <label for="vehicle_id" class="block text-sm font-medium text-gray-700 mb-1">Veículo</label>
                        @if($vehicles->isEmpty())
                            <p class="text-sm text-red-600">
                                Nenhum veículo cadastrado.
                                <a href="{{ route('vehicles.create') }}" class="underline">Cadastrar agora</a>
                            </p>
                        @else
                            <select name="vehicle_id" id="vehicle_id" class="form-control">
                                <option value="">Selecione o veículo...</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}">
                                        {{ $v->apelido }}{{ $v->marca ? ' — ' . $v->marca : '' }}{{ $v->modelo ? ' ' . $v->modelo : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- Campo KM --}}
                    @if(!empty($data['is_combustivel']))
                    <div>
                        <label for="km_abastecimento" class="block text-sm font-medium text-gray-700 mb-1">
                            KM no abastecimento
                            @if(!empty($data['fuel']['km']))
                                <span class="ml-1 text-xs font-normal text-amber-600">(lido da NFC-e)</span>
                            @else
                                <span class="ml-1 text-xs font-normal text-gray-400">(opcional)</span>
                            @endif
                        </label>
                        <input
                            type="number"
                            name="km_abastecimento"
                            id="km_abastecimento"
                            class="form-control"
                            min="0"
                            step="1"
                            placeholder="Ex: 45230"
                            value="{{ !empty($data['fuel']['km']) ? $data['fuel']['km'] : '' }}"
                        >
                        <p class="mt-1 text-xs text-gray-400">Deixe em branco para preencher depois no histórico do veículo.</p>
                    </div>
                    @endif

                    {{-- Resumo do abastecimento que será salvo --}}
                    @if(!empty($data['fuel']))
                    <div class="bg-amber-50 border border-amber-200 rounded p-3 text-xs text-amber-800 space-y-1">
                        <p><strong>Combustível:</strong> {{ $data['fuel']['nome_produto'] }}</p>
                        <p><strong>Litros:</strong> {{ number_format($data['fuel']['litros'] ?? 0, 3, ',', '.') }} L</p>
                        <p><strong>Valor:</strong> R$ {{ number_format($data['fuel']['valor'], 2, ',', '.') }}</p>
                        @if(!empty($data['fuel']['litros']))
                        <p><strong>Preço/L:</strong> R$ {{ number_format($data['fuel']['valor'] / $data['fuel']['litros'], 3, ',', '.') }}</p>
                        @endif
                        <p><strong>Posto:</strong> {{ $data['fuel']['posto'] }}</p>
                        <p><strong>Data:</strong> {{ $data['fuel']['data'] ? \Carbon\Carbon::parse($data['fuel']['data'])->format('d/m/Y') : 'N/A' }}</p>
                    </div>
                    @endif
                </div>

                <div class="space-y-3 mt-4">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white text-lg font-semibold rounded-md transition">
                        ✅ Confirmar e Salvar
                    </button>
                    <a href="{{ route('import.create') }}"
                       class="block w-full text-center inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">
                        ← Voltar e Corrigir
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleVehicleSelect(value) {
    const wrap = document.getElementById('vehicle-select-wrap');
    if (value === 'veiculo') {
        wrap.classList.remove('hidden');
    } else {
        wrap.classList.add('hidden');
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('destino');
    if (sel) toggleVehicleSelect(sel.value);
});
</script>
@endsection
