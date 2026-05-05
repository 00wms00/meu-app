@extends('layouts.app')

@section('title', 'Gerenciar Agrupamentos')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🔗 Gerenciar Agrupamentos</h1>
            <p class="mt-1 text-gray-600">Organize produtos iguais com nomes diferentes</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('products.agrupar-automatico') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn-primary text-sm">🤖 Agrupar Automático</button>
            </form>
            <a href="{{ route('products.index') }}" class="btn-back">← Voltar</a>
        </div>
    </div>
</div>

<!-- Busca -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar produtos..." class="form-control flex-1">
        <button type="submit" class="btn-primary">🔍</button>
        @if($search)
        <a href="{{ route('products.agrupamentos') }}" class="btn-outline-secondary">Limpar</a>
        @endif
    </form>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif

@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
    @foreach($errors->all() as $error) ❌ {{ $error }} @endforeach
</div>
@endif

<!-- Abas -->
<div class="mb-6 border-b border-gray-200">
    <nav class="flex gap-4">
        <button onclick="mostrarAba('grupos')" id="tab-grupos" 
                class="px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-600">
            📁 Grupos ({{ $grupos->count() }})
        </button>
        <button onclick="mostrarAba('soltos')" id="tab-soltos" 
                class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
            📦 Produtos Soltos ({{ $naoAgrupados->count() }})
        </button>
    </nav>
</div>

