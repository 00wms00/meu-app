@extends('layouts.app')

@section('title', 'ML - Agrupamento Inteligente')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🧠 Agrupamento Inteligente</h1>
            <p class="mt-1 text-gray-600">
                O algoritmo encontrou <strong>{{ count($sugestoes) }}</strong> sugestões de agrupamento
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            {{-- Filtros por confiança --}}
            <div class="flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
                <button type="button" onclick="filtrarConfianca('todos')" id="filtro-todos"
                    class="filtro-btn px-3 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Todos</button>
                <button type="button" onclick="filtrarConfianca('Alta')" id="filtro-Alta"
                    class="filtro-btn px-3 py-1.5 bg-white text-green-700 hover:bg-green-50 transition">✅ Alta</button>
                <button type="button" onclick="filtrarConfianca('Média')" id="filtro-Média"
                    class="filtro-btn px-3 py-1.5 bg-white text-yellow-700 hover:bg-yellow-50 transition">⚡ Média</button>
                <button type="button" onclick="filtrarConfianca('Baixa')" id="filtro-Baixa"
                    class="filtro-btn px-3 py-1.5 bg-white text-gray-500 hover:bg-gray-50 transition">🔵 Baixa</button>
            </div>
            <button type="button" onclick="confirmarAltaConfianca()" class="btn-success text-sm">✅ Confirmar Alta Confiança</button>
            <button type="button" onclick="confirmarTodos()" class="btn-outline-secondary text-sm">✅ Confirmar Todos</button>
            <button type="button" onclick="pularTodos()" class="btn-outline-secondary text-sm">⏭️ Pular Todos</button>
            <a href="{{ route('products.agrupamentos') }}" class="btn-outline-secondary">← Voltar</a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif

<div id="sugestoesContainer" class="space-y-4">
    @forelse($sugestoes as $index => $sugestao)
    @php
        $match = $sugestao['melhor_match']['match'] ?? 'Baixa';
        $sim   = $sugestao['melhor_match']['similaridade'];
        $badgeClass = match($match) {
            'Alta'  => 'bg-green-100 text-green-700',
            'Média' => 'bg-yellow-100 text-yellow-700',
            default => 'bg-gray-100 text-gray-500',
        };
        $barClass = match(true) {
            $sim > 70 => 'bg-green-500',
            $sim > 50 => 'bg-yellow-500',
            default   => 'bg-gray-400',
        };
    @endphp
    <div class="sugestao-card bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition"
         id="card-{{ $sugestao['produto']->id }}"
         data-confianca="{{ $match }}">
        <div class="flex items-start justify-between gap-4">
            <!-- Produto Original -->
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">Produto</span>
                    @if($sugestao['produto']->foto)
                    <img src="{{ asset('storage/' . $sugestao['produto']->foto) }}"
                         alt="Foto de {{ $sugestao['produto']->nome }}"
                         style="width:30px;height:30px;object-fit:cover;border-radius:4px;"
                         loading="lazy" width="30" height="30">
                    @endif
                </div>
                <p class="text-sm text-gray-800">{{ $sugestao['produto']->nome }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $sugestao['produto']->invoiceItems->count() }} compras</p>
            </div>

            <!-- Seta -->
            <div class="flex items-center pt-4">
                <span class="text-2xl text-gray-300" aria-hidden="true">→</span>
            </div>

            <!-- Produto Similar Sugerido -->
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Sugestão</span>
                    {{-- Badge de confiança --}}
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $badgeClass }}">
                        {{ $match }} · {{ $sim }}%
                    </span>
                </div>
                <p class="text-sm font-medium text-gray-800">
                    {{ $sugestao['melhor_match']['product']->nome }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $sugestao['melhor_match']['product']->invoiceItems->count() }} compras
                </p>

                {{-- Razões da similaridade --}}
                @if(!empty($sugestao['melhor_match']['detalhes']))
                <p class="text-xs text-gray-400 mt-1 italic">
                    {{ implode(' · ', $sugestao['melhor_match']['detalhes']) }}
                </p>
                @endif

                @if(count($sugestao['similares']) > 1)
                <div class="mt-2">
                    <p class="text-xs text-gray-400 mb-1">Outras opções:</p>
                    @foreach(array_slice($sugestao['similares'], 1, 3) as $alt)
                    <button type="button"
                            onclick="selecionarAlternativa(@json($sugestao['produto']->id), @json($alt['product']->id), @json($alt['product']->nome))"
                            class="text-xs text-blue-500 hover:text-blue-700 block">
                        ↳ {{ $alt['product']->nome }} ({{ $alt['similaridade'] }}%)
                    </button>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Botões de Ação -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <button type="button"
                        onclick="confirmarAgrupamento(@json($sugestao['produto']->id), @json($sugestao['melhor_match']['product']->id), this)"
                        class="btn-success text-sm px-4 py-2" title="Agrupar"
                        aria-label="Agrupar {{ $sugestao['produto']->nome }} com {{ $sugestao['melhor_match']['product']->nome }}">
                    ✅ Agrupar
                </button>
                <button type="button"
                        onclick="pularSugestao(@json($sugestao['produto']->id), this)"
                        class="btn-outline-secondary text-sm px-3 py-2" title="Pular"
                        aria-label="Pular sugestão para {{ $sugestao['produto']->nome }}">
                    ⏭️
                </button>
                <button type="button"
                        onclick="ignorarSugestao(@json($sugestao['produto']->id), this)"
                        class="text-red-400 hover:text-red-600 text-sm px-2 py-2" title="Ignorar"
                        aria-label="Ignorar sugestão para {{ $sugestao['produto']->nome }}">
                    ✕
                </button>
            </div>
        </div>

        <!-- Barra de Similaridade -->
        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3"
             role="progressbar"
             aria-valuenow="{{ $sim }}"
             aria-valuemin="0" aria-valuemax="100"
             aria-label="Similaridade: {{ $sim }}%">
            <div class="h-1.5 rounded-full {{ $barClass }}" style="width: {{ $sim }}%"></div>
        </div>

        <!-- Status (oculto inicialmente) -->
        <div class="status-message hidden mt-2 text-sm font-medium" aria-live="polite"></div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center col-span-full">
        <span class="text-6xl" aria-hidden="true">🧠</span>
        <p class="text-gray-500 mt-4 text-lg">Nenhuma sugestão encontrada!</p>
        <p class="text-sm text-gray-400 mt-1">O algoritmo não encontrou produtos similares para agrupar.</p>
        <a href="{{ route('products.agrupamentos') }}" class="btn-primary mt-4 inline-block">← Voltar para Agrupamentos</a>
    </div>
    @endforelse
