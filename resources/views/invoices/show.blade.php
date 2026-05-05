@extends('layouts.app')

@section('title', 'Nota #' . $invoice->numero)

@section('content')
{{-- Cabeçalho --}}
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Nota Fiscal</h1>
            <p class="mt-1 text-gray-600">Detalhes da compra #{{ $invoice->numero }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('invoices.edit', $invoice) }}"
               class="inline-flex items-center px-3 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 text-sm font-semibold rounded-md transition">✏️ Editar</a>
            <a href="{{ route('invoices.index') }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">← Voltar</a>
        </div>
    </div>
</div>

{{-- Conteúdo Principal --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Coluna Esquerda --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Informações da Nota --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">📄 Informações da Nota</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Estabelecimento</span>
                        <p class="text-sm text-gray-900 mt-1">{{ $invoice->nome_estabelecimento }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">CNPJ</span>
                        <p class="text-sm text-gray-900 mt-1">{{ $invoice->cnpj }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Número / Série</span>
                        <p class="text-sm text-gray-900 mt-1">{{ $invoice->numero }} (Série: {{ $invoice->serie }})</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Data de Emissão</span>
                        <p class="text-sm text-gray-900 mt-1">{{ $invoice->data_emissao->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase">Pagamento</span>
                        <p class="text-sm text-gray-900 mt-1">{{ $invoice->forma_pagamento ?: 'Não informado' }}</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <span class="text-xs font-medium text-gray-500 uppercase">Chave de Acesso</span>
                    <p class="text-xs text-gray-500 font-mono break-all mt-1">{{ $invoice->chave_formatada ?? chunk_split($invoice->chave, 4, ' ') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">🛒 Itens da Compra</h2>
            </div>

            @if($invoice->items->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qtde</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Unid.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vl. Unit.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vl. Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoice->items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('products.show', $item->product) }}"
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            {{ $item->product->nome }}
                                        </a>
                                        @if($item->codigo_produto)
                                            <div class="text-xs text-gray-400">Cód: {{ $item->codigo_produto }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        @if(strtoupper($item->unidade) === 'KG')
                                            {{ number_format($item->quantidade, 3, ',', '.') }}
                                        @else
                                            {{ number_format($item->quantidade, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $item->unidade }}</td>
                                    <td class="px-4 py-3 text-sm text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <span class="text-5xl" aria-hidden="true">📦</span>
                    <p class="mt-3 text-gray-500">Nenhum item registrado nesta nota.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Coluna Direita --}}
    <div class="space-y-6">

        {{-- Totais --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">💰 Totais</h2>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Qtd. Itens</span>
                        <span class="text-sm font-medium bg-gray-100 px-2 py-1 rounded text-gray-800">{{ $invoice->total_itens }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Valor Total</span>
                        <span class="text-sm font-medium text-gray-800">R$ {{ number_format($invoice->valor_total, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Descontos</span>
                        <span class="text-sm font-medium text-red-600">- R$ {{ number_format($invoice->descontos, 2, ',', '.') }}</span>
                    </div>
                    <div class="border-t pt-3 mt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-800">Valor Pago</span>
                            <span class="text-base font-bold text-green-600">R$ {{ number_format($invoice->valor_pago, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ações --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">⚙️ Ações</h2>
            </div>
            <div class="p-6 space-y-3">
                <a href="{{ route('invoices.edit', $invoice) }}"
                   class="block text-center w-full inline-flex items-center justify-center px-3 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 text-sm font-semibold rounded-md transition">✏️ Editar Nota</a>
                {{-- type="button": abre painel de confirmação; não submete form --}}
                <button type="button"
                        id="btnAbrirExcluir"
                        class="w-full inline-flex items-center justify-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-md transition">
                    🗑️ Excluir Nota
                </button>
            </div>
        </div>

        {{-- Confirmação de Exclusão (inline) --}}
        <div id="painelExcluir" class="hidden bg-red-50 border border-red-200 rounded-lg p-4"
             role="alertdialog" aria-labelledby="excluirMsg">
            <p id="excluirMsg" class="text-sm text-red-700 mb-3">
                ⚠️ Tem certeza? Esta ação não pode ser desfeita.
            </p>
            <div class="flex gap-2">
                <button type="button" id="btnCancelarExcluir"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold rounded-md transition">Cancelar</button>
                <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-md transition">🗑️ Confirmar Exclusão</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const btnAbrir    = document.getElementById('btnAbrirExcluir');
    const btnCancelar = document.getElementById('btnCancelarExcluir');
    const painel      = document.getElementById('painelExcluir');

    function abrirModal()  { painel.classList.remove('hidden'); btnCancelar.focus(); }
    function fecharModal() { painel.classList.add('hidden');    btnAbrir.focus(); }

    btnAbrir.addEventListener('click', abrirModal);
    btnCancelar.addEventListener('click', fecharModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharModal(); });
</script>
@endpush
@endsection
