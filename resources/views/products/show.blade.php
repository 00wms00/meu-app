@extends('layouts.app')

@section('title', 'Histórico: ' . $produtoExibicao->nome)

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $produtoExibicao->nome }}</h1>
        <p class="mt-1 text-gray-600">Histórico de preços</p>
    </div>
    <div class="flex flex-wrap gap-2 items-center">

        @if($alertaExistente)
            <span id="alertaBadge"
                  class="inline-flex items-center gap-1 px-3 py-1.5 rounded text-sm
                         {{ $alertaExistente->ativo ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-gray-100 text-gray-500 border border-gray-300' }}"
                  title="Alerta em {{ $alertaExistente->variacao_percentual }}%">
                {{ $alertaExistente->ativo ? '🔔' : '🔕' }}
                Alerta {{ $alertaExistente->ativo ? 'ativo' : 'pausado' }}
                &bull; {{ $alertaExistente->variacao_percentual }}%
            </span>
        @endif

        <button
            id="btnAbrirAlerta"
            data-action="{{ route('alertas.criar', $product) }}"
            data-nome="{{ $produtoExibicao->nome }}"
            data-limite="{{ $alertaExistente->variacao_percentual ?? 10 }}"
            class="btn-outline-secondary text-sm flex items-center gap-1"
            aria-label="{{ $alertaExistente ? 'Editar alerta de preço' : 'Criar alerta de preço' }}">
            🔔 {{ $alertaExistente ? 'Editar Alerta' : 'Alerta de Preço' }}
        </button>

        <a href="{{ route('products.edit', $produtoExibicao) }}" class="btn-outline-primary text-sm">✏️ Editar</a>

        {{--
            ANTES: route('products.similares', $produtoExibicao) — usava o canônico,
            mas o controller de similares recebe $product via route model binding
            (o ID que está na URL). Quando o usuário chegou via redirect do show
            (produto agrupado → canônico), $produtoExibicao->id pode ser diferente
            de $product->id, gerando uma URL de similares com ID inconsistente.

            AGORA: usa $product (o binding original da rota), garantindo que
            similares.blade.php receba o mesmo $product e o ← Voltar consiga
            reconstruir o caminho correto via canonical_product_id.
        --}}
        <a href="{{ route('products.similares', $product) }}" class="btn-outline-secondary text-sm flex items-center gap-1">
            🧠 Similares
        </a>

        <a href="{{ route('products.index') }}" class="btn-back">← Voltar</a>
    </div>
</div>

@if(session('alerta_criado'))
    <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded"
         role="status"
         aria-live="polite">
        <span aria-hidden="true">✅</span>
        <span>{{ session('success') }}</span>
        <a href="{{ route('alertas.index') }}" class="ml-auto text-sm underline hover:no-underline">
            Ver todos os alertas
        </a>
    </div>
@endif

@if($product->id !== $produtoExibicao->id)
    <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-4" role="status">
        📎 Este produto está agrupado como <strong>{{ $produtoExibicao->nome }}</strong>
    </div>
@endif

{{-- Foto + Variação --}}
<div class="flex items-start gap-4 mb-6">

    {{-- Foto --}}
    <div class="flex-shrink-0 w-24 bg-white rounded-lg shadow-sm border border-gray-200 p-1.5 text-center">
        @if($produtoExibicao->foto)
            <div class="relative group">
                <img src="{{ asset('storage/' . $produtoExibicao->foto) }}"
                     alt="Foto de {{ $produtoExibicao->nome }}"
                     class="w-20 h-20 object-cover rounded"
                     loading="lazy"
                     width="80" height="80">
                <form action="{{ route('products.foto.remover', $produtoExibicao) }}" method="POST"
                      class="absolute -top-1 -right-1 hidden group-hover:block"
                      data-confirm="Remover a foto de '{{ $produtoExibicao->nome }}'?">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center leading-none"
                            title="Remover foto"
                            aria-label="Remover foto de {{ $produtoExibicao->nome }}">
                        ✕
                    </button>
                </form>
            </div>
        @else
            <div class="w-20 h-20 bg-gray-100 rounded border-2 border-dashed border-gray-300 flex flex-col items-center justify-center"
                 aria-label="Sem foto cadastrada">
                <span class="text-2xl text-gray-400" aria-hidden="true">📷</span>
            </div>
        @endif

        <form action="{{ route('products.foto', $produtoExibicao) }}" method="POST"
              enctype="multipart/form-data" class="mt-1">
            @csrf
            <label for="inputFotoProduto" class="cursor-pointer">
                <span class="text-xs text-blue-600">{{ $produtoExibicao->foto ? 'Trocar' : 'Add' }}</span>
                <input type="file" name="foto" id="inputFotoProduto"
                       accept="image/jpeg,image/png,image/webp"
                       class="hidden"
                       onchange="this.form.submit()">
            </label>
        </form>
        @error('foto')
            <p class="text-xs text-red-500 mt-1" role="alert">{{ $message }}</p>
        @enderror
    </div>

    {{-- Variação --}}
    <div class="flex-1">
        @if($serie->isNotEmpty() && ! is_null($variacao))
            <div class="p-4 rounded-lg {{ $variacao > 0 ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700' }}">
                <div class="flex items-center gap-3">
                    <span class="text-2xl" aria-hidden="true">{{ $variacao > 0 ? '📈' : '📉' }}</span>
                    <div>
                        <p class="font-semibold">
                            Variação: <strong>{{ number_format($variacao, 2, ',', '.') }}%</strong>
                        </p>
                        <p class="text-sm">
                            R$ {{ number_format($serie->first()['valor_unitario'], 2, ',', '.') }}
                            &rarr;
                            R$ {{ number_format($serie->last()['valor_unitario'], 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@if($serie->isNotEmpty())
    {{-- Gráfico --}}
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📈 Evolução do Preço Unitário</h2>
        <div class="relative h-72">
            <canvas id="historicoChart" role="img" aria-label="Gráfico de evolução do preço unitário de {{ $produtoExibicao->nome }}"></canvas>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold text-gray-800">📋 Todas as Compras</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" aria-label="Histórico de compras de {{ $produtoExibicao->nome }}">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="text-left py-3 px-6 text-sm font-semibold text-gray-700">Data</th>
                        <th scope="col" class="text-center py-3 px-6 text-sm font-semibold text-gray-700">Unidade</th>
                        <th scope="col" class="text-right py-3 px-6 text-sm font-semibold text-gray-700">Preço Unitário</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serie as $ponto)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-6 text-sm">{{ $ponto['data'] }}</td>
                            <td class="py-3 px-6 text-sm text-center">{{ $ponto['unidade'] }}</td>
                            <td class="py-3 px-6 text-sm text-right font-semibold tabular-nums">
                                R$ {{ number_format($ponto['valor_unitario'], 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="bg-white rounded-lg shadow-md p-12 text-center mb-6">
        <span class="text-6xl" aria-hidden="true">📦</span>
        <p class="text-gray-500 mt-4 text-lg">Nenhuma compra registrada ainda.</p>
    </div>
@endif

{{-- Produtos Agrupados --}}
@if($agrupados->isNotEmpty())
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">🔗 Produtos Agrupados ({{ $agrupados->count() }})</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($agrupados as $agrupado)
                <a href="{{ route('products.show', $agrupado) }}"
                   class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 bg-blue-50 rounded-lg p-2">
                    @if($agrupado->foto)
                        <img src="{{ asset('storage/' . $agrupado->foto) }}"
                             class="w-8 h-8 object-cover rounded"
                             alt="Foto de {{ $agrupado->nome }}"
                             width="32" height="32"
                             loading="lazy">
                    @else
                        <span class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center text-xs" aria-hidden="true">📷</span>
                    @endif
                    <span class="truncate">{{ $agrupado->nome }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

{{-- Modal de Alerta de Preço --}}
<div id="modalAlerta"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalAlertaTitulo">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full mx-4 p-6">
        <h3 id="modalAlertaTitulo" class="text-lg font-semibold text-gray-800 mb-2">🔔 Alerta de Preço</h3>
        <p class="text-sm text-gray-600 mb-4" id="modalAlertaProduto"></p>
        <form id="formAlerta" method="POST">
            @csrf
            <label for="inputLimiteAlerta" class="block text-sm font-medium text-gray-700 mb-1">
                Alertar quando o preço aumentar
            </label>
            <div class="flex items-center gap-2 mb-4">
                <input type="number" name="limite_alerta" id="inputLimiteAlerta"
                       value="10" min="1" max="100"
                       class="form-control w-20 text-center" required>
                <span class="text-sm text-gray-500">%</span>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btnFecharModal" class="btn-outline-secondary text-sm">Cancelar</button>
                <button type="submit" class="btn-primary text-sm">💾 Salvar Alerta</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    @if($serie->isNotEmpty())
    const serie = @json($serie);
    const ctx = document.getElementById('historicoChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: serie.map(s => new Date(s.data + 'T00:00:00').toLocaleDateString('pt-BR')),
                datasets: [{
                    label: 'Preço Unitário (R$)',
                    data: serie.map(s => s.valor_unitario),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 5,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: v => 'R$ ' + v.toFixed(2).replace('.', ',') } }
                }
            }
        });
    }
    @endif

    /* ── Modal de Alerta ─────────────────────────────────────── */
    const modal       = document.getElementById('modalAlerta');
    const formAlerta  = document.getElementById('formAlerta');
    const btnAbrir    = document.getElementById('btnAbrirAlerta');
    const btnFechar   = document.getElementById('btnFecharModal');
    const inputLimite = document.getElementById('inputLimiteAlerta');
    let ultimoFoco    = null;

    function abrirModal() {
        ultimoFoco = document.activeElement;
        formAlerta.action = btnAbrir.dataset.action;
        document.getElementById('modalAlertaProduto').textContent =
            'Produto: ' + btnAbrir.dataset.nome;
        inputLimite.value = btnAbrir.dataset.limite || 10;
        modal.classList.remove('hidden');
        setTimeout(function () { inputLimite.focus(); }, 50);
    }

    function fecharModal() {
        modal.classList.add('hidden');
        inputLimite.value = btnAbrir.dataset.limite || 10;
        if (ultimoFoco) ultimoFoco.focus();
    }

    btnAbrir.addEventListener('click', abrirModal);
    btnFechar.addEventListener('click', fecharModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) fecharModal(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) fecharModal();
    });
});
</script>
@endpush
