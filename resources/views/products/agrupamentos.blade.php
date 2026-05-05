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
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                    🤖 Agrupar Automático
                </button>
            </form>
            <a href="{{ route('products.index') }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">← Voltar</a>
        </div>
    </div>
</div>

{{-- Busca --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar produtos..."
               class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm flex-1"
               aria-label="Buscar produtos">
        <button type="submit"
                class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition"
                aria-label="Buscar">🔍</button>
        @if($search)
            <a href="{{ route('products.agrupamentos') }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold rounded-md transition">Limpar</a>
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
        {{-- type="button": botões de aba não devem submeter nenhum form --}}
        <button type="button" onclick="mostrarAba('grupos')" id="tab-grupos" role="tab" aria-selected="true" aria-controls="aba-grupos"
                class="px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-600">
            📁 Grupos ({{ $grupos->count() }})
        </button>
        <button type="button" onclick="mostrarAba('soltos')" id="tab-soltos" role="tab" aria-selected="false" aria-controls="aba-soltos"
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
                        <span class="text-2xl" aria-hidden="true">📌</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-800 text-lg">{{ $grupo->nome }}</span>
                                {{-- @json() evita XSS — addslashes() não é escape seguro em contexto JS --}}
                                <button type="button"
                                        onclick="editarNomeGrupo(@json($grupo->id), @json($grupo->nome))"
                                        class="text-gray-400 hover:text-blue-600 text-sm"
                                        aria-label="Renomear grupo {{ $grupo->nome }}">✏️</button>
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
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold rounded-md transition"
                                aria-label="Adicionar produto ao grupo {{ $grupo->nome }}">
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

            <div class="divide-y divide-gray-100">
                @foreach($grupo->groupedProducts as $agrupado)
                    <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <span class="text-gray-300" aria-hidden="true">↳</span>
                            <span class="text-sm text-gray-700">{{ $agrupado->nome }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <form action="{{ route('products.tornar-canonico', $agrupado) }}" method="POST" class="inline"
                                  data-confirm="Tornar '{{ $agrupado->nome }}' como produto PRINCIPAL do grupo?">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 border border-green-300 text-green-600 hover:bg-green-50 text-xs font-semibold rounded-md transition">
                                    📌 Principal
                                </button>
                            </form>
                            <form action="{{ route('products.desagrupar', $agrupado) }}" method="POST" class="inline"
                                  data-confirm="Soltar '{{ $agrupado->nome }}' do grupo?">
                                @csrf
                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs"
                                        aria-label="Soltar {{ $agrupado->nome }} do grupo">✕ Soltar</button>
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
            <span class="text-6xl" aria-hidden="true">📁</span>
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
                           class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm flex-1 min-w-40">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition whitespace-nowrap opacity-50"
                            id="btnCriarGrupo" disabled>
                        📁 Criar Grupo
                    </button>
                    <a href="{{ route('products.ml-interativo') }}"
                       class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                        🧠 ML Interativo
                    </a>
                </div>

                <div class="divide-y divide-gray-100">
                    @foreach($naoAgrupados as $prod)
                        <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                            <label class="flex items-center gap-3 flex-1 cursor-pointer">
                                <input type="checkbox" name="produto_ids[]" value="{{ $prod->id }}"
                                       class="produtoCheck rounded border-gray-300 text-blue-600">
                                <span class="text-sm text-gray-700">{{ $prod->nome }}</span>
                                <span class="text-xs text-gray-400">({{ $prod->invoiceItems->count() }}x)</span>
                            </label>
                            <form action="{{ route('products.tornar-canonico', $prod) }}" method="POST" class="inline"
                                  data-confirm="Tornar '{{ $prod->nome }}' como produto principal (canônico)?">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold rounded-md transition">
                                    📌 Tornar Principal
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <span class="text-6xl" aria-hidden="true">✅</span>
            <p class="text-gray-500 mt-4 text-lg">Todos os produtos estão agrupados!</p>
        </div>
    @endif
</div>

{{-- Modal Renomear Grupo --}}
<div id="modalRenomear"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
     role="dialog" aria-modal="true" aria-labelledby="modalRenomearTitulo">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full mx-4 p-6">
        <h3 id="modalRenomearTitulo" class="text-lg font-semibold text-gray-800 mb-4">✏️ Renomear Grupo</h3>
        <form id="formRenomear" method="POST">
            @csrf
            @method('PATCH')
            <label for="inputNomeGrupo" class="sr-only">Novo nome do grupo</label>
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

{{-- Banner de Confirmação Inline --}}
<div id="bannerConfirm"
     class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 bg-white border border-gray-300 shadow-lg rounded-lg px-6 py-4 flex items-center gap-4 z-50"
     role="alertdialog" aria-labelledby="bannerConfirmMsg" aria-live="assertive">
    <span id="bannerConfirmMsg" class="text-sm text-gray-700 flex-1"></span>
    <button type="button" id="bannerConfirmCancel"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold rounded-md transition">Cancelar</button>
    <button type="button" id="bannerConfirmOk"
            class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-md transition">Confirmar</button>
</div>

@push('scripts')
<script>
    // ── Abas ───────────────────────────────────────────────────────────────────
    function mostrarAba(aba) {
        ['grupos','soltos'].forEach(id => {
            document.getElementById('aba-' + id).classList.toggle('hidden', id !== aba);
            const tab = document.getElementById('tab-' + id);
            const ativo = id === aba;
            tab.setAttribute('aria-selected', ativo ? 'true' : 'false');
            tab.className = 'px-4 py-2 text-sm font-medium border-b-2 ' +
                (ativo ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700');
        });
    }

    // ── Modal Renomear Grupo ─────────────────────────────────────────────────────
    function editarNomeGrupo(id, nome) {
        const form  = document.getElementById('formRenomear');
        const input = document.getElementById('inputNomeGrupo');
        form.action = `/produtos/grupos/${id}/renomear`;
        input.value = nome;
        document.getElementById('modalRenomear').classList.remove('hidden');
        input.focus();
    }

    const btnFecharRen = document.getElementById('btnFecharRenomear');
    const modalRen    = document.getElementById('modalRenomear');

    btnFecharRen.addEventListener('click', fecharRenomear);
    modalRen.addEventListener('click', e => { if (e.target === modalRen) fecharRenomear(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharRenomear(); });

    function fecharRenomear() { modalRen.classList.add('hidden'); }

    // ── Mostrar painel Adicionar ───────────────────────────────────────────────────
    function mostrarAdicionar(grupoId) {
        document.getElementById('adicionar-' + grupoId).classList.remove('hidden');
    }

    // ── Habilitar botão Criar Grupo ao selecionar checkboxes ─────────────────────────
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('produtoCheck')) return;
        const count = document.querySelectorAll('.produtoCheck:checked').length;
        const btn   = document.getElementById('btnCriarGrupo');
        if (btn) {
            btn.textContent = 'Criar Grupo (' + count + ')';
            btn.disabled    = count < 2;
            btn.classList.toggle('opacity-50', count < 2);
        }
    });

    // ── Banner de Confirmação Inline ───────────────────────────────────────────────
    (function () {
        const banner   = document.getElementById('bannerConfirm');
        const msg      = document.getElementById('bannerConfirmMsg');
        const btnOk    = document.getElementById('bannerConfirmOk');
        const btnCanel = document.getElementById('bannerConfirmCancel');
        let pendingForm = null;

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.dataset.confirm) return;
            e.preventDefault();
            pendingForm = form;
            msg.textContent = form.dataset.confirm;
            banner.classList.remove('hidden');
            btnOk.focus();
        });

        btnOk.addEventListener('click', () => {
            if (pendingForm) { pendingForm.submit(); pendingForm = null; }
            banner.classList.add('hidden');
        });

        btnCanel.addEventListener('click', () => {
            pendingForm = null;
            banner.classList.add('hidden');
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !banner.classList.contains('hidden')) {
                pendingForm = null;
                banner.classList.add('hidden');
            }
        });
    })();
</script>
@endpush
@endsection
