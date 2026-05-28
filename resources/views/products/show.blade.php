@extends('layouts.app')

@section('title', 'Histórico: ' . $produtoExibicao->nome)

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ \App\Helpers\ProductHelper::displayName($produtoExibicao) }}</h1>
        <p class="mt-1 text-gray-600">Histórico de preços</p>
    </div>
    <div class="flex flex-wrap gap-2 items-center">

        @if($alertaExistente)
            <span id="alertaBadge"
                  class="inline-flex items-center gap-1 px-3 py-1.5 rounded text-sm
                         {{ $alertaExistente->ativo ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-gray-100 text-gray-500 border border-gray-300' }}"
                  title="Alerta em {{ $alertaExistente->variacao_percentual }}%">
                {{ $alertaExistente->ativo ? '🔔' : '🔕' }}
                Alerta {{ $alertaExistente->ativo ? 'ativo' : 'pausado' }}
                &bull; {{ $alertaExistente->variacao_percentual }}%
            </span>
        @endif

        <button
            id="btnAbrirAlerta"
            data-action="{{ route('products.alerta.criar', $product) }}"
            data-nome="{{ \App\Helpers\ProductHelper::displayName($produtoExibicao) }}"
            data-limite="{{ $alertaExistente->variacao_percentual ?? 10 }}"
            class="btn-outline-secondary text-sm flex items-center gap-1"
            aria-label="{{ $alertaExistente ? 'Editar alerta de preço' : 'Criar alerta de preço' }}">
            🔔 {{ $alertaExistente ? 'Editar Alerta' : 'Alerta de Preço' }}
        </button>

        <a href="{{ route('products.edit', $produtoExibicao) }}" class="btn-outline-primary text-sm">✏️ Editar</a>
        <a href="{{ route('products.similares', $product) }}" class="btn-outline-secondary text-sm flex items-center gap-1">🧠 Similares</a>
        <a href="{{ route('products.index') }}" class="btn-back">← Voltar</a>
    </div>
</div>

@if(session('alerta_criado'))
    <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded"
         role="status" aria-live="polite">
        <span aria-hidden="true">✅</span>
        <span>{{ session('success') }}</span>
        <a href="{{ route('alertas.index') }}" class="ml-auto text-sm underline hover:no-underline">Ver todos os alertas</a>
    </div>
@endif

@if($product->id !== $produtoExibicao->id)
    <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-4" role="status">
        📎 Este produto está agrupado como <strong>{{ \App\Helpers\ProductHelper::displayName($produtoExibicao) }}</strong>
    </div>
@endif

{{-- Foto + Variação --}}
<div class="flex items-start gap-4 mb-6">
    <div class="flex-shrink-0 w-24 bg-white rounded-lg shadow-sm border border-gray-200 p-1.5 text-center">
        @if($produtoExibicao->foto)
            <div class="relative group">
                <img src="{{ asset('storage/' . $produtoExibicao->foto) }}"
                     alt="Foto de {{ \App\Helpers\ProductHelper::displayName($produtoExibicao) }}"
                     class="w-20 h-20 object-cover rounded" loading="lazy" width="80" height="80">
                <form action="{{ route('products.foto.remover', $produtoExibicao) }}" method="POST"
                      class="absolute -top-1 -right-1 hidden group-hover:block"
                      data-confirm="Remover a foto de '{{ \App\Helpers\ProductHelper::displayName($produtoExibicao) }}'?">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center leading-none"
                            title="Remover foto" aria-label="Remover foto">✕</button>
                </form>
            </div>
        @else
            <div class="w-20 h-20 bg-gray-100 rounded border-2 border-dashed border-gray-300 flex flex-col items-center justify-center" aria-label="Sem foto">
                <span class="text-2xl text-gray-400" aria-hidden="true">📷</span>
            </div>
        @endif
        <form action="{{ route('products.foto', $produtoExibicao) }}" method="POST" enctype="multipart/form-data" class="mt-1">
            @csrf
            <label for="inputFotoProduto" class="cursor-pointer">
                <span class="text-xs text-blue-600">{{ $produtoExibicao->foto ? 'Trocar' : 'Add' }}</span>
                <input type="file" name="foto" id="inputFotoProduto" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.form.submit()">
            </label>
        </form>
        @error('foto')<p class="text-xs text-red-500 mt-1" role="alert">{{ $message }}</p>@enderror
    </div>

    <div class="flex-1">
        @if($serie->isNotEmpty() && ! is_null($variacao))
            <div class="p-4 rounded-lg {{ $variacao > 0 ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700' }}">
                <div class="flex items-center gap-3">
                    <span class="text-2xl" aria-hidden="true">{{ $variacao > 0 ? '📈' : '📉' }}</span>
                    <div>
                        <p class="font-semibold">Variação: <strong>{{ number_format($variacao, 2, ',', '.') }}%</strong></p>
                        <p class="text-sm">
                            R$ {{ number_format($serie->first()['valor_unitario'], 2, ',', '.') }}
                            &rarr;
                            R$ {{ number_format($serie->last()['valor_unitario'], 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ====================== ANÁLISE DE PREÇOS ====================== --}}
@if($statsHistorico['total'] > 0)
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">

    {{-- Cabeçalho com filtro de período --}}
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-gray-800">📊 Análise de Preços</h2>

        <form method="GET" class="flex flex-wrap items-center gap-2" id="formPeriodo">
            {{-- Atalhos --}}
            <div class="flex gap-1" role="group" aria-label="Período rápido">
                @foreach([
                    '30d'       => 'Últimos 30 dias',
                    '6m'        => 'Últimos 6 meses',
                    'historico' => 'Histórico completo',
                    'custom'    => 'Personalizado',
                ] as $chave => $label)
                    <a href="{{ route('products.show', array_merge(['product' => $product->id], $chave !== 'custom' ? ['periodo' => $chave] : ['periodo' => 'custom', 'data_inicio' => $dataInicioAnalise?->format('Y-m-d') ?? now()->subDays(30)->format('Y-m-d'), 'data_fim' => $dataFimAnalise?->format('Y-m-d') ?? now()->format('Y-m-d')])) }}"
                       class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold border transition
                              {{ $periodoAtivo === $chave ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Campos de período personalizado --}}
            <div id="campoPeriodoCustom" class="flex items-center gap-2 {{ $periodoAtivo !== 'custom' ? 'hidden' : '' }}">
                <input type="hidden" name="periodo" value="custom">
                <input type="date" name="data_inicio"
                       value="{{ $periodoAtivo === 'custom' ? $dataInicioAnalise?->format('Y-m-d') : now()->subDays(30)->format('Y-m-d') }}"
                       class="border border-gray-300 rounded px-2 py-1 text-xs">
                <span class="text-xs text-gray-500">até</span>
                <input type="date" name="data_fim"
                       value="{{ $periodoAtivo === 'custom' ? $dataFimAnalise?->format('Y-m-d') : now()->format('Y-m-d') }}"
                       class="border border-gray-300 rounded px-2 py-1 text-xs">
                <button type="submit" class="inline-flex items-center px-2.5 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition">Filtrar</button>
            </div>
        </form>
    </div>

    {{-- Cards de estatísticas do período selecionado --}}
    @if($estatisticas['total'] > 0)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-gray-200">

        {{-- Preço Médio --}}
        <div class="bg-white px-6 py-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Preço Médio</p>
            <p class="text-xl font-bold text-gray-900 tabular-nums">R$ {{ number_format($estatisticas['media'], 2, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $estatisticas['total'] }} {{ Str::plural('compra', $estatisticas['total']) }}</p>
        </div>

        {{-- Mínimo --}}
        <div class="bg-white px-6 py-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Mínimo</p>
            <p class="text-xl font-bold text-green-600 tabular-nums">R$ {{ number_format($estatisticas['minimo'], 2, ',', '.') }}</p>
            @if($estatisticas['minimo'] < $estatisticas['media'])
                @php $diffMin = (($estatisticas['minimo'] - $estatisticas['media']) / $estatisticas['media']) * 100; @endphp
                <p class="text-xs text-green-500 mt-1">{{ number_format($diffMin, 1, ',', '.') }}% abaixo da média</p>
            @endif
        </div>

        {{-- Máximo --}}
        <div class="bg-white px-6 py-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Máximo</p>
            <p class="text-xl font-bold text-red-500 tabular-nums">R$ {{ number_format($estatisticas['maximo'], 2, ',', '.') }}</p>
            @if($estatisticas['maximo'] > $estatisticas['media'])
                @php $diffMax = (($estatisticas['maximo'] - $estatisticas['media']) / $estatisticas['media']) * 100; @endphp
                <p class="text-xs text-red-400 mt-1">+{{ number_format($diffMax, 1, ',', '.') }}% acima da média</p>
            @endif
        </div>

        {{-- Moda --}}
        <div class="bg-white px-6 py-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Moda</p>
            @if($estatisticas['moda'] !== null)
                <p class="text-xl font-bold text-indigo-600 tabular-nums">R$ {{ number_format($estatisticas['moda'], 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Preço mais frequente</p>
            @else
                <p class="text-xl font-bold text-gray-300">—</p>
            @endif
        </div>
    </div>

    {{-- Comparativo fixo 30d vs 6m vs Histórico --}}
    <div class="border-t border-gray-100 px-6 py-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Comparativo de Médias</p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" aria-label="Comparativo de preço médio por período">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase">
                        <th class="text-left pb-2 pr-6 font-medium">Período</th>
                        <th class="text-right pb-2 px-4 font-medium">Compras</th>
                        <th class="text-right pb-2 px-4 font-medium">Média</th>
                        <th class="text-right pb-2 px-4 font-medium">Mínimo</th>
                        <th class="text-right pb-2 px-4 font-medium">Máximo</th>
                        <th class="text-right pb-2 pl-4 font-medium">Moda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach([
                        ['label' => 'Últimos 30 dias',     'stats' => $stats30d],
                        ['label' => 'Últimos 6 meses',     'stats' => $stats6m],
                        ['label' => 'Histórico completo',  'stats' => $statsHistorico],
                    ] as $linha)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 pr-6 text-gray-700 font-medium">{{ $linha['label'] }}</td>
                            @if($linha['stats']['total'] > 0)
                                <td class="py-2 px-4 text-right text-gray-600 tabular-nums">{{ $linha['stats']['total'] }}</td>
                                <td class="py-2 px-4 text-right font-semibold tabular-nums">R$ {{ number_format($linha['stats']['media'], 2, ',', '.') }}</td>
                                <td class="py-2 px-4 text-right text-green-600 tabular-nums">R$ {{ number_format($linha['stats']['minimo'], 2, ',', '.') }}</td>
                                <td class="py-2 px-4 text-right text-red-500 tabular-nums">R$ {{ number_format($linha['stats']['maximo'], 2, ',', '.') }}</td>
                                <td class="py-2 pl-4 text-right text-indigo-600 tabular-nums">
                                    {{ $linha['stats']['moda'] !== null ? 'R$ ' . number_format($linha['stats']['moda'], 2, ',', '.') : '—' }}
                                </td>
                            @else
                                <td colspan="5" class="py-2 px-4 text-center text-gray-400 text-xs">Sem compras no período</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
        <div class="px-6 py-8 text-center text-gray-400">
            <span class="text-3xl" aria-hidden="true">🔍</span>
            <p class="mt-2 text-sm">Nenhuma compra encontrada no período selecionado.</p>
        </div>
    @endif
</div>
@endif

{{-- ====================== GRÁFICO ============================ --}}
@if($serie->isNotEmpty())
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📈 Evolução do Preço Unitário</h2>
        <div class="relative h-72">
            <canvas id="historicoChart" role="img" aria-label="Gráfico de evolução do preço"></canvas>
        </div>
    </div>

    {{-- Tabela Todas as Compras --}}
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800">📋 Todas as Compras</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" aria-label="Histórico de compras">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wide">Produto</th>
                        <th scope="col" class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wide">Data</th>
                        <th scope="col" class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wide">Mercado</th>
                        <th scope="col" class="text-center py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wide">UN</th>
                        <th scope="col" class="text-right py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wide">Preço Unit.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($serie as $ponto)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-sm text-gray-800 max-w-[200px]">
                                <span class="block truncate" title="{{ $ponto['nome_produto'] }}">{{ $ponto['nome_produto'] }}</span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($ponto['data'])->format('d/m/Y') }}
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 max-w-[180px]">
                                <span class="block truncate" title="{{ $ponto['mercado'] }}">{{ $ponto['mercado'] }}</span>
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-500">{{ $ponto['unidade'] ?: '—' }}</td>
                            <td class="py-3 px-4 text-sm text-right font-semibold tabular-nums text-gray-900">
                                R$ {{ number_format($ponto['valor_unitario'], 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="bg-white rounded-lg shadow-md p-12 text-center mb-6">
        <span class="text-6xl" aria-hidden="true">📦</span>
        <p class="text-gray-500 mt-4 text-lg">Nenhuma compra registrada ainda.</p>
    </div>
@endif

{{-- Produtos Agrupados --}}
@if($agrupados->isNotEmpty())
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">🔗 Produtos Agrupados ({{ $agrupados->count() }})</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($agrupados as $agrupado)
                <a href="{{ route('products.show', $agrupado) }}"
                   class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 rounded-lg p-2">
                    @if($agrupado->foto)
                        <img src="{{ asset('storage/' . $agrupado->foto) }}" class="w-8 h-8 object-cover rounded"
                             alt="Foto de {{ $agrupado->nome }}" width="32" height="32" loading="lazy">
                    @else
                        <span class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center text-xs" aria-hidden="true">📷</span>
                    @endif
                    <span class="truncate">{{ $agrupado->nome }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

{{-- Modal de Alerta de Preço --}}
<div id="modalAlerta" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
     role="dialog" aria-modal="true" aria-labelledby="modalAlertaTitulo">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full mx-4 p-6">
        <h3 id="modalAlertaTitulo" class="text-lg font-semibold text-gray-800 mb-2">🔔 Alerta de Preço</h3>
        <p class="text-sm text-gray-600 mb-4" id="modalAlertaProduto"></p>
        <form id="formAlerta" method="POST">
            @csrf
            <label for="inputLimiteAlerta" class="block text-sm font-medium text-gray-700 mb-1">Alertar quando o preço aumentar</label>
            <div class="flex items-center gap-2 mb-4">
                <input type="number" name="limite_alerta" id="inputLimiteAlerta"
                       value="10" min="1" max="100" class="form-control w-20 text-center" required>
                <span class="text-sm text-gray-500">%</span>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btnFecharModal" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="submit" class="btn-primary text-sm">💾 Salvar Alerta</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Mostrar/ocultar campos de período custom ──────────────── */
    document.querySelectorAll('a[href*="periodo="]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const isPeriodoCustom = this.href.includes('periodo=custom');
            document.getElementById('campoPeriodoCustom').classList.toggle('hidden', !isPeriodoCustom);
        });
    });

    @if($serie->isNotEmpty())
    const serie = @json($serie);
    const ctx = document.getElementById('historicoChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: serie.map(s => new Date(s.data + 'T00:00:00').toLocaleDateString('pt-BR')),
                datasets: [{
                    label: 'Preço Unitário (R$)',
                    data: serie.map(s => s.valor_unitario),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 5,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: v => 'R$ ' + v.toFixed(2).replace('.', ',') } }
                }
            }
        });
    }
    @endif

    /* ── Modal de Alerta ─────────────────────────────────────── */
    const modal       = document.getElementById('modalAlerta');
    const formAlerta  = document.getElementById('formAlerta');
    const btnAbrir    = document.getElementById('btnAbrirAlerta');
    const btnFechar   = document.getElementById('btnFecharModal');
    const inputLimite = document.getElementById('inputLimiteAlerta');
    let ultimoFoco    = null;

    function abrirModal() {
        ultimoFoco = document.activeElement;
        formAlerta.action = btnAbrir.dataset.action;
        document.getElementById('modalAlertaProduto').textContent = 'Produto: ' + btnAbrir.dataset.nome;
        inputLimite.value = btnAbrir.dataset.limite || 10;
        modal.classList.remove('hidden');
        setTimeout(function () { inputLimite.focus(); }, 50);
    }

    function fecharModal() {
        modal.classList.add('hidden');
        inputLimite.value = btnAbrir.dataset.limite || 10;
        if (ultimoFoco) ultimoFoco.focus();
    }

    btnAbrir.addEventListener('click', abrirModal);
    btnFechar.addEventListener('click', fecharModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) fecharModal(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) fecharModal();
    });
});
</script>
@endpush
