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
                                <span class="font-semibold text-gray-800 text-lg">{{ \App\Helpers\ProductHelper::displayName($grupo) }}</span>
                                <button type="button"
                                        onclick="editarNomeGrupo('{{ $grupo->id }}', '{{ $grupo->nome }}')"
                                        class="text-gray-400 hover:text-blue-600 text-sm">✏️</button>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ $grupo->groupedProducts->count() }} agrupado(s) &middot; {{ $grupo->invoice_items_count }} compras
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
                            <div>
                                <span class="text-sm text-gray-700">{{ \App\Helpers\ProductHelper::displayName($agrupado) }}</span>
                                <span class="block text-xs text-gray-400 font-mono">{{ $agrupado->nome_normalizado ?? \App\Helpers\ProductHelper::normalizar($agrupado->nome) }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    onclick="tornarPrincipal('{{ $agrupado->id }}', '{{ \App\Helpers\ProductHelper::displayName($agrupado) }}')"
                                    class="inline-flex items-center px-3 py-1.5 border border-green-300 text-green-600 hover:bg-green-50 text-xs font-semibold rounded-md transition">
                                📌 Principal
                            </button>
                            <form action="{{ route('products.desagrupar', $agrupado) }}" method="POST" class="inline"
                                  data-confirm="Soltar '{{ \App\Helpers\ProductHelper::displayName($agrupado) }}' do grupo?">
                                @csrf
                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs">✕ Soltar</button>
                            </form>
                        </div>
                    </div>
                @endforeach

                {{-- Painel Adicionar --}}
                <div id="adicionar-{{ $grupo->id }}" class="hidden px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <form action="{{ route('products.adicionar-ao-grupo', $grupo) }}" method="POST">
                        @csrf

                        {{-- Campo de pesquisa --}}
                        <div class="relative mb-2">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">🔍</span>
                            <input
                                type="text"
                                class="search-adicionar w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white"
                                placeholder="Pesquisar por nome ou nome normalizado…"
                                autocomplete="off"
                                data-grupo="{{ $grupo->id }}"
                            >
                        </div>

                        {{-- Contador --}}
                        <p class="text-xs text-gray-500 mb-2">
                            Mostrando <span class="count-visivel-{{ $grupo->id }} font-medium text-gray-700">{{ min(5, $naoAgrupados->count()) }}</span>
                            de {{ $naoAgrupados->count() }} &mdash;
                            <span class="count-selecionados-{{ $grupo->id }} font-medium text-blue-600">0</span> selecionado(s)
                            <span class="hint-busca-{{ $grupo->id }} text-gray-400">
                                @if($naoAgrupados->count() > 5) &mdash; digite para ver mais @endif
                            </span>
                        </p>

                        {{-- Lista filtrável (os 5 primeiros visíveis por padrão) --}}
                        <div class="lista-adicionar-{{ $grupo->id }} max-h-56 overflow-y-auto space-y-1 mb-3 rounded border border-gray-200 bg-white p-2">
                            @foreach($naoAgrupados as $i => $prod)
                                @php
                                    $nomNorm = $prod->nome_normalizado
                                        ?? \App\Helpers\ProductHelper::normalizar($prod->nome);
                                @endphp
                                <label
                                    class="produto-item-{{ $grupo->id }} flex items-center gap-2 text-sm cursor-pointer hover:bg-blue-50 p-1.5 rounded {{ $i >= 5 ? 'item-extra hidden' : '' }}"
                                    data-nome="{{ Str::lower($prod->nome) }}"
                                    data-norm="{{ $nomNorm }}"
                                >
                                    <input type="checkbox"
                                           name="produto_ids[]"
                                           value="{{ $prod->id }}"
                                           class="check-adicionar-{{ $grupo->id }} rounded border-gray-300 text-blue-600 shrink-0">
                                    <div class="min-w-0">
                                        <span class="block truncate font-medium text-gray-800">{{ \App\Helpers\ProductHelper::displayName($prod) }}</span>
                                        <span class="block text-xs text-gray-400 font-mono truncate">{{ $nomNorm }}</span>
                                    </div>
                                </label>
                            @endforeach

                            <p class="sem-resultado-{{ $grupo->id }} hidden text-center text-gray-400 text-xs py-4">Nenhum produto encontrado.</p>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-md transition">
                                Adicionar ao grupo
                            </button>
                            <button type="button"
                                    onclick="fecharAdicionar('{{ $grupo->id }}')"
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

            <form action="{{ route('products.criar-grupo') }}" method="POST" id="formCriarGrupo" onsubmit="return validarCriarGrupo()">
                @csrf
                <div class="px-6 py-3 bg-gray-50 border-b flex flex-wrap gap-3 items-center">
                    <input type="text" name="nome_grupo" placeholder="Nome do grupo (opcional)"
                           class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm flex-1 min-w-40">
                    <button type="submit" id="btnCriarGrupo" disabled
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md transition whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
                        📁 Selecione 2+ produtos
                    </button>
                    <a href="{{ route('products.ml-interativo') }}"
                       class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                        🧠 ML Interativo
                    </a>
                </div>

                <div class="divide-y divide-gray-100">
                    @foreach($naoAgrupados as $prod)
                        @php
                            $nomNormSolto = $prod->nome_normalizado
                                ?? \App\Helpers\ProductHelper::normalizar($prod->nome);
                        @endphp
                        <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                            <label class="flex items-center gap-3 flex-1 cursor-pointer min-w-0">
                                <input type="checkbox" name="produto_ids[]" value="{{ $prod->id }}"
                                       class="produtoCheck rounded border-gray-300 text-blue-600 shrink-0">
                                <div class="min-w-0">
                                    <span class="block text-sm text-gray-700 font-medium truncate">{{ \App\Helpers\ProductHelper::displayName($prod) }}</span>
                                    <span class="block text-xs text-gray-400 font-mono truncate">{{ $nomNormSolto }}</span>
                                </div>
                                <span class="text-xs text-gray-400 shrink-0">({{ $prod->invoice_items_count }}x)</span>
                            </label>
                            <button type="button"
                                    onclick="tornarPrincipal('{{ $prod->id }}', '{{ \App\Helpers\ProductHelper::displayName($prod) }}')"
                                    class="ml-2 inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold rounded-md transition shrink-0">
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

{{-- Modal Renomear Grupo --}}
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

{{-- Banner de Confirmação --}}
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
const LIMITE_BUSCA = 5;

// ── Normalização JS (espelha ProductSimilarityService::normalizarNome) ─────
function normalizarNome(str) {
    const acentos = {
        'á':'a','à':'a','ã':'a','â':'a','ä':'a',
        'é':'e','è':'e','ê':'e','ë':'e',
        'í':'i','ì':'i','î':'i','ï':'i',
        'ó':'o','ò':'o','õ':'o','ô':'o','ö':'o',
        'ú':'u','ù':'u','û':'u','ü':'u',
        'ç':'c','ñ':'n'
    };
    let s = str.toLowerCase();
    s = s.replace(/[áàãâäéèêëíìîïóòõôöúùûüçñ]/g, c => acentos[c] || c);
    s = s.replace(/\b\d+[.,]?\d*\s*(kg|g|gr|l|ml|un|und|cx|pc|pct|lt|dz|x)\b/gi, '');
    s = s.replace(/\bc\/\d+\b/gi, '');
    s = s.replace(/[^a-z0-9\s]/g, ' ');
    return s.replace(/\s+/g, ' ').trim();
}

// ── Abas ──────────────────────────────────────────────────────────────
function mostrarAba(aba) {
    document.getElementById('aba-grupos').classList.toggle('hidden', aba !== 'grupos');
    document.getElementById('aba-soltos').classList.toggle('hidden', aba !== 'soltos');
    document.getElementById('tab-grupos').className = 'px-4 py-2 text-sm font-medium border-b-2 ' +
        (aba === 'grupos' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700');
    document.getElementById('tab-soltos').className = 'px-4 py-2 text-sm font-medium border-b-2 ' +
        (aba === 'soltos' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700');
}

// ── Painel Adicionar ──────────────────────────────────────────────────
function mostrarAdicionar(grupoId) {
    const painel = document.getElementById('adicionar-' + grupoId);
    painel.classList.remove('hidden');
    const inp = painel.querySelector('.search-adicionar');
    if (inp) setTimeout(() => inp.focus(), 50);
}

function fecharAdicionar(grupoId) {
    const painel = document.getElementById('adicionar-' + grupoId);
    painel.classList.add('hidden');

    const inp = painel.querySelector('.search-adicionar');
    if (inp) inp.value = '';

    // Restaura estado inicial: só os 5 primeiros visíveis, extras escondidos
    const items = document.querySelectorAll('.produto-item-' + grupoId);
    let count = 0;
    items.forEach(item => {
        item.classList.remove('hidden');
        count++;
        if (item.classList.contains('item-extra')) {
            item.classList.add('hidden');
        }
    });

    document.querySelectorAll('.check-adicionar-' + grupoId).forEach(cb => cb.checked = false);

    const hint = document.querySelector('.hint-busca-' + grupoId);
    if (hint) hint.classList.remove('hidden');

    atualizarContadores(grupoId);
}

// ── Pesquisa em tempo real com limite de 5 resultados ────────────────────
document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('search-adicionar')) return;

    const grupoId = e.target.dataset.grupo;
    const query   = normalizarNome(e.target.value);
    const items   = document.querySelectorAll('.produto-item-' + grupoId);
    const semRes  = document.querySelector('.sem-resultado-' + grupoId);
    const hint    = document.querySelector('.hint-busca-' + grupoId);

    // Sem busca ativa: volta ao estado padrão (5 primeiros visíveis)
    if (query === '') {
        items.forEach(item => {
            if (item.classList.contains('item-extra')) {
                item.classList.add('hidden');
            } else {
                item.classList.remove('hidden');
            }
        });
        if (semRes) semRes.classList.add('hidden');
        if (hint) hint.classList.remove('hidden');
        atualizarContadores(grupoId);
        return;
    }

    // Com busca: mostra só os primeiros LIMITE_BUSCA que correspondem
    if (hint) hint.classList.add('hidden');

    let exibidos = 0;
    items.forEach(item => {
        const nomeOrig = item.dataset.nome || '';
        const nomeNorm = item.dataset.norm  || '';
        const match    = nomeOrig.includes(query) || nomeNorm.includes(query);

        if (match && exibidos < LIMITE_BUSCA) {
            item.classList.remove('hidden');
            exibidos++;
        } else {
            item.classList.add('hidden');
        }
    });

    if (semRes) semRes.classList.toggle('hidden', exibidos > 0);
    atualizarContadores(grupoId);
});

