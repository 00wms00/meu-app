@extends('layouts.app')

@section('title', 'Veículo: ' . $vehicle->apelido)

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $vehicle->apelido }}</h1>
        <p class="text-sm text-gray-600">
            {{ trim(($vehicle->marca . ' ' . $vehicle->modelo)) ?: 'Veículo' }}
            @if($vehicle->ano)
                &bull; {{ $vehicle->ano }}
            @endif
            @if($vehicle->placa)
                &bull; Placa {{ $vehicle->placa }}
            @endif
        </p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn-outline-primary">Editar veículo</a>
        <a href="{{ route('vehicles.index') }}" class="btn-back">← Voltar</a>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Form nova despesa --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Nova Despesa</h2>
            <form action="{{ route('vehicles.expenses.store', $vehicle) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="data" class="block text-sm font-medium text-gray-700">Data</label>
                    <input type="date" name="data" id="data" value="{{ old('data', now()->toDateString()) }}" class="form-control mt-1" required>
                </div>

                <div>
                    <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="tipo" id="tipo" class="form-control mt-1" required>
                        @php
                            $tipos = ['combustivel' => 'Combustível', 'manutencao' => 'Manutenção', 'seguro' => 'Seguro', 'impostos' => 'Impostos/IPVA', 'pedagio' => 'Pedágio/Estacionamento', 'outros' => 'Outros'];
                            $tipoOld = old('tipo');
                        @endphp
                        <option value="" disabled {{ $tipoOld ? '' : 'selected' }}>Selecione...</option>
                        @foreach($tipos as $valor => $label)
                            <option value="{{ $valor }}" {{ $tipoOld === $valor ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="valor" class="block text-sm font-medium text-gray-700">Valor</label>
                    <input type="number" step="0.01" name="valor" id="valor" value="{{ old('valor') }}" class="form-control mt-1" min="0" required>
                </div>

                <div>
                    <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                    <input type="text" name="descricao" id="descricao" value="{{ old('descricao') }}" class="form-control mt-1" placeholder="Ex: Troca de óleo, Abastecimento, Seguro anual...">
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" class="btn-primary w-full">Salvar despesa</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Lista de despesas --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Despesas</h2>
                <p class="text-sm text-gray-600">
                    Km atual: <strong>{{ number_format($vehicle->km_atual, 0, ',', '.') }} km</strong>
                </p>
            </div>

            @if($expenses->isEmpty())
                <div class="p-6 text-gray-500 text-sm">
                    Nenhuma despesa cadastrada ainda.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php
                                $labels = ['combustivel' => 'Combustível', 'manutencao' => 'Manutenção', 'seguro' => 'Seguro', 'impostos' => 'Impostos/IPVA', 'pedagio' => 'Pedágio/Estacionamento', 'outros' => 'Outros'];
                            @endphp
                            @foreach($expenses as $expense)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $expense->data->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">
                                        {{ $labels[$expense->tipo] ?? ucfirst($expense->tipo) }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $expense->descricao ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">R$ {{ number_format($expense->valor, 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right text-sm">
                                        <form action="{{ route('vehicles.expenses.destroy', [$vehicle, $expense]) }}" method="POST" class="inline" onsubmit="return confirm('Remover esta despesa?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
