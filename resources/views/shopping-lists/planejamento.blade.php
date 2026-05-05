@extends('layouts.app')

@section('title', 'Planejamento Inteligente')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🧠 Planejamento Inteligente</h1>
    <p class="mt-1 text-gray-600">Análise avançada baseada no seu histórico de compras</p>
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
    <div class="flex items-start justify-between">
        <div>
            <h2 class="text-xl font-bold text-blue-800">📦 Hora da Compra do Mês!</h2>
            <p class="text-blue-600 mt-1">Última compra grande: {{ $compraMensal['ultima_data'] }} ({{ $compraMensal['dias_desde_ultima'] }} dias atrás)</p>
        </div>
        <a href="{{ route('shopping-lists.index') }}" class="btn-primary">Criar Lista Mensal</a>
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
            <p class="text-sm font-medium text-gray-800">{{ $item['produto']->nome }}</p>
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
                        <span class="font-medium">{{ $item['produto']->nome }}</span>
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($sugestoesDias as $s)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">{{ $s['categoria']->emoji }}</span>
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ $s['dias_ate'] == 0 ? 'Hoje' : "{$s['dias_ate']} dia(s)" }}</span>
            </div>
            <p class="font-semibold text-gray-800">{{ $s['categoria']->nome }}</p>
            <p class="text-xs text-gray-500">🗓️ {{ $s['dia_nome'] }} · {{ $s['proxima_data'] }}</p>
            <form action="{{ route('shopping-lists.rapida') }}" method="POST" class="mt-2">
                @csrf
                <input type="hidden" name="categoria_id" value="{{ $s['categoria']->id }}">
                <input type="hidden" name="tipo" value="semanal">
                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">➕ Criar lista</button>
            </form>
        </div>
        @endforeach
    </div>
</div>

<!-- Listas Ativas -->
@if($listasAtivas->count() > 0)
<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📝 Listas Ativas</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($listasAtivas as $lista)
        <a href="{{ route('shopping-lists.show', $lista) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 hover:shadow-md transition">
            <h3 class="font-semibold text-sm">{{ $lista->nome }}</h3>
            <p class="text-xs text-gray-500">{{ $lista->items_comprados_count }}/{{ $lista->items_count }} comprados</p>
        </a>
        @endforeach
    </div>
</div>
@endif
@endsection
