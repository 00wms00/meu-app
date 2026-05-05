@extends('layouts.app')

@section('title', 'Categorizar Produtos')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🏷️ Categorizar Produtos</h1>
            <p class="mt-1 text-gray-600">Organize seus produtos por categoria</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('categories.index') }}" class="btn-outline-secondary text-sm">
                ⚙️ Gerenciar Categorias
            </a>
            <a href="{{ route('products.index') }}" class="btn-back">← Voltar</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Sidebar de Categorias -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-4 py-3 border-b bg-gray-50 font-semibold text-gray-700">📂 Categorias</div>
            <div class="divide-y">
                <a href="{{ route('products.categorias') }}" 
                   class="block px-4 py-3 text-sm hover:bg-gray-50 {{ !$cf ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600' }}">
                    📋 Todas
                </a>
                @foreach($categorias as $cat)
                <a href="{{ route('products.categorias', ['categoria' => $cat->id]) }}" 
                   class="flex items-center justify-between px-4 py-3 text-sm hover:bg-gray-50 {{ $cf == $cat->id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600' }}">
                    <span>{{ $cat->emoji }} {{ $cat->nome }}</span>
                    <span class="text-xs text-gray-400">({{ $contagemCategorias[$cat->id] ?? 0 }})</span>
                </a>
                @endforeach
                <!-- Sem categoria -->
                <a href="{{ route('products.categorias', ['categoria' => 'sem']) }}" 
                   class="flex items-center justify-between px-4 py-3 text-sm hover:bg-gray-50 {{ $cf === 'sem' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600' }}">
                    <span>📦 Sem categoria</span>
                    <span class="text-xs text-gray-400">({{ $contagemCategorias['sem'] ?? 0 }})</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de Produtos -->
    <div class="lg:col-span-3">
        <!-- Busca e Ações em Lote -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
            <form method="GET" class="flex gap-2 mb-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar produto..." class="form-control text-sm flex-1">
                @if($cf)
                <input type="hidden" name="categoria" value="{{ $cf }}">
                @endif
                <button type="submit" class="btn-primary text-sm">🔍</button>
            </form>
            
            <!-- Ações em lote -->
            <form action="{{ route('products.categorizar-lote') }}" method="POST" id="formCategorizarLote" class="flex gap-2 items-center">
                @csrf
                <select name="categoria" class="form-control text-sm w-56">
                    <option value="">Selecionar categoria...</option>
                    @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->nome }}</option>
                    @endforeach
                    <option value="">📦 Sem categoria</option>
                </select>
                <button type="submit" class="btn-primary text-sm" id="btnCategorizarLote" disabled>
                    📁 Categorizar (0)
                </button>
            </form>
        </div>

        <!-- Produtos -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            @if($produtos->count() > 0)
            <div class="divide-y">
                @foreach($produtos as $produto)
                <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center gap-3 flex-1">
                        <input type="checkbox" name="produto_ids[]" value="{{ $produto->id }}" 
                               form="formCategorizarLote" class="rounded border-gray-300 checkbox-cat"
                               onchange="atualizarBotaoCategorizar()">
                        <div>
                            <span class="text-sm text-gray-800">{{ $produto->nome }}</span>
                            @if($produto->category)
                            <span class="text-xs ml-2 px-2 py-0.5 rounded-full text-white" 
                                  style="background-color: {{ $produto->category->cor ?? '#6b7280' }}">
                                {{ $produto->category->emoji }} {{ $produto->category->nome }}
                            </span>
                            @else
                            <span class="text-xs ml-2 text-gray-400">📦 Sem categoria</span>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Troca rápida de categoria -->
                    <form action="{{ route('products.atualizar-categoria', $produto) }}" method="POST" class="flex items-center gap-1">
                        @csrf
                        <select name="categoria" class="text-xs border border-gray-300 rounded px-2 py-1" onchange="this.form.submit()">
                            <option value="">📦 Sem categoria</option>
                            @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ $produto->category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->emoji }} {{ $cat->nome }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @endforeach
            </div>
            <div class="p-4 border-t">
                {{ $produtos->links() }}
            </div>
            @else
            <div class="p-8 text-center text-gray-500">
                <span class="text-4xl">📦</span>
                <p class="mt-2">Nenhum produto encontrado.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function atualizarBotaoCategorizar() {
    const count = document.querySelectorAll('.checkbox-cat:checked').length;
    const btn = document.getElementById('btnCategorizarLote');
    btn.textContent = '📁 Categorizar (' + count + ')';
    btn.disabled = count < 1;
    btn.classList.toggle('opacity-50', count < 1);
}
document.addEventListener('DOMContentLoaded', atualizarBotaoCategorizar);
</script>
@endpush
