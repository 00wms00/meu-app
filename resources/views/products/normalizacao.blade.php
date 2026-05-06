@extends('layouts.app')

@section('title', 'Normalização de Produtos')

@section('content')
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">🏷️ Normalização de Produtos</h1>
            <p class="mt-1 text-gray-600">Aprove ou ajuste os nomes normalizados</p>
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

<!-- Filtros -->
<div class="flex gap-2 mb-6">
    <a href="?status=pendente" class="px-4 py-2 rounded text-sm {{ $status == 'pendente' ? 'bg-yellow-100 text-yellow-700 font-medium' : 'bg-gray-100 text-gray-600' }}">🟡 Pendentes</a>
    <a href="?status=revisar" class="px-4 py-2 rounded text-sm {{ $status == 'revisar' ? 'bg-blue-100 text-blue-700 font-medium' : 'bg-gray-100 text-gray-600' }}">🔵 Para Revisar</a>
    <a href="?status=aprovado" class="px-4 py-2 rounded text-sm {{ $status == 'aprovado' ? 'bg-green-100 text-green-700 font-medium' : 'bg-gray-100 text-gray-600' }}">✅ Aprovados</a>
</div>

<!-- Lista -->
<div class="space-y-4">
    @forelse($produtos as $produto)
    @php
        // Gerar análise se não existir
        if (!isset($analises[$produto->id]) && !$produto->nome_normalizado) {
            try {
                $analises[$produto->id] = app(\App\Services\ProductNormalizationService::class)->analyze($produto);
            } catch (\Exception $e) {
                $analises[$produto->id] = null;
            }
        }
        $analise = $analises[$produto->id] ?? null;
    @endphp
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <!-- Nome Original -->
                <div class="mb-3">
                    <span class="text-xs text-gray-400 uppercase font-medium">Nome Original (NF-e)</span>
                    <p class="text-sm text-gray-700 font-mono bg-gray-50 p-2 rounded mt-1">{{ $produto->nome }}</p>
                </div>

                @if($analise)
                <!-- Análise -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <span class="text-xs text-blue-500 uppercase font-medium">🔍 Sugestão do Sistema</span>
                    
                    <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500">Tipo:</span>
                            <span class="font-medium {{ $analise['componentes']['tipo'] && $analise['componentes']['tipo'] !== 'outro' ? 'text-green-700' : 'text-red-500' }}">
                                {{ $analise['componentes']['tipo'] ?: 'Não identificado' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Marca:</span>
                            <span class="font-medium {{ $analise['componentes']['marca'] ? 'text-green-700' : 'text-red-500' }}">
                                {{ $analise['componentes']['marca'] ?: 'Não identificada' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Característica:</span>
                            <span class="font-medium">{{ $analise['componentes']['caracteristica'] ?: 'Nenhuma' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Quantidade:</span>
                            <span class="font-medium {{ $analise['componentes']['quantidade'] ? 'text-green-700' : 'text-red-500' }}">
                                {{ $analise['componentes']['quantidade'] ?: 'Não identificada' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-blue-200">
                        <span class="text-xs text-blue-500">Nome sugerido:</span>
                        <p class="text-sm font-medium text-blue-800 bg-white p-2 rounded mt-1">{{ $analise['nome_exibicao_sugerido'] }}</p>
                    </div>

                    <!-- Formulário de Aprovação -->
                    <form action="{{ route('products.normalizar', $produto) }}" method="POST" class="mt-3 flex gap-2">
                        @csrf
                        <input type="text" name="nome_exibicao" 
                               value="{{ $analise['nome_exibicao_sugerido'] }}"
                               class="form-control text-sm flex-1">
                        <button type="submit" class="btn-success text-sm whitespace-nowrap">✅ Aprovar</button>
                    </form>
                </div>
                @elseif($produto->normalizacao_status === 'aprovado')
                <!-- Aprovado -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <span class="text-xs text-green-500 uppercase font-medium">✅ Aprovado</span>
                    <p class="text-sm font-medium text-green-800 mt-1">{{ $produto->nome_exibicao }}</p>
                    <p class="text-xs text-green-400 mt-1">{{ $produto->normalizado_em?->format('d/m/Y H:i') }}</p>
                </div>
                @else
                <!-- Sem análise -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                    <span class="text-sm text-gray-400">Análise indisponível</span>
                    <form action="{{ route('products.normalizar', $produto) }}" method="POST" class="mt-2 flex gap-2">
                        @csrf
                        <input type="text" name="nome_exibicao" value="{{ $produto->nome }}" class="form-control text-sm flex-1">
                        <button type="submit" class="btn-success text-sm">✅</button>
                    </form>
                </div>
                @endif
            </div>

            <!-- Status -->
            <div class="flex-shrink-0">
                @if(!$produto->normalizacao_status || $produto->normalizacao_status === 'pendente')
                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">Pendente</span>
                @elseif($produto->normalizacao_status === 'revisar')
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Revisar</span>
                @else
                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Aprovado</span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg p-12 text-center">
        <span class="text-6xl">🎉</span>
        <p class="text-gray-500 mt-4 text-lg">Nenhum produto {{ $status }} encontrado.</p>
    </div>
    @endforelse
</div>

<div class="mt-6">{{ $produtos->links() }}</div>
@endsection
