@extends('layouts.app')

@section('title', 'Relatório por Período')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">📊 Relatório por Período</h1>
            <p class="mt-1 text-gray-600">
                {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
                <span class="text-xs text-gray-400 ml-2">({{ $totais['num_notas'] }} notas)</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('relatorio.mensal') }}" class="btn-outline-secondary text-sm">📅 Mensal</a>
            <a href="{{ route('dashboard') }}" class="btn-back">← Dashboard</a>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
            <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="form-control">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
            <input type="date" name="data_fim" value="{{ $dataFim }}" class="form-control">
        </div>
        <button type="submit" class="btn-primary">🔍 Filtrar</button>
        <div class="flex gap-1">
            <a href="{{ route('relatorio.periodo') }}" class="btn-outline-secondary text-xs px-2 py-1">Hoje</a>
            <a href="{{ route('relatorio.periodo', ['data_inicio' => now()->subDays(7)->format('Y-m-d'), 'data_fim' => now()->format('Y-m-d')]) }}" class="btn-outline-secondary text-xs px-2 py-1">7 dias</a>
            <a href="{{ route('relatorio.periodo', ['data_inicio' => now()->subDays(30)->format('Y-m-d'), 'data_fim' => now()->format('Y-m-d')]) }}" class="btn-outline-secondary text-xs px-2 py-1">30 dias</a>
            <a href="{{ route('relatorio.periodo', ['data_inicio' => now()->startOfMonth()->format('Y-m-d'), 'data_fim' => now()->format('Y-m-d')]) }}" class="btn-outline-secondary text-xs px-2 py-1">Este mês</a>
        </div>
    </form>
</div>

