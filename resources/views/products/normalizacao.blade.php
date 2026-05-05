@extends('layouts.app')

@section('title', 'Normalização de Produtos')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🏷️ Normalização de Produtos</h1>
            <p class="mt-1 text-gray-600">Aprove ou ajuste os nomes normalizados dos produtos</p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('products.normalizar-todos') }}" method="POST">
                @csrf
                <button type="submit" class="btn-primary text-sm" onclick="return confirm('Aprovar TODOS os produtos pendentes?')">
                    ✅ Aprovar Todos
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Filtros por status -->
<div class="flex gap-2 mb-6">
    <a href="{{ route('products.normalizacao', ['status' => 'pendente']) }}" 
       class="px-4 py-2 rounded text-sm {{ $status == 'pendente' ? 'bg-yellow-100 text-yellow-700 font-medium' : 'bg-gray-100 text-gray-600' }}">
        🟡 Pendentes
    </a>
    <a href="{{ route('products.normalizacao', ['status' => 'revisar']) }}" 
       class="px-4 py-2 rounded text-sm {{ $status == 'revisar' ? 'bg-blue-100 text-blue-700 font-medium' : 'bg-gray-100 text-gray-600' }}">
        🔵 Para Revisar
    </a>
    <a href="{{ route('products.normalizacao', ['status' => 'aprovado']) }}" 
       class="px-4 py-2 rounded text-sm {{ $status == 'aprovado' ? 'bg-green-100 text-green-700 font-medium' : 'bg-gray-100 text-gray-600' }}">
        ✅ Aprovados
    </a>
</div>

<!-- Lista de Produtos -->
<div class="space-y-4">
    @forelse($produtos as $produto)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <!-- Nome Original -->
                <div class="mb-2">
                    <span class="text-xs text-gray-400 uppercase">Nome Original (NF-e)</span>
                    <p class="text-sm text-gray-700">{{ $produto->nome }}</p>
                </div>

                @if(isset($analises[$produto->id]))
                <!-- Sugestão Automática -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                    <span class="text-xs text-blue-500 uppercase font-medium">🤖 Sugestão do Sistema</span>
                    
                    <div class="mt-2 space-y-1 text-sm">
                        @php $comp = $analises[$produto->id]['componentes']; @endphp
                        <div class="flex gap-2">
                            <span class="text-gray-500 w-24">Tipo:</span>
                            <span class="font-medium">{{ $comp['tipo'] ?: 'Não identificado' }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-gray-500 w-24">Marca:</span>
                            <span class="font-medium">{{ $comp['marca'] ?: 'Não identificada' }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-gray-500 w-24">Característica:</span>
                            <span class="font-medium">{{ $comp['caracteristica'] ?: 'Nenhuma' }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-gray-500 w-24">Quantidade:</span>
                            <span class="font-medium">{{ $comp['quantidade'] ?: 'Não identificada' }}</span>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-blue-200">
                        <span class="text-xs text-blue-500">Nome sugerido:</span>
                        <p class="text-sm font-medium text-blue-800">{{ $analises[$produto->id]['nome_exibicao_sugerido'] }}</p>
                    </div>

                    <!-- Formulário de Aprovação -->
                    <form action="{{ route('products.normalizar', $produto) }}" method="POST" class="mt-3 flex gap-2">
                        @csrf
                        <input type="text" name="nome_exibicao" 
                               value="{{ $analises[$produto->id]['nome_exibicao_sugerido'] }}"
                               class="form-control text-sm flex-1"
                               placeholder="Nome de exibição...">
                        <button type="submit" class="btn-success text-sm whitespace-nowrap">✅ Aprovar</button>
                    </form>
                </div>
                @elseif($produto->normalizacao_status === 'aprovado')
                <!-- Produto já aprovado -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                    <span class="text-xs text-green-500">✅ Aprovado</span>
                    <p class="text-sm font-medium text-green-800 mt-1">{{ $produto->nome_exibicao }}</p>
                    <p class="text-xs text-green-400 mt-1">{{ $produto->normalizado_em?->format('d/m/Y H:i') }}</p>
                </div>
                @endif
            </div>

            <!-- Status -->
            <div class="flex-shrink-0">
                @if($produto->normalizacao_status === 'pendente' || !$produto->normalizacao_status)
                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">Pendente</span>
                @elseif($produto->normalizacao_status === 'revisar')
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Revisar</span>
                @elseif($produto->normalizacao_status === 'aprovado')
                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Aprovado</span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg p-8 text-center">
        <p class="text-gray-500">Nenhum produto {{ $status }} encontrado.</p>
    </div>
    @endforelse
</div>

<div class="mt-6">
    {{ $produtos->links() }}
</div>
@endsection
