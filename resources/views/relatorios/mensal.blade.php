@extends('layouts.app')

@section('title', 'Relatório Mensal')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">📊 Relatório Mensal</h1>
            <p class="mt-1 text-gray-600">Produtos comprados em {{ $meses[$mes] }} de {{ $ano }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn-back">← Dashboard</a>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
            <select name="mes" class="form-control">
                @foreach($meses as $num => $nome)
                    <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
            <select name="ano" class="form-control">
                @foreach($anos as $anoItem)
                    <option value="{{ $anoItem }}" {{ $ano == $anoItem ? 'selected' : '' }}>{{ $anoItem }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">🔍 Filtrar</button>
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

<!-- ✅ NOVO: Gráfico de Gastos por Categoria -->
@if($gastosPorCategoria->count() > 0)
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Gráfico de Pizza -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">🍩 Gastos por Categoria</h3>
        <div class="relative" style="height: 350px;">
            <canvas id="categoriaPieChart"></canvas>
        </div>
        <div class="mt-4 text-xs text-gray-500 text-center">
            Total: R$ {{ number_format($gastosPorCategoria->sum('gasto_total'), 2, ',', '.') }}
        </div>
    </div>
    
    <!-- Gráfico de Barras -->
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
@endif

<!-- Tabela de Produtos -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold text-gray-800">🛒 Produtos - {{ $meses[$mes] }}/{{ $ano }}</h2>
    </div>
    
    @if($produtos->count() > 0)
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
                    <td class="px-4 py-3 text-sm text-right">R$ {{ number_format($produto->preco_medio, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right text-green-600">R$ {{ number_format($produto->preco_minimo, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right text-red-600">R$ {{ number_format($produto->preco_maximo, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold">R$ {{ number_format($produto->gasto_total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-12 text-center text-gray-500">Nenhum produto encontrado.</div>
    @endif
</div>

<!-- Rankings e Botão Imprimir -->
@if($produtos->count() > 0)
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
@endif
@endsection

@push('scripts')
@if(isset($gastosPorCategoria) && $gastosPorCategoria->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorias = @json($gastosPorCategoria);
    
    // Cores e dados
    const labels = categorias.map(c => c.categoria_emoji + ' ' + c.categoria_nome);
    const data = categorias.map(c => parseFloat(c.gasto_total));
    const colors = categorias.map(c => c.categoria_cor || '#6b7280');
    
    // Gráfico de Pizza (Donut)
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
                    hoverBorderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    },
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
    
    // Gráfico de Barras Horizontal
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
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.x.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endif
@endpush
