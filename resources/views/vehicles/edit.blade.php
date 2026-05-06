@extends('layouts.app')

@section('title', 'Editar Veículo')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-4">Editar Veículo</h1>

@if ($errors->any())
    <div class="mb-4 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('vehicles.update', $vehicle) }}" method="POST" class="bg-white rounded-lg shadow p-6 max-w-xl">
    @csrf
    @method('PUT')

    <div class="mb-4">
        <label for="apelido" class="block text-sm font-medium text-gray-700">Apelido *</label>
        <input type="text" name="apelido" id="apelido" value="{{ old('apelido', $vehicle->apelido) }}"
               class="form-control mt-1" required>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="marca" class="block text-sm font-medium text-gray-700">Marca</label>
            <input type="text" name="marca" id="marca" value="{{ old('marca', $vehicle->marca) }}" class="form-control mt-1">
        </div>
        <div>
            <label for="modelo" class="block text-sm font-medium text-gray-700">Modelo</label>
            <input type="text" name="modelo" id="modelo" value="{{ old('modelo', $vehicle->modelo) }}" class="form-control mt-1">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
            <label for="ano" class="block text-sm font-medium text-gray-700">Ano</label>
            <input type="number" name="ano" id="ano" value="{{ old('ano', $vehicle->ano) }}" class="form-control mt-1" min="1950" max="{{ now()->year }}">
        </div>
        <div>
            <label for="placa" class="block text-sm font-medium text-gray-700">Placa</label>
            <input type="text" name="placa" id="placa" value="{{ old('placa', $vehicle->placa) }}" class="form-control mt-1" maxlength="10">
        </div>
        <div>
            <label for="tipo_combustivel" class="block text-sm font-medium text-gray-700">Combustível</label>
            <input type="text" name="tipo_combustivel" id="tipo_combustivel" value="{{ old('tipo_combustivel', $vehicle->tipo_combustivel) }}" class="form-control mt-1" placeholder="Gasolina, Etanol, Flex...">
        </div>
    </div>

    <div class="mt-4">
        <label for="km_atual" class="block text-sm font-medium text-gray-700">Km atual</label>
        <input type="number" name="km_atual" id="km_atual" value="{{ old('km_atual', $vehicle->km_atual) }}" class="form-control mt-1" min="0">
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('vehicles.index') }}" class="btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">Salvar</button>
    </div>
</form>
@endsection
