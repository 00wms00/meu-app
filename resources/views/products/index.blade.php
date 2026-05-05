@extends('layouts.app')

@section('title', 'Produtos')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">📦 Produtos</h1>
    <p class="mt-2 text-gray-600">Histórico de preços dos produtos</p>
</div>

{{-- Busca --}}
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <form method="GET" role="search">
        <div class="flex gap-4">
            <div class="flex-1">
                <label for="searchProduto" class="sr-only">Buscar produto pelo nome</label>
                <input type="search" name="search" id="searchProduto"
                       value="{{ request('search') }}"
                       placeholder="Buscar produto pelo nome..."
                       class="form-control">
            </div>
            {{-- btn-primary inclui o estilo base via @apply; prefixo 'btn' era redundante --}}
            <button type="submit" class="btn-primary">
                🔍 Buscar
            </button>
        </div>
    </form>
</div>

{{-- Lista de Produtos --}}
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-semibold text-gray-800">
            @if($products->isNotEmpty())
                {{ $products->total() }} produto(s) encontrado(s)
            @else
                Nenhuma produto encontrado
            @endif
        </h2>
    </div>

    @if($products->isNotEmpty())
        {{--
            ATENÇÃO N+1: $product->invoiceItems->count() dentro do @foreach dispara
            uma query por produto. Corrija no controller adicionando withCount:

            Product::withCount('invoiceItems')
                   ->when(request('search'), fn($q, $s) => $q->where('nome', 'like', "%$s%"))
                   ->paginate(20);

            Na view, substitua $product->invoiceItems->count() por $product->invoice_items_count.
        --}}
        <div class="overflow-x-auto">
            <table class="w-full" aria-label="Lista de produtos">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="text-left py-3 px-6 text-sm font-semibold text-gray-700">Produto</th>
                        <th scope="col" class="text-center py-3 px-6 text-sm font-semibold text-gray-700">Compras</th>
                        <th scope="col" class="text-center py-3 px-6 text-sm font-semibold text-gray-700">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr class="border-b hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-900">{{ $product->nome }}</div>
                                @if($product->unidade_padrao)
                                    <div class="text-xs text-gray-500">Unidade: {{ $product->unidade_padrao }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                {{-- Use $product->invoice_items_count após adicionar withCount() no controller --}}
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                                    {{ $product->invoiceItems->count() }} compras
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                {{-- btn-outline-primary + btn-sm: variantão semântica correta --}}
                                <a href="{{ route('products.show', $product) }}"
                                   class="btn-outline-primary btn-sm">
                                    📈 Histórico de Preços
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-6 border-t">
            {{ $products->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <span class="text-6xl" aria-hidden="true">📦</span>
            <p class="text-gray-500 mt-4 text-lg">Nenhum produto cadastrado ainda.</p>
            <p class="text-gray-400 text-sm">Importe uma NFC-e para começar.</p>
            <a href="{{ route('import.create') }}" class="btn-primary mt-4 inline-block">
                📥 Importar NFC-e
            </a>
        </div>
    @endif
</div>
@endsection
