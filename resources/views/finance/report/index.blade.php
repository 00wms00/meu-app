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
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 text-xs text-gray-600">
        <div>Despesas manuais: <strong>R$ {{ number_format($totalDespesasManuais, 2, ',', '.') }}</strong></div>
        <div>Mercado (notas): <strong>R$ {{ number_format($totalMercadoPeriodo, 2, ',', '.') }}</strong></div>
        <div>Veículos: <strong>R$ {{ number_format($totalVeiculosPeriodo, 2, ',', '.') }}</strong></div>
    </div>
</div>

{{-- Gráfico 1: Receitas x Despesas por mês --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-2">Receitas x Despesas por mês</h2>
    <div style="position:relative; height:260px">
        <canvas id="chartMeses"></canvas>
    </div>
</div>

{{-- Gráfico 2: Despesas por categoria --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-2">Despesas por categoria</h2>
    <div style="position:relative; height:260px">
        <canvas id="chartCategorias"></canvas>
    </div>
</div>

{{-- Gráfico 3: Receitas x Despesas por pessoa --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-2">Receitas x Despesas por pessoa</h2>
    <div style="position:relative; height:260px">
        <canvas id="chartPessoas"></canvas>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labelsMeses      = @json($labelsMeses ?? []);
    const serieReceitasMes = @json($serieReceitasMes ?? []);
    const serieDespesasMes = @json($serieDespesasMes ?? []);

    const labelsCategorias = @json($labelsCategorias ?? []);
    const serieDespesasCat = @json($serieDespesasCategorias ?? []);

    const labelsPessoas    = @json($labelsPessoas ?? []);
    const serieReceitasPes = @json($serieReceitasPessoas ?? []);
    const serieDespesasPes = @json($serieDespesasPessoas ?? []);

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

    if (labelsCategorias.length) {
        new Chart(document.getElementById('chartCategorias'), {
            type: 'doughnut',
            data: {
                labels: labelsCategorias,
                datasets: [{ data: serieDespesasCat, backgroundColor: ['#0ea5e9','#6366f1','#f97316','#22c55e','#e11d48','#a855f7','#facc15','#4b5563','#22d3ee','#a3e635'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    if (labelsPessoas.length) {
        new Chart(document.getElementById('chartPessoas'), {
            type: 'bar',
            data: {
                labels: labelsPessoas,
                datasets: [
                    { label: 'Receitas', data: serieReceitasPes, backgroundColor: '#16a34a' },
                    { label: 'Despesas', data: serieDespesasPes, backgroundColor: '#dc2626' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }
</script>
@endpush
