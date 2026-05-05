@extends('layouts.app')

@section('title', $lista->nome)

@section('content')
<div class="mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🛒 {{ $lista->nome }}</h1>
            <p class="mt-1 text-gray-600 text-sm">Criada em {{ $lista->created_at->format('d/m/Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($lista->ativa)
                <form action="{{ route('shopping-lists.finalizar', $lista) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md transition">✅ Finalizar Compra</button>
                </form>
            @else
                <form action="{{ route('shopping-lists.reabrir', $lista) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">🔓 Reabrir</button>
                </form>
            @endif
            <a href="{{ route('shopping-lists.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">← Voltar</a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4" role="status">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4" role="alert">❌ {{ session('error') }}</div>
@endif

<!-- Progresso -->
@php $total = $lista->items->count(); $comprados = $lista->items->where('comprado', true)->count(); @endphp
@if($total > 0)
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <div class="flex justify-between text-sm text-gray-600 mb-1">
        <span>{{ $comprados }} de {{ $total }} itens comprados</span>
        <span>{{ $total > 0 ? intval(($comprados / $total) * 100) : 0 }}%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2" role="progressbar"
         aria-valuenow="{{ $total > 0 ? intval(($comprados / $total) * 100) : 0 }}"
         aria-valuemin="0" aria-valuemax="100">
        <div class="h-2 rounded-full bg-green-500 transition-all"
             style="width: {{ $total > 0 ? ($comprados / $total) * 100 : 0 }}%"></div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Lista de Itens -->
    <div class="lg:col-span-2">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">📋 Itens da Lista</h2>

        @if($lista->items->isEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center text-gray-500">
                <p>Nenhum item adicionado ainda.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($lista->items->sortBy('comprado') as $item)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 flex items-center gap-3 {{ $item->comprado ? 'opacity-60' : '' }}">
                    <form action="{{ route('items.toggle', $item) }}" method="POST" class="flex-shrink-0">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="w-6 h-6 rounded border-2 flex items-center justify-center transition
                                       {{ $item->comprado ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 hover:border-green-400' }}"
                                aria-label="{{ $item->comprado ? 'Desmarcar' : 'Marcar como comprado' }}">
                            @if($item->comprado)✓@endif
                        </button>
                    </form>

                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 {{ $item->comprado ? 'line-through text-gray-400' : '' }} truncate">{{ $item->nome }}</p>
                        <p class="text-xs text-gray-500">{{ $item->quantidade }} {{ $item->unidade }}</p>
                    </div>

                    @if($item->valor_unitario)
                    <div class="text-right text-sm">
                        <p class="text-gray-600">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400">{{ number_format($item->quantidade * $item->valor_unitario, 2, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($lista->ativa)
                    <form action="{{ route('items.remove', $item) }}" method="POST" class="flex-shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-500 transition" aria-label="Remover item">✕</button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
        @endif

        <!-- Formulário Adicionar Item Manual -->
        @if($lista->ativa)
        <div class="mt-4 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">➕ Adicionar Item</h3>
            <form action="{{ route('shopping-lists.items.add', $lista) }}" method="POST">
                @csrf
                <div class="flex flex-wrap gap-2">
                    <input type="text" name="nome"
                           placeholder="Nome do produto" class="flex-1 min-w-[150px] border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm" required>
                    <input type="number" name="quantidade" step="0.01"
                           value="1" class="w-20 text-center border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm" required>
                    <select name="unidade" id="itemUnidade" class="w-20 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                        <option>un</option><option>kg</option><option>g</option>
                        <option>l</option><option>ml</option><option>cx</option><option>pct</option>
                    </select>
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">Adicionar</button>
                </div>
            </form>
        </div>
        @endif
    </div>

    <!-- Painel Lateral -->
    <div class="space-y-4">

        <!-- Produtos Frequentes -->
        @if($lista->ativa && $produtosFrequentes->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">💡 Adicionar Frequentes</h3>
            <form action="{{ route('shopping-lists.frequentes', $lista) }}" method="POST">
                @csrf
                <div class="space-y-2 mb-3 max-h-64 overflow-y-auto">
                    @foreach($produtosFrequentes as $produto)
                    <label class="flex items-center gap-2 cursor-pointer p-1.5 rounded hover:bg-gray-50">
                        <input type="checkbox" name="produtos[]" value="{{ $produto->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">{{ $produto->nome }}</span>
                        <span class="ml-auto text-xs text-gray-400">{{ $produto->invoice_items_count }}×</span>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">Adicionar Selecionados</button>
            </form>
        </div>
        @endif

        <!-- Resumo Financeiro -->
        @if($lista->items->whereNotNull('valor_unitario')->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">💰 Resumo Financeiro</h3>
            <div class="space-y-1 text-sm">
                @php
                    $valorTotal    = $lista->items->sum(fn($i) => $i->quantidade * $i->valor_unitario);
                    $valorComprado = $lista->items->where('comprado', true)->sum(fn($i) => $i->quantidade * $i->valor_unitario);
                    $valorPendente = $valorTotal - $valorComprado;
                @endphp
                <div class="flex justify-between">
                    <span class="text-gray-500">Total estimado</span>
                    <span class="font-medium">R$ {{ number_format($valorTotal, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Já comprado</span>
                    <span class="text-green-600 font-medium">R$ {{ number_format($valorComprado, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-t border-gray-100 pt-1 mt-1">
                    <span class="text-gray-500">Pendente</span>
                    <span class="font-bold">R$ {{ number_format($valorPendente, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
        @endif

        <!-- Ações da Lista -->
        @if($lista->ativa)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">⚙️ Ações da Lista</h3>

            {{-- Renomear --}}
            <button type="button" id="btnRenomear"
                    class="w-full text-left text-sm text-gray-600 hover:text-gray-800 py-1 transition">✏️ Renomear lista</button>

            <div id="formRenomear" class="hidden mt-2">
                <form action="{{ route('shopping-lists.update', $lista) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <label for="inputRenomear" class="sr-only">Novo nome</label>
                    <input type="text" name="nome" id="inputRenomear" value="{{ $lista->nome }}"
                           class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm mb-4" placeholder="Novo nome..." required>
                    <div class="flex gap-2">
                        <button type="button" id="btnFecharRenomear" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">Cancelar</button>
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">Salvar</button>
                    </div>
                </form>
            </div>

            {{-- Excluir --}}
            <form action="{{ route('shopping-lists.destroy', $lista) }}" method="POST" class="mt-2"
                  data-confirm="Excluir esta lista permanentemente?">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full text-left text-sm text-red-500 hover:text-red-700 py-1 transition">🗑 Excluir lista</button>
            </form>
        </div>
        @endif
    </div>
</div>

<script>
    const btnRenomear   = document.getElementById('btnRenomear');
    const btnFecharRen  = document.getElementById('btnFecharRenomear');
    const formRenomear  = document.getElementById('formRenomear');
    const inputRenomear = document.getElementById('inputRenomear');

    btnRenomear?.addEventListener('click', () => {
        formRenomear.classList.toggle('hidden');
        if (!formRenomear.classList.contains('hidden')) inputRenomear.focus();
    });

    btnFecharRen?.addEventListener('click', () => formRenomear.classList.add('hidden'));
</script>
@endsection
