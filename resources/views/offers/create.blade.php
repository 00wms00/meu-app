@extends('layouts.app')

@section('title', 'Nova Oferta')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">✍️ Cadastrar Ofertas Manualmente</h1>
</div>

<form action="{{ route('offers.store') }}" method="POST">
    @csrf
    <div id="ofertas-container">
        <div class="flex gap-2 mb-2">
            <input type="text" name="ofertas[0][nome_produto]"
                   class="w-full flex-1 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                   placeholder="Produto" required>
            <input type="text" name="ofertas[0][preco_oferta]"
                   class="w-24 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                   placeholder="Preço" required>
            <input type="text" name="ofertas[0][unidade]"
                   class="w-20 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                   value="UN">
        </div>
    </div>
    {{-- type="button" explícito — não deve submeter o form, apenas adicionar linha --}}
    <button type="button" onclick="addOferta()" class="text-blue-600 text-sm mt-2">➕ Adicionar produto</button>
    <br>
    <button type="submit"
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition mt-4">
        💾 Salvar
    </button>
</form>

<script>
    const inputClasses = 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm';
    let count = 1;
    function addOferta() {
        const div = document.getElementById('ofertas-container');
        div.innerHTML += `<div class="flex gap-2 mb-2">
            <input type="text" name="ofertas[${count}][nome_produto]" class="w-full flex-1 ${inputClasses}" placeholder="Produto" required>
            <input type="text" name="ofertas[${count}][preco_oferta]" class="w-24 ${inputClasses}" placeholder="Preço" required>
            <input type="text" name="ofertas[${count}][unidade]" class="w-20 ${inputClasses}" value="UN">
            <button type="button" onclick="this.parentElement.remove()" class="text-red-400" aria-label="Remover produto">✕</button>
        </div>`;
        count++;
    }
</script>
@endsection