// ── Contadores ────────────────────────────────────────────────────────────
function atualizarContadores(grupoId) {
    const items      = document.querySelectorAll('.produto-item-' + grupoId);
    const visiveis   = Array.from(items).filter(i => !i.classList.contains('hidden')).length;
    const selecionados = document.querySelectorAll('.check-adicionar-' + grupoId + ':checked').length;
    const spanV = document.querySelector('.count-visivel-' + grupoId);
    const spanS = document.querySelector('.count-selecionados-' + grupoId);
    if (spanV) spanV.textContent = visiveis;
    if (spanS) spanS.textContent = selecionados;
}

document.addEventListener('change', function(e) {
    const matchAdic = e.target.className.match(/check-adicionar-(\S+)/);
    if (matchAdic) { atualizarContadores(matchAdic[1]); return; }

    if (!e.target.classList.contains('produtoCheck')) return;
    const count = document.querySelectorAll('.produtoCheck:checked').length;
    const btn   = document.getElementById('btnCriarGrupo');
    if (!btn) return;
    if (count >= 2)      { btn.textContent = '📁 Criar Grupo (' + count + ' produtos)'; btn.disabled = false; }
    else if (count === 1){ btn.textContent = '📁 Selecione mais 1 produto'; btn.disabled = true; }
    else                 { btn.textContent = '📁 Selecione 2+ produtos';    btn.disabled = true; }
});

