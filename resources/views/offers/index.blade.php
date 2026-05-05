@extends('layouts.app')

@section('title', 'Ofertas e Encartes')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">🏷️ Ofertas e Encartes</h1>
        <p class="mt-1 text-gray-600">Compare preços e economize</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('offers.create') }}" class="btn-outline-primary text-sm">✍️ Cadastrar Manual</a>
        <button onclick="document.getElementById('uploadEncarte').click()" class="btn-primary text-sm flex items-center gap-1">
            🤖 Upload Encarte (IA)
        </button>
        <form id="formUpload" action="{{ route('offers.upload-encarte') }}" method="POST" enctype="multipart/form-data" class="hidden">
            @csrf
            <input type="file" name="encarte" id="uploadEncarte" accept="image/*" onchange="document.getElementById('formUpload').submit()">
        </form>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">✅ {{ session('success') }}</div>
@endif

@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
    @foreach($errors->all() as $e) ❌ {{ $e }}<br> @endforeach
</div>
@endif

@forelse($ofertas as $estab => $ofertasEstab)
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b bg-gray-50 rounded-t-lg">
        <h2 class="font-semibold text-gray-800">🏪 {{ $estab }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs">Produto</th><th class="px-4 py-2 text-right text-xs">Preço</th><th class="px-4 py-2 text-center text-xs">Unid.</th><th class="px-4 py-2 text-center text-xs">Validade</th><th class="px-4 py-2 text-center text-xs">Ações</th></tr></thead>
            <tbody class="divide-y">
                @foreach($ofertasEstab as $oferta)
                <tr class="hover:bg-gray-50 {{ !$oferta->ativa ? 'opacity-50' : '' }}">
                    <td class="px-4 py-2 text-sm">{{ $oferta->nome_produto }}</td>
                    <td class="px-4 py-2 text-sm text-right font-bold text-green-600">R$ {{ number_format($oferta->preco_oferta, 2, ',', '.') }}</td>
                    <td class="px-4 py-2 text-sm text-center">{{ $oferta->unidade }}</td>
                    <td class="px-4 py-2 text-xs text-center">{{ $oferta->validade_fim?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">
                        <form action="{{ route('offers.toggle', $oferta) }}" method="POST" class="inline">@csrf<button type="submit" class="text-xs">{{ $oferta->ativa ? '👁️' : '👁️‍🗨️' }}</button></form>
                        <form action="{{ route('offers.destroy', $oferta) }}" method="POST" class="inline">@csrf @method('DELETE')<button type="submit" class="text-xs text-red-400" onclick="return confirm('Excluir?')">🗑️</button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="bg-white rounded-lg p-12 text-center"><span class="text-6xl">🏷️</span><p class="text-gray-500 mt-4">Nenhuma oferta cadastrada.</p><p class="text-sm text-gray-400">Faça upload de um encarte ou cadastre manualmente.</p></div>
@endforelse
@endsection