</div>

<!-- Resumo (aparece após ações) -->
<div id="resumo" class="hidden mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-3">📊 Resumo</h2>
    <div class="grid grid-cols-3 gap-4 text-center">
        <div class="p-4 bg-green-50 rounded-lg">
            <p class="text-2xl font-bold text-green-600" id="countAgrupados">0</p>
            <p class="text-sm text-green-700">Agrupados</p>
        </div>
        <div class="p-4 bg-yellow-50 rounded-lg">
            <p class="text-2xl font-bold text-yellow-600" id="countPulados">0</p>
            <p class="text-sm text-yellow-700">Pulados</p>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
            <p class="text-2xl font-bold text-gray-600" id="countIgnorados">0</p>
            <p class="text-sm text-gray-700">Ignorados</p>
        </div>
    </div>
    <a href="{{ route('products.agrupamentos') }}" class="btn-primary mt-4 inline-block">📁 Ir para Agrupamentos</a>
</div>

<!-- Toast de notificação -->
<div id="toast" class="hidden fixed bottom-6 right-6 bg-gray-800 text-white px-4 py-3 rounded-lg shadow-lg z-50 text-sm" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
<script>
let contador = { agrupados: 0, pulados: 0, ignorados: 0 };
let filtroAtivo = 'todos';

function mostrarToast(mensagem, tipo = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = mensagem;
    toast.className = 'fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg z-50 text-sm text-white ' +
        (tipo === 'success' ? 'bg-green-600' : tipo === 'error' ? 'bg-red-600' : 'bg-gray-600');
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 2500);
}

function filtrarConfianca(nivel) {
    filtroAtivo = nivel;

    // Atualiza estilo dos botões de filtro
    document.querySelectorAll('.filtro-btn').forEach(b => {
        b.classList.remove('ring-2', 'ring-offset-1', 'ring-gray-400', 'font-semibold');
    });
    const btnAtivo = document.getElementById('filtro-' + nivel);
    if (btnAtivo) btnAtivo.classList.add('ring-2', 'ring-offset-1', 'ring-gray-400', 'font-semibold');

    document.querySelectorAll('.sugestao-card').forEach(card => {
        if (nivel === 'todos') {
            card.style.display = '';
        } else {
            card.style.display = card.dataset.confianca === nivel ? '' : 'none';
        }
    });
}

