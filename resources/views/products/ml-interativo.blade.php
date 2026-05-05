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
        <div class="flex gap-2">
            <button onclick="confirmarTodos()" class="btn-success text-sm">✅ Confirmar Todos</button>
            <button onclick="pularTodos()" class="btn-outline-secondary text-sm">⏭️ Pular Todos</button>
            <a href="{{ route('products.agrupamentos') }}" class="btn-back">← Voltar</a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif

<div id="sugestoesContainer" class="space-y-4">
    @forelse($sugestoes as $index => $sugestao)
    <div class="sugestao-card bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition" 
         id="card-{{ $sugestao['produto']->id }}">
        <div class="flex items-start justify-between gap-4">
            <!-- Produto Original -->
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">Produto</span>
                    @if($sugestao['produto']->foto)
                    <img src="{{ asset('storage/' . $sugestao['produto']->foto) }}" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
                    @endif
                </div>
                <p class="text-sm text-gray-800">{{ $sugestao['produto']->nome }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $sugestao['produto']->invoiceItems->count() }} compras</p>
            </div>

            <!-- Seta -->
            <div class="flex items-center pt-4">
                <span class="text-2xl text-gray-300">→</span>
            </div>

            <!-- Produto Similar Sugerido -->
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Sugestão</span>
                    <span class="text-xs font-bold {{ $sugestao['melhor_match']['similaridade'] > 70 ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $sugestao['melhor_match']['similaridade'] }}%
                    </span>
                </div>
                <p class="text-sm font-medium text-gray-800">
                    {{ $sugestao['melhor_match']['product']->nome }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $sugestao['melhor_match']['product']->invoiceItems->count() }} compras
                </p>
                
                @if(count($sugestao['similares']) > 1)
                <div class="mt-2">
                    <p class="text-xs text-gray-400 mb-1">Outras opções:</p>
                    @foreach(array_slice($sugestao['similares'], 1, 3) as $alt)
                    <button onclick="selecionarAlternativa('{{ $sugestao['produto']->id }}', '{{ $alt['product']->id }}', '{{ addslashes($alt['product']->nome) }}')"
                            class="text-xs text-blue-500 hover:text-blue-700 block">
                        ↳ {{ $alt['product']->nome }} ({{ $alt['similaridade'] }}%)
                    </button>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Botões de Ação -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <button onclick="confirmarAgrupamento('{{ $sugestao['produto']->id }}', '{{ $sugestao['melhor_match']['product']->id }}', this)"
                        class="btn-success text-sm px-4 py-2" title="Agrupar">
                    ✅ Agrupar
                </button>
                <button onclick="pularSugestao('{{ $sugestao['produto']->id }}', this)"
                        class="btn-outline-secondary text-sm px-3 py-2" title="Pular">
                    ⏭️
                </button>
                <button onclick="ignorarSugestao('{{ $sugestao['produto']->id }}', this)"
                        class="text-red-400 hover:text-red-600 text-sm px-2 py-2" title="Ignorar">
                    ✕
                </button>
            </div>
        </div>

        <!-- Barra de Similaridade -->
        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3">
            <div class="h-1.5 rounded-full {{ $sugestao['melhor_match']['similaridade'] > 70 ? 'bg-green-500' : ($sugestao['melhor_match']['similaridade'] > 50 ? 'bg-yellow-500' : 'bg-gray-400') }}"
                 style="width: {{ $sugestao['melhor_match']['similaridade'] }}%"></div>
        </div>

        <!-- Status (oculto inicialmente) -->
        <div class="status-message hidden mt-2 text-sm font-medium"></div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center col-span-full">
        <span class="text-6xl">🧠</span>
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
<div id="toast" class="hidden fixed bottom-6 right-6 bg-gray-800 text-white px-4 py-3 rounded-lg shadow-lg z-50 text-sm"></div>
@endsection

@push('scripts')
<script>
let contador = { agrupados: 0, pulados: 0, ignorados: 0 };

function mostrarToast(mensagem, tipo = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = mensagem;
    toast.className = 'fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg z-50 text-sm text-white ' + 
        (tipo === 'success' ? 'bg-green-600' : tipo === 'error' ? 'bg-red-600' : 'bg-gray-600');
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 2500);
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
        
        // Desabilitar botões
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

function confirmarTodos() {
    if (confirm('Deseja confirmar TODAS as sugestões de agrupamento?\n\nTodas as sugestões serão aplicadas automaticamente.')) {
        document.querySelectorAll('.sugestao-card').forEach(card => {
            const btnAgrupar = card.querySelector('button[onclick*="confirmarAgrupamento"]');
            if (btnAgrupar && !btnAgrupar.disabled) {
                btnAgrupar.click();
            }
        });
    }
}

function pularTodos() {
    if (confirm('Pular todas as sugestões restantes?')) {
        document.querySelectorAll('.sugestao-card').forEach(card => {
            const btnPular = card.querySelector('button[onclick*="pularSugestao"]');
            if (btnPular && !btnPular.disabled) {
                btnPular.click();
            }
        });
    }
}

function atualizarResumo() {
    document.getElementById('countAgrupados').textContent = contador.agrupados;
    document.getElementById('countPulados').textContent = contador.pulados;
    document.getElementById('countIgnorados').textContent = contador.ignorados;
    document.getElementById('resumo').classList.remove('hidden');
}

// Atualizar resumo ao carregar
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelectorAll('.sugestao-card').length === 0) {
        document.getElementById('resumo').classList.remove('hidden');
    }
});
</script>
@endpush
