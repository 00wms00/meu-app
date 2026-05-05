@extends('layouts.app')

@section('title', 'Lançamento Manual')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">✍️ Lançamento Manual</h1>
            <p class="mt-1 text-gray-600">Registre compras sem NFC-e (feira, pequenos comércios, etc.)</p>
        </div>
        <a href="{{ route('invoices.index') }}" class="btn-back">← Voltar</a>
    </div>
</div>

<form id="formLancamento" action="{{ route('lancamento.store') }}" method="POST">
    @csrf
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna Principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Dados da Compra -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
                    <h2 class="text-lg font-semibold text-gray-800">📝 Dados da Compra</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estabelecimento *</label>
                            <input type="text" name="nome_estabelecimento" value="{{ old('nome_estabelecimento') }}" 
                                   class="form-control" required placeholder="Ex: Feira do Produtor, Mercado do Bairro...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data da Compra *</label>
                            <input type="datetime-local" name="data_emissao" 
                                   value="{{ old('data_emissao', now()->format('Y-m-d\TH:i')) }}" 
                                   class="form-control" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ (opcional)</label>
                            <input type="text" name="cnpj" value="{{ old('cnpj') }}" 
                                   class="form-control" placeholder="00.000.000/0000-00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="form-control">
                                <option value="">Selecionar...</option>
                                <option value="Dinheiro" {{ old('forma_pagamento') == 'Dinheiro' ? 'selected' : '' }}>💵 Dinheiro</option>
                                <option value="Pix" {{ old('forma_pagamento') == 'Pix' ? 'selected' : '' }}>📱 Pix</option>
                                <option value="Cartão de Débito" {{ old('forma_pagamento') == 'Cartão de Débito' ? 'selected' : '' }}>💳 Cartão de Débito</option>
                                <option value="Cartão de Crédito" {{ old('forma_pagamento') == 'Cartão de Crédito' ? 'selected' : '' }}>💳 Cartão de Crédito</option>
                                <option value="Outros" {{ old('forma_pagamento') == 'Outros' ? 'selected' : '' }}>📦 Outros</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descontos (R$)</label>
                            <input type="text" name="descontos" id="descontos" value="{{ old('descontos', '0,00') }}" 
                                   class="form-control" oninput="atualizarTotais()">
                        </div>
                    </div>
                    
                    <!-- Chave de Acesso -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <label class="block text-sm font-medium text-gray-700 mb-1">🔑 Chave de Acesso (automática)</label>
                        <input type="text" name="chave" value="{{ old('chave', $proximaChave) }}" 
                               class="form-control text-xs font-mono bg-gray-100" readonly>
                        <p class="text-xs text-gray-500 mt-1">
                            Formato especial para lançamentos manuais (9999 + sequencial).<br>
                            Não conflita com NFC-e oficiais.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Itens da Compra -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-green-50 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">🛒 Itens da Compra</h2>
                    <button type="button" onclick="adicionarItem()" 
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                        <span class="text-lg">➕</span> Adicionar Item
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto *</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Qtde *</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Unid.</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vl. Unitário *</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vl. Total *</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="itensTableBody" class="bg-white divide-y divide-gray-200">
                            <tr class="item-row" data-index="0">
                                <td class="px-3 py-2">
                                    <input type="text" name="itens[0][nome]" 
                                           class="w-full border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm"
                                           placeholder="Nome do produto" required>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="itens[0][quantidade]" value="1"
                                           class="w-16 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm quantidade-input"
                                           data-index="0" oninput="recalcularItem(0, 'quantidade')">
                                </td>
                                <td class="px-3 py-2">
                                    <select name="itens[0][unidade]" class="w-16 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-1 py-1 text-xs">
                                        <option value="UN">UN</option>
                                        <option value="KG">KG</option>
                                        <option value="L">L</option>
                                        <option value="CX">CX</option>
                                        <option value="PC">PC</option>
                                        <option value="FD">FD</option>
                                        <option value="LT">LT</option>
                                        <option value="DZ">DZ</option>
                                        <option value="Bandeja">Bandeja</option>
                                        <option value="Pacote">Pacote</option>
                                        <option value="Maço">Maço</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end">
                                        <span class="text-gray-400 text-sm mr-1">R$</span>
                                        <input type="text" name="itens[0][valor_unitario]" value="0,00"
                                               class="w-20 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm unitario-input"
                                               data-index="0" oninput="recalcularItem(0, 'unitario')">
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end">
                                        <span class="text-gray-400 text-sm mr-1">R$</span>
                                        <input type="text" name="itens[0][valor_total]" value="0,00"
                                               class="w-20 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm font-semibold total-input"
                                               data-index="0" oninput="recalcularItem(0, 'total')">
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" onclick="removerItem(this)" class="text-red-400 hover:text-red-600 text-sm">✕</button>
                                    <input type="hidden" name="itens[0][ultimo_campo]" value="unitario" class="ultimo-campo-input" data-index="0">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Coluna Direita - Totais -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800">💰 Totais</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Qtd. Itens</span>
                            <span class="text-sm font-medium bg-gray-100 px-2 py-1 rounded" id="totalItens">1</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Valor Total</span>
                            <span class="text-lg font-bold text-gray-800" id="valorTotalLabel">R$ 0,00</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Descontos</span>
                            <span class="text-sm font-medium text-red-600" id="descontosLabel">R$ 0,00</span>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-800">Valor a Pagar</span>
                                <span class="text-xl font-bold text-green-600" id="valorPagoLabel">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full mt-6 text-lg py-3">
                        💾 Registrar Compra
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let itemIndex = 1;

