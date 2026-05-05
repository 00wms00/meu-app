@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">📊 Dashboard</h1>
    <p class="mt-2 text-gray-600">Acompanhe seus gastos e notas fiscais</p>
</div>

<!-- Cards de Resumo -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-blue-100 text-sm">Gasto do Mês</p>
        <p class="text-2xl font-bold mt-1">R$ {{ number_format($totalGasto, 2, ',', '.') }}</p>
    </div>
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-green-100 text-sm">Notas este mês</p>
        <p class="text-2xl font-bold mt-1">{{ $numNotas }}</p>
    </div>
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
        <p class="text-purple-100 text-sm">Estabelecimentos</p>
        <p class="text-2xl font-bold mt-1">{{ $totalEstabelecimentos }}</p>
    </div>
</div>

<!-- ✅ NOVO: Gráfico de Evolução Mensal -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">📈 Evolução Mensal</h3>
            <span class="text-xs text-gray-500">Últimos 6 meses</span>
        </div>
        <div class="relative" style="height: 280px;">
            <canvas id="evolucaoMensalChart"></canvas>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">📊 Gastos Diários</h3>
            <span class="text-xs text-gray-500">Últimos 30 dias</span>
        </div>
        <div class="relative" style="height: 280px;">
            <canvas id="gastosDiariosChart"></canvas>
        </div>
    </div>
</div>

<!-- Últimas Notas e Acesso Rápido -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">📄 Últimas Notas</h2>
                <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">Ver Todas →</a>
            </div>
            @if($ultimosCupons->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">Estabelecimento</th>
                            <th class="text-right py-3 px-4 text-xs font-medium text-gray-500 uppercase">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($ultimosCupons as $cupom)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-sm">{{ $cupom->data_emissao->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 text-sm">
                                <a href="{{ route('invoices.show', $cupom) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ Str::limit($cupom->nome_estabelecimento, 30) }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-sm text-right font-semibold text-green-600">
                                R$ {{ number_format($cupom->valor_pago, 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-8 text-center text-gray-500">
                <p>Nenhuma nota fiscal importada ainda.</p>
                <a href="{{ route('import.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition mt-3 inline-block">📥 Importar Primeira NFC-e</a>
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">⚡ Acesso Rápido</h3>
            <div class="space-y-2">
                <a href="{{ route('import.create') }}" class="w-full block text-center inline-flex items-center justify-center px-4 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 text-sm font-semibold rounded-md transition">📥 Importar NFC-e</a>
                <a href="{{ route('lancamento.create') }}" class="w-full block text-center inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">✍️ Lançamento Manual</a>
                <a href="{{ route('invoices.index') }}" class="w-full block text-center inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">📋 Ver Notas</a>
                <a href="{{ route('relatorio.mensal') }}" class="w-full block text-center inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">📊 Relatório Mensal</a>
                <a href="{{ route('relatorio.periodo') }}" class="w-full block text-center inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-md transition">📅 Por Período</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Gráfico de Evolução Mensal
    const ctxMensal = document.getElementById('evolucaoMensalChart');
    if (ctxMensal) {
        const meses = @json($mesesEvolucao);
        const valores = @json($valoresEvolucao);
        
        new Chart(ctxMensal, {
            type: 'line',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Gasto Total (R$)',
                    data: valores,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Gastos Diários
    const ctxDiario = document.getElementById('gastosDiariosChart');
    if (ctxDiario) {
        const dias = @json($diasEvolucao);
        const valores = @json($valoresDiarios);
        
        // Calcular média móvel (7 dias)
        const mediaMovel = [];
        for (let i = 0; i < valores.length; i++) {
            const inicio = Math.max(0, i - 3);
            const fim = Math.min(valores.length - 1, i + 3);
            let soma = 0;
            let count = 0;
            for (let j = inicio; j <= fim; j++) {
                soma += valores[j];
                count++;
            }
            mediaMovel.push(soma / count);
        }
        
        new Chart(ctxDiario, {
            type: 'bar',
            data: {
                labels: dias,
                datasets: [
                    {
                        label: 'Gasto Diário',
                        data: valores,
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        borderRadius: 4,
                        order: 2,
                    },
                    {
                        label: 'Média Móvel (7 dias)',
                        data: mediaMovel,
                        type: 'line',
                        borderColor: '#ef4444',
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
                        tension: 0.4,
                        order: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxTicksLimit: 10,
                            maxRotation: 0,
                        }
                    },
                    y: {
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
@endpush
