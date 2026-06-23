@extends('layouts.app')

@section('title', 'Editar Nota #'.$invoice->numero)

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">✏️ Editar Nota Fiscal</h1>
            <p class="mt-1 text-gray-600">#{{ $invoice->numero }} - {{ $invoice->nome_estabelecimento }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="submit" form="formEditarNota"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                💾 Salvar Todas as Alterações
            </button>
            <a href="{{ route('invoices.show', $invoice) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">
                ← Cancelar
            </a>
        </div>
    </div>
</div>

<!-- Formulário principal de edição -->
<form id="formEditarNota" action="{{ route('invoices.update', $invoice) }}" method="POST">
    @csrf
    @method('PUT')

    {{-- Campos hidden que recebem os valores numéricos (ponto decimal) antes do submit --}}
    <input type="hidden" name="valor_pago" id="input_valor_pago"
           value="{{ old('valor_pago', $invoice->valor_pago) }}">
    <input type="hidden" name="descontos_raw" id="input_descontos_raw"
           value="{{ old('descontos_raw', $invoice->descontos) }}">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Coluna da Esquerda -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informações da Nota -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                    <h2 class="text-lg font-semibold text-gray-800">📄 Informações da Nota</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Estabelecimento</label>
                            <input type="text" name="nome_estabelecimento"
                                   value="{{ old('nome_estabelecimento', $invoice->nome_estabelecimento) }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm font-medium" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">CNPJ</label>
                            <input type="text" name="cnpj"
                                   value="{{ old('cnpj', $invoice->cnpj) }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Número</label>
                            <input type="text" name="numero"
                                   value="{{ old('numero', $invoice->numero) }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Série</label>
                            <input type="text" name="serie"
                                   value="{{ old('serie', $invoice->serie) }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Data de Emissão</label>
                            <input type="datetime-local" name="data_emissao"
                                   value="{{ old('data_emissao', $invoice->data_emissao->format('Y-m-d\TH:i')) }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Itens da Nota -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-green-50 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">🛒 Itens da Compra</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qtde</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Unid.</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vl. Unitário</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase text-orange-600">Desconto R$</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vl. Total</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="itensTableBody">
                            @foreach($invoice->items as $item)
                            <tr class="hover:bg-gray-50 item-row" data-item-id="{{ $item->id }}">
                                <td class="px-3 py-2">
                                    <input type="text"
                                           name="itens[{{ $item->id }}][nome]"
                                           value="{{ $item->product->nome }}"
                                           class="w-full border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text"
                                           name="itens[{{ $item->id }}][quantidade]"
                                           value="{{ number_format($item->quantidade, 3, ',', '.') }}"
                                           class="w-20 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm quantidade-input"
                                           data-item-id="{{ $item->id }}"
                                           oninput="recalcularItem('{{ $item->id }}', 'quantidade')">
                                </td>
                                <td class="px-3 py-2">
                                    <select name="itens[{{ $item->id }}][unidade]"
                                            class="w-20 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-1 py-1 text-xs">
                                        <option value="UN" {{ $item->unidade == 'UN' ? 'selected' : '' }}>UN</option>
                                        <option value="KG" {{ $item->unidade == 'KG' ? 'selected' : '' }}>KG</option>
                                        <option value="L" {{ $item->unidade == 'L' ? 'selected' : '' }}>L</option>
                                        <option value="CX" {{ $item->unidade == 'CX' ? 'selected' : '' }}>CX</option>
                                        <option value="PC" {{ $item->unidade == 'PC' ? 'selected' : '' }}>PC</option>
                                        <option value="FD" {{ $item->unidade == 'FD' ? 'selected' : '' }}>FD</option>
                                        <option value="LT" {{ $item->unidade == 'LT' ? 'selected' : '' }}>LT</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end">
                                        <span class="text-gray-400 text-sm mr-1">R$</span>
                                        <input type="text"
                                               name="itens[{{ $item->id }}][valor_unitario]"
                                               value="{{ number_format($item->valor_unitario, 2, ',', '.') }}"
                                               class="w-24 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm unitario-input"
                                               data-item-id="{{ $item->id }}"
                                               oninput="recalcularItem('{{ $item->id }}', 'unitario')">
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end">
                                        <span class="text-orange-400 text-sm mr-1">R$</span>
                                        <input type="text"
                                               value=""
                                               placeholder="0,00"
                                               class="w-20 text-right border-0 border-b border-orange-300 focus:border-orange-500 focus:ring-0 px-2 py-1 text-sm desconto-input text-orange-600 font-medium"
                                               data-item-id="{{ $item->id }}"
                                               oninput="recalcularItem('{{ $item->id }}', 'desconto')">
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end">
                                        <span class="text-gray-400 text-sm mr-1">R$</span>
                                        <input type="text"
                                               name="itens[{{ $item->id }}][valor_total]"
                                               value="{{ number_format($item->valor_total, 2, ',', '.') }}"
                                               class="w-24 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm font-semibold total-input"
                                               data-item-id="{{ $item->id }}"
                                               oninput="recalcularItem('{{ $item->id }}', 'total')">
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button"
                                            onclick="removerItem(this, '{{ $item->id }}')"
                                            class="text-red-500 hover:text-red-700 text-lg"
                                            title="Remover item">
                                        🗑️
                                    </button>
                                    <input type="hidden" name="itens[{{ $item->id }}][ultimo_campo]"
                                           value="unitario" class="ultimo-campo-input" data-item-id="{{ $item->id }}">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="px-4 py-3 bg-gray-50">
                                    <button type="button" onclick="adicionarItem()"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                                        <span class="text-lg">➕</span> Adicionar novo item
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Botão Salvar (dentro do form) -->
            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white text-lg font-semibold rounded-md transition">
                    💾 Salvar Todas as Alterações
                </button>
            </div>
        </div>

        <!-- Coluna da Direita -->
        <div class="space-y-6">
            <!-- Totais -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800">💰 Totais</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Qtd. Itens</span>
                            <span class="text-sm font-medium" id="totalItens">{{ $invoice->items->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Valor Total</span>
                            <span class="text-sm font-medium" id="valorTotalLabel">
                                R$ {{ number_format($invoice->valor_total, 2, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Descontos</label>
                            <div class="flex items-center">
                                <span class="text-gray-400 mr-1">R$</span>
                                {{-- Campo visual (BR) — NÃO tem name para não ir no POST --}}
                                <input type="text" id="descontos"
                                       value="{{ old('descontos_raw') !== null ? number_format(old('descontos_raw'), 2, ',', '.') : number_format($invoice->descontos, 2, ',', '.') }}"
                                       class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm"
                                       oninput="atualizarValorPago()">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Status</label>
                            <select name="status"
                                    class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                <option value="pago"   {{ old('status', $invoice->status) == 'pago'    ? 'selected' : '' }}>✅ Pago</option>
                                <option value="pgoCC"  {{ old('status', $invoice->status) == 'pgoCC'   ? 'selected' : '' }}>💳 Pago CC</option>
                                <option value="pendente" {{ old('status', $invoice->status) == 'pendente' ? 'selected' : '' }}>⏳ Pendente</option>
                            </select>
                        </div>
                        <div class="border-t pt-3 mt-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-800">Valor Pago</span>
                                <span class="text-lg font-bold text-green-600" id="valorPagoLabel">
                                    R$ {{ number_format($invoice->valor_pago, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Forma de Pagamento</label>
                            <input type="text" name="forma_pagamento" id="forma_pagamento"
                                   value="{{ old('forma_pagamento', $invoice->forma_pagamento) }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Formulário SEPARADO para exclusão -->
<div class="mt-6 max-w-lg mx-auto">
    <form id="formExcluirNota" action="{{ route('invoices.destroy', $invoice) }}" method="POST"
          onsubmit="return confirm('Tem certeza que deseja EXCLUIR esta nota fiscal?\n\nEsta ação NÃO PODE ser desfeita!')">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-md transition">
            🗑️ Excluir Nota Fiscal
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
let novoItemIndex = 0;

function parseFloatBR(value) {
    if (!value) return 0;
    value = value.toString().replace(/[^\d,.-]/g, '');
    if (value.includes(',') && value.includes('.')) {
        value = value.replace(/\./g, '');
    }
    value = value.replace(',', '.');
    return parseFloat(value) || 0;
}

function formatMoney(value) {
    return value.toFixed(2).replace('.', ',');
}

function recalcularItem(itemId, campoAlterado) {
    const row = document.querySelector(`[data-item-id="${itemId}"]`);
    if (!row) return;

    const qtdInput         = row.querySelector('.quantidade-input');
    const unitInput        = row.querySelector('.unitario-input');
    const descontoInput    = row.querySelector('.desconto-input');
    const totalInput       = row.querySelector('.total-input');
    const ultimoCampoInput = row.querySelector('.ultimo-campo-input');

    const quantidade = parseFloatBR(qtdInput.value);
    if (quantidade <= 0) return;

    if (campoAlterado === 'desconto') {
        const totalOriginal = parseFloat(totalInput.dataset.originalTotal);
        const descontoRS    = parseFloatBR(descontoInput.value);
        const novoTotal     = Math.max(0, totalOriginal - descontoRS);
        const novoUnitario  = novoTotal / quantidade;
        totalInput.value = formatMoney(novoTotal);
        unitInput.value  = formatMoney(novoUnitario);
        ultimoCampoInput.value = 'unitario';
    } else if (campoAlterado === 'unitario') {
        const novoTotal = parseFloatBR(unitInput.value) * quantidade;
        totalInput.value = formatMoney(novoTotal);
        totalInput.dataset.originalTotal = novoTotal.toFixed(2);
        if (descontoInput) descontoInput.value = '';
        ultimoCampoInput.value = 'unitario';
    } else if (campoAlterado === 'total') {
        const total = parseFloatBR(totalInput.value);
        unitInput.value = formatMoney(total / quantidade);
        totalInput.dataset.originalTotal = total.toFixed(2);
        if (descontoInput) descontoInput.value = '';
        ultimoCampoInput.value = 'total';
    } else if (campoAlterado === 'quantidade') {
        if (ultimoCampoInput.value === 'total') {
            const total = parseFloatBR(totalInput.value);
            unitInput.value = formatMoney(total / quantidade);
            totalInput.dataset.originalTotal = total.toFixed(2);
        } else {
            const novoTotal = parseFloatBR(unitInput.value) * quantidade;
            totalInput.value = formatMoney(novoTotal);
            totalInput.dataset.originalTotal = novoTotal.toFixed(2);
        }
        if (descontoInput) descontoInput.value = '';
    }

    atualizarTotais();
}

function atualizarTotais() {
    let valorTotal = 0;
    let qtdItens   = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        valorTotal += parseFloatBR(row.querySelector('.total-input').value);
        qtdItens++;
    });

    const descontos = parseFloatBR(document.getElementById('descontos').value);
    const valorPago = Math.max(0, valorTotal - descontos);

    document.getElementById('totalItens').textContent      = qtdItens;
    document.getElementById('valorTotalLabel').textContent = 'R$ ' + formatMoney(valorTotal);
    document.getElementById('valorPagoLabel').textContent  = 'R$ ' + formatMoney(valorPago);

    // Atualiza os hidden que serão submetidos com ponto decimal
    document.getElementById('input_valor_pago').value   = valorPago.toFixed(2);
    document.getElementById('input_descontos_raw').value = descontos.toFixed(2);
}

function atualizarValorPago() {
    atualizarTotais();
}

function removerItem(btn, itemId) {
    if (!confirm('Tem certeza que deseja remover este item?')) return;
    btn.closest('tr').remove();

    const form  = document.getElementById('formEditarNota');
    const input = document.createElement('input');
    input.type  = 'hidden';
    input.name  = 'itens_removidos[]';
    input.value = itemId;
    form.appendChild(input);

    atualizarTotais();
}

function adicionarItem() {
    novoItemIndex++;
    const newId  = 'novo_' + novoItemIndex;
    const tbody  = document.getElementById('itensTableBody');
    const newRow = document.createElement('tr');
    newRow.className = 'hover:bg-gray-50 item-row bg-yellow-50';
    newRow.setAttribute('data-item-id', newId);

    newRow.innerHTML = `
        <td class="px-3 py-2">
            <input type="text" name="itens[${newId}][nome]" value=""
                   class="w-full border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm"
                   placeholder="Nome do novo produto" required>
        </td>
        <td class="px-3 py-2">
            <input type="text" name="itens[${newId}][quantidade]" value="1"
                   class="w-20 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm quantidade-input"
                   data-item-id="${newId}"
                   oninput="recalcularItem('${newId}', 'quantidade')">
        </td>
        <td class="px-3 py-2">
            <select name="itens[${newId}][unidade]"
                    class="w-20 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-1 py-1 text-xs">
                <option value="UN">UN</option>
                <option value="KG">KG</option>
                <option value="L">L</option>
                <option value="CX">CX</option>
                <option value="PC">PC</option>
                <option value="FD">FD</option>
                <option value="LT">LT</option>
            </select>
        </td>
        <td class="px-3 py-2">
            <div class="flex items-center justify-end">
                <span class="text-gray-400 text-sm mr-1">R$</span>
                <input type="text" name="itens[${newId}][valor_unitario]" value="0,00"
                       class="w-24 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm unitario-input"
                       data-item-id="${newId}"
                       oninput="recalcularItem('${newId}', 'unitario')">
            </div>
        </td>
        <td class="px-3 py-2">
            <div class="flex items-center justify-end">
                <span class="text-orange-400 text-sm mr-1">R$</span>
                <input type="text" value="" placeholder="0,00"
                       class="w-20 text-right border-0 border-b border-orange-300 focus:border-orange-500 focus:ring-0 px-2 py-1 text-sm desconto-input text-orange-600 font-medium"
                       data-item-id="${newId}"
                       oninput="recalcularItem('${newId}', 'desconto')">
            </div>
        </td>
        <td class="px-3 py-2">
            <div class="flex items-center justify-end">
                <span class="text-gray-400 text-sm mr-1">R$</span>
                <input type="text" name="itens[${newId}][valor_total]" value="0,00"
                       class="w-24 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm font-semibold total-input"
                       data-original-total="0"
                       data-item-id="${newId}"
                       oninput="recalcularItem('${newId}', 'total')">
            </div>
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" onclick="this.closest('tr').remove(); atualizarTotais();"
                    class="text-red-500 hover:text-red-700 text-lg" title="Remover item">
                🗑️
            </button>
            <input type="hidden" name="itens[${newId}][ultimo_campo]" value="unitario"
                   class="ultimo-campo-input" data-item-id="${newId}">
            <input type="hidden" name="itens[${newId}][novo]" value="1">
        </td>
    `;

    tbody.appendChild(newRow);
    atualizarTotais();
    newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.total-input').forEach(function (input) {
        input.dataset.originalTotal = parseFloatBR(input.value).toFixed(2);
    });
    atualizarTotais();
});
</script>
@endpush
