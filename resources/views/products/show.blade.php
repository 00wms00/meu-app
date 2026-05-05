@extends('layouts.app')

@section('title', 'Histórico: '.$produtoExibicao->nome)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $produtoExibicao->nome }}</h1>
        <p class="mt-1 text-gray-600">Histórico de preços</p>
    </div>
    <div class="flex gap-2">
        <button onclick="mostrarModalAlerta('{{ $produtoExibicao->id }}', '{{ addslashes($produtoExibicao->nome) }}')" 
                class="btn-outline-secondary text-sm flex items-center gap-1">
            🔔 Alerta de Preço
        </button>
        <a href="{{ route('products.edit', $produtoExibicao) }}" class="btn-outline-primary text-sm">✏️ Editar</a>
        <a href="{{ route('products.similares', $produtoExibicao) }}" class="btn-outline-secondary text-sm flex items-center gap-1">
    🧠 Similares
</a>
        <a href="{{ route('products.index') }}" class="btn-back">← Voltar</a>
    </div>
</div>

@if($product->id !== $produtoExibicao->id)
<div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-4">
    📎 Este produto está agrupado como <strong>{{ $produtoExibicao->nome }}</strong>
</div>
@endif

<!-- Foto 85x85 + Variação -->
<div class="flex items-start gap-4 mb-6">
    <!-- Foto 85x85 -->
    <div class="flex-shrink-0">
        <div style="width: 95px; height: 115px;" class="bg-white rounded-lg shadow-sm border border-gray-200 p-1.5 text-center">
            @if($produtoExibicao->foto)
            <div class="relative group">
                <img src="{{ asset('storage/' . $produtoExibicao->foto) }}" 
                     alt="{{ $produtoExibicao->nome }}" 
                     style="width: 85px; height: 85px; object-fit: cover; border-radius: 4px;"
                     loading="lazy">
                <form action="{{ route('products.foto.remover', $produtoExibicao) }}" method="POST" 
                      style="position: absolute; top: -4px; right: -4px;" class="hidden group-hover:block">
                    @csrf @method('DELETE')
                    <button type="submit" style="background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                            onclick="return confirm('Remover foto?')" title="Remover">✕</button>
                </form>
            </div>
            @else
            <div style="width: 85px; height: 85px; background: #f3f4f6; border-radius: 4px; border: 2px dashed #d1d5db; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <span style="font-size: 24px; color: #9ca3af;">📷</span>
            </div>
            @endif
            
            <form action="{{ route('products.foto', $produtoExibicao) }}" method="POST" enctype="multipart/form-data" style="margin-top: 4px;">
                @csrf
                <label style="cursor: pointer;">
                    <span style="font-size: 11px; color: #2563eb;">
                        {{ $produtoExibicao->foto ? 'Trocar' : 'Add' }}
                    </span>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="this.form.submit()">
                </label>
            </form>
            @error('foto')<p style="font-size: 10px; color: #ef4444;">{{ $message }}</p>@enderror
        </div>
    </div>

    <!-- Variação -->
    <div class="flex-1">
        @if($serie->count() > 0 && !is_null($variacao))
        <div class="p-4 rounded-lg {{ $variacao > 0 ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700' }}">
            <div class="flex items-center">
                <span class="text-2xl mr-3">{{ $variacao > 0 ? '📈' : '📉' }}</span>
                <div>
                    <p class="font-semibold">Variação: <strong>{{ number_format($variacao, 2, ',', '.') }}%</strong></p>
                    <p class="text-sm">R$ {{ number_format($serie->first()['valor_unitario'], 2, ',', '.') }} → R$ {{ number_format($serie->last()['valor_unitario'], 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@if($serie->count() > 0)
<!-- Gráfico -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📈 Evolução do Preço Unitário</h2>
    <div class="relative" style="height: 300px;">
        <canvas id="historicoChart"></canvas>
    </div>
</div>

<!-- Tabela -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="p-6 border-b"><h2 class="text-lg font-semibold text-gray-800">📋 Todas as Compras</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-6 text-sm font-semibold text-gray-700">Data</th>
                    <th class="text-center py-3 px-6 text-sm font-semibold text-gray-700">Unidade</th>
                    <th class="text-right py-3 px-6 text-sm font-semibold text-gray-700">Preço Unitário</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serie as $ponto)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-6 text-sm">{{ \Carbon\Carbon::parse($ponto['data'])->format('d/m/Y') }}</td>
                    <td class="py-3 px-6 text-sm text-center">{{ $ponto['unidade'] }}</td>
                    <td class="py-3 px-6 text-sm text-right font-semibold">R$ {{ number_format($ponto['valor_unitario'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="bg-white rounded-lg shadow-md p-12 text-center mb-6">
    <span class="text-6xl">📦</span>
    <p class="text-gray-500 mt-4 text-lg">Nenhuma compra registrada.</p>
</div>
@endif

<!-- Produtos Agrupados -->
@if($agrupados->count() > 0)
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">🔗 Produtos Agrupados ({{ $agrupados->count() }})</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach($agrupados as $agrupado)
        <a href="{{ route('products.show', $agrupado) }}" class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 rounded-lg p-2">
            @if($agrupado->foto)
            <img src="{{ asset('storage/' . $agrupado->foto) }}" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;" alt="">
            @else
            <span style="width: 32px; height: 32px; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px;">📷</span>
            @endif
            <span class="truncate">{{ $agrupado->nome }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif

<!-- Modal de Alerta -->
<div id="modalAlerta" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">🔔 Criar Alerta de Preço</h3>
        <p class="text-sm text-gray-600 mb-4" id="modalAlertaProduto"></p>
        <form id="formAlerta" method="POST">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-1">Alertar quando o preço aumentar</label>
            <div class="flex items-center gap-2 mb-4">
                <input type="number" name="limite_alerta" value="10" min="1" max="100" class="form-control w-20 text-center" required>
                <span class="text-sm text-gray-500">%</span>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('modalAlerta').classList.add('hidden')" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="submit" class="btn-primary text-sm">💾 Criar Alerta</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
@if($serie->count() > 0)
document.addEventListener('DOMContentLoaded', function () {
    const serie = @json($serie);
    const ctx = document.getElementById('historicoChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: serie.map(s => new Date(s.data).toLocaleDateString('pt-BR')),
            datasets: [{
                label: 'Preço Unitário (R$)',
                data: serie.map(s => s.valor_unitario),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2, tension: 0.3, pointRadius: 5, fill: true,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: v => 'R$ ' + v.toFixed(2).replace('.', ',') } } }
        }
    });
});
@endif

function mostrarModalAlerta(produtoId, nome) {
    document.getElementById('modalAlertaProduto').textContent = 'Produto: ' + nome;
    document.getElementById('formAlerta').action = '/products/' + produtoId + '/alerta';
    document.getElementById('modalAlerta').classList.remove('hidden');
}
document.getElementById('modalAlerta').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
@endpush
