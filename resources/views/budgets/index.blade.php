@extends('layouts.app')

@section('title', 'Orçamento Mensal')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">💰 Orçamento Mensal</h1>
            <p class="mt-1 text-gray-600">{{ $meses[$mes] }} de {{ $ano }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('budgets.index', ['mes' => $mes == 1 ? 12 : $mes - 1, 'ano' => $mes == 1 ? $ano - 1 : $ano]) }}" 
               class="btn-outline-secondary text-sm">← Mês anterior</a>
            <a href="{{ route('budgets.index', ['mes' => now()->month, 'ano' => now()->year]) }}" 
               class="btn-outline-secondary text-sm">📅 Hoje</a>
            <a href="{{ route('budgets.index', ['mes' => $mes == 12 ? 1 : $mes + 1, 'ano' => $mes == 12 ? $ano + 1 : $ano]) }}" 
               class="btn-outline-secondary text-sm">Próximo mês →</a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
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
                    // Definir cor da barra baseado no status
                    if ($cat['limite'] == 0) {
                        $barColor = '#d1d5db'; // cinza
                        $textColor = '#9ca3af';
                    } elseif ($cat['porcentagem'] >= 100) {
                        $barColor = '#ef4444'; // vermelho
                        $textColor = '#dc2626';
                    } elseif ($cat['porcentagem'] >= 80) {
                        $barColor = '#f59e0b'; // amarelo
                        $textColor = '#d97706';
                    } else {
                        $barColor = '#22c55e'; // verde
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
                                <span style="background-color: #fee2e2; color: #dc2626;" class="text-xs px-2 py-0.5 rounded-full font-medium">🔴 Excedido</span>
                                @elseif($cat['status'] === 'alerta')
                                <span style="background-color: #fef3c7; color: #d97706;" class="text-xs px-2 py-0.5 rounded-full font-medium">🟡 Alerta</span>
                                @endif
                            @endif
                        </div>
                        <div class="text-right text-sm">
                            @if($cat['gasto'] > 0)
                            <span class="font-semibold" style="color: {{ $cat['limite'] > 0 && $cat['gasto'] > $cat['limite'] ? '#dc2626' : '#374151' }}">
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
                    
                    <!-- Barra de Progresso com estilo inline -->
                    <div style="width: 100%; background-color: #e5e7eb; border-radius: 9999px; height: 10px; overflow: hidden;">
                        <div style="width: {{ $barWidth }}%; background-color: {{ $barColor }}; height: 10px; border-radius: 9999px; transition: all 0.5s ease;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-top: 4px;">
                        <span style="color: {{ $cat['porcentagem'] > 100 ? '#dc2626' : '#6b7280' }}; font-weight: {{ $cat['porcentagem'] > 100 ? '500' : '400' }};">
                            {{ number_format($cat['porcentagem'], 1, ',', '.') }}%
                        </span>
                        @if($cat['limite'] > 0)
                            @if($cat['porcentagem'] > 100)
                            <span style="color: #dc2626; font-weight: 500;">
                                R$ {{ number_format($cat['gasto'] - $cat['limite'], 2, ',', '.') }} acima
                            </span>
                            @else
                            <span style="color: #9ca3af;">
                                R$ {{ number_format($cat['limite'] - $cat['gasto'], 2, ',', '.') }} livre
                            </span>
                            @endif
                        @else
                        <span style="color: #9ca3af;">Sem limite</span>
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
            <div class="px-6 py-4 border-b bg-blue-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-800">⚙️ Definir Orçamento</h2>
                <p class="text-xs text-gray-500 mt-1">{{ $meses[$mes] }}/{{ $ano }}</p>
            </div>
            <div class="p-6">
                <form action="{{ route('budgets.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="ano" value="{{ $ano }}">
                    <input type="hidden" name="mes" value="{{ $mes }}">
                    
                    <div class="mb-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                        <p class="text-xs text-yellow-700">
                            💡 <strong>Dica:</strong> Defina limites por categoria para acompanhar seus gastos.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orçamento Total</label>
                        <div class="flex items-center">
                            <span class="text-gray-500 mr-1 text-lg">R$</span>
                            <input type="text" name="valor_total" 
                                   value="{{ $budget->valor_total > 0 ? number_format($budget->valor_total, 2, ',', '.') : '' }}" 
                                   class="form-control text-lg font-bold" placeholder="0,00">
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Limites por Categoria</label>
                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                            @foreach($categorias as $cat)
                            @php
                                $orcamentoCat = $budgetCategories->firstWhere('category_id', $cat->id);
                                $valorAtual = $orcamentoCat ? (float) $orcamentoCat->valor_limite : 0;
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="text-sm w-6">{{ $cat->emoji }}</span>
                                <span class="text-xs text-gray-600 w-24 truncate">{{ $cat->nome }}</span>
                                <div class="flex items-center flex-1">
                                    <span class="text-gray-400 text-xs mr-1">R$</span>
                                    <input type="text" name="categorias[{{ $cat->id }}][valor_limite]" 
                                           value="{{ $valorAtual > 0 ? number_format($valorAtual, 2, ',', '.') : '' }}"
                                           class="form-control text-xs py-1.5 w-full" placeholder="0,00">
                                    <input type="hidden" name="categorias[{{ $cat->id }}][category_id]" value="{{ $cat->id }}">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full mt-4 text-lg py-3">
                        💾 Salvar Orçamento
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