<!-- ABA: GRUPOS -->
<div id="aba-grupos">
    @forelse($grupos as $grupo)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100 rounded-t-lg">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📌</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-800 text-lg">{{ $grupo->nome }}</span>
                            <button onclick="editarNomeGrupo('{{ $grupo->id }}', '{{ addslashes($grupo->nome) }}')" 
                                    class="text-gray-400 hover:text-blue-600 text-sm">✏️</button>
                        </div>
                        <span class="text-xs text-gray-500">{{ $grupo->groupedProducts->count() }} agrupado(s) · {{ $grupo->invoiceItems->count() }} compras</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('products.show', $grupo) }}" class="text-blue-600 hover:text-blue-800 text-sm border border-blue-300 rounded px-3 py-1.5">📈 Histórico</a>
                    <button onclick="mostrarAdicionar('{{ $grupo->id }}')" class="text-green-600 hover:text-green-800 text-sm border border-green-300 rounded px-3 py-1.5">➕ Adicionar</button>
                    <form action="{{ route('products.desfazer-grupo', $grupo) }}" method="POST" class="inline"
                          onsubmit="return confirm('Desfazer o grupo? Todos ficarão soltos.')">
                        @csrf
                        <button type="submit" class="text-orange-600 hover:text-orange-800 text-sm border border-orange-300 rounded px-3 py-1.5">🔓 Desfazer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($grupo->groupedProducts as $agrupado)
            <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <span class="text-gray-300">↳</span>
                    <span class="text-sm text-gray-700">{{ $agrupado->nome }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <form action="{{ route('products.tornar-canonico', $agrupado) }}" method="POST" class="inline"
                          onsubmit="return confirm('Tornar este produto como PRINCIPAL?')">
                        @csrf
                        <button type="submit" class="text-green-500 hover:text-green-700 text-xs border border-green-300 rounded px-2 py-1">📌 Principal</button>
                    </form>
                    <form action="{{ route('products.desagrupar', $agrupado) }}" method="POST" class="inline"
                          onsubmit="return confirm('Soltar este produto?')">
                        @csrf
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">✕ Soltar</button>
                    </form>
                </div>
            </div>
            @endforeach
            
            <div id="adicionar-{{ $grupo->id }}" class="hidden px-6 py-3 bg-gray-50">
                <form action="{{ route('products.adicionar-ao-grupo', $grupo) }}" method="POST">
                    @csrf
                    <p class="text-sm text-gray-600 mb-2">Selecione os produtos:</p>
                    <div class="max-h-48 overflow-y-auto space-y-2 mb-3">
                        @foreach($naoAgrupados as $prod)
                        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 p-1 rounded">
                            <input type="checkbox" name="produto_ids[]" value="{{ $prod->id }}" class="rounded border-gray-300 text-blue-600">
                            {{ $prod->nome }}
                        </label>
                        @endforeach
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded">Adicionar</button>
                        <button type="button" onclick="document.getElementById('adicionar-{{ $grupo->id }}').classList.add('hidden')" class="text-xs text-gray-500 px-3 py-1.5">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <span class="text-6xl">📁</span>
        <p class="text-gray-500 mt-4 text-lg">Nenhum grupo criado ainda.</p>
    </div>
    @endforelse
</div>

<!-- ABA: PRODUTOS SOLTOS -->
<div id="aba-soltos" class="hidden">
    @if($naoAgrupados->count() > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50 rounded-t-lg">
            <h2 class="font-semibold text-gray-800">📦 Produtos Soltos</h2>
            <p class="text-xs text-gray-500 mt-1">Marque 2+ produtos e crie um grupo, ou torne um produto como principal</p>
        </div>
        
        <form action="{{ route('products.criar-grupo') }}" method="POST" id="formCriarGrupo">
            @csrf
            <div class="px-6 py-3 bg-gray-50 border-b flex gap-3">
                <input type="text" name="nome_grupo" placeholder="Nome do grupo (opcional)" class="form-control text-sm flex-1">
                <button type="submit" class="btn-primary text-sm whitespace-nowrap" id="btnCriarGrupo" disabled>
                    Criar Grupo (0)
                </button>
                <a href="{{ route('products.ml-interativo') }}" class="btn-primary text-sm flex items-center gap-1">
    🧠 ML Interativo
</a>
            </div>
        </form>

        <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
            @foreach($naoAgrupados as $produto)
            <div class="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                <label class="flex items-center gap-3 cursor-pointer flex-1">
                    <input type="checkbox" name="produto_ids[]" value="{{ $produto->id }}" 
                           form="formCriarGrupo"
                           class="rounded border-gray-300 text-blue-600 checkbox-solto" onchange="atualizarBotao()">
                    <div>
                        <span class="text-sm text-gray-700">{{ $produto->nome }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $produto->invoiceItems->count() }} compras</span>
                    </div>
                </label>
                <form action="{{ route('products.tornar-canonico', $produto) }}" method="POST" class="inline-flex-shrink-0 ml-2"
                      onsubmit="return confirm('Tornar como PRINCIPAL?')">
                    @csrf
                    <button type="submit" class="text-green-600 hover:text-green-800 text-xs border border-green-300 rounded px-3 py-1.5 hover:bg-green-50 whitespace-nowrap">
                        📌 Tornar Principal
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <span class="text-6xl">🎉</span>
        <p class="text-gray-500 mt-4 text-lg">Todos os produtos estão organizados!</p>
    </div>
    @endif
</div>

<!-- Modal de Renomear -->
<div id="modalRenomear" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">✏️ Renomear Grupo</h3>
        <form id="formRenomear" method="POST">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-1">Novo nome</label>
            <input type="text" name="nome" id="inputRenomear" class="form-control mb-4" required>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="fecharModalRenomear()" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="submit" class="btn-primary text-sm">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function mostrarAba(aba) {
    document.getElementById('aba-grupos').classList.toggle('hidden', aba !== 'grupos');
    document.getElementById('aba-soltos').classList.toggle('hidden', aba !== 'soltos');
    ['tab-grupos', 'tab-soltos'].forEach(id => {
        const el = document.getElementById(id);
        if (id === 'tab-' + aba) {
            el.classList.add('border-blue-600', 'text-blue-600');
            el.classList.remove('border-transparent', 'text-gray-500');
        } else {
            el.classList.remove('border-blue-600', 'text-blue-600');
            el.classList.add('border-transparent', 'text-gray-500');
        }
    });
}

function mostrarAdicionar(id) {
    document.getElementById('adicionar-' + id).classList.toggle('hidden');
}

function atualizarBotao() {
    const count = document.querySelectorAll('.checkbox-solto:checked').length;
    const btn = document.getElementById('btnCriarGrupo');
    if (btn) {
        btn.textContent = 'Criar Grupo (' + count + ')';
        btn.disabled = count < 2;
        btn.classList.toggle('opacity-50', count < 2);
    }
}

function editarNomeGrupo(id, nome) {
    document.getElementById('inputRenomear').value = nome;
    // CORRIGIDO: products (com "c") em vez de produtos
    document.getElementById('formRenomear').action = '/products/' + id + '/renomear-grupo';
    document.getElementById('modalRenomear').classList.remove('hidden');
}

function fecharModalRenomear() {
    document.getElementById('modalRenomear').classList.add('hidden');
}

document.getElementById('modalRenomear').addEventListener('click', function(e) {
    if (e.target === this) fecharModalRenomear();
});

document.addEventListener('DOMContentLoaded', atualizarBotao);
</script>
@endpush
