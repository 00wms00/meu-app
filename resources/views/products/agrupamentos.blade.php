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
            <a href="{{ route('products.index') }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">← Voltar</a>
        </div>
    </div>
</div>

{{-- Busca --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar produtos..."
               class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm flex-1">
        <button type="submit"
                class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">🔍</button>
        @if($search)
            <a href="{{ route('products.agrupamentos') }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold rounded-md transition">Limpar</a>
        @endif
    </form>
</div>

{{-- Mensagens --}}
@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
        ✅ {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
        @foreach($errors->all() as $error)
            ❌ {{ $error }}<br>
        @endforeach
    </div>
@endif

{{-- Abas --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="flex gap-4">
        <button type="button" onclick="mostrarAba('grupos')" id="tab-grupos"
                class="px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-600">
            📁 Grupos ({{ $grupos->count() }})
        </button>
        <button type="button" onclick="mostrarAba('soltos')" id="tab-soltos"
                class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
            📦 Produtos Soltos ({{ $naoAgrupados->count() }})
        </button>
    </nav>
</div>

{{-- ============================================ --}}
{{-- ABA: GRUPOS --}}
{{-- ============================================ --}}
<div id="aba-grupos">
    @forelse($grupos as $grupo)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4">
            {{-- Cabeçalho do Grupo --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100 rounded-t-lg">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📌</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-800 text-lg">{{ $grupo->nome }}</span>
                                <button type="button"
                                        onclick="editarNomeGrupo('{{ $grupo->id }}', '{{ $grupo->nome }}')"
                                        class="text-gray-400 hover:text-blue-600 text-sm">✏️</button>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ $grupo->groupedProducts->count() }} agrupado(s) &middot; {{ $grupo->invoiceItems->count() }} compras
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('products.show', $grupo) }}"
                           class="inline-flex items-center px-3 py-1.5 border border-blue-600 text-blue-600 hover:bg-blue-50 text-xs font-semibold rounded-md transition">
                            📈 Histórico
                        </a>
                        <button type="button"
                                onclick="mostrarAdicionar('{{ $grupo->id }}')"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold rounded-md transition">
                            ➕ Adicionar
                        </button>
                        <form action="{{ route('products.desfazer-grupo', $grupo) }}" method="POST" class="inline"
                              data-confirm="Desfazer o grupo '{{ $grupo->nome }}'? Todos os produtos ficarão soltos.">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 border border-orange-300 text-orange-600 hover:bg-orange-50 text-xs font-semibold rounded-md transition">
                                🔓 Desfazer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Produtos do Grupo --}}
            <div class="divide-y divide-gray-100">
                @foreach($grupo->groupedProducts as $agrupado)
                    <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <span class="text-gray-300">↳</span>
                            <span class="text-sm text-gray-700">{{ $agrupado->nome }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    onclick="tornarPrincipal('{{ $agrupado->id }}', '{{ $agrupado->nome }}')"
                                    class="inline-flex items-center px-3 py-1.5 border border-green-300 text-green-600 hover:bg-green-50 text-xs font-semibold rounded-md transition">
                                📌 Principal
                            </button>
                            <form action="{{ route('products.desagrupar', $agrupado) }}" method="POST" class="inline"
                                  data-confirm="Soltar '{{ $agrupado->nome }}' do grupo?">
                                @csrf
                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs">✕ Soltar</button>
                            </form>
                        </div>
                    </div>
                @endforeach

                {{-- Painel Adicionar (escondido por padrão) --}}
                <div id="adicionar-{{ $grupo->id }}" class="hidden px-6 py-3 bg-gray-50">
                    <form action="{{ route('products.adicionar-ao-grupo', $grupo) }}" method="POST">
                        @csrf
                        <p class="text-sm text-gray-600 mb-2">Selecione os produtos:</p>
                        <div class="max-h-48 overflow-y-auto space-y-2 mb-3">
                            @foreach($naoAgrupados as $prod)
                                <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-100 p-1 rounded">
                                    <input type="checkbox" name="produto_ids[]" value="{{ $prod->id }}"
                                           class="rounded border-gray-300 text-blue-600">
                                    {{ $prod->nome }}
                                </label>
                            @endforeach
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-md transition">Adicionar</button>
                            <button type="button"
                                    onclick="document.getElementById('adicionar-{{ $grupo->id }}').classList.add('hidden')"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold rounded-md transition">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <span class="text-6xl">📁</span>
            <p class="text-gray-500 mt-4 text-lg">Nenhum grupo criado ainda.</p>
            <p class="text-gray-400 text-sm mt-1">Use a aba "Produtos Soltos" para selecionar 2+ produtos e criar um grupo.</p>
        </div>
    @endforelse
</div>

{{-- ============================================ --}}
{{-- ABA: PRODUTOS SOLTOS --}}
{{-- ============================================ --}}
<div id="aba-soltos" class="hidden">
    @if($naoAgrupados->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50 rounded-t-lg">
                <h2 class="font-semibold text-gray-800">📦 Produtos Soltos</h2>
                <p class="text-xs text-gray-500 mt-1">Marque 2+ produtos e crie um grupo, ou torne um produto como principal individualmente</p>
            </div>

            {{-- Linha com nome do grupo + botão Criar Grupo --}}
            <form action="{{ route('products.criar-grupo') }}" method="POST" id="formCriarGrupo" onsubmit="return validarCriarGrupo()">
                @csrf
                <div class="px-6 py-3 bg-gray-50 border-b flex flex-wrap gap-3 items-center">
                    <input type="text" name="nome_grupo" placeholder="Nome do grupo (opcional)"
                           class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm flex-1 min-w-40">
                    <button type="submit"
                            id="btnCriarGrupo"
                            disabled
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md transition whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
                        📁 Selecione 2+ produtos
                    </button>
                    <a href="{{ route('products.ml-interativo') }}"
                       class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                        🧠 ML Interativo
                    </a>
                </div>

                {{-- Lista de produtos com checkboxes --}}
                <div class="divide-y divide-gray-100">
                    @foreach($naoAgrupados as $prod)
                        <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                            {{-- Checkbox (dentro do form de Criar Grupo) --}}
                            <label class="flex items-center gap-3 flex-1 cursor-pointer">
                                <input type="checkbox" name="produto_ids[]" value="{{ $prod->id }}"
                                       class="produtoCheck rounded border-gray-300 text-blue-600">
                                <span class="text-sm text-gray-700">{{ $prod->nome }}</span>
                                <span class="text-xs text-gray-400">({{ $prod->invoiceItems->count() }}x)</span>
                            </label>

                            {{-- Botão Tornar Principal (type=button, NÃO submete o formulário) --}}
                            <button type="button"
                                    onclick="tornarPrincipal('{{ $prod->id }}', '{{ $prod->nome }}')"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold rounded-md transition">
                                📌 Tornar Principal
                            </button>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <span class="text-6xl">✅</span>
            <p class="text-gray-500 mt-4 text-lg">Todos os produtos estão agrupados!</p>
        </div>
    @endif
</div>

{{-- ============================================ --}}
{{-- Modal Renomear Grupo --}}
{{-- ============================================ --}}
<div id="modalRenomear"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">✏️ Renomear Grupo</h3>
        <form id="formRenomear" method="POST">
            @csrf
            <input type="text" name="nome" id="inputNomeGrupo"
                   class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm mb-4"
                   placeholder="Novo nome..." required>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btnFecharRenomear"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold rounded-md transition">Cancelar</button>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================ --}}
{{-- Banner de Confirmação --}}
{{-- ============================================ --}}
<div id="bannerConfirm"
     class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 bg-white border border-gray-300 shadow-lg rounded-lg px-6 py-4 flex items-center gap-4 z-50">
    <span id="bannerConfirmMsg" class="text-sm text-gray-700 flex-1"></span>
    <button type="button" id="bannerConfirmCancel"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold rounded-md transition">Cancelar</button>
    <button type="button" id="bannerConfirmOk"
            class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-md transition">Confirmar</button>
</div>

@endsection

@push('scripts')
<script>
// ── Abas ───────────────────────────────────────────────────────────────────
function mostrarAba(aba) {
    document.getElementById('aba-grupos').classList.toggle('hidden', aba !== 'grupos');
    document.getElementById('aba-soltos').classList.toggle('hidden', aba !== 'soltos');

    document.getElementById('tab-grupos').className = 'px-4 py-2 text-sm font-medium border-b-2 ' +
        (aba === 'grupos' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700');
    document.getElementById('tab-soltos').className = 'px-4 py-2 text-sm font-medium border-b-2 ' +
        (aba === 'soltos' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700');
}

// ── Tornar Principal (cria formulário separado, sem aninhar) ──────────────
function tornarPrincipal(produtoId, nome) {
    if (!confirm("Tornar '" + nome + "' como produto principal (canônico)?")) {
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/products/' + produtoId + '/tornar-canonico';
    form.style.display = 'none';

    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
}

// ── Modal Renomear Grupo ─────────────────────────────────────────────────────
function editarNomeGrupo(id, nome) {
    document.getElementById('formRenomear').action = '/products/' + id + '/renomear-grupo';
    document.getElementById('inputNomeGrupo').value = nome;
    document.getElementById('modalRenomear').classList.remove('hidden');
    document.getElementById('inputNomeGrupo').focus();
}

document.getElementById('btnFecharRenomear').addEventListener('click', function() {
    document.getElementById('modalRenomear').classList.add('hidden');
});

document.getElementById('modalRenomear').addEventListener('click', function(e) {
    if (e.target === this) document.getElementById('modalRenomear').classList.add('hidden');
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('modalRenomear').classList.add('hidden');
});

// ── Painel Adicionar Produtos ao Grupo ──────────────────────────────────────
function mostrarAdicionar(grupoId) {
    document.getElementById('adicionar-' + grupoId).classList.remove('hidden');
}

// ── Validação do Criar Grupo ───────────────────────────────────────────────
function validarCriarGrupo() {
    const count = document.querySelectorAll('.produtoCheck:checked').length;
    if (count < 2) {
        alert('Selecione pelo menos 2 produtos para criar um grupo.');
        return false;
    }
    return true;
}

// ── Atualizar botão Criar Grupo conforme checkboxes ────────────────────────
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('produtoCheck')) return;

    const count = document.querySelectorAll('.produtoCheck:checked').length;
    const btn = document.getElementById('btnCriarGrupo');

    if (!btn) return;

    if (count >= 2) {
        btn.textContent = '📁 Criar Grupo (' + count + ' produtos)';
        btn.disabled = false;
    } else if (count === 1) {
        btn.textContent = '📁 Selecione mais 1 produto';
        btn.disabled = true;
    } else {
        btn.textContent = '📁 Selecione 2+ produtos';
        btn.disabled = true;
    }
});

// ── Banner de Confirmação ──────────────────────────────────────────────────
(function() {
    const banner = document.getElementById('bannerConfirm');
    const msg = document.getElementById('bannerConfirmMsg');
    const btnOk = document.getElementById('bannerConfirmOk');
    const btnCancel = document.getElementById('bannerConfirmCancel');
    let pendingForm = null;

    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form.dataset.confirm) return;
        e.preventDefault();
        pendingForm = form;
        msg.textContent = form.dataset.confirm;
        banner.classList.remove('hidden');
        btnOk.focus();
    });

    btnOk.addEventListener('click', function() {
        if (pendingForm) {
            pendingForm.submit();
            pendingForm = null;
        }
        banner.classList.add('hidden');
    });

    btnCancel.addEventListener('click', function() {
        pendingForm = null;
        banner.classList.add('hidden');
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !banner.classList.contains('hidden')) {
            pendingForm = null;
            banner.classList.add('hidden');
        }
    });
})();
</script>
@endpush