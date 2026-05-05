@extends('layouts.app')

@section('title', 'Ofertas')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">🏷️ Ofertas</h1>
        <p class="text-gray-600 text-sm mt-1">Acompanhe promoções e preços especiais</p>
    </div>
    <div class="flex gap-2">
        {{-- btn-outline-primary: inclui estilo base via @apply; sem prefixo 'btn' redundante --}}
        <a href="{{ route('offers.create') }}" class="btn-outline-primary text-sm">✍️ Cadastrar Manual</a>
        {{-- type="button" explícito — este botão abre um input file via JS, não submete form --}}
        <button type="button" id="btnUploadEncarte" class="btn-primary text-sm flex items-center gap-1">
            📷 Encarte
        </button>
        <form id="formUpload" action="{{ route('offers.upload-encarte') }}" method="POST"
              enctype="multipart/form-data" class="hidden">
            @csrf
            <input type="file" id="inputEncarte" name="imagem" accept="image/*">
        </form>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4" role="status">
        ✅ {{ session('success') }}
    </div>
@endif

@if($ofertas->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <span class="text-5xl" aria-hidden="true">🏷️</span>
        <p class="text-gray-500 mt-4">Nenhuma oferta cadastrada ainda.</p>
        <a href="{{ route('offers.create') }}" class="btn-primary mt-4 inline-block">✍️ Cadastrar Primeira Oferta</a>
    </div>
@else
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Produto</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Preço</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Validade</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Status</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ofertas as $oferta)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 text-sm font-medium">{{ $oferta->nome_produto }}</td>
                    <td class="py-3 px-4 text-sm text-right font-semibold text-green-600">
                        R$ {{ number_format($oferta->preco_oferta, 2, ',', '.') }}
                        @if($oferta->unidade)
                            <span class="text-gray-400 font-normal text-xs">/{{ $oferta->unidade }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-sm text-center text-gray-500">
                        {{ $oferta->validade ? $oferta->validade->format('d/m/Y') : '—' }}
                    </td>
                    <td class="py-3 px-4 text-center">
                        <form action="{{ route('offers.toggle', $oferta) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs {{ $oferta->ativo ? 'text-green-600 hover:text-green-800' : 'text-gray-400 hover:text-gray-600' }}">
                                {{ $oferta->ativo ? '✅ Ativa' : '⭕ Inativa' }}
                            </button>
                        </form>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <form action="{{ route('offers.destroy', $oferta) }}" method="POST" class="inline"
                              data-confirm="Excluir a oferta de {{ $oferta->nome_produto }}?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-400 hover:text-red-600"
                                    aria-label="Excluir oferta {{ $oferta->nome_produto }}">
                                🗑️
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<script>
    // Abre o input file ao clicar no botão de encarte
    document.getElementById('btnUploadEncarte').addEventListener('click', function () {
        document.getElementById('inputEncarte').click();
    });
    // Submete o form automaticamente após selecionar a imagem
    document.getElementById('inputEncarte').addEventListener('change', function () {
        if (this.files.length > 0) {
            document.getElementById('formUpload').submit();
        }
    });
</script>
@endsection
