@extends('layouts.app')

@section('title', 'Lançamento Manual')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">✍️ Lançamento Manual</h1>
            <p class="mt-1 text-gray-600">Registre compras sem NFC-e (feira, pequenos comércios, etc.)</p>
        </div>
        <a href="{{ route('invoices.index') }}"
           class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">
            ← Voltar
        </a>
    </div>
</div>

<form id="formLancamento" action="{{ route('lancamento.store') }}" method="POST">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Coluna Principal --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Dados da Compra --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
                    <h2 class="text-lg font-semibold text-gray-800">📝 Dados da Compra</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="sm:col-span-2">
                            <label for="nomeEstabelecimento" class="block text-sm font-medium text-gray-700 mb-1">Estabelecimento *</label>
                            <input type="text" name="nome_estabelecimento" id="nomeEstabelecimento"
                                   value="{{ old('nome_estabelecimento') }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required
                                   placeholder="Ex: Feira do Produtor, Mercado do Bairro...">
                        </div>

                        <div>
                            <label for="dataEmissao" class="block text-sm font-medium text-gray-700 mb-1">Data da Compra *</label>
                            <input type="datetime-local" name="data_emissao" id="dataEmissao"
                                   value="{{ old('data_emissao', now()->format('Y-m-d\TH:i')) }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm" required>
                        </div>

                        <div>
                            <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-1">CNPJ (opcional)</label>
                            <input type="text" name="cnpj" id="cnpj"
                                   value="{{ old('cnpj') }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                   placeholder="00.000.000/0000-00">
                        </div>

                        <div>
                            <label for="formaPagamento" class="block text-sm font-medium text-gray-700 mb-1">Forma de Pagamento</label>
                            <select name="forma_pagamento" id="formaPagamento"
                                    class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                <option value="">Selecionar...</option>
                                <option value="Dinheiro"         {{ old('forma_pagamento') === 'Dinheiro'           ? 'selected' : '' }}>💵 Dinheiro</option>
                                <option value="Pix"              {{ old('forma_pagamento') === 'Pix'                ? 'selected' : '' }}>📱 Pix</option>
                                <option value="Cartão de Débito" {{ old('forma_pagamento') === 'Cartão de Débito'  ? 'selected' : '' }}>💳 Cartão de Débito</option>
                                <option value="Cartão de Crédito" {{ old('forma_pagamento') === 'Cartão de Crédito' ? 'selected' : '' }}>💳 Cartão de Crédito</option>
                                <option value="Outros"            {{ old('forma_pagamento') === 'Outros'             ? 'selected' : '' }}>📦 Outros</option>
                            </select>
                        </div>

                        <div>
                            <label for="descontos" class="block text-sm font-medium text-gray-700 mb-1">Descontos (R$)</label>
                            <input type="text" name="descontos" id="descontos"
                                   value="{{ old('descontos', '0,00') }}"
                                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                   inputmode="decimal">
                        </div>
                    </div>

                    {{-- Chave de Acesso --}}
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <label for="chaveAcesso" class="block text-sm font-medium text-gray-700 mb-1">🔑 Chave de Acesso (automática)</label>
                        <input type="text" name="chave" id="chaveAcesso"
                               value="{{ old('chave', $proximaChave) }}"
                               class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs font-mono bg-gray-100" readonly>
                        <p class="text-xs text-gray-500 mt-1">
                            Formato especial para lançamentos manuais (9999 + sequencial).<br>
                            Não conflita com NFC-e oficiais.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Itens da Compra --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-green-50 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">🛒 Itens da Compra</h2>
                    <button type="button" id="btnAdicionarItem"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                        <span class="text-lg" aria-hidden="true">➕</span> Adicionar Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" aria-label="Itens da compra">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-left   text-xs font-medium text-gray-500 uppercase">Produto *</th>
                                <th scope="col" class="px-3 py-3 text-center  text-xs font-medium text-gray-500 uppercase w-20">Qtde *</th>
                                <th scope="col" class="px-3 py-3 text-center  text-xs font-medium text-gray-500 uppercase w-20">Unid.</th>
                                <th scope="col" class="px-3 py-3 text-right   text-xs font-medium text-gray-500 uppercase">Vl. Unitário *</th>
                                <th scope="col" class="px-3 py-3 text-right   text-xs font-medium text-gray-500 uppercase">Vl. Total *</th>
                                <th scope="col" class="px-3 py-3 text-center  text-xs font-medium text-gray-500 uppercase w-12"><span class="sr-only">Remover</span></th>
                            </tr>
                        </thead>
                        <tbody id="itensTableBody" class="bg-white divide-y divide-gray-200">
                            {{-- linha inicial gerada pelo JS via criarLinhaItem(0) --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Coluna Direita — Totais --}}
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800">💰 Totais</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Qtd. Itens</span>
                            <span class="text-sm font-medium bg-gray-100 px-2 py-1 rounded tabular-nums" id="totalItens">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Valor Total</span>
                            <span class="text-lg font-bold text-gray-800 tabular-nums" id="valorTotalLabel">R$ 0,00</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Descontos</span>
                            <span class="text-sm font-medium text-red-600 tabular-nums" id="descontosLabel">R$ 0,00</span>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-800">Valor a Pagar</span>
                                <span class="text-xl font-bold text-green-600 tabular-nums" id="valorPagoLabel">R$ 0,00</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full inline-flex items-center justify-center mt-6 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-lg font-semibold rounded-md transition">
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
{{--
    Módulo encapsulado: sem variáveis globais expostas, sem oninput inline,
    sem dois blocos <script> separados, sem alert() nativo.
--}}
(function () {
    'use strict';

    let itemIndex = 0;
    const UNIDADES = ['UN','KG','L','CX','PC','FD','LT','DZ','Bandeja','Pacote','Maço'];

    /* ── Helpers numéricos ──────────────────────────────────── */
    function parseFloatBR(value) {
        if (!value) return 0;
        let v = value.toString().replace(/[^\d,.-]/g, '');
        if (v.includes(',') && v.includes('.')) v = v.replace(/\./g, '');
        v = v.replace(',', '.');
        return parseFloat(v) || 0;
    }

    function formatMoney(value) {
        return value.toFixed(2).replace('.', ',');
    }

    /* ── Criação de linha ────────────────────────────────────── */
    function opcoesUnidade(idx) {
        return UNIDADES.map(u =>
            `<option value="${u}">${u}</option>`
        ).join('');
    }

    function criarLinhaItem(idx) {
        const tr = document.createElement('tr');
        tr.className = 'item-row' + (idx > 0 ? ' bg-yellow-50' : '');
        tr.dataset.index = idx;
        tr.innerHTML = `
            <td class="px-3 py-2">
                <label for="item_nome_${idx}" class="sr-only">Nome do produto (item ${idx + 1})</label>
                <input type="text" name="itens[${idx}][nome]" id="item_nome_${idx}"
                       class="w-full border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm"
                       placeholder="Nome do produto" required>
            </td>
            <td class="px-3 py-2">
                <label for="item_qtd_${idx}" class="sr-only">Quantidade (item ${idx + 1})</label>
                <input type="text" name="itens[${idx}][quantidade]" id="item_qtd_${idx}" value="1"
                       class="w-16 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm quantidade-input"
                       inputmode="decimal">
            </td>
            <td class="px-3 py-2">
                <label for="item_unid_${idx}" class="sr-only">Unidade (item ${idx + 1})</label>
                <select name="itens[${idx}][unidade]" id="item_unid_${idx}"
                        class="w-16 text-center border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-1 py-1 text-xs">
                    ${opcoesUnidade(idx)}
                </select>
            </td>
            <td class="px-3 py-2">
                <div class="flex items-center justify-end">
                    <span class="text-gray-400 text-sm mr-1" aria-hidden="true">R$</span>
                    <label for="item_unit_${idx}" class="sr-only">Valor unitário (item ${idx + 1})</label>
                    <input type="text" name="itens[${idx}][valor_unitario]" id="item_unit_${idx}" value="0,00"
                           class="w-20 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm unitario-input"
                           inputmode="decimal">
                </div>
            </td>
            <td class="px-3 py-2">
                <div class="flex items-center justify-end">
                    <span class="text-gray-400 text-sm mr-1" aria-hidden="true">R$</span>
                    <label for="item_total_${idx}" class="sr-only">Valor total (item ${idx + 1})</label>
                    <input type="text" name="itens[${idx}][valor_total]" id="item_total_${idx}" value="0,00"
                           class="w-20 text-right border-0 border-b border-gray-200 focus:border-blue-500 focus:ring-0 px-2 py-1 text-sm font-semibold total-input"
                           inputmode="decimal">
                </div>
            </td>
            <td class="px-3 py-2 text-center">
                <button type="button" class="btn-remover text-red-400 hover:text-red-600 text-sm"
                        aria-label="Remover item ${idx + 1}">✕</button>
                <input type="hidden" name="itens[${idx}][ultimo_campo]" value="unitario"
                       class="ultimo-campo-input">
            </td>
        `;
        return tr;
    }

    /* ── Recálculo de uma linha ─────────────────────────────────── */
    function recalcularLinha(row, campo) {
        const qtdEl   = row.querySelector('.quantidade-input');
        const unitEl  = row.querySelector('.unitario-input');
        const totalEl = row.querySelector('.total-input');
        const ucEl    = row.querySelector('.ultimo-campo-input');

        const qtd   = parseFloatBR(qtdEl.value);
        const unit  = parseFloatBR(unitEl.value);
        const total = parseFloatBR(totalEl.value);

        if (qtd <= 0) return;

        if (campo === 'unitario') {
            totalEl.value = formatMoney(unit * qtd);
            ucEl.value    = 'unitario';
        } else if (campo === 'total') {
            unitEl.value = formatMoney(total / qtd);
            ucEl.value   = 'total';
        } else if (campo === 'quantidade') {
            if (ucEl.value === 'total' && total > 0) {
                unitEl.value = formatMoney(total / qtd);
            } else {
                totalEl.value = formatMoney(unit * qtd);
            }
        }

        atualizarTotais();
    }

    /* ── Totais globais ───────────────────────────────────────── */
    function atualizarTotais() {
        let valorTotal = 0;
        let qtdItens   = 0;

        document.querySelectorAll('.item-row').forEach(function (row) {
            const total = parseFloatBR(row.querySelector('.total-input').value);
            if (total > 0) { valorTotal += total; qtdItens++; }
        });

        const descontos = parseFloatBR(document.getElementById('descontos').value || '0');
        const valorPago = Math.max(0, valorTotal - descontos);

        document.getElementById('totalItens').textContent    = qtdItens;
        document.getElementById('valorTotalLabel').textContent = 'R$ ' + formatMoney(valorTotal);
        document.getElementById('descontosLabel').textContent  = 'R$ ' + formatMoney(descontos);
        document.getElementById('valorPagoLabel').textContent  = 'R$ ' + formatMoney(valorPago);
    }

    /* ── Adicionar / Remover linhas ───────────────────────────────── */
    function adicionarItem() {
        const tbody = document.getElementById('itensTableBody');
        const row   = criarLinhaItem(itemIndex);
        tbody.appendChild(row);
        itemIndex++;
        atualizarTotais();
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.querySelector('input[type="text"]').focus();
    }

    function removerItem(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            /* Substituído alert() por banner inline não bloqueante */
            const banner = document.getElementById('bannerMinItem');
            if (banner) { banner.hidden = false; setTimeout(() => { banner.hidden = true; }, 3000); }
            return;
        }
        btn.closest('tr').remove();
        atualizarTotais();
    }

    /* ── Delegação de eventos na tabela ──────────────────────────── */
    function bindTabela() {
        const tbody = document.getElementById('itensTableBody');

        tbody.addEventListener('input', function (e) {
            const row = e.target.closest('.item-row');
            if (!row) return;
            if (e.target.classList.contains('unitario-input'))   recalcularLinha(row, 'unitario');
            if (e.target.classList.contains('total-input'))      recalcularLinha(row, 'total');
            if (e.target.classList.contains('quantidade-input')) recalcularLinha(row, 'quantidade');
        });

        tbody.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remover')) removerItem(e.target);
        });
    }

    /* ── Submit: normalizar vírgula → ponto ──────────────────────────── */
    function bindSubmit() {
        document.getElementById('formLancamento').addEventListener('submit', function () {
            this.querySelectorAll('input').forEach(function (input) {
                if (input.name.includes('valor') ||
                    input.name.includes('quantidade') ||
                    input.name === 'descontos') {
                    input.value = input.value.replace(/\./g, '').replace(',', '.');
                }
            });
        });
    }

    /* ── Init ───────────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('btnAdicionarItem')
                .addEventListener('click', adicionarItem);
        document.getElementById('descontos')
                .addEventListener('input', atualizarTotais);
        bindTabela();
        bindSubmit();
        adicionarItem(); /* cria a primeira linha */
    });
}());
</script>
@endpush
