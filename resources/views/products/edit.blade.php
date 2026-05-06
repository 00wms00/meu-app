@extends('layouts.app')

@section('title', 'Editar: ' . $product->nome)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">✏️ Editar Produto</h1>
        <p class="mt-1 text-gray-600">{{ \App\Helpers\ProductHelper::displayName($product) }}</p>
    </div>
    <a href="{{ route('products.show', $product) }}" class="btn-back">← Voltar</a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="{{ route('products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')
            
            <!-- Nome Original (somente leitura) -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome Original (NF-e)</label>
                <input type="text" value="{{ $product->nome }}" class="form-control bg-gray-50 text-gray-500" readonly disabled>
                <p class="text-xs text-gray-400 mt-1">Este é o nome original da nota fiscal e não pode ser alterado.</p>
            </div>

            <!-- Nome de Exibição (editável) -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nome de Exibição
                    @if($product->nome_exibicao && $product->normalizacao_status === 'aprovado')
                    <span class="text-xs text-green-500 ml-1">✓ Normalizado</span>
                    @else
                    <span class="text-xs text-yellow-500 ml-1">Pendente</span>
                    @endif
                </label>
                <input type="text" name="nome_exibicao" 
                       value="{{ old('nome_exibicao', $product->nome_exibicao ?? '') }}" 
                       class="form-control" 
                       placeholder="Nome amigável para exibição...">
                <p class="text-xs text-gray-400 mt-1">Nome que será exibido nas listas e relatórios.</p>
            </div>

            <!-- Status da Normalização -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status da Normalização</label>
                <select name="normalizacao_status" class="form-control">
                    <option value="pendente" {{ $product->normalizacao_status == 'pendente' ? 'selected' : '' }}>🟡 Pendente</option>
                    <option value="aprovado" {{ $product->normalizacao_status == 'aprovado' ? 'selected' : '' }}>✅ Aprovado</option>
                    <option value="revisar" {{ $product->normalizacao_status == 'revisar' ? 'selected' : '' }}>🔵 Revisar</option>
                </select>
            </div>

            <!-- Assinatura (informativo) -->
            @if($product->nome_normalizado)
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <label class="block text-xs font-medium text-gray-500 mb-1">Assinatura do Sistema</label>
                <p class="text-sm font-mono text-gray-600">{{ $product->nome_normalizado }}</p>
            </div>
            @endif

            <!-- Unidade Padrão -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Unidade Padrão</label>
                <select name="unidade_padrao" class="form-control">
                    <option value="">Não definida</option>
                    <option value="UN" {{ $product->unidade_padrao == 'UN' ? 'selected' : '' }}>UN - Unidade</option>
                    <option value="KG" {{ $product->unidade_padrao == 'KG' ? 'selected' : '' }}>KG - Quilograma</option>
                    <option value="L" {{ $product->unidade_padrao == 'L' ? 'selected' : '' }}>L - Litro</option>
                    <option value="ML" {{ $product->unidade_padrao == 'ML' ? 'selected' : '' }}>ML - Mililitro</option>
                    <option value="G" {{ $product->unidade_padrao == 'G' ? 'selected' : '' }}>G - Grama</option>
                    <option value="CX" {{ $product->unidade_padrao == 'CX' ? 'selected' : '' }}>CX - Caixa</option>
                    <option value="PC" {{ $product->unidade_padrao == 'PC' ? 'selected' : '' }}>PC - Peça</option>
                    <option value="FD" {{ $product->unidade_padrao == 'FD' ? 'selected' : '' }}>FD - Fardo</option>
                </select>
            </div>

            <!-- Foto -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                @if($product->foto)
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ asset('storage/' . $product->foto) }}" style="width: 85px; height: 85px; object-fit: cover; border-radius: 4px;">
                    <span class="text-xs text-gray-400">Foto atual</span>
                </div>
                @endif
                <input type="file" name="foto" accept="image/*" class="form-control text-sm">
                <p class="text-xs text-gray-400 mt-1">Deixe em branco para manter a foto atual.</p>
            </div>

            <div class="flex gap-3 justify-end mt-6">
                <a href="{{ route('products.show', $product) }}" class="btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">💾 Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection
