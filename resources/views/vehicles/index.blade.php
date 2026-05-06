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
    <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
        Nenhum veículo cadastrado ainda.
    </div>
@else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apelido</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modelo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placa</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Km atual</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($vehicles as $vehicle)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $vehicle->apelido }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            {{ trim(($vehicle->marca . ' ' . $vehicle->modelo)) ?: '-' }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $vehicle->ano ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $vehicle->placa ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($vehicle->km_atual, 0, ',', '.') }} km</td>
                        <td class="px-4 py-2 text-right text-sm">
                            <a href="{{ route('vehicles.edit', $vehicle) }}" class="text-blue-600 hover:underline mr-3">Editar</a>
                            <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Remover o veículo {{ $vehicle->apelido }}?');">
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
