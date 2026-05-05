@extends('layouts.app')

@section('title', 'Similares: '.$product->nome)

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">🧠 Produtos Similares</h1>
            <p class="mt-1 text-gray-600">
                Análise para: <strong>{{ $product->nome }}</strong>
            </p>
        </div>
        <a href="{{ route('products.show', $product) }}" class="btn-back">← Voltar</a>
    </div>
</div>

@if(count($similares) > 0)
<div class="space-y-3">
    @foreach($similares as $item)
    @php
        $p = $item['product'];
        $matchColor = $item['match'] === 'Alta' ? 'text-green-600 bg-green-50' : ($item['match'] === 'Média' ? 'text-yellow-600 bg-yellow-50' : 'text-gray-500 bg-gray-50');
    @endphp
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 flex-1">
                @if($p->foto)
                <img src="{{ asset('storage/' . $p->foto) }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                @endif
                <div>
                    <a href="{{ route('products.show', $p) }}" class="font-medium text-blue-600 hover:text-blue-800">
                        {{ $p->nome }}
                    </a>
                    <div class="text-xs text-gray-400 mt-0.5">
                        {{ $p->invoiceItems->count() }} compras
                        @if($p->unidade_padrao) · {{ $p->unidade_padrao }} @endif
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-gray-700">{{ $item['similaridade'] }}%</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $matchColor }}">{{ $item['match'] }}</span>
                
                @if(!$p->is_canonical && !$p->canonical_product_id)
                <form action="{{ route('products.agrupar', $product) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="canonical_id" value="{{ $p->id }}">
                    <button type="submit" class="text-xs text-green-600 hover:text-green-800 border border-green-300 rounded px-2 py-1">
                        🔗 Agrupar
                    </button>
                </form>
                @endif
            </div>
        </div>
        
        <!-- Barra de similaridade -->
        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3">
            <div class="h-1.5 rounded-full {{ $item['similaridade'] > 70 ? 'bg-green-500' : ($item['similaridade'] > 50 ? 'bg-yellow-500' : 'bg-gray-400') }}"
                 style="width: {{ $item['similaridade'] }}%"></div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
    <span class="text-6xl">🧠</span>
    <p class="text-gray-500 mt-4 text-lg">Nenhum produto similar encontrado.</p>
    <p class="text-sm text-gray-400 mt-1">O algoritmo não encontrou correspondências significativas.</p>
</div>
@endif
@endsection
