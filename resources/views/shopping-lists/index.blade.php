@extends('layouts.app')

@section('title', 'Listas de Compras')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🛒 Listas de Compras</h1>
            <p class="mt-1 text-gray-600">Crie e gerencie suas listas</p>
        </div>
        <button onclick="mostrarModalNovaLista()" class="btn-primary">➕ Nova Lista</button>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif

<!-- Listas Ativas -->
@if($listas->where('ativa', true)->count() > 0)
<h2 class="text-lg font-semibold text-gray-800 mb-3">📝 Listas Ativas</h2>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    @foreach($listas->where('ativa', true) as $lista)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
        <a href="{{ route('shopping-lists.show', $lista) }}" class="block mb-3">
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
            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3">
                <div class="h-1.5 rounded-full bg-green-500" style="width: {{ $lista->items_count > 0 ? ($lista->items_comprados_count / $lista->items_count) * 100 : 0 }}%"></div>
            </div>
            @endif
        </a>
        
        <!-- Botões sempre visíveis -->
        <div class="flex gap-2 pt-2 border-t border-gray-100">
            <a href="{{ route('shopping-lists.show', $lista) }}" class="text-xs text-blue-600 hover:text-blue-800">📋 Abrir</a>
            <form action="{{ route('shopping-lists.destroy', $lista) }}" method="POST" class="inline"
                  onsubmit="return confirm('Excluir a lista &quot;{{ addslashes($lista->nome) }}&quot;?\n\nEsta ação não pode ser desfeita.')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs text-red-500 hover:text-red-700">🗑️ Excluir</button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif

<!-- Listas Concluídas -->
@if($listas->where('ativa', false)->count() > 0)
<h2 class="text-lg font-semibold text-gray-800 mb-3">✅ Listas Concluídas</h2>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($listas->where('ativa', false) as $lista)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition opacity-75">
        <a href="{{ route('shopping-lists.show', $lista) }}" class="block mb-3">
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
        
        <div class="flex gap-2 pt-2 border-t border-gray-100">
            <a href="{{ route('shopping-lists.show', $lista) }}" class="text-xs text-blue-600 hover:text-blue-800">📋 Abrir</a>
            <form action="{{ route('shopping-lists.destroy', $lista) }}" method="POST" class="inline"
                  onsubmit="return confirm('Excluir a lista &quot;{{ addslashes($lista->nome) }}&quot;?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs text-red-500 hover:text-red-700">🗑️ Excluir</button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif

@if($listas->count() == 0)
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
    <span class="text-6xl">🛒</span>
    <p class="text-gray-500 mt-4 text-lg">Nenhuma lista de compras ainda.</p>
    <button onclick="mostrarModalNovaLista()" class="btn-primary mt-3">➕ Criar Primeira Lista</button>
</div>
@endif

<!-- Modal Nova Lista -->
<div id="modalNovaLista" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">➕ Nova Lista de Compras</h3>
        <form action="{{ route('shopping-lists.store') }}" method="POST">
            @csrf
            <input type="text" name="nome" class="form-control mb-4" placeholder="Ex: Compras da semana" required>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('modalNovaLista').classList.add('hidden')" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="submit" class="btn-primary text-sm">Criar Lista</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function mostrarModalNovaLista() {
    document.getElementById('modalNovaLista').classList.remove('hidden');
}
document.getElementById('modalNovaLista').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
@endpush