function processarAcao(produtoId, canonicoId, acao, botao) {
    const card = document.getElementById('card-' + produtoId);

    fetch('{{ route('products.ml-confirmar') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            produto_id: produtoId,
            canonico_id: canonicoId,
            acao: acao
        })
    })
    .then(r => r.json())
    .then(data => {
        if (acao === 'agrupar') {
            contador.agrupados++;
            card.querySelector('.status-message').className = 'status-message mt-2 text-sm font-medium text-green-600';
            card.querySelector('.status-message').textContent = '✅ Agrupado com sucesso!';
            card.style.opacity = '0.6';
            card.style.backgroundColor = '#f0fdf4';
            mostrarToast('✅ ' + data.message, 'success');
        } else if (acao === 'pular') {
            contador.pulados++;
            card.style.opacity = '0.4';
            card.querySelector('.status-message').className = 'status-message mt-2 text-sm font-medium text-yellow-600';
            card.querySelector('.status-message').textContent = '⏭️ Pulado';
            mostrarToast('Produto pulado', 'warning');
        } else {
            contador.ignorados++;
            card.remove();
            mostrarToast('Produto ignorado');
        }

        card.querySelectorAll('button').forEach(b => b.disabled = true);
        atualizarResumo();
    })
    .catch(err => {
        mostrarToast('Erro ao processar', 'error');
        console.error(err);
    });
}

function confirmarAgrupamento(produtoId, canonicoId, botao) {
    if (confirm('Deseja agrupar estes produtos?\n\nO produto será vinculado ao grupo sugerido.')) {
        processarAcao(produtoId, canonicoId, 'agrupar', botao);
    }
}

function pularSugestao(produtoId, botao) {
    processarAcao(produtoId, null, 'pular', botao);
}

function ignorarSugestao(produtoId, botao) {
    if (confirm('Ignorar esta sugestão?\n\nO card será removido da lista.')) {
        processarAcao(produtoId, null, 'ignorar', botao);
    }
}

function selecionarAlternativa(produtoId, novoCanonicoId, nome) {
    if (confirm('Agrupar com "' + nome + '" em vez da sugestão principal?')) {
        processarAcao(produtoId, novoCanonicoId, 'agrupar', null);
    }
}

function confirmarAltaConfianca() {
    const cards = document.querySelectorAll('.sugestao-card[data-confianca="Alta"]');
    if (cards.length === 0) {
        mostrarToast('Nenhuma sugestão de Alta confiança disponível', 'warning');
        return;
    }
    if (confirm('Confirmar todos os ' + cards.length + ' agrupamentos de Alta confiança?')) {
        cards.forEach(card => {
            const btn = card.querySelector('button[onclick*="confirmarAgrupamento"]');
            if (btn && !btn.disabled) {
                // extrai IDs do onclick sem executar confirm novamente
                const match = btn.getAttribute('onclick').match(/confirmarAgrupamento\((\d+),\s*(\d+)/);
                if (match) processarAcao(parseInt(match[1]), parseInt(match[2]), 'agrupar', btn);
            }
        });
    }
}

function confirmarTodos() {
    if (confirm('Deseja confirmar TODAS as sugestões visíveis?')) {
        document.querySelectorAll('.sugestao-card').forEach(card => {
            if (card.style.display === 'none') return;
            const btnAgrupar = card.querySelector('button[onclick*="confirmarAgrupamento"]');
            if (btnAgrupar && !btnAgrupar.disabled) {
                const match = btnAgrupar.getAttribute('onclick').match(/confirmarAgrupamento\((\d+),\s*(\d+)/);
                if (match) processarAcao(parseInt(match[1]), parseInt(match[2]), 'agrupar', btnAgrupar);
            }
        });
    }
}

function pularTodos() {
    document.querySelectorAll('.sugestao-card').forEach(card => {
        if (card.style.display === 'none') return;
        const btnPular = card.querySelector('button[onclick*="pularSugestao"]');
        if (btnPular && !btnPular.disabled) btnPular.click();
    });
}

function atualizarResumo() {
    document.getElementById('countAgrupados').textContent = contador.agrupados;
    document.getElementById('countPulados').textContent   = contador.pulados;
    document.getElementById('countIgnorados').textContent = contador.ignorados;

    const total = contador.agrupados + contador.pulados + contador.ignorados;
    if (total > 0) document.getElementById('resumo').classList.remove('hidden');
}

// Ativa filtro "todos" por padrão
document.addEventListener('DOMContentLoaded', () => filtrarConfianca('todos'));
</script>
@endpush
