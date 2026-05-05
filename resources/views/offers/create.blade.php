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
            <input type="text" name="ofertas[0][nome_produto]" class="form-control flex-1" placeholder="Produto" required>
            <input type="text" name="ofertas[0][preco_oferta]" class="form-control w-24" placeholder="Preço" required>
            <input type="text" name="ofertas[0][unidade]" class="form-control w-20" value="UN">
        </div>
    </div>
    {{-- type="button" explícito — não deve submeter o form, apenas adicionar linha --}}
    <button type="button" onclick="addOferta()" class="text-blue-600 text-sm mt-2">➕ Adicionar produto</button>
    <br>
    <button type="submit" class="btn-primary mt-4">💾 Salvar</button>
</form>

<script>
    let count = 1;
    function addOferta() {
        const div = document.getElementById('ofertas-container');
        div.innerHTML += `<div class="flex gap-2 mb-2">
            <input type="text" name="ofertas[${count}][nome_produto]" class="form-control flex-1" placeholder="Produto" required>
            <input type="text" name="ofertas[${count}][preco_oferta]" class="form-control w-24" placeholder="Preço" required>
            <input type="text" name="ofertas[${count}][unidade]" class="form-control w-20" value="UN">
            <button type="button" onclick="this.parentElement.remove()" class="text-red-400" aria-label="Remover produto">✕</button>
        </div>`;
        count++;
    }
</script>
@endsection
