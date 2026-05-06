@extends('layouts.app')

@section('title', 'Veículo: ' . $vehicle->apelido)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $vehicle->apelido }}</h1>
        <p class="text-sm text-gray-600">
            {{ trim($vehicle->marca . ' ' . $vehicle->modelo) ?: 'Veículo' }}
            @if($vehicle->ano) &bull; {{ $vehicle->ano }} @endif
            @if($vehicle->placa) &bull; Placa {{ $vehicle->placa }} @endif
            @if($vehicle->tipo_combustivel) &bull; {{ ucfirst($vehicle->tipo_combustivel) }} @endif
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
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- KPIs rápidos --}}
@php
    $totalCombust = $fuelEntries->sum('valor');
    $totalDespesas = $expenses->sum('valor');
    $totalLitros = $fuelEntries->whereNotNull('litros')->sum('litros');
    $mediaPreco = $totalLitros > 0 ? $totalCombust / $totalLitros : null;
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Km atual</p>
        <p class="text-xl font-bold text-gray-900">{{ number_format($vehicle->km_atual, 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total combustível</p>
        <p class="text-xl font-bold text-blue-700">R$ {{ number_format($totalCombust, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total despesas</p>
        <p class="text-xl font-bold text-orange-700">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Preço médio/l</p>
        <p class="text-xl font-bold text-gray-900">{{ $mediaPreco ? 'R$ ' . number_format($mediaPreco, 3, ',', '.') : '-' }}</p>
    </div>
</div>

{{-- Tabs --}}
<div x-data="{ tab: '{{ session('_tab', 'fuel') }}' }">
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-6">
            <button @click="tab = 'fuel'" :class="tab === 'fuel' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="border-b-2 pb-3 text-sm font-medium">
                ⛽ Abastecimentos
                <span class="ml-1 text-xs bg-gray-100 rounded-full px-2">{{ $fuelEntries->count() }}</span>
            </button>
            <button @click="tab = 'expenses'" :class="tab === 'expenses' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="border-b-2 pb-3 text-sm font-medium">
                🔧 Manutenção e Despesas
                <span class="ml-1 text-xs bg-gray-100 rounded-full px-2">{{ $expenses->count() }}</span>
            </button>
        </nav>
    </div>

    {{-- TAB: ABASTECIMENTOS --}}
    <div x-show="tab === 'fuel'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Formulário --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Registrar abastecimento</h2>
                    <form action="{{ route('vehicles.fuel.store', $vehicle) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data</label>
                            <input type="date" name="data" value="{{ old('data', now()->toDateString()) }}" class="form-control mt-1" required>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Valor (R$)</label>
                                <input type="number" step="0.01" name="valor" value="{{ old('valor') }}" class="form-control mt-1" min="0" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Litros</label>
                                <input type="number" step="0.001" name="litros" value="{{ old('litros') }}" class="form-control mt-1" min="0" placeholder="Ex: 40.500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Km no abastecimento</label>
                            <input type="number" name="km_abastecimento" value="{{ old('km_abastecimento', $vehicle->km_atual ?: '') }}" class="form-control mt-1" min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de combustível</label>
                            <select name="tipo_combustivel" class="form-control mt-1">
                                <option value="">Selecione...</option>
                                @php $tiposComb = ['gasolina'=>'Gasolina', 'gasolina_aditivada'=>'Gasolina Aditivada', 'etanol'=>'Etanol', 'diesel'=>'Diesel', 'gnv'=>'GNV', 'eletrico'=>'Elétrico']; @endphp
                                @foreach($tiposComb as $v => $l)
                                    <option value="{{ $v }}" {{ old('tipo_combustivel', $vehicle->tipo_combustivel) === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Posto</label>
                            <input type="text" name="posto" value="{{ old('posto') }}" class="form-control mt-1" placeholder="Nome do posto">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="tanque_cheio" id="tanque_cheio" value="1" {{ old('tanque_cheio') ? 'checked' : '' }} class="rounded">
                            <label for="tanque_cheio" class="text-sm text-gray-700">Tanque cheio</label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Obs</label>
                            <input type="text" name="descricao" value="{{ old('descricao') }}" class="form-control mt-1">
                        </div>
                        <button type="submit" class="btn-primary w-full mt-2">Salvar abastecimento</button>
                    </form>
                </div>
            </div>

            {{-- Lista --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-base font-semibold text-gray-800">Histórico de abastecimentos</h2>
                    </div>
                    @if($fuelEntries->isEmpty())
                        <div class="p-6 text-gray-500 text-sm">Nenhum abastecimento registrado ainda.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Litros</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">R$/L</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Km</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">km/L</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($fuelEntries as $entry)
                                        @php $consumo = $entry->consumoMedio(); @endphp
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-gray-700">{{ $entry->data->format('d/m/Y') }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-700 text-right">{{ $entry->litros ? number_format($entry->litros, 3, ',', '.') : '-' }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-700 text-right">
                                                @if($entry->litros && $entry->litros > 0)
                                                    {{ number_format($entry->valor / $entry->litros, 3, ',', '.') }}
                                                @else -
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-sm font-medium text-gray-900 text-right">R$ {{ number_format($entry->valor, 2, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-700 text-right">{{ $entry->km_abastecimento ? number_format($entry->km_abastecimento, 0, ',', '.') : '-' }}</td>
                                            <td class="px-3 py-2 text-sm text-right {{ $consumo ? 'text-green-700 font-medium' : 'text-gray-400' }}">
                                                {{ $consumo ? number_format($consumo, 1, ',', '.') : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-600">
                                                {{ $tiposComb[$entry->tipo_combustivel] ?? ($entry->tipo_combustivel ? ucfirst($entry->tipo_combustivel) : '-') }}
                                                @if($entry->tanque_cheio) <span class="text-xs text-blue-600">• cheio</span> @endif
                                            </td>
                                            <td class="px-3 py-2 text-right text-sm">
                                                <form action="{{ route('vehicles.fuel.destroy', [$vehicle, $entry]) }}" method="POST" class="inline" onsubmit="return confirm('Remover este abastecimento?')">
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
    </div>

    {{-- TAB: DESPESAS --}}
    <div x-show="tab === 'expenses'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Nova despesa</h2>
                    <form action="{{ route('vehicles.expenses.store', $vehicle) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data</label>
                            <input type="date" name="data" value="{{ old('data', now()->toDateString()) }}" class="form-control mt-1" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo</label>
                            <select name="tipo" class="form-control mt-1" required>
                                @php $tipos = ['manutencao'=>'Manutenção', 'seguro'=>'Seguro', 'impostos'=>'Impostos/IPVA', 'pedagio'=>'Pedágio/Estacionamento', 'outros'=>'Outros']; @endphp
                                <option value="" disabled selected>Selecione...</option>
                                @foreach($tipos as $v => $l)
                                    <option value="{{ $v }}" {{ old('tipo') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor (R$)</label>
                            <input type="number" step="0.01" name="valor" value="{{ old('valor') }}" class="form-control mt-1" min="0" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição</label>
                            <input type="text" name="descricao" value="{{ old('descricao') }}" class="form-control mt-1" placeholder="Ex: Troca de óleo, Seguro anual...">
                        </div>
                        <button type="submit" class="btn-primary w-full mt-2">Salvar despesa</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-base font-semibold text-gray-800">Histórico de despesas</h2>
                    </div>
                    @if($expenses->isEmpty())
                        <div class="p-6 text-gray-500 text-sm">Nenhuma despesa cadastrada ainda.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @php $labExp = ['manutencao'=>'Manutenção','seguro'=>'Seguro','impostos'=>'Impostos/IPVA','pedagio'=>'Pedágio/Estacionamento','outros'=>'Outros']; @endphp
                                    @foreach($expenses as $exp)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $exp->data->format('d/m/Y') }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $labExp[$exp->tipo] ?? ucfirst($exp->tipo) }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $exp->descricao ?? '-' }}</td>
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right">R$ {{ number_format($exp->valor, 2, ',', '.') }}</td>
                                            <td class="px-4 py-2 text-right text-sm">
                                                <form action="{{ route('vehicles.expenses.destroy', [$vehicle, $exp]) }}" method="POST" class="inline" onsubmit="return confirm('Remover esta despesa?')">
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
    </div>
</div>
@endsection