function parseFloatBR(value) {
    if (!value) return 0;
    value = value.toString().replace(/[^\d,.-]/g, '');
    if (value.includes(',') && value.includes('.')) value = value.replace(/\./g, '');
    value = value.replace(',', '.');
    return parseFloat(value) || 0;
}

function formatMoney(value) {
    return value.toFixed(2).replace('.', ',');
}

function recalcularItem(index, campo) {
    const row = document.querySelector(`[data-index="${index}"]`);
    if (!row) return;
    
    const qtd = parseFloatBR(row.querySelector('.quantidade-input').value);
    const unit = parseFloatBR(row.querySelector('.unitario-input').value);
    const total = parseFloatBR(row.querySelector('.total-input').value);
    const ultimoCampo = row.querySelector('.ultimo-campo-input');
    
    if (qtd <= 0) return;
    
    if (campo === 'unitario') {
        row.querySelector('.total-input').value = formatMoney(unit * qtd);
        ultimoCampo.value = 'unitario';
    } else if (campo === 'total') {
        row.querySelector('.unitario-input').value = formatMoney(total / qtd);
        ultimoCampo.value = 'total';
    } else if (campo === 'quantidade') {
        if (ultimoCampo.value === 'total' && total > 0) {
            row.querySelector('.unitario-input').value = formatMoney(total / qtd);
        } else {
            row.querySelector('.total-input').value = formatMoney(unit * qtd);
        }
    }
    
    atualizarTotais();
}

function atualizarTotais() {
    let valorTotal = 0;
    let qtdItens = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        const total = parseFloatBR(row.querySelector('.total-input').value);
        if (total > 0) {
            valorTotal += total;
            qtdItens++;
        }
    });
    
    const descontos = parseFloatBR(document.getElementById('descontos').value || '0');
    const valorPago = Math.max(0, valorTotal - descontos);
    
    document.getElementById('totalItens').textContent = qtdItens;
    document.getElementById('valorTotalLabel').textContent = 'R$ ' + formatMoney(valorTotal);
    document.getElementById('descontosLabel').textContent = 'R$ ' + formatMoney(descontos);
    document.getElementById('valorPagoLabel').textContent = 'R$ ' + formatMoney(valorPago);
}

function adicionarItem() {
    const tbody = document.getElementById('itensTableBody');
    const row = document.createElement('tr');
    row.className = 'item-row bg-yellow-50';
    row.setAttribute('data-index', itemIndex);
    
    row.innerHTML = `
        <td class="px-3 py-2">
            <input type="text" name="itens[${itemIndex}][nome]" 
                   class="w-full border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm"
                   placeholder="Nome do produto" required>
        </td>
        <td class="px-3 py-2">
            <input type="text" name="itens[${itemIndex}][quantidade]" value="1"
                   class="w-16 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm quantidade-input"
                   data-index="${itemIndex}" oninput="recalcularItem(${itemIndex}, 'quantidade')">
        </td>
        <td class="px-3 py-2">
            <select name="itens[${itemIndex}][unidade]" class="w-16 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-1 py-1 text-xs">
                <option value="UN">UN</option><option value="KG">KG</option><option value="L">L</option>
                <option value="CX">CX</option><option value="PC">PC</option><option value="FD">FD</option>
                <option value="LT">LT</option><option value="DZ">DZ</option>
                <option value="Bandeja">Bandeja</option><option value="Pacote">Pacote</option><option value="Maço">Maço</option>
            </select>
        </td>
        <td class="px-3 py-2">
            <div class="flex items-center justify-end">
                <span class="text-gray-400 text-sm mr-1">R$</span>
                <input type="text" name="itens[${itemIndex}][valor_unitario]" value="0,00"
                       class="w-20 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm unitario-input"
                       data-index="${itemIndex}" oninput="recalcularItem(${itemIndex}, 'unitario')">
            </div>
        </td>
        <td class="px-3 py-2">
            <div class="flex items-center justify-end">
                <span class="text-gray-400 text-sm mr-1">R$</span>
                <input type="text" name="itens[${itemIndex}][valor_total]" value="0,00"
                       class="w-20 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm font-semibold total-input"
                       data-index="${itemIndex}" oninput="recalcularItem(${itemIndex}, 'total')">
            </div>
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" onclick="removerItem(this)" class="text-red-400 hover:text-red-600 text-sm">✕</button>
            <input type="hidden" name="itens[${itemIndex}][ultimo_campo]" value="unitario" class="ultimo-campo-input" data-index="${itemIndex}">
        </td>
    `;
    
    tbody.appendChild(row);
    itemIndex++;
    atualizarTotais();
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function removerItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) {
        alert('É necessário pelo menos um item.');
        return;
    }
    btn.closest('tr').remove();
    atualizarTotais();
}

document.addEventListener('DOMContentLoaded', function() {
    atualizarTotais();
});
</script>
<!-- Script adicional para garantir conversão -->
<script>
// Interceptar o submit para garantir formato correto
document.getElementById('formLancamento').addEventListener('submit', function(e) {
    // Converter todos os inputs de valor
    this.querySelectorAll('input').forEach(function(input) {
        if (input.name.includes('valor') || input.name.includes('quantidade') || input.name === 'descontos') {
            input.value = input.value.replace(/\./g, '').replace(',', '.');
        }
    });
});
</script>
@endpush
