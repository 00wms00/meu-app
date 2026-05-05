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

{{-- Busca --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar produtos..."
               class="form-control flex-1" aria-label="Buscar produtos">
        <button type="submit" class="btn-primary" aria-label="Buscar">🔍</button>
        @if($search)
            <a href="{{ route('products.agrupamentos') }}" class="btn-outline-secondary">Limpar</a>
        @endif
    </form>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4" role="status">
        ✅ {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4" role="alert">
        @foreach($errors->all() as $error)❌ {{ $error }}<br>@endforeach
    </div>
@endif

{{-- Abas --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="flex gap-4" role="tablist">
        <button onclick="mostrarAba('grupos')" id="tab-grupos" role="tab" aria-selected="true" aria-controls="aba-grupos"
                class="px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-600">
            📁 Grupos ({{ $grupos->count() }})
        </button>
        <button onclick="mostrarAba('soltos')" id="tab-soltos" role="tab" aria-selected="false" aria-controls="aba-soltos"
                class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
            📦 Produtos Soltos ({{ $naoAgrupados->count() }})
        </button>
    </nav>
</div>

{{-- ABA: GRUPOS --}}
<div id="aba-grupos" role="tabpanel" aria-labelledby="tab-grupos">
    @forelse($grupos as $grupo)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100 rounded-t-lg">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📌</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-800 text-lg">{{ $grupo->nome }}</span>
                                {{-- @json() evita XSS — addslashes() não é escape seguro em contexto JS --}}
                                <button onclick="editarNomeGrupo(@json($grupo->id), @json($grupo->nome))"
                                        class="text-gray-400 hover:text-blue-600 text-sm"
                                        aria-label="Renomear grupo {{ $grupo->nome }}">✏️</button>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{--
                                    N+1: certifique-se que o controller carrega com:
                                    ->with(['groupedProducts', 'invoiceItems'])
                                --}}
                                {{ $grupo->groupedProducts->count() }} agrupado(s) &middot; {{ $grupo->invoiceItems->count() }} compras
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('products.show', $grupo) }}"
                           class="text-blue-600 hover:text-blue-800 text-sm border border-blue-300 rounded px-3 py-1.5">
                            📈 Histórico
                        </a>
                        <button onclick="mostrarAdicionar('{{ $grupo->id }}')"
                                class="text-green-600 hover:text-green-800 text-sm border border-green-300 rounded px-3 py-1.5">
                            ➕ Adicionar
                        </button>
                        {{--
                            Substituiu onsubmit=confirm() por data-confirm-form:
                            JS intercepta o submit, exibe banner inline antes de submeter.
                        --}}
                        <form action="{{ route('products.desfazer-grupo', $grupo) }}" method="POST" class="inline"
                              data-confirm="Desfazer o grupo '{{ $grupo->nome }}'? Todos os produtos ficarão soltos.">
                            @csrf
                            <button type="submit" class="text-orange-600 hover:text-orange-800 text-sm border border-orange-300 rounded px-3 py-1.5">
                                🔓 Desfazer
                            </button>
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
                                  data-confirm="Tornar '{{ $agrupado->nome }}' como produto PRINCIPAL do grupo?">
                                @csrf
                                <button type="submit" class="text-green-500 hover:text-green-700 text-xs border border-green-300 rounded px-2 py-1">
                                    📌 Principal
                                </button>
                            </form>
                            <form action="{{ route('products.desagrupar', $agrupado) }}" method="POST" class="inline"
                                  data-confirm="Soltar '{{ $agrupado->nome }}' do grupo?">
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
                                <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-100 p-1 rounded">
                                    <input type="checkbox" name="produto_ids[]" value="{{ $prod->id }}"
                                           class="rounded border-gray-300 text-blue-600">
                                    {{ $prod->nome }}
                                </label>
                            @endforeach
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700">Adicionar</button>
                            <button type="button"
                                    onclick="document.getElementById('adicionar-{{ $grupo->id }}').classList.add('hidden')"
                                    class="text-xs text-gray-500 px-3 py-1.5">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <span class="text-6xl">📁</span>
            <p class="text-gray-500 mt-4 text-lg">Nenhum grupo criado ainda.</p>
            <p class="text-gray-400 text-sm mt-1">Use "Agrupar Automático" ou selecione produtos na aba Produtos Soltos.</p>
        </div>
    @endforelse
</div>

{{-- ABA: PRODUTOS SOLTOS --}}
<div id="aba-soltos" class="hidden" role="tabpanel" aria-labelledby="tab-soltos">
    @if($naoAgrupados->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50 rounded-t-lg">
                <h2 class="font-semibold text-gray-800">📦 Produtos Soltos</h2>
                <p class="text-xs text-gray-500 mt-1">Marque 2+ produtos e crie um grupo, ou torne um produto como principal</p>
            </div>

            <form action="{{ route('products.criar-grupo') }}" method="POST" id="formCriarGrupo">
                @csrf
                <div class="px-6 py-3 bg-gray-50 border-b flex flex-wrap gap-3 items-center">
                    <input type="text" name="nome_grupo" placeholder="Nome do grupo (opcional)"
                           class="form-control text-sm flex-1">
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
                                   class="rounded border-gray-300 text-blue-600 checkbox-solto"
                                   onchange="atualizarBotao()">
                            <div>
                                <span class="text-sm text-gray-700">{{ $produto->nome }}</span>
                                {{--
                                    N+1: o controller deve passar $naoAgrupados com:
                                    ->withCount('invoiceItems')
                                    e usar $produto->invoice_items_count aqui.
                                --}}
                                <span class="text-xs text-gray-400 ml-2">{{ $produto->invoiceItems->count() }} compras</span>
                            </div>
                        </label>
                        <form action="{{ route('products.tornar-canonico', $produto) }}" method="POST" class="ml-2 flex-shrink-0"
                              data-confirm="Tornar '{{ $produto->nome }}' como produto PRINCIPAL?">
                            @csrf
                            <button type="submit"
                                    class="text-green-600 hover:text-green-800 text-xs border border-green-300 rounded px-3 py-1.5 hover:bg-green-50 whitespace-nowrap">
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

{{-- Modal Renomear Grupo --}}
<div id="modalRenomear"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalRenomearTitulo">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 id="modalRenomearTitulo" class="text-lg font-semibold text-gray-800 mb-4">✏️ Renomear Grupo</h3>
        <form id="formRenomear" method="POST">
            @csrf
            <label for="inputRenomear" class="block text-sm font-medium text-gray-700 mb-1">Novo nome</label>
            <input type="text" name="nome" id="inputRenomear" class="form-control mb-4" required>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btnFecharRenomear" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="submit" class="btn-primary text-sm">Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Banner de confirmação inline (substitui confirm() nativo) --}}
<div id="bannerConfirm"
     class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-50 bg-white border border-gray-300 shadow-lg rounded-lg px-5 py-4 flex items-center gap-4 max-w-sm w-full"
     role="alertdialog"
     aria-modal="true"
     aria-labelledby="bannerConfirmMsg">
    <p id="bannerConfirmMsg" class="text-sm text-gray-700 flex-1"></p>
    <div class="flex gap-2 flex-shrink-0">
        <button id="bannerConfirmCancel" class="btn-outline-secondary text-xs">Cancelar</button>
        <button id="bannerConfirmOk" class="btn-delete text-xs">Confirmar</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Abas ──────────────────────────────────────────────────────────────────
    window.mostrarAba = function (aba) {
        ['grupos', 'soltos'].forEach(id => {
            document.getElementById('aba-' + id).classList.toggle('hidden', id !== aba);
            const tab = document.getElementById('tab-' + id);
            const ativo = (id === aba);
            tab.setAttribute('aria-selected', ativo);
            tab.classList.toggle('border-blue-600', ativo);
            tab.classList.toggle('text-blue-600', ativo);
            tab.classList.toggle('border-transparent', !ativo);
            tab.classList.toggle('text-gray-500', !ativo);
        });
    };

    // ── Painel adicionar produto ao grupo ─────────────────────────────────────
    window.mostrarAdicionar = function (id) {
        document.getElementById('adicionar-' + id).classList.toggle('hidden');
    };

    // ── Contador do botão Criar Grupo ────────────────────────────────────────
    window.atualizarBotao = function () {
        const count = document.querySelectorAll('.checkbox-solto:checked').length;
        const btn   = document.getElementById('btnCriarGrupo');
        if (btn) {
            btn.textContent = 'Criar Grupo (' + count + ')';
            btn.disabled    = count < 2;
            btn.classList.toggle('opacity-50', count < 2);
        }
    };
    atualizarBotao();

    // ── Modal Renomear ────────────────────────────────────────────────────────
    const modalRenomear  = document.getElementById('modalRenomear');
    const btnFecharRen   = document.getElementById('btnFecharRenomear');
    const inputRenomear  = document.getElementById('inputRenomear');

    window.editarNomeGrupo = function (id, nome) {
        inputRenomear.value = nome;
        document.getElementById('formRenomear').action = '/products/' + id + '/renomear-grupo';
        modalRenomear.classList.remove('hidden');
        inputRenomear.focus();
    };

    function fecharRenomear() {
        modalRenomear.classList.add('hidden');
    }

    btnFecharRen.addEventListener('click', fecharRenomear);
    modalRenomear.addEventListener('click', e => { if (e.target === modalRenomear) fecharRenomear(); });

    // ── Banner de confirmação (substitui confirm() nativo) ───────────────────
    const banner       = document.getElementById('bannerConfirm');
    const bannerMsg    = document.getElementById('bannerConfirmMsg');
    const bannerOk     = document.getElementById('bannerConfirmOk');
    const bannerCancel = document.getElementById('bannerConfirmCancel');
    let   pendingForm  = null;

    function mostrarBanner(msg, form) {
        bannerMsg.textContent = msg;
        pendingForm = form;
        banner.classList.remove('hidden');
        bannerOk.focus();
    }

    function fecharBanner() {
        banner.classList.add('hidden');
        pendingForm = null;
    }

    bannerOk.addEventListener('click', function () {
        if (pendingForm) pendingForm.submit();
        fecharBanner();
    });

    bannerCancel.addEventListener('click', fecharBanner);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (!modalRenomear.classList.contains('hidden')) fecharRenomear();
            if (!banner.classList.contains('hidden'))        fecharBanner();
        }
    });

    // Intercepta todos os forms com data-confirm
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            mostrarBanner(form.dataset.confirm, form);
        });
    });

});
</script>
@endpush
