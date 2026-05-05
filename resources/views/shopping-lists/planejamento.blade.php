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
                        class="form-control text-sm py-1.5 pr-8" required>
                    <option value="">Selecione…</option>
                    @foreach(\App\Models\Category::where('user_id', auth()->id())->ordenado()->get() as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="rapidaTipo" class="block text-xs text-gray-600 mb-1">Tipo</label>
                <select name="tipo" id="rapidaTipo" class="form-control text-sm py-1.5">
                    <option value="semanal">🗓 Semanal</option>
                    <option value="mensal">📆 Mensal</option>
                </select>
            </div>
            <button type="submit" class="btn-primary text-sm py-1.5">
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
        <p class="text-xs text-gray-500">Média diária</p>
        <p class="text-xl font-bold">R$ {{ number_format($tendencias['media_diaria'], 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Projeção fim do mês</p>
        <p class="text-xl font-bold text-blue-600">R$ {{ number_format($tendencias['projecao'], 2, ',', '.') }}</p>
    </div>
</div>

<!-- Alerta de Compra Mensal -->
@if($compraMensal['sugerir'])
<div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-blue-800">📦 Hora da Compra do Mês!</h2>
            <p class="text-blue-600 mt-1">
                Última compra grande: {{ $compraMensal['ultima_data'] }}
                ({{ $compraMensal['dias_desde_ultima'] }} dias atrás)
            </p>
        </div>
        {{--
            ANTES: <a href="{{ route('shopping-lists.index') }}"> — levava para a listagem,
            não criava nada. O usuário precisava criar manualmente uma lista depois.
            AGORA: form POST /lista-rapida com tipo=mensal. A categoria é opcional aqui;
            o controller usa categoria_id, então oferecemos um select inline.
            Se o usuário não quiser selecionar categoria, pode usar o widget do topo.
        --}}
        <form action="{{ route('shopping-lists.rapida') }}" method="POST" class="flex items-center gap-2 flex-shrink-0">
            @csrf
            <select name="categoria_id" class="form-control text-sm py-1.5 pr-6" required
                    aria-label="Categoria para lista mensal">
                <option value="">Categoria…</option>
                @foreach(\App\Models\Category::where('user_id', auth()->id())->ordenado()->get() as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->nome }}</option>
                @endforeach
            </select>
            <input type="hidden" name="tipo" value="mensal">
            <button type="submit" class="btn-primary whitespace-nowrap">
                📦 Criar Lista Mensal
            </button>
        </form>
    </div>
</div>
@endif

<!-- Reposição Urgente -->
@if(count($reposicaoUrgente) > 0)
<div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
    <h2 class="text-lg font-bold text-red-800 mb-3">🚨 Precisa Repor Urgentemente</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach(array_slice($reposicaoUrgente, 0, 8) as $item)
        <div class="bg-white rounded-lg p-3 border border-red-100">
            <p class="text-sm font-medium text-gray-800">{{ $item['produto_nome'] }}</p>
            <p class="text-xs text-red-500">Última compra: {{ $item['ultima_compra'] }} ({{ $item['dias_desde_ultima'] }} dias)</p>
            <p class="text-xs text-gray-400">Ciclo normal: {{ $item['intervalo_medio'] }} dias</p>
            @if($item['preco_estimado'])
            <p class="text-xs text-blue-500 mt-1">~R$ {{ number_format($item['preco_estimado'], 2, ',', '.') }}</p>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Ciclo de Consumo -->
    <div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-gray-50"><h2 class="font-semibold text-gray-800">🔄 Ciclo de Consumo</h2></div>
            <div class="divide-y max-h-96 overflow-y-auto">
                @foreach(array_slice($cicloConsumo, 0, 15) as $item)
                <div class="px-4 py-3 flex items-center justify-between text-sm">
                    <div class="flex-1">
                        <span class="font-medium">{{ $item['produto_nome'] }}</span>
                        <span class="text-xs text-gray-400 ml-2">a cada {{ $item['intervalo_medio'] }} dias</span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $item['status'] === 'urgente' ? 'bg-red-100 text-red-700' : ($item['status'] === 'atencao' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                        {{ $item['status'] === 'urgente' ? '🔴 Urgente' : ($item['status'] === 'atencao' ? '🟡 Atenção' : '✅ OK') }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Economia Potencial -->
    @if(count($economiaPotencial) > 0)
    <div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b bg-gray-50"><h2 class="font-semibold text-gray-800">💡 Economia Potencial</h2></div>
            <div class="divide-y max-h-96 overflow-y-auto">
                @foreach($economiaPotencial as $eco)
                <div class="px-4 py-3 text-sm">
                    <p class="font-medium">{{ $eco['produto'] }}</p>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>🏪 {{ $eco['mais_barato'] }}: <strong class="text-green-600">R$ {{ number_format($eco['preco_barato'], 2, ',', '.') }}</strong></span>
                        <span>vs {{ $eco['mais_caro'] }}: R$ {{ number_format($eco['preco_caro'], 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-green-600 mt-0.5">Economia de {{ $eco['diferenca_percentual'] }}% (R$ {{ number_format($eco['diferenca'], 2, ',', '.') }})</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Próximos Dias Sugeridos -->
<div class="mt-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📅 Próximas Compras Sugeridas</h2>
    @if($sugestoesDias)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($sugestoesDias as $s)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">{{ $s['categoria_emoji'] ?? '🛒' }}</span>
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                    {{ $s['dias_ate'] == 0 ? 'Hoje' : "{$s['dias_ate']} dia(s)" }}
                </span>
            </div>
            <p class="font-semibold text-gray-800">{{ $s['categoria_nome'] }}</p>
            <p class="text-xs text-gray-500">🗓️ {{ $s['dia_nome'] }} &middot; {{ $s['proxima_data'] }}</p>
            <form action="{{ route('shopping-lists.rapida') }}" method="POST" class="mt-2">
                @csrf
                <input type="hidden" name="categoria_id" value="{{ $s['categoria_id'] }}">
                <input type="hidden" name="tipo" value="semanal">
                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">➕ Criar lista semanal</button>
            </form>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
        <span class="text-4xl" aria-hidden="true">🗓️</span>
        <p class="text-gray-500 mt-3">Sem sugestões automáticas ainda. Use o widget acima para criar uma lista rápida.</p>
    </div>
    @endif
</div>

<!-- Listas Ativas -->
@if($listasAtivas->count() > 0)
<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📝 Listas Ativas</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($listasAtivas as $lista)
        <a href="{{ route('shopping-lists.show', $lista) }}"
           class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 hover:shadow-md transition">
            <h3 class="font-semibold text-sm">{{ $lista->nome }}</h3>
            <p class="text-xs text-gray-500">{{ $lista->items_comprados_count }}/{{ $lista->items_count }} comprados</p>
        </a>
        @endforeach
    </div>
</div>
@endif
@endsection
