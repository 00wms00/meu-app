@extends('layouts.app')

@section('title', 'Editar Produto')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">✏️ Editar Produto</h1>
    <p class="mt-1 text-gray-600">Renomeie ou ajuste o produto</p>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto</label>
                    <input type="text" name="nome" 
                           value="{{ old('nome', $product->nome) }}" 
                           class="form-control" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidade Padrão</label>
                    <select name="unidade_padrao" class="form-control">
                        <option value="">Selecionar...</option>
                        <option value="UN" {{ $product->unidade_padrao == 'UN' ? 'selected' : '' }}>UN - Unidade</option>
                        <option value="KG" {{ $product->unidade_padrao == 'KG' ? 'selected' : '' }}>KG - Quilograma</option>
                        <option value="L" {{ $product->unidade_padrao == 'L' ? 'selected' : '' }}>L - Litro</option>
                        <option value="CX" {{ $product->unidade_padrao == 'CX' ? 'selected' : '' }}>CX - Caixa</option>
                        <option value="PC" {{ $product->unidade_padrao == 'PC' ? 'selected' : '' }}>PC - Peça</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn btn-primary">
                    💾 Salvar Alterações
                </button>
                <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
