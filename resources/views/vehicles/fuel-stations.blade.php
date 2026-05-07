@extends('layouts.app')

@section('title', 'Comparativo de Postos')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">⛽ Comparativo de Postos</h1>
        <p class="text-sm text-gray-500 mt-0.5">Ranking de preços, evolução e melhor custo-benefício por tipo de combustível.</p>
    </div>
    <a href="{{ route('vehicles.index') }}" class="btn-back self-start sm:self-auto">← Veículos</a>
</div>

{{-- FILTROS --}}
<form method="GET" action="{{ route('vehicles.report.fuel-stations') }}" class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de combustível</label>
            <select name="tipo" class="form-control text-sm w-auto">
                <option value="todos" {{ $tipoComb === 'todos' ? 'selected' : '' }}>Todos</option>
                @foreach($tiposDisp as $t)
                    <option value="{{ $t }}" {{ $tipoComb === $t ? 'selected' : '' }}>
                        {{ $tiposLabels[$t] ?? ucfirst($t) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Período</label>
            <select name="periodo" class="form-control text-sm w-auto">
                @foreach(['3m'=>'Últimos 3 meses','6m'=>'Últimos 6 meses','12m'=>'Últimos 12 meses','todos'=>'Todo o histórico'] as $v => $l)
                    <option value="{{ $v }}" {{ $periodo === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary text-sm px-4 py-2">Filtrar</button>
    </div>
</form>

@if($ranking->isEmpty())
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-400">
        <p class="text-4xl mb-3">⛽</p>
        <p class="text-base font-medium">Nenhum abastecimento com posto e litros registrados.</p>
        <p class="text-sm mt-1">Preencha o campo "Posto" e "Litros" ao registrar abastecimentos para ver esta análise.</p>
    </div>
@else

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Postos visitados</p>
        <p class="text-xl font-bold text-gray-900">{{ $totalPostos }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Abastecimentos</p>
        <p class="text-xl font-bold text-gray-900">{{ $totalEntradas }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-2 border-green-500">
        <p class="text-xs text-gray-500 uppercase tracking-wide">🏆 Menor preço médio</p>
        <p class="text-xl font-bold text-green-700">R$ {{ number_format($ranking->min('preco_medio'), 3, ',', '.') }}</p>
        <p class="text-xs text-gray-400 truncate">{{ $ranking->where('is_melhor', true)->first()?->posto ?? '-' }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-2 border-red-400">
        <p class="text-xs text-gray-500 uppercase tracking-wide">📈 Maior preço médio</p>
        <p class="text-xl font-bold text-red-600">R$ {{ number_format($ranking->max('preco_medio'), 3, ',', '.') }}</p>
        <p class="text-xs text-gray-400 truncate">{{ $ranking->sortByDesc('preco_medio')->first()?->posto ?? '-' }}</p>
    </div>
</div>

{{-- MELHOR POR TIPO --}}
@if($melhorPorTipo->isNotEmpty())
<div class="mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">🏆 Melhor posto por tipo de combustível</h2>
    <div class="flex flex-wrap gap-3">
        @foreach($melhorPorTipo as $tipo => $entry)
        <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 min-w-[180px]">
            <p class="text-xs text-green-700 font-semibold uppercase tracking-wide">{{ $tiposLabels[$tipo] ?? ucfirst($tipo) }}</p>
            <p class="text-sm font-bold text-gray-900 mt-0.5 truncate">{{ $entry->posto }}</p>
            <p class="text-lg font-bold text-green-700">R$ {{ number_format($entry->preco_medio, 3, ',', '.') }}<span class="text-xs font-normal text-gray-500">/L</span></p>
            <p class="text-xs text-gray-400">{{ $entry->visitas }} {{ $entry->visitas == 1 ? 'visita' : 'visitas' }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- EVOLUÇÃO DO PREÇO --}}
@if(count($labelsEvolucao) >= 2)
<div class="bg-white rounded-lg shadow p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">📈 Evolução do preço por litro (média mensal)</h2>
    <div style="position:relative; height:260px">
        <canvas id="chartEvolucao"></canvas>
    </div>
</div>
@endif

{{-- RANKING DE POSTOS --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h2 class="text-base font-semibold text-gray-800">Ranking de postos por preço médio/L</h2>
        <span class="text-xs text-gray-400">{{ $ranking->count() }} {{ $ranking->count() == 1 ? 'posto' : 'postos' }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-10">#</th>
                    <th class="px-4 py-3 text-left   text-xs font-medium text-gray-500 uppercase">Posto</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-green-700 uppercase">Preço médio/L</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Preço mín/L</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Vs. melhor</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Litros</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Gasto total</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Visitas</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Última visita</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($ranking as $i => $row)
                <tr class="hover:bg-gray-50 {{ $row->is_melhor ? 'bg-green-50' : '' }}">
                    <td class="px-4 py-3 text-center">
                        @if($i === 0)
                            <span class="text-base">🥇</span>
                        @elseif($i === 1)
                            <span class="text-base">🥈</span>
                        @elseif($i === 2)
                            <span class="text-base">🥉</span>
                        @else
                            <span class="text-xs text-gray-400">{{ $i + 1 }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm font-medium text-gray-900">{{ $row->posto }}</span>
                        @if($row->is_melhor)
                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Mais barato</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        <span class="text-sm font-bold {{ $row->is_melhor ? 'text-green-700' : 'text-gray-900' }}">
                            R$ {{ number_format($row->preco_medio, 3, ',', '.') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 tabular-nums">
                        R$ {{ number_format($row->preco_minimo, 3, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        @if($row->economia_pct > 0)
                            <span class="text-xs font-medium text-red-600">+{{ number_format($row->economia_pct, 1, ',', '.') }}%</span>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 tabular-nums">
                        {{ number_format($row->total_litros, 1, ',', '.') }} L
                    </td>
                    <td class="px-4 py-3 text-right text-sm text-gray-700 tabular-nums">
                        R$ {{ number_format($row->total_valor, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                        {{ $row->visitas }}
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-gray-500">
                        {{ $row->ultima_visita }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif

@if(count($labelsEvolucao) >= 2)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const datasets = @json($datasetsEvolucao);
    // Chart.js não aceita null em pontos com spanGaps; converte undefined
    datasets.forEach(d => { d.data = d.data.map(v => v === null ? NaN : v); });

    new Chart(document.getElementById('chartEvolucao').getContext('2d'), {
        type: 'line',
        data: {
            labels: @json($labelsEvolucao),
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: R$ ${ctx.raw.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })}/L`,
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'R$/L', font: { size: 11 } },
                    ticks: {
                        font: { size: 11 },
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 3 })
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { ticks: { font: { size: 11 } } }
            }
        }
    });
});
</script>
@endif

@endsection
