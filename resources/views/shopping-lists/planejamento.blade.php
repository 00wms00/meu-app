@extends('layouts.app')

@section('title', 'Planejamento Inteligente')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🧠 Planejamento Inteligente</h1>
        <p class="mt-1 text-gray-600">Análise avançada baseada no seu histórico de compras</p>
    </div>

    {{--
        Widget de Lista Rápida — único ponto de entrada explícito para POST /lista-rapida.
        Antes: a rota existia mas só era acessível pelos cards de "Próximas Compras",
        que dependem do algoritmo gerar sugestões. Sem sugestões, a rota era inacessível.
        Agora: o usuário pode criar uma lista rápida a qualquer momento escolhendo
        a categoria e o tipo diretamente.
    --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 flex flex-wrap items-end gap-3">
        <p class="w-full text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">⚡ Nova Lista Rápida</p>
        <form action="{{ route('shopping-lists.rapida') }}" method="POST" class="flex flex-wrap items-end gap-2">
            @csrf
            <div>
                <label for="rapidaCategoriaId" class="block text-xs text-gray-600 mb-1">Categoria</label>
                <select name="categoria_id" id="rapidaCategoriaId"
                        class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm py-1.5 pr-8" required>
                    <option value="">Selecione…</option>
                    @foreach(\App\Models\Category::where('user_id', auth()->id())->ordenado()->get() as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="rapidaTipo" class="block text-xs text-gray-600 mb-1">Tipo</label>
                <select name="tipo" id="rapidaTipo" class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm py-1.5">
                    <option value="semanal">🗓 Semanal</option>
                    <option value="mensal">📆 Mensal</option>
                </select>
            </div>
            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                ➕ Criar Lista
            </button>
        </form>
    </div>
</div>

<!-- Cards de Tendências -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Gasto este mês</p>
        <p class="text-xl font-bold">R$ {{ number_format($tendencias['gasto_atual'], 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500">vs mês passado</p>
        <p class="text-xl font-bold {{ $tendencias['variacao'] > 0 ? 'text-red-600' : 'text-green-600' }}">
            {{ $tendencias['variacao'] > 0 ? '+' : '' }}{{ $tendencias['variacao'] }}%
        </p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Média p/ lista</p>
        <p class="text-xl font-bold">R$ {{ number_format($tendencias['media_lista'], 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Total de listas</p>
        <p class="text-xl font-bold">{{ $tendencias['total_listas'] }}</p>
    </div>
</div>

<!-- Próximas Compras -->
@if($proximasCompras->isNotEmpty())
<div class="mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-3">🔮 Próximas Compras Sugeridas</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($proximasCompras as $sugestao)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $sugestao['categoria']->emoji }} {{ $sugestao['categoria']->nome }}</h3>
                    <p class="text-xs text-gray-500">{{ $sugestao['tipo'] === 'mensal' ? '📆 Mensal' : '🗓 Semanal' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Previsão</p>
                    <p class="font-bold text-gray-800">R$ {{ number_format($sugestao['valor_previsto'], 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="w-full bg-gray-200 rounded-full h-1.5 mb-2">
                <div class="h-1.5 rounded-full {{ $sugestao['urgencia'] === 'alta' ? 'bg-red-500' : ($sugestao['urgencia'] === 'media' ? 'bg-yellow-500' : 'bg-green-500') }}"
                     style="width: {{ min(100, $sugestao['score'] * 10) }}%"></div>
            </div>

            <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                <span>Urgência: <span class="font-medium {{ $sugestao['urgencia'] === 'alta' ? 'text-red-600' : ($sugestao['urgencia'] === 'media' ? 'text-yellow-600' : 'text-green-600') }}">{{ ucfirst($sugestao['urgencia']) }}</span></span>
                <span>Score: {{ $sugestao['score'] }}/10</span>
            </div>

            <form action="{{ route('shopping-lists.rapida') }}" method="POST">
                @csrf
                <input type="hidden" name="categoria_id" value="{{ $sugestao['categoria']->id }}">
                <input type="hidden" name="tipo" value="{{ $sugestao['tipo'] }}">
                <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition">
                    ➕ Criar Lista
                </button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Análise por Categoria -->
@if($analiseCategoria->isNotEmpty())
<div class="mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-3">📊 Análise por Categoria</h2>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Listas</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gasto Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Média</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Frequência</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($analiseCategoria as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $item['categoria']->emoji }} {{ $item['categoria']->nome }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ $item['total_listas'] }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">R$ {{ number_format($item['gasto_total'], 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">R$ {{ number_format($item['media_gasto'], 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $item['frequencia'] === 'alta' ? 'bg-green-100 text-green-700' :
                               ($item['frequencia'] === 'media' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ ucfirst($item['frequencia']) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Histórico de Sazonalidade -->
@if($sazonalidade->isNotEmpty())
<div>
    <h2 class="text-lg font-semibold text-gray-800 mb-3">📅 Histórico Mensal</h2>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mês</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Listas</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Gasto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($sazonalidade as $mes)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-800">{{ $mes['mes_nome'] }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ $mes['total_listas'] }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">R$ {{ number_format($mes['total_gasto'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
