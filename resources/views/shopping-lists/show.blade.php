@extends('layouts.app')

@section('title', $shoppingList->nome)

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🛒 {{ $shoppingList->nome }}</h1>
            <p class="mt-1 text-gray-600">
                {{ $shoppingList->items->where('comprado', true)->count() }}/{{ $shoppingList->items->count() }} comprados
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($shoppingList->ativa)
            <form action="{{ route('shopping-lists.finalizar', $shoppingList) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn-success text-sm">✅ Finalizar Compra</button>
            </form>
            @else
            <form action="{{ route('shopping-lists.reabrir', $shoppingList) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn-outline-secondary text-sm">🔓 Reabrir</button>
            </form>
            @endif
            <a href="{{ route('shopping-lists.index') }}" class="btn-back">← Voltar</a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Itens da Lista -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">📋 Itens</h2>
                <div class="flex gap-2">
                    <form action="{{ route('shopping-lists.limpar', $shoppingList) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-gray-500 hover:text-red-500">🗑️ Limpar comprados</button>
                    </form>
                </div>
            </div>

            @if($shoppingList->items->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($shoppingList->items as $item)
                <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 {{ $item->comprado ? 'opacity-60' : '' }}">
                    <div class="flex items-center gap-3 flex-1">
                        @if($shoppingList->ativa)
                        <form action="{{ route('items.toggle', $item) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xl {{ $item->comprado ? 'text-green-500' : 'text-gray-300 hover:text-green-400' }}">
                                {{ $item->comprado ? '✅' : '⬜' }}
                            </button>
                        </form>
                        @else
                        <span class="text-xl">{{ $item->comprado ? '✅' : '⬜' }}</span>
                        @endif
                        <div>
                            <span class="text-sm {{ $item->comprado ? 'line-through text-gray-400' : 'text-gray-800' }}">
                                {{ $item->nome }}
                            </span>
                            <span class="text-xs text-gray-400 ml-2">
                                {{ $item->quantidade > 0 ? number_format($item->quantidade, strtoupper($item->unidade) == 'KG' ? 3 : 0, ',', '.') : '' }} {{ $item->unidade }}
                            </span>
                            @if($item->preco_estimado)
                            <span class="text-xs text-blue-500 ml-2">~R$ {{ number_format($item->preco_estimado * $item->quantidade, 2, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                    @if($shoppingList->ativa)
                    <form action="{{ route('items.remove', $item) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-600 text-sm">🗑️</button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div class="p-8 text-center text-gray-500">
                <p>Nenhum item na lista.</p>
            </div>
            @endif

            <!-- Total estimado -->
            @php
                $totalEstimado = $shoppingList->items->sum(function($i) { return ($i->preco_estimado ?? 0) * $i->quantidade; });
            @endphp
            @if($totalEstimado > 0)
            <div class="px-6 py-3 bg-gray-50 border-t text-right">
                <span class="text-sm text-gray-600">Total estimado:</span>
                <span class="text-lg font-bold text-blue-600 ml-2">R$ {{ number_format($totalEstimado, 2, ',', '.') }}</span>
            </div>
            @endif
        </div>

        <!-- Adicionar Item Manual -->
        @if($shoppingList->ativa)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">➕ Adicionar Item</h3>
            <form action="{{ route('shopping-lists.items.add', $shoppingList) }}" method="POST" class="flex flex-wrap gap-2">
                @csrf
                <input type="text" name="nome" placeholder="Nome do produto" class="form-control text-sm flex-1 min-w-[150px]" required>
                <input type="text" name="quantidade" value="1" class="form-control text-sm w-20 text-center" required>
                <select name="unidade" class="form-control text-sm w-20">
                    <option value="UN">UN</option><option value="KG">KG</option><option value="L">L</option>
                </select>
                <button type="submit" class="btn-primary text-sm">Adicionar</button>
            </form>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-4">
        @if($shoppingList->ativa)
        <!-- Sugestões -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">💡 Sugestões</h3>
            
            <form action="{{ route('shopping-lists.sugerir', $shoppingList) }}" method="POST" class="mb-3">
                @csrf
                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm">🤖 Sugerir itens frequentes</button>
            </form>

            <p class="text-xs text-gray-500 mb-2">Produtos mais comprados:</p>
            <form action="{{ route('shopping-lists.frequentes', $shoppingList) }}" method="POST">
                @csrf
                <div class="max-h-60 overflow-y-auto space-y-2 mb-3">
                    @foreach($produtosFrequentes->take(15) as $prod)
                    <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 p-1 rounded">
                        <input type="checkbox" name="produtos[]" value="{{ $prod->id }}" class="rounded border-gray-300 text-blue-600">
                        {{ $prod->nome }}
                        <span class="text-xs text-gray-400">({{ $prod->invoice_items_count }}x)</span>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="btn-primary text-sm w-full">Adicionar Selecionados</button>
            </form>
        </div>
        @endif

        <!-- Ações -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">⚙️ Ações</h3>
            <div class="space-y-2">
                <button onclick="renomearLista('{{ $shoppingList->id }}', '{{ addslashes($shoppingList->nome) }}')" 
                        class="text-blue-600 hover:text-blue-800 text-sm block">✏️ Renomear</button>
                <form action="{{ route('shopping-lists.destroy', $shoppingList) }}" method="POST"
                      onsubmit="return confirm('Excluir esta lista?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm">🗑️ Excluir Lista</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Renomear -->
<div id="modalRenomear" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">✏️ Renomear Lista</h3>
        <form id="formRenomear" method="POST">
            @csrf @method('PUT')
            <input type="text" name="nome" id="inputRenomear" class="form-control mb-4" required>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('modalRenomear').classList.add('hidden')" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="submit" class="btn-primary text-sm">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function renomearLista(id, nome) {
    document.getElementById('inputRenomear').value = nome;
    document.getElementById('formRenomear').action = '/shopping-lists/' + id;
    document.getElementById('modalRenomear').classList.remove('hidden');
}
document.getElementById('modalRenomear').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
@endpush
