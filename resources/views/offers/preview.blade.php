@extends('layouts.app')

@section('title', 'Revisar Encarte')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">🤖 Revisar Extração do Encarte</h1>
    <p class="mt-1 text-gray-600">
        Estabelecimento: <strong>{{ $data['estabelecimento'] ?: 'Não identificado' }}</strong> · 
        {{ $data['total_produtos'] }} produtos encontrados
    </p>
    @if($data['validade_texto'])
    <p class="text-xs text-gray-400 mt-1">📅 {{ $data['validade_texto'] }}</p>
    @endif
</div>

<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
    <p class="text-sm text-yellow-700">⚠️ Revise os dados extraídos pela IA. Corrija se necessário antes de salvar.</p>
</div>

<form action="{{ route('offers.save-preview') }}" method="POST">
    @csrf
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs">Produto</th>
                        <th class="px-4 py-2 text-right text-xs w-24">Preço</th>
                        <th class="px-4 py-2 text-center text-xs w-16">Qtd</th>
                        <th class="px-4 py-2 text-center text-xs w-16">Unid</th>
                        <th class="px-4 py-2 text-left text-xs">Obs</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($data['produtos'] as $i => $produto)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <input type="text" name="produtos[{{ $i }}][nome]" value="{{ $produto['nome'] ?? '' }}" class="w-full border-0 border-b border-gray-200 text-sm">
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" name="produtos[{{ $i }}][preco]" value="{{ $produto['preco'] ?? '' }}" class="w-20 text-right border-0 border-b border-gray-200 text-sm font-bold text-green-600">
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" name="produtos[{{ $i }}][quantidade]" value="{{ $produto['quantidade'] ?? '1' }}" class="w-12 text-center border-0 border-b border-gray-200 text-sm">
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" name="produtos[{{ $i }}][unidade]" value="{{ $produto['unidade'] ?? 'UN' }}" class="w-14 text-center border-0 border-b border-gray-200 text-sm">
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" name="produtos[{{ $i }}][observacao]" value="{{ $produto['observacao'] ?? '' }}" class="w-full border-0 border-b border-gray-200 text-xs text-gray-400">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <input type="hidden" name="estabelecimento" value="{{ $data['estabelecimento'] }}">
    <input type="hidden" name="fonte" value="Encarte IA">

    <div class="flex gap-3 justify-end">
        <a href="{{ route('offers.index') }}" class="btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">💾 Salvar Ofertas</button>
    </div>
</form>
@endsection
