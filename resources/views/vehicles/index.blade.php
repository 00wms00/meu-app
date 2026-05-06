@extends('layouts.app')

@section('title', 'Veículos')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold text-gray-900">Veículos</h1>
    <a href="{{ route('vehicles.create') }}" class="btn-primary">+ Novo Veículo</a>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

@if($vehicles->isEmpty())
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500 mb-4">Nenhum veículo cadastrado ainda.</p>
        <a href="{{ route('vehicles.create') }}" class="btn-primary">Cadastrar primeiro veículo</a>
    </div>
@else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veículo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Combustível</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Km atual</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Alertas</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($vehicles as $vehicle)
                    @php
                        $veicReminders = $reminders->get($vehicle->id, collect());
                        $temVencido    = $veicReminders->contains(fn($r) => $r->statusAlerta($vehicle->km_atual) === 'vencido');
                        $temProximo    = $veicReminders->contains(fn($r) => $r->statusAlerta($vehicle->km_atual) === 'proximo');
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('vehicles.show', $vehicle) }}"
                               class="text-sm font-semibold text-blue-700 hover:underline">
                                {{ $vehicle->apelido }}
                            </a>
                            @if($vehicle->marca || $vehicle->modelo)
                                <p class="text-xs text-gray-400">
                                    {{ trim($vehicle->marca . ' ' . $vehicle->modelo) }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $vehicle->ano ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $vehicle->placa ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($vehicle->tipo_combustivel ?? '-') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 text-right tabular-nums">
                            {{ number_format($vehicle->km_atual ?? 0, 0, ',', '.') }} km
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($temVencido)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-white bg-red-600 rounded-full px-2 py-0.5">
                                    🔴 Vencido
                                </span>
                            @elseif($temProximo)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-yellow-800 bg-yellow-200 rounded-full px-2 py-0.5">
                                    ⚠️ Próximo
                                </span>
                            @elseif($veicReminders->isNotEmpty())
                                <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-100 rounded-full px-2 py-0.5">
                                    ✅ Em dia
                                </span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                            <a href="{{ route('vehicles.show', $vehicle) }}"
                               class="text-blue-600 hover:underline mr-3">Painel</a>
                            <a href="{{ route('vehicles.edit', $vehicle) }}"
                               class="text-gray-500 hover:underline mr-3">Editar</a>
                            <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Remover o veículo {{ addslashes($vehicle->apelido) }}? Esta ação também apaga todos os abastecimentos e despesas vinculados.')">
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
@endsection