<!-- Cards de Resumo -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-blue-100 text-sm">Gasto Total</p>
        <p class="text-2xl font-bold mt-1">R$ {{ number_format($totais['gasto_total'], 2, ',', '.') }}</p>
    </div>
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-green-100 text-sm">Quantidade Total</p>
        <p class="text-2xl font-bold mt-1">{{ number_format($totais['itens_total'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-purple-100 text-sm">Produtos (agrupados)</p>
        <p class="text-2xl font-bold mt-1">{{ $totais['produtos_diferentes'] }}</p>
    </div>
</div>

@if($produtos->count() > 0)
<!-- Gráficos -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">🍩 Gastos por Categoria</h3>
        <div class="relative" style="height: 350px;">
            <canvas id="categoriaPieChart"></canvas>
        </div>
        <div class="mt-4 text-xs text-gray-500 text-center">
            Total: R$ {{ number_format($gastosPorCategoria->sum('gasto_total'), 2, ',', '.') }}
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Gastos por Categoria</h3>
        <div class="relative" style="height: 350px;">
            <canvas id="categoriaBarChart"></canvas>
        </div>
    </div>
</div>

<!-- Tabela de Categorias -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold text-gray-800">📂 Detalhamento por Categoria</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Compras</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gasto Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">% do Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($gastosPorCategoria as $cat)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $cat->categoria_cor }}"></span>
                            <span class="text-sm text-gray-800">{{ $cat->categoria_emoji }} {{ $cat->categoria_nome }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-center">{{ $cat->num_compras }}x</td>
                    <td class="px-4 py-3 text-sm text-right font-semibold">R$ {{ number_format($cat->gasto_total, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right">
                        <div class="flex items-center justify-end gap-2">
                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full" style="width: {{ $cat->porcentagem }}%; background-color: {{ $cat->categoria_cor }}"></div>
                            </div>
                            <span class="text-xs text-gray-500">{{ number_format($cat->porcentagem, 1, ',', '.') }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                <tr>
                    <td class="px-4 py-3 text-sm font-bold">TOTAL</td>
                    <td class="px-4 py-3 text-sm text-center font-bold">{{ $gastosPorCategoria->sum('num_compras') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold">R$ {{ number_format($gastosPorCategoria->sum('gasto_total'), 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Tabela de Produtos -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold text-gray-800">
            🛒 Produtos - {{ \Carbon\Carbon::parse($dataInicio)->format('d/m') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
        </h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qtde</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Compras</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Preço Médio</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Menor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Maior</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gasto Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($produtos as $produto)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('products.show', $produto->produto_id) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                            {{ $produto->produto_nome }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-center">
                        {{ strtoupper($produto->unidade) == 'KG' ? number_format($produto->quantidade_total, 3, ',', '.') . ' KG' : number_format($produto->quantidade_total, 0, ',', '.') . ' ' . $produto->unidade }}
                    </td>
                    <td class="px-4 py-3 text-sm text-center">{{ $produto->num_compras }}x</td>
                    <td class="px-4 py-3 text-sm text-right">
                        <span class="font-semibold text-gray-700">R$ {{ number_format($produto->preco_medio, 2, ',', '.') }}</span>
                        @if($produto->unidade)
                        <span class="text-xs text-gray-400 block">/{{ $produto->unidade }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-green-600">R$ {{ number_format($produto->preco_minimo, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right text-red-600">R$ {{ number_format($produto->preco_maximo, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold">R$ {{ number_format($produto->gasto_total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                <tr>
                    <td class="px-4 py-3 text-sm font-bold">TOTAIS</td>
                    <td class="px-4 py-3 text-sm text-center font-bold">{{ number_format($totais['itens_total'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-center font-bold">{{ $produtos->sum('num_compras') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold">-</td>
                    <td class="px-4 py-3 text-sm text-right font-bold">-</td>
                    <td class="px-4 py-3 text-sm text-right font-bold">-</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-lg">R$ {{ number_format($totais['gasto_total'], 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Rankings -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">🏆 Mais Comprados</h3>
        <div class="space-y-3">
            @foreach($produtos->sortByDesc('quantidade_total')->take(5) as $index => $produto)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xl font-bold {{ $index == 0 ? 'text-yellow-500' : ($index == 1 ? 'text-gray-400' : ($index == 2 ? 'text-orange-600' : 'text-gray-300')) }}">#{{ $index + 1 }}</span>
                    <div>
                        <p class="text-sm font-medium">{{ Str::limit($produto->produto_nome, 25) }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($produto->quantidade_total, 0, ',', '.') }} {{ $produto->unidade }} · R$ {{ number_format($produto->preco_medio, 2, ',', '.') }}/{{ $produto->unidade }}</p>
                    </div>
                </div>
                <span class="text-sm font-semibold">R$ {{ number_format($produto->gasto_total, 2, ',', '.') }}</span>
            </div>
            @endforeach
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">💰 Maiores Gastos</h3>
        <div class="space-y-3">
            @foreach($produtos->sortByDesc('gasto_total')->take(5) as $index => $produto)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xl font-bold {{ $index == 0 ? 'text-yellow-500' : ($index == 1 ? 'text-gray-400' : ($index == 2 ? 'text-orange-600' : 'text-gray-300')) }}">#{{ $index + 1 }}</span>
                    <div>
                        <p class="text-sm font-medium">{{ Str::limit($produto->produto_nome, 25) }}</p>
                        <p class="text-xs text-gray-500">{{ $produto->num_compras }} compras</p>
                    </div>
                </div>
                <span class="text-sm font-bold">R$ {{ number_format($produto->gasto_total, 2, ',', '.') }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-6 text-center">
    <button onclick="window.print()" class="btn-secondary">🖨️ Imprimir Relatório</button>
</div>
@else
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
    <span class="text-6xl">📊</span>
    <p class="text-gray-500 mt-4 text-lg">Nenhum dado encontrado neste período.</p>
</div>
@endif
@endsection

@push('scripts')
@if($produtos->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorias = @json($gastosPorCategoria);
    const labels = categorias.map(c => c.categoria_emoji + ' ' + c.categoria_nome);
    const data = categorias.map(c => parseFloat(c.gasto_total));
    const colors = categorias.map(c => c.categoria_cor || '#6b7280');
    
    const ctxPie = document.getElementById('categoriaPieChart');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.parsed;
                                const percent = ((value / total) * 100).toFixed(1);
                                return ' R$ ' + value.toFixed(2).replace('.', ',') + ' (' + percent + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    const ctxBar = document.getElementById('categoriaBarChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Gasto Total (R$)',
                    data: data,
                    backgroundColor: colors,
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { callback: v => 'R$ ' + v.toFixed(0) }
                    }
                }
            }
        });
    }
});
</script>
@endif
@endpush
