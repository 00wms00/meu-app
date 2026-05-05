@extends('layouts.app')
@section('title', 'Cadastrar Ofertas')
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold">✍️ Cadastrar Ofertas Manualmente</h1></div>
<form action="{{ route('offers.store') }}" method="POST">
    @csrf
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-4">
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="col-span-2"><input type="text" name="estabelecimento" class="form-control" placeholder="Estabelecimento *" required></div>
            <div><input type="text" name="fonte" class="form-control" placeholder="Fonte (ex: Encarte)"></div>
        </div>
        <div id="ofertasContainer"><p class="text-sm text-gray-500">Adicione produtos abaixo:</p></div>
        <button type="button" onclick="addOferta()" class="text-blue-600 text-sm mt-2">➕ Adicionar produto</button>
    </div>
    <button type="submit" class="btn-primary">💾 Salvar</button>
</form>
<script>
let count = 0;
function addOferta() {
    const div = document.getElementById('ofertasContainer');
    div.innerHTML += `<div class="flex gap-2 mb-2"><input type="text" name="ofertas[${count}][nome_produto]" class="form-control flex-1" placeholder="Produto" required><input type="text" name="ofertas[${count}][preco_oferta]" class="form-control w-24" placeholder="Preço" required><input type="text" name="ofertas[${count}][unidade]" class="form-control w-20" value="UN"><button type="button" onclick="this.parentElement.remove()" class="text-red-400">✕</button></div>`;
    count++;
}
addOferta();
</script>
@endsection
