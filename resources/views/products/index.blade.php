@extends('layouts.app')

@section('title', 'Produtos')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">📦 Produtos</h1>
            <p class="mt-1 text-gray-600">{{ $products->total() }} produtos cadastrados</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('products.normalizacao') }}" class="btn-outline-secondary text-sm">🏷️ Normalizar</a>
            <a href="{{ route('products.categorias') }}" class="btn-outline-secondary text-sm">📂 Categorizar</a>
            <a href="{{ route('products.agrupamentos') }}" class="btn-outline-secondary text-sm">🔗 Agrupamentos</a>
        </div>
    </div>
</div>

<!-- Busca -->
<form method="GET" class="mb-4 flex gap-2">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar..." class="form-control flex-1">
    <button type="submit" class="btn-primary">🔍</button>
</form>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    @if($products->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Nome Original</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($products as $product)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                            {{ \App\Helpers\ProductHelper::displayName($product) }}
                        </a>
                        @if($product->nome_exibicao && $product->normalizacao_status === 'aprovado')
                        <span class="text-xs text-green-500 ml-1">✓</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-400 text-center">
                        @if($product->nome_exibicao && $product->nome !== $product->nome_exibicao)
                        <span class="text-xs" title="{{ $product->nome }}">{{ Str::limit($product->nome, 40) }}</span>
                        @else
                        <span class="text-xs">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-800 text-sm">📈</a>
                        <a href="{{ route('products.edit', $product) }}" class="text-gray-400 hover:text-gray-600 text-sm ml-2">✏️</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t">{{ $products->links() }}</div>
    @else
    <div class="p-8 text-center text-gray-500">Nenhum produto encontrado.</div>
    @endif
</div>
@endsection