// ── Tornar Principal ───────────────────────────────────────────────────
function tornarPrincipal(produtoId, nome) {
    if (!confirm("Tornar '" + nome + "' como produto principal (canônico)?")) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/products/' + produtoId + '/tornar-canonico';
    form.style.display = 'none';
    var csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

// ── Modal Renomear ──────────────────────────────────────────────────────
function editarNomeGrupo(id, nome) {
    document.getElementById('formRenomear').action = '/products/' + id + '/renomear-grupo';
    document.getElementById('inputNomeGrupo').value = nome;
    document.getElementById('modalRenomear').classList.remove('hidden');
    document.getElementById('inputNomeGrupo').focus();
}
document.getElementById('btnFecharRenomear').addEventListener('click', () =>
    document.getElementById('modalRenomear').classList.add('hidden'));
document.getElementById('modalRenomear').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.getElementById('modalRenomear').classList.add('hidden');
});

// ── Criar Grupo ──────────────────────────────────────────────────────────
function validarCriarGrupo() {
    if (document.querySelectorAll('.produtoCheck:checked').length < 2) {
        alert('Selecione pelo menos 2 produtos para criar um grupo.');
        return false;
    }
    return true;
}

// ── Banner de Confirmação ────────────────────────────────────────────────
(function() {
    const banner = document.getElementById('bannerConfirm');
    const msg    = document.getElementById('bannerConfirmMsg');
    const btnOk  = document.getElementById('bannerConfirmOk');
    const btnCan = document.getElementById('bannerConfirmCancel');
    let pf = null;

    document.addEventListener('submit', function(e) {
        if (!e.target.dataset.confirm) return;
        e.preventDefault(); pf = e.target;
        msg.textContent = pf.dataset.confirm;
        banner.classList.remove('hidden'); btnOk.focus();
    });
    btnOk.addEventListener('click', () => { if (pf) { pf.submit(); pf = null; } banner.classList.add('hidden'); });
    btnCan.addEventListener('click', () => { pf = null; banner.classList.add('hidden'); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !banner.classList.contains('hidden')) { pf = null; banner.classList.add('hidden'); }
    });
})();
</script>
@endpush
