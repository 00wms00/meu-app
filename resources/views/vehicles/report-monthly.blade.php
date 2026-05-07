@extends('layouts.app')

@section('title', 'Resumo Mensal — Veículos')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">📊 Resumo mensal por veículo</h1>
        <p class="text-sm text-gray-500 mt-0.5">Combustível, manutenção e outras despesas por período.</p>
    </div>
    <a href="{{ route('vehicles.index') }}" class="btn-back self-start sm:self-auto">← Veículos</a>
</div>

{{-- Seletor de mês --}}
<form method="GET" action="{{ route('vehicles.report.monthly') }}" class="mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <label class="text-sm font-medium text-gray-700">Período:</label>
        <select name="mes" class="form-control w-auto text-sm">
            @foreach($mesesDisp as $item)
                <option value="{{ $item['mes'] }}"
                        @selected($item['mes'] == $mes && $item['ano'] == $ano)
                        data-ano="{{ $item['ano'] }}">
                    {{ $item['label'] }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="ano" id="ano-hidden" value="{{ $ano }}">
        <button type="submit" class="btn-primary text-sm px-4 py-2">Ver</button>
    </div>
</form>
<script>
    document.querySelector('[name="mes"]').addEventListener('change', function () {
        document.getElementById('ano-hidden').value = this.options[this.selectedIndex].dataset.ano;
    });
</script>

@if(empty($resumo))
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-400">
        <p class="text-4xl mb-3">🚗</p>
        <p class="text-base font-medium">Nenhum dado registrado neste período.</p>
        <p class="text-sm mt-1">Registre abastecimentos ou despesas nos seus veículos para ver o resumo aqui.</p>
    </div>
@else

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total geral</p>
        <p class="text-xl font-bold text-gray-900">R$ {{ number_format($totaisGerais['total'], 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">⛽ Combustível</p>
        <p class="text-xl font-bold text-blue-700">R$ {{ number_format($totaisGerais['combustivel'], 2, ',', '.') }}</p>
        @if($totaisGerais['total'] > 0)
            <p class="text-xs text-gray-400 mt-1">{{ number_format($totaisGerais['combustivel'] / $totaisGerais['total'] * 100, 1) }}% do total</p>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">🔧 Manutenção</p>
        <p class="text-xl font-bold text-orange-600">R$ {{ number_format($totaisGerais['manutencao'], 2, ',', '.') }}</p>
        @if($totaisGerais['total'] > 0)
            <p class="text-xs text-gray-400 mt-1">{{ number_format($totaisGerais['manutencao'] / $totaisGerais['total'] * 100, 1) }}% do total</p>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">📋 Outros</p>
        <p class="text-xl font-bold text-purple-600">R$ {{ number_format($totaisGerais['outros'], 2, ',', '.') }}</p>
        @if($totaisGerais['total'] > 0)
            <p class="text-xs text-gray-400 mt-1">{{ number_format($totaisGerais['outros'] / $totaisGerais['total'] * 100, 1) }}% do total</p>
        @endif
    </div>
</div>

{{-- Gráfico --}}
@if(count($chartLabels) > 0)
<div class="bg-white rounded-lg shadow p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-3">Distribuição por veículo</h2>
    <div style="position:relative; height:220px">
        <canvas id="chartResumo"></canvas>
    </div>
</div>
@endif

{{-- Tabela --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h2 class="text-base font-semibold text-gray-800">Detalhamento por veículo</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left   text-xs font-medium text-gray-500 uppercase">Veículo</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-blue-600 uppercase">⛽ Combustível</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Litros</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">Preço/L</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-orange-600 uppercase">🔧 Manutenção</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-purple-600 uppercase">📋 Outros</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-gray-900 uppercase">Total</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Abast.</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ver</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($resumo as $row)
                @php
                    $pct_comb  = $row['total'] > 0 ? $row['combustivel'] / $row['total'] * 100 : 0;
                    $pct_manut = $row['total'] > 0 ? $row['manutencao']  / $row['total'] * 100 : 0;
                    $pct_out   = $row['total'] > 0 ? $row['outros']      / $row['total'] * 100 : 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('vehicles.show', $row['vehicle']) }}"
                           class="font-medium text-gray-900 hover:text-blue-700">
                            {{ $row['vehicle']->apelido }}
                        </a>
                        @if($row['vehicle']->modelo)
                            <p class="text-xs text-gray-400">
                                {{ trim($row['vehicle']->marca . ' ' . $row['vehicle']->modelo) }}
                                @if($row['vehicle']->placa) · {{ $row['vehicle']->placa }} @endif
                            </p>
                        @endif
                        {{-- mini barra proporcional --}}
                        <div class="mt-1.5 flex h-1.5 rounded-full overflow-hidden w-32">
                            <div class="bg-blue-500"   style="width:{{ $pct_comb }}%"></div>
                            <div class="bg-orange-400" style="width:{{ $pct_manut }}%"></div>
                            <div class="bg-purple-400" style="width:{{ $pct_out }}%"></div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        @if($row['combustivel'] > 0)
                            <span class="text-sm font-medium text-blue-700">R$ {{ number_format($row['combustivel'], 2, ',', '.') }}</span>
                        @else <span class="text-sm text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 tabular-nums">
                        {{ $row['litros'] ? number_format($row['litros'], 2, ',', '.') . ' L' : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 tabular-nums">
                        {{ $row['media_preco_litro'] ? 'R$ ' . number_format($row['media_preco_litro'], 3, ',', '.') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        @if($row['manutencao'] > 0)
                            <span class="text-sm font-medium text-orange-600">R$ {{ number_format($row['manutencao'], 2, ',', '.') }}</span>
                        @else <span class="text-sm text-gray-300">—</span>
                        @endif
                    </td>
                    {{-- Outros com tooltip Alpine --}}
                    <td class="px-4 py-3 text-right tabular-nums" x-data="{ open: false }">
                        @if($row['outros'] > 0)
                            <div class="relative inline-block">
                                <button type="button"
                                        @mouseenter="open=true" @mouseleave="open=false"
                                        class="text-sm font-medium text-purple-600 underline decoration-dotted cursor-help">
                                    R$ {{ number_format($row['outros'], 2, ',', '.') }}
                                </button>
                                <div x-show="open" x-cloak
                                     class="absolute right-0 bottom-full mb-1 w-48 bg-gray-900 text-white text-xs rounded shadow-lg p-2 z-10 leading-6">
                                    @foreach(['seguro'=>'Seguro','impostos'=>'Impostos/IPVA','pedagio'=>'Pedágio/Estac.','outros'=>'Outros'] as $tk => $tl)
                                        @if($row['por_tipo']->get($tk))
                                            <div class="flex justify-between">
                                                <span>{{ $tl }}</span>
                                                <span>R$ {{ number_format($row['por_tipo']->get($tk), 2, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else <span class="text-sm text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        <span class="text-sm font-bold text-gray-900">R$ {{ number_format($row['total'], 2, ',', '.') }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                        {{ $row['abastecimentos'] ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('vehicles.show', $row['vehicle']) }}"
                           class="text-xs text-blue-500 hover:text-blue-700">Ver →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-100 border-t-2 border-gray-300">
                <tr>
                    <td class="px-4 py-3 text-sm font-bold text-gray-700">Total do período</td>
                    <td class="px-4 py-3 text-right text-sm font-bold text-blue-700 tabular-nums">
                        R$ {{ number_format($totaisGerais['combustivel'], 2, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                    <td class="px-4 py-3 text-right text-sm font-bold text-orange-600 tabular-nums">
                        R$ {{ number_format($totaisGerais['manutencao'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-bold text-purple-600 tabular-nums">
                        R$ {{ number_format($totaisGerais['outros'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900 tabular-nums">
                        R$ {{ number_format($totaisGerais['total'], 2, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endif

@if(count($chartLabels) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('chartResumo').getContext('2d'), {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                { label: '⛽ Combustível', data: @json($chartCombust), backgroundColor: 'rgba(37,99,235,0.75)',  borderRadius: 4, borderSkipped: false },
                { label: '🔧 Manutenção',  data: @json($chartManut),   backgroundColor: 'rgba(234,88,12,0.75)',  borderRadius: 4, borderSkipped: false },
                { label: '📋 Outros',      data: @json($chartOutros),  backgroundColor: 'rgba(147,51,234,0.65)', borderRadius: 4, borderSkipped: false },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: R$ ${ctx.raw.toLocaleString('pt-BR', {minimumFractionDigits:2})}`,
                        footer: items => `Total: R$ ${items.reduce((s,i)=>s+i.raw,0).toLocaleString('pt-BR',{minimumFractionDigits:2})}`
                    }
                }
            },
            scales: {
                x: { stacked: true, ticks: { font: { size: 11 } } },
                y: {
                    stacked: true,
                    ticks: { font: { size: 11 }, callback: v => 'R$ ' + v.toLocaleString('pt-BR') },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                }
            }
        }
    });
});
</script>
@endif

@endsection
