@extends('layouts.app')

@section('title', 'Relatório Mensal')

@section('content')

{{--
    Rankings pré-calculados aqui para manter lógica fora dos loops de template.
    O ideal seria o controller passar $maisCoprados e $maioresGastos diretamente.
--}}
@php
    $maisComprados = $produtos->sortByDesc('quantidade_total')->values()->take(5);
    $maioresGastos = $produtos->sortByDesc('gasto_total')->values()->take(5);
    $rankColors    = ['text-yellow-500', 'text-gray-400', 'text-orange-600', 'text-gray-300', 'text-gray-300'];
@endphp

<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">📊 Relatório Mensal</h1>
            <p class="mt-1 text-gray-600">Produtos comprados em {{ $meses[$mes] }} de {{ $ano }}</p>
        </div>
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">← Dashboard</a>
    </div>
</div>

{{-- Filtros --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
            <select name="mes" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                @foreach($meses as $num => $nome)
                    <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
            <select name="ano" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                @foreach($anos as $anoItem)
                    <option value="{{ $anoItem }}" {{ $ano == $anoItem ? 'selected' : '' }}>{{ $anoItem }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">🔍 Filtrar</button>
    </form>
</div>

{{-- Cards de Resumo --}}
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

{{-- Gráficos por Categoria --}}
@if($gastosPorCategoria->count() > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">🍩 Distribuição por Categoria</h3>
            <div class="relative h-[350px]">
                <canvas id="categoriaPieChart"></canvas>
            </div>
            <p class="mt-3 text-xs text-gray-500 text-center">
                Total: R$ {{ number_format($gastosPorCategoria->sum('gasto_total'), 2, ',', '.') }}
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Valor Gasto por Categoria</h3>
            <div class="relative h-[350px]">
                <canvas id="categoriaBarChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Tabela de Categorias --}}
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
                                    {{--
                                        Cor dinâmica via CSS custom property no elemento.
                                        Evita style= direto; Tailwind não purga valores dinâmicos.
                                    --}}
                                    <span class="w-3 h-3 rounded-full flex-shrink-0 categoria-dot"
                                          data-cor="{{ $cat->categoria_cor }}"></span>
                                    <span class="text-sm text-gray-800">{{ $cat->categoria_emoji }} {{ $cat->categoria_nome }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">{{ $cat->num_compras }}x</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold">
                                R$ {{ number_format($cat->gasto_total, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="categoria-bar h-2 rounded-full"
                                             data-cor="{{ $cat->categoria_cor }}"
                                             data-width="{{ $cat->porcentagem }}"></div>
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

{{-- Tabela de Produtos --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-semibold text-gray-800">🛒 Produtos — {{ $meses[$mes] }}/{{ $ano }}</h2>
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
                                <a href="{{ route('products.show', $produto->produto_id) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                    {{ $produto->produto_nome }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                @if(strtoupper($produto->unidade) === 'KG')
                                    {{ number_format($produto->quantidade_total, 3, ',', '.') }} KG
                                @else
                                    {{ number_format($produto->quantidade_total, 0, ',', '.') }} {{ $produto->unidade }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-center">{{ $produto->num_compras }}x</td>
                            <td class="px-4 py-3 text-sm text-right">R$ {{ number_format($produto->preco_medio, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-green-600">R$ {{ number_format($produto->preco_minimo, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right text-red-500">R$ {{ number_format($produto->preco_maximo, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold">R$ {{ number_format($produto->gasto_total, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-12 text-center">
            <span class="text-5xl" aria-hidden="true">📭</span>
            <p class="text-gray-500 mt-4">Nenhum produto encontrado para este período.</p>
        </div>
    @endif
</div>

{{-- Rankings --}}
@if($produtos->count() > 0)
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">🏆 Mais Comprados (Qtde)</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($maisComprados as $i => $produto)
            <div class="px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xl {{ $rankColors[$i] ?? 'text-gray-300' }}" aria-hidden="true">{{ ['🥇','🥈','🥉','4️⃣','5️⃣'][$i] ?? ($i+1).'.' }}</span>
                    <span class="text-sm">{{ $produto->produto_nome }}</span>
                </div>
                <span class="text-sm font-semibold text-gray-700">
                    @if(strtoupper($produto->unidade) === 'KG')
                        {{ number_format($produto->quantidade_total, 3, ',', '.') }} KG
                    @else
                        {{ number_format($produto->quantidade_total, 0, ',', '.') }} {{ $produto->unidade }}
                    @endif
                </span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">💸 Maiores Gastos</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($maioresGastos as $i => $produto)
            <div class="px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xl {{ $rankColors[$i] ?? 'text-gray-300' }}" aria-hidden="true">{{ ['🥇','🥈','🥉','4️⃣','5️⃣'][$i] ?? ($i+1).'.' }}</span>
                    <span class="text-sm">{{ $produto->produto_nome }}</span>
                </div>
                <span class="text-sm font-semibold text-gray-700">R$ {{ number_format($produto->gasto_total, 2, ',', '.') }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="mt-6 flex justify-end">
    <button onclick="window.print()"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold rounded-md transition">🖨️ Imprimir Relatório</button>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Cores dinâmicas via data-attribute ──────────────────── */
    document.querySelectorAll('.categoria-dot').forEach(function (el) {
        el.style.backgroundColor = el.dataset.cor || '#6b7280';
    });
    document.querySelectorAll('.categoria-bar').forEach(function (el) {
        el.style.backgroundColor = el.dataset.cor || '#6b7280';
        el.style.width = (el.dataset.width || 0) + '%';
    });

    /* ── Dados para os gráficos ──────────────────────────────── */
    const categorias = @json($gastosPorCategoria->map(fn($c) => [
        'nome'  => $c->categoria_emoji . ' ' . $c->categoria_nome,
        'valor' => round($c->gasto_total, 2),
        'cor'   => $c->categoria_cor,
    ])->values());

    if (!categorias.length) return;

    const labels = categorias.map(c => c.nome);
    const dados  = categorias.map(c => c.valor);
    const cores  = categorias.map(c => c.cor);

    /* ── Pie Chart ───────────────────────────────────────────── */
    new Chart(document.getElementById('categoriaPieChart'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data: dados, backgroundColor: cores, borderWidth: 2 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return ` R$ ${ctx.parsed.toFixed(2).replace('.', ',')} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });

    /* ── Bar Chart ───────────────────────────────────────────── */
    new Chart(document.getElementById('categoriaBarChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Gasto (R$)',
                data: dados,
                backgroundColor: cores,
                borderWidth: 0,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    ticks: {
                        callback: v => 'R$ ' + v.toFixed(2).replace('.', ',')
                    }
                }
            }
        }
    });
});
</script>
@endpush
