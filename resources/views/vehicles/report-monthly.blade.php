@extends('layouts.app')

@section('title', 'Resumo por veículo')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">📊 Resumo por veículo</h1>
        <p class="text-sm text-gray-500 mt-0.5">Combustível, manutenção e outras despesas — <span class="font-medium text-gray-700">{{ $labelPeriodo }}</span></p>
    </div>
    <a href="{{ route('vehicles.index') }}" class="btn-back self-start sm:self-auto">← Veículos</a>
</div>

{{-- ===== FILTRO DE PERÍODO ===== --}}
<div x-data="{
    modo: '{{ $modo }}',
    ano:  '{{ $modo === 'mes' ? $dataInicio->year : now()->year }}',
    mes:  '{{ $modo === 'mes' ? $dataInicio->month : now()->month }}',
    dia:  '{{ $modo === 'dia' ? $dataInicio->toDateString() : now()->toDateString() }}',
    ini:  '{{ $modo === 'livre' ? $dataInicio->toDateString() : now()->startOfMonth()->toDateString() }}',
    fim:  '{{ $modo === 'livre' ? $dataFim->toDateString() : now()->toDateString() }}',
}" class="bg-white rounded-lg shadow p-4 mb-6">

    <div class="flex gap-2 mb-4 border-b border-gray-200 pb-3">
        <button type="button"
            @click="modo = 'mes'"
            :class="modo === 'mes' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            class="px-3 py-1.5 rounded text-sm font-medium transition">
            📅 Este mês
        </button>
        <button type="button"
            @click="modo = 'dia'"
            :class="modo === 'dia' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            class="px-3 py-1.5 rounded text-sm font-medium transition">
            📆 Dia
        </button>
        <button type="button"
            @click="modo = 'livre'"
            :class="modo === 'livre' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            class="px-3 py-1.5 rounded text-sm font-medium transition">
            🗓️ Intervalo livre
        </button>
    </div>

    <form method="GET" action="{{ route('vehicles.report.monthly') }}">
        <input type="hidden" name="modo" :value="modo">

        <div x-show="modo === 'mes'" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mês</label>
                <select name="mes" x-model="mes" class="form-control text-sm w-auto">
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::create(2000,$m,1)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Ano</label>
                <select name="ano" x-model="ano" class="form-control text-sm w-auto">
                    @foreach(range(now()->year, now()->year - 5) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-1 items-end">
                @foreach($mesesDisp->take(6) as $item)
                    <a href="{{ route('vehicles.report.monthly', ['modo'=>'mes','ano'=>$item['ano'],'mes'=>$item['mes']]) }}"
                       class="px-2 py-1 text-xs rounded border {{ $modo === 'mes' && $dataInicio->month == $item['mes'] && $dataInicio->year == $item['ano'] ? 'bg-blue-50 border-blue-400 text-blue-700 font-semibold' : 'border-gray-200 text-gray-500 hover:border-blue-300 hover:text-blue-600' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
            <button type="submit" class="btn-primary text-sm px-4 py-2">Ver</button>
        </div>

        <div x-show="modo === 'dia'" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Data</label>
                <input type="date" name="dia" x-model="dia" class="form-control text-sm">
            </div>
            <div class="flex gap-2">
                <button type="button"
                    @click="dia = '{{ now()->toDateString() }}'"
                    class="px-3 py-1.5 text-xs rounded border border-gray-200 text-gray-600 hover:border-blue-300 hover:text-blue-600">Hoje</button>
                <button type="button"
                    @click="dia = '{{ now()->subDay()->toDateString() }}'"
                    class="px-3 py-1.5 text-xs rounded border border-gray-200 text-gray-600 hover:border-blue-300 hover:text-blue-600">Ontem</button>
            </div>
            <button type="submit" class="btn-primary text-sm px-4 py-2">Ver</button>
        </div>

        <div x-show="modo === 'livre'" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">De</label>
                <input type="date" name="data_inicio" x-model="ini" class="form-control text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Até</label>
                <input type="date" name="data_fim" x-model="fim" class="form-control text-sm">
            </div>
            <div class="flex flex-wrap gap-1 items-end">
                <button type="button"
                    @click="ini = '{{ now()->startOfMonth()->toDateString() }}'; fim = '{{ now()->toDateString() }}'"
                    class="px-2 py-1 text-xs rounded border border-gray-200 text-gray-500 hover:border-blue-300 hover:text-blue-600">Mês atual</button>
                <button type="button"
                    @click="ini = '{{ now()->subMonth()->startOfMonth()->toDateString() }}'; fim = '{{ now()->subMonth()->endOfMonth()->toDateString() }}'"
                    class="px-2 py-1 text-xs rounded border border-gray-200 text-gray-500 hover:border-blue-300 hover:text-blue-600">Mês passado</button>
                <button type="button"
                    @click="ini = '{{ now()->startOfYear()->toDateString() }}'; fim = '{{ now()->toDateString() }}'"
                    class="px-2 py-1 text-xs rounded border border-gray-200 text-gray-500 hover:border-blue-300 hover:text-blue-600">Este ano</button>
                <button type="button"
                    @click="ini = '{{ now()->subDays(29)->toDateString() }}'; fim = '{{ now()->toDateString() }}'"
                    class="px-2 py-1 text-xs rounded border border-gray-200 text-gray-500 hover:border-blue-300 hover:text-blue-600">Últimos 30d</button>
                <button type="button"
                    @click="ini = '{{ now()->subDays(89)->toDateString() }}'; fim = '{{ now()->toDateString() }}'"
                    class="px-2 py-1 text-xs rounded border border-gray-200 text-gray-500 hover:border-blue-300 hover:text-blue-600">Últimos 90d</button>
            </div>
            <button type="submit" class="btn-primary text-sm px-4 py-2">Ver</button>
        </div>
    </form>
</div>

@if(empty($resumo))
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-400">
        <p class="text-4xl mb-3">🚗</p>
        <p class="text-base font-medium">Nenhum dado registrado neste período.</p>
        <p class="text-sm mt-1">Tente outro intervalo ou registre abastecimentos e despesas.</p>
    </div>
@else

{{-- KPIs — sempre 5 colunas em 1 linha --}}
<div class="grid grid-cols-5 gap-4 mb-6">
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
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">🛣️ KM rodados</p>
        @if($totaisGerais['km_rodados'] > 0)
            <p class="text-xl font-bold text-green-700">{{ number_format($totaisGerais['km_rodados'], 0, ',', '.') }} km</p>
        @else
            <p class="text-xl font-bold text-gray-300">—</p>
            <p class="text-xs text-gray-400 mt-1">Informe o KM nos abastecimentos</p>
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
                    <th class="px-4 py-3 text-right  text-xs font-medium text-green-700 uppercase">🛣️ KM rodados</th>
                    <th class="px-4 py-3 text-right  text-xs font-medium text-gray-500 uppercase">R$/km</th>
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
                    {{-- KM RODADOS --}}
                    <td class="px-4 py-3 text-right tabular-nums">
                        @if($row['km_rodados'])
                            <span class="text-sm font-semibold text-green-700">{{ number_format($row['km_rodados'], 0, ',', '.') }} km</span>
                        @else
                            <span class="text-sm text-gray-300" title="Informe o KM nos abastecimentos">—</span>
                        @endif
                    </td>
                    {{-- R$/KM --}}
                    <td class="px-4 py-3 text-right tabular-nums">
                        @if($row['custo_por_km'])
                            <span class="text-sm text-gray-600">{{ number_format($row['custo_por_km'], 3, ',', '.') }}</span>
                        @else
                            <span class="text-sm text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        @if($row['manutencao'] > 0)
                            <span class="text-sm font-medium text-orange-600">R$ {{ number_format($row['manutencao'], 2, ',', '.') }}</span>
                        @else <span class="text-sm text-gray-300">—</span>
                        @endif
                    </td>
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
                    <td class="px-4 py-3 text-right text-sm font-bold text-green-700 tabular-nums">
                        @if($totaisGerais['km_rodados'] > 0)
                            {{ number_format($totaisGerais['km_rodados'], 0, ',', '.') }} km
                        @else —
                        @endif
                    </td>
                    <td></td>
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
