@extends('layouts.app')

@section('title', 'Listas de Compras')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🛒 Listas de Compras</h1>
            <p class="mt-1 text-gray-600">Crie e gerencie suas listas</p>
        </div>
        {{-- type="button" evita submit acidental caso o botão esteja dentro de um form pai --}}
        <button type="button" id="btnNovaLista" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">➕ Nova Lista</button>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4" role="status">✅ {{ session('success') }}</div>
@endif

@php
    $listasAtivas     = $listas->where('ativa', true);
    $listasConcluidas = $listas->where('ativa', false);
@endphp

@if($listasAtivas->isNotEmpty())
    <h2 class="text-lg font-semibold text-gray-800 mb-3">📝 Listas Ativas</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        @foreach($listasAtivas as $lista)
            <a href="{{ route('shopping-lists.show', $lista) }}"
               class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition block">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-gray-800">{{ $lista->nome }}</h3>
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Ativa</span>
                </div>
                <div class="flex gap-4 text-sm text-gray-500">
                    <span>{{ $lista->items_count }} itens</span>
                    <span class="text-green-600">{{ $lista->items_comprados_count }} ✓</span>
                    <span class="text-gray-400">{{ $lista->items_pendentes_count }} pendentes</span>
                </div>
                @if($lista->items_count > 0)
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3" role="progressbar"
                         aria-valuenow="{{ intval(($lista->items_comprados_count / $lista->items_count) * 100) }}"
                         aria-valuemin="0" aria-valuemax="100">
                        <div class="h-1.5 rounded-full bg-green-500"
                             style="width: {{ ($lista->items_comprados_count / $lista->items_count) * 100 }}%"></div>
                    </div>
                @endif
            </a>
        @endforeach
    </div>
@endif

@if($listasConcluidas->isNotEmpty())
    <h2 class="text-lg font-semibold text-gray-800 mb-3">✅ Listas Concluídas</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($listasConcluidas as $lista)
            <a href="{{ route('shopping-lists.show', $lista) }}"
               class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition block opacity-75">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-gray-800">{{ $lista->nome }}</h3>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Concluída</span>
                </div>
                <div class="text-sm text-gray-500">
                    <span>{{ $lista->items_count }} itens</span>
                    @if($lista->data_compra)
                        <span class="ml-2">· {{ $lista->data_compra->format('d/m/Y') }}</span>
                    @endif
                    @if($lista->valor_total > 0)
                        <span class="ml-2">· R$ {{ number_format($lista->valor_total, 2, ',', '.') }}</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

@if($listas->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <span class="text-6xl" aria-hidden="true">🛒</span>
        <p class="text-gray-500 mt-4 text-lg">Nenhuma lista de compras ainda.</p>
        <button type="button" id="btnNovaListaEmpty" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition mt-3">➕ Criar Primeira Lista</button>
    </div>
@endif

{{-- Modal Nova Lista --}}
<div id="modalNovaLista"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalNovaListaTitulo">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 id="modalNovaListaTitulo" class="text-lg font-semibold text-gray-800 mb-4">➕ Nova Lista de Compras</h3>
        <form action="{{ route('shopping-lists.store') }}" method="POST">
            @csrf
            <label for="inputNomeLista" class="sr-only">Nome da lista</label>
            <input type="text" name="nome" id="inputNomeLista"
                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm mb-4" placeholder="Ex: Compras da semana" required>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btnFecharNovaLista" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">Cancelar</button>
                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">✅ Criar Lista</button>
            </div>
        </form>
    </div>
</div>

<script>
    const btnNovaLista      = document.getElementById('btnNovaLista');
    const btnNovaListaEmpty = document.getElementById('btnNovaListaEmpty');
    const btnFechar         = document.getElementById('btnFecharNovaLista');
    const modal             = document.getElementById('modalNovaLista');
    const inputNome         = document.getElementById('inputNomeLista');

    function abrirModal() {
        modal.classList.remove('hidden');
        inputNome.focus();
    }

    btnNovaLista?.addEventListener('click', abrirModal);
    btnNovaListaEmpty?.addEventListener('click', abrirModal);
    btnFechar?.addEventListener('click', () => modal.classList.add('hidden'));

    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.add('hidden');
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') modal.classList.add('hidden');
    });
</script>
@endsection
