@extends('layouts.app')

@section('title', 'Orçamento Mensal')

@section('content')
@php
    $mesAnteriorNav = \Carbon\Carbon::create($ano, $mes, 1)->subMonth();
    $mesAtualNav    = \Carbon\Carbon::create($ano, $mes, 1);
    $mesProximoNav  = \Carbon\Carbon::create($ano, $mes, 1)->addMonth();

    $labelAnterior = mb_strtolower($mesAnteriorNav->translatedFormat('M')) . '/' . $mesAnteriorNav->format('y');
    $labelAtual    = mb_strtolower($mesAtualNav->translatedFormat('M'))    . '/' . $mesAtualNav->format('y');
    $labelProximo  = mb_strtolower($mesProximoNav->translatedFormat('M'))  . '/' . $mesProximoNav->format('y');
@endphp

<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">💰 Orçamento Mensal</h1>
            <p class="mt-1 text-gray-600">{{ $meses[$mes] }} de {{ $ano }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('budgets.index', ['mes' => $mesAnteriorNav->month, 'ano' => $mesAnteriorNav->year]) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">← {{ $labelAnterior }}</a>
            <a href="{{ route('budgets.index', ['mes' => $mesAtualNav->month, 'ano' => $mesAtualNav->year]) }}"
               class="inline-flex items-center px-3 py-2 border border-blue-400 text-blue-700 bg-blue-50 hover:bg-blue-100 text-sm font-semibold rounded-md transition">{{ $labelAtual }}</a>
            <a href="{{ route('budgets.index', ['mes' => $mesProximoNav->month, 'ano' => $mesProximoNav->year]) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">{{ $labelProximo }} →</a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif
@if(session('warning'))
<div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-4">⚠️ {{ session('warning') }}</div>
@endif

<!-- Cards de Resumo -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Orçamento Total</p>
        <p class="text-2xl font-bold {{ $totalOrcado > 0 ? 'text-blue-600' : 'text-gray-400' }}">
            R$ {{ number_format($totalOrcado, 2, ',', '.') }}
        </p>
        @if($totalOrcado == 0)
        <p class="text-xs text-orange-500 mt-1">⚠️ Não definido</p>
        @endif
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Gasto Real</p>
        <p class="text-2xl font-bold text-gray-800">
            R$ {{ number_format($totalGasto, 2, ',', '.') }}
        </p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">
            @if($totalOrcado > 0)
                {{ $totalGasto > $totalOrcado ? 'Excedido' : 'Disponível' }}
            @else
                Status
            @endif
        </p>
        @if($totalOrcado > 0)
        <p class="text-2xl font-bold {{ $totalGasto > $totalOrcado ? 'text-red-600' : 'text-green-600' }}">
            @if($totalGasto > $totalOrcado)
                - R$ {{ number_format($totalGasto - $totalOrcado, 2, ',', '.') }}
            @else
                R$ {{ number_format($totalOrcado - $totalGasto, 2, ',', '.') }}
            @endif
        </p>
        @else
        <p class="text-lg text-gray-400">Defina um orçamento</p>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Lista de Categorias -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">📊 Gastos por Categoria</h2>
                @php
                    $catsComGasto = 0;
                    foreach ($dadosCategorias as $d) {
                        if ($d['gasto'] > 0) $catsComGasto++;
                    }
                @endphp
                <span class="text-xs text-gray-500">{{ $catsComGasto }} categorias com gastos</span>
            </div>

            @if(count($dadosCategorias) > 0)
            <div class="divide-y divide-gray-200">
                @foreach($dadosCategorias as $cat)
                @php
                    if ($cat['limite'] == 0) {
                        $barColor  = '#d1d5db';
                        $textColor = '#9ca3af';
                    } elseif ($cat['porcentagem'] >= 100) {
                        $barColor  = '#ef4444';
                        $textColor = '#dc2626';
                    } elseif ($cat['porcentagem'] >= 80) {
                        $barColor  = '#f59e0b';
                        $textColor = '#d97706';
                    } else {
                        $barColor  = '#22c55e';
                        $textColor = '#16a34a';
                    }
                    $barWidth = min(100, $cat['porcentagem']);
                @endphp
                <div class="p-4 hover:bg-gray-50 {{ $cat['gasto'] == 0 ? 'opacity-50' : '' }}">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ $cat['emoji'] }}</span>
                            <span class="text-sm font-medium text-gray-800">{{ $cat['nome'] }}</span>
                            @if($cat['limite'] > 0)
                                @if($cat['status'] === 'excedido')
                                <span style="background-color:#fee2e2;color:#dc2626" class="text-xs px-2 py-0.5 rounded-full font-medium">🔴 Excedido</span>
                                @elseif($cat['status'] === 'alerta')
                                <span style="background-color:#fef3c7;color:#d97706" class="text-xs px-2 py-0.5 rounded-full font-medium">🟡 Alerta</span>
                                @endif
                            @endif
                        </div>
                        <div class="text-right text-sm">
                            @if($cat['gasto'] > 0)
                            <span class="font-semibold" style="color:{{ $cat['limite'] > 0 && $cat['gasto'] > $cat['limite'] ? '#dc2626' : '#374151' }}">
                                R$ {{ number_format($cat['gasto'], 2, ',', '.') }}
                            </span>
                            @else
                            <span class="text-gray-400">R$ 0,00</span>
                            @endif
                            @if($cat['limite'] > 0)
                            <span class="text-gray-400 text-xs"> / R$ {{ number_format($cat['limite'], 2, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                    <div style="width:100%;background-color:#e5e7eb;border-radius:9999px;height:10px;overflow:hidden">
                        <div style="width:{{ $barWidth }}%;background-color:{{ $barColor }};height:10px;border-radius:9999px;transition:all .5s ease"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-top:4px">
                        <span style="color:{{ $cat['porcentagem'] > 100 ? '#dc2626' : '#6b7280' }};font-weight:{{ $cat['porcentagem'] > 100 ? '500' : '400' }}">
                            {{ number_format($cat['porcentagem'], 1, ',', '.') }}%
                        </span>
                        @if($cat['limite'] > 0)
                            @if($cat['porcentagem'] > 100)
                            <span style="color:#dc2626;font-weight:500">R$ {{ number_format($cat['gasto'] - $cat['limite'], 2, ',', '.') }} acima</span>
                            @else
                            <span style="color:#9ca3af">R$ {{ number_format($cat['limite'] - $cat['gasto'], 2, ',', '.') }} livre</span>
                            @endif
                        @else
                        <span style="color:#9ca3af">Sem limite</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="p-8 text-center text-gray-500">
                <span class="text-4xl">📊</span>
                <p class="mt-2">Nenhum gasto registrado neste mês.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Formulário de Orçamento -->
    <div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-6">
            <div class="px-6 py-4 border-b bg-blue-50 rounded-t-lg flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">⚙️ Definir Orçamento</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $meses[$mes] }}/{{ $ano }}</p>
                </div>

                @if($temMesAnterior)
                <form action="{{ route('budgets.copiar') }}" method="POST" id="form-copiar">
                    @csrf
                    <input type="hidden" name="mes" value="{{ $mes }}">
                    <input type="hidden" name="ano" value="{{ $ano }}">
                    <button
                        type="button"
                        onclick="confirmarCopia()"
                        title="Copiar orçamento de {{ $nomeMesAnterior }}/{{ $mesAnteriorData->year }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-blue-300 text-blue-700 hover:bg-blue-50 text-xs font-semibold rounded-md transition shadow-sm whitespace-nowrap"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-4 10h6a2 2 0 002-2v-8a2 2 0 00-2-2h-6a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Copiar {{ $nomeMesAnterior }}
                    </button>
                </form>
                @endif
            </div>

            <div class="p-6">
                <form action="{{ route('budgets.store') }}" method="POST" id="form-orcamento">
                    @csrf
                    <input type="hidden" name="ano" value="{{ $ano }}">
                    <input type="hidden" name="mes" value="{{ $mes }}">

                    <div class="mb-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                        <p class="text-xs text-yellow-700">
                            💡 <strong>Dica:</strong> Defina limites por categoria. O total é calculado automaticamente.
                        </p>
                    </div>

                    {{-- Total calculado ao vivo (somente leitura) --}}
                    <div class="mb-4 flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <span class="text-sm font-medium text-blue-800">Total orçado</span>
                        <span id="total-preview" class="text-lg font-bold text-blue-700">
                            R$ {{ number_format($totalOrcado, 2, ',', '.') }}
                        </span>
                    </div>

                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Limites por Categoria</label>
                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1" id="lista-categorias">
                            @foreach($categorias as $cat)
                            @php
                                $orcamentoCat = $budgetCategories->firstWhere('category_id', $cat->id);
                                $valorAtual   = $orcamentoCat ? (float) $orcamentoCat->valor_limite : 0;
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="text-sm w-6">{{ $cat->emoji }}</span>
                                <span class="text-xs text-gray-600 w-24 truncate" title="{{ $cat->nome }}">{{ $cat->nome }}</span>
                                <div class="flex items-center flex-1">
                                    <span class="text-gray-400 text-xs mr-1">R$</span>
                                    <input
                                        type="text"
                                        name="categorias[{{ $cat->id }}][valor_limite]"
                                        value="{{ $valorAtual > 0 ? number_format($valorAtual, 2, ',', '.') : '' }}"
                                        class="cat-valor w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs py-1.5"
                                        placeholder="0,00"
                                        inputmode="numeric"
                                        autocomplete="off"
                                    >
                                    <input type="hidden" name="categorias[{{ $cat->id }}][category_id]" value="{{ $cat->id }}">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full mt-4 inline-flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-lg font-semibold rounded-md transition">
                        💾 Salvar Orçamento
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function parseBR(str) {
    if (!str) return 0;
    return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
}

function formatBR(n) {
    return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalcTotal() {
    let total = 0;
    document.querySelectorAll('.cat-valor').forEach(function (el) {
        total += parseBR(el.value);
    });
    document.getElementById('total-preview').textContent = 'R$ ' + formatBR(total);
}

function mascaraMoeda(input) {
    input.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '');
        if (!v) { this.value = ''; recalcTotal(); return; }
        v = (parseInt(v, 10) / 100).toFixed(2);
        this.value = v
            .replace('.', ',')
            .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        recalcTotal();
    });
    input.addEventListener('focus', function () {
        setTimeout(() => this.select(), 10);
    });
}

document.querySelectorAll('.cat-valor').forEach(mascaraMoeda);

function confirmarCopia() {
    if (confirm('Substituir o orçamento de {{ $meses[$mes] }}/{{ $ano }} pelos valores de {{ $nomeMesAnterior }}/{{ $mesAnteriorData->year }}?\n\nOs limites existentes serão sobrescritos.')) {
        document.getElementById('form-copiar').submit();
    }
}
</script>
@endsection
