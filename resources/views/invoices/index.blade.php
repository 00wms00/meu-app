@extends('layouts.app')

@section('title', 'Notas Fiscais')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">📋 Notas Fiscais</h1>
    <p class="mt-2 text-gray-600">Todas as notas importadas</p>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
            <input type="date" name="de" value="{{ request('de') }}" class="form-control">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
            <input type="date" name="ate" value="{{ request('ate') }}" class="form-control">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estabelecimento</label>
            <input type="text" name="estabelecimento" value="{{ request('estabelecimento') }}" placeholder="Buscar..." class="form-control">
        </div>
        <div class="flex items-end">
            {{-- btn-primary já inclui estilo base; class 'btn btn-primary' era redundante --}}
            <button type="submit" class="btn-primary w-full">
                🔍 Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Lista de Notas -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-semibold text-gray-800">
            @if($invoices->count() > 0)
                {{ $invoices->total() }} nota(s) encontrada(s)
            @else
                Nenhuma nota encontrada
            @endif
        </h2>
    </div>

    @if($invoices->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-6 text-sm font-semibold text-gray-700">Data</th>
                    <th class="text-left py-3 px-6 text-sm font-semibold text-gray-700">Estabelecimento</th>
                    <th class="text-left py-3 px-6 text-sm font-semibold text-gray-700">Número</th>
                    <th class="text-right py-3 px-6 text-sm font-semibold text-gray-700">Valor</th>
                    <th class="text-center py-3 px-6 text-sm font-semibold text-gray-700">Itens</th>
                    <th class="text-center py-3 px-6 text-sm font-semibold text-gray-700">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="py-4 px-6 text-sm">
                        <div class="font-medium">{{ $invoice->data_emissao->format('d/m/Y') }}</div>
                        <div class="text-gray-500 text-xs">{{ $invoice->data_emissao->format('H:i') }}</div>
                    </td>
                    <td class="py-4 px-6 text-sm">
                        {{ Str::limit($invoice->nome_estabelecimento, 30) }}
                    </td>
                    <td class="py-4 px-6 text-sm">
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                            #{{ $invoice->numero }}
                        </span>
                    </td>
                    <td class="py-4 px-6 text-sm text-right font-semibold text-green-600">
                        R$ {{ number_format($invoice->valor_pago, 2, ',', '.') }}
                    </td>
                    <td class="py-4 px-6 text-sm text-center">
                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                            {{ $invoice->total_itens }} itens
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            📋 Detalhes
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div class="p-6 border-t">
        {{ $invoices->links() }}
    </div>
    @else
    <div class="text-center py-12">
        <span class="text-6xl" aria-hidden="true">📄</span>
        <p class="text-gray-500 mt-4 text-lg">Nenhuma nota fiscal encontrada.</p>
        <a href="{{ route('import.create') }}" class="btn-primary mt-4 inline-block">
            📥 Importar NFC-e
        </a>
    </div>
    @endif
</div>
@endsection
