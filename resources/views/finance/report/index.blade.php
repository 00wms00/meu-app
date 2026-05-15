@extends('layouts.app')

@section('title', 'Relatório Financeiro')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Relatório Financeiro</h1>
    <p class="mt-1 text-gray-600">
        Período: {{ $inicio->format('d/m/Y') }} — {{ $fim->format('d/m/Y') }}
    </p>
</div>

<form method="GET" action="{{ route('finance.report.index') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6 flex flex-wrap gap-4 items-end">
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Data início</label>
        <input type="date" name="data_inicio" value="{{ $inicio->format('Y-m-d') }}" class="form-input">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Data fim</label>
        <input type="date" name="data_fim" value="{{ $fim->format('Y-m-d') }}" class="form-input">
    </div>
    <input type="hidden" name="preset" value="personalizado">
    <button type="submit" class="btn-primary">Filtrar período</button>
    <a href="{{ route('finance.report.index', ['preset' => 'esse-mes']) }}" class="btn-outline-secondary">Esse mês</a>
</form>

{{-- Cards de resumo --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="p-4 bg-green-50 rounded-lg border border-green-100">
        <p class="text-xs text-green-700 font-medium">Receitas</p>
        <p class="text-2xl font-bold text-green-700 mt-1">R$ {{ number_format($totalReceitas, 2, ',', '.') }}</p>
    </div>
    <div class="p-4 bg-red-50 rounded-lg border border-red-100">
        <p class="text-xs text-red-700 font-medium">Despesas</p>
        <p class="text-2xl font-bold text-red-700 mt-1">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</p>
    </div>
    <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
        <p class="text-xs text-blue-700 font-medium">Saldo</p>
        <p class="text-2xl font-bold {{ $saldo >= 0 ? 'text-blue-700' : 'text-red-700' }} mt-1">R$ {{ number_format($saldo, 2, ',', '.') }}</p>
    </div>
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 text-xs text-gray-600 space-y-1">
        <div>Manuais: <strong>R$ {{ number_format($totalDespesasManuais, 2, ',', '.') }}</strong></div>
        <div>Mercado: <strong>R$ {{ number_format($totalMercadoPeriodo, 2, ',', '.') }}</strong></div>
        <div>Veículos: <strong>R$ {{ number_format($totalVeiculosPeriodo, 2, ',', '.') }}</strong></div>
        <div class="pt-1 border-t border-gray-200">
            🔒 Fixas: <strong>R$ {{ number_format($totalFixas, 2, ',', '.') }}</strong>
        </div>
        <div>
            🎯 Variáveis: <strong>R$ {{ number_format($totalVariaveis, 2, ',', '.') }}</strong>
        </div>
    </div>
</div>

{{-- Gráfico 1: Receitas x Despesas por mês --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-3">Receitas x Despesas por mês</h2>
    <div style="position:relative; height:260px">
        <canvas id="chartMeses"></canvas>
    </div>
</div>

{{-- Gráfico 2: Despesas por categoria --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-3">Despesas por categoria</h2>
    <div style="position:relative; height:260px">
        <canvas id="chartCategorias"></canvas>
    </div>
</div>

{{-- Gráficos 3 e 4: Fixas e Variáveis lado a lado --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h2 class="text-sm font-semibold text-gray-800 mb-1">🔒 Despesas Fixas por categoria</h2>
        <p class="text-xs text-gray-500 mb-3">Total: <strong>R$ {{ number_format($totalFixas, 2, ',', '.') }}</strong></p>
        <div style="position:relative; height:240px">
            <canvas id="chartFixasCat"></canvas>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h2 class="text-sm font-semibold text-gray-800 mb-1">🎯 Despesas Variáveis por categoria</h2>
        <p class="text-xs text-gray-500 mb-3">Total: <strong>R$ {{ number_format($totalVariaveis + $totalMercadoPeriodo + $totalVeiculosPeriodo, 2, ',', '.') }}</strong></p>
        <div style="position:relative; height:240px">
            <canvas id="chartVariaveisCat"></canvas>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const COLORS = ['#0ea5e9','#6366f1','#f97316','#22c55e','#e11d48','#a855f7','#facc15','#4b5563','#22d3ee','#a3e635','#fb923c','#34d399'];

    const labelsMeses      = @json($labelsMeses ?? []);
    const serieReceitasMes = @json($serieReceitasMes ?? []);
    const serieDespesasMes = @json($serieDespesasMes ?? []);

    const labelsCategorias   = @json($labelsCategorias ?? []);
    const serieDespesasCat   = @json($serieDespesasCategorias ?? []);

    const labelsFixasCat     = @json($labelsFixasCat ?? []);
    const serieFixasCat      = @json($serieFixasCat ?? []);

    const labelsVariaveisCat = @json($labelsVariaveisCat ?? []);
    const serieVariaveisCat  = @json($serieVariaveisCat ?? []);

    // 1. Linha: Receitas x Despesas por mês
    if (labelsMeses.length) {
        new Chart(document.getElementById('chartMeses'), {
            type: 'line',
            data: {
                labels: labelsMeses,
                datasets: [
                    { label: 'Receitas', data: serieReceitasMes, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.1)', tension: 0.3 },
                    { label: 'Despesas', data: serieDespesasMes, borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.1)', tension: 0.3 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }

    // 2. Doughnut: Todas as despesas por categoria
    if (labelsCategorias.length) {
        new Chart(document.getElementById('chartCategorias'), {
            type: 'doughnut',
            data: {
                labels: labelsCategorias,
                datasets: [{ data: serieDespesasCat, backgroundColor: COLORS }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // 3. Barras horizontais: Fixas por categoria
    if (labelsFixasCat.length) {
        new Chart(document.getElementById('chartFixasCat'), {
            type: 'bar',
            data: {
                labels: labelsFixasCat,
                datasets: [{ label: 'Fixas', data: serieFixasCat, backgroundColor: '#6366f1' }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    }

    // 4. Barras horizontais: Variáveis por categoria
    if (labelsVariaveisCat.length) {
        new Chart(document.getElementById('chartVariaveisCat'), {
            type: 'bar',
            data: {
                labels: labelsVariaveisCat,
                datasets: [{ label: 'Variáveis', data: serieVariaveisCat, backgroundColor: '#f97316' }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    }
</script>
@endpush
