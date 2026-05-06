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

{{-- KPIs --}}
@php
    $totalCombust  = $fuelEntries->sum('valor');
    $totalDespesas = $expenses->sum('valor');
    $totalLitros   = $fuelEntries->whereNotNull('litros')->sum('litros');
    $mediaPreco    = $totalLitros > 0 ? $totalCombust / $totalLitros : null;
    $mediaCustoKm  = count($chartConsumo)
        ? round(array_sum(array_column($chartConsumo, 'custo_km')) / count($chartConsumo), 3)
        : null;

    $temVencido = $reminders->contains(fn($r) => $r->statusAlerta($vehicle->km_atual) === 'vencido');
    $temProximo = $reminders->contains(fn($r) => $r->statusAlerta($vehicle->km_atual) === 'proximo');
@endphp
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Km atual</p>
        <p class="text-xl font-bold text-gray-900">{{ number_format($vehicle->km_atual ?? 0, 0, ',', '.') }}</p>
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
        <p class="text-xs text-gray-500 uppercase tracking-wide">Preço médio/L</p>
        <p class="text-xl font-bold text-gray-900">{{ $mediaPreco ? 'R$ ' . number_format($mediaPreco, 3, ',', '.') : '-' }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Consumo médio</p>
        <p class="text-xl font-bold {{ $consumoMedioGeral ? 'text-green-700' : 'text-gray-400' }}">
            {{ $consumoMedioGeral ? number_format($consumoMedioGeral, 1, ',', '.') . ' km/L' : '-' }}
        </p>
    </div>
</div>

@if($mediaCustoKm)
<div class="mb-6">
    <div class="inline-flex items-center gap-3 bg-white rounded-lg shadow px-5 py-3">
        <span class="text-xs text-gray-500 uppercase tracking-wide">Custo médio por km</span>
        <span class="text-xl font-bold text-purple-700">R$ {{ number_format($mediaCustoKm, 3, ',', '.') }}/km</span>
        <span class="text-xs text-gray-400">(só combustível)</span>
    </div>
</div>
@endif

{{-- Tabs --}}
<div x-data="{ tab: '{{ (session('_fragment') === 'reminders' || $errors->isNotEmpty()) ? 'reminders' : 'fuel' }}' }">
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
            <button @click="tab = 'reminders'" :class="tab === 'reminders' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="border-b-2 pb-3 text-sm font-medium flex items-center gap-1">
                🔔 Lembretes
                <span class="ml-1 text-xs bg-gray-100 rounded-full px-2">{{ $reminders->count() }}</span>
                @if($temVencido)
                    <span class="inline-block w-2 h-2 rounded-full bg-red-600 ml-1" title="Lembrete vencido"></span>
                @elseif($temProximo)
                    <span class="inline-block w-2 h-2 rounded-full bg-yellow-400 ml-1" title="Lembrete próximo"></span>
                @endif
            </button>
        </nav>
    </div>

    {{-- TAB: ABASTECIMENTOS --}}
    <div x-show="tab === 'fuel'" id="fuel">

        @if(count($chartConsumo) >= 2)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">📈 Consumo (km/L)</h2>
                <div style="position:relative; height:200px">
                    <canvas id="chartConsumo"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">💰 Custo por km (R$/km)</h2>
                <div style="position:relative; height:200px">
                    <canvas id="chartCusto"></canvas>
                </div>
            </div>
        </div>
        @elseif(count($chartConsumo) === 1)
        <div class="mb-4 text-sm text-gray-500 bg-gray-50 border rounded px-4 py-3">
            📊 Registre mais 1 abastecimento com KM para ver os gráficos de consumo e custo.
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                            <label class="block text-sm font-medium text-gray-700">KM no abastecimento</label>
                            <input type="number" name="km_abastecimento" value="{{ old('km_abastecimento', $vehicle->km_atual ?: '') }}" class="form-control mt-1" min="0" placeholder="Ex: 45230">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de combustível</label>
                            <select name="tipo_combustivel" class="form-control mt-1">
                                <option value="">Selecione...</option>
                                @php $tiposComb = ['gasolina'=>'Gasolina','gasolina_aditivada'=>'Gasolina Aditivada','etanol'=>'Etanol','diesel'=>'Diesel','gnv'=>'GNV','eletrico'=>'Elétrico']; @endphp
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
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">KM</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">km/L</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">R$/km</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @php
                                        $allFuelAsc = $fuelEntries->sortBy('id');
                                        $custoKmPorId = collect($chartConsumo)->keyBy('entry_id');
                                    @endphp
                                    @foreach($fuelEntries as $entry)
                                        @php
                                            $consumo  = $entry->consumoMedio($allFuelAsc);
                                            $custoKm  = $custoKmPorId->get($entry->id)['custo_km'] ?? null;
                                        @endphp
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
                                            <td class="px-3 py-2 text-sm text-right" x-data="{ editing: false }">
                                                <span x-show="!editing" class="cursor-pointer group">
                                                    <span @click="editing = true"
                                                          class="tabular-nums {{ $entry->km_abastecimento ? '' : 'text-gray-400 italic' }}"
                                                          title="Clique para editar">
                                                        {{ $entry->km_abastecimento ? number_format($entry->km_abastecimento, 0, ',', '.') : '—' }}
                                                    </span>
                                                    <span @click="editing = true" class="ml-1 text-gray-300 group-hover:text-blue-400 text-xs" title="Editar KM">✏️</span>
                                                </span>
                                                <form x-show="editing"
                                                      action="{{ route('vehicles.fuel.updateKm', [$vehicle, $entry]) }}"
                                                      method="POST"
                                                      class="inline-flex items-center gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="number"
                                                           name="km_abastecimento"
                                                           value="{{ $entry->km_abastecimento }}"
                                                           min="0"
                                                           class="w-24 border border-blue-400 rounded px-1 py-0.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-blue-400"
                                                           @keydown.escape="editing = false"
                                                           x-ref="kmInput"
                                                           x-init="$watch('editing', v => v && $nextTick(() => $refs.kmInput.focus()))"
                                                    >
                                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm" title="Salvar">✔</button>
                                                    <button type="button" @click="editing = false" class="text-gray-400 hover:text-gray-600 text-sm" title="Cancelar">✕</button>
                                                </form>
                                            </td>
                                            <td class="px-3 py-2 text-sm text-right {{ $consumo ? 'text-green-700 font-semibold' : 'text-gray-400' }}">
                                                {{ $consumo ? number_format($consumo, 1, ',', '.') : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-sm text-right {{ $custoKm ? 'text-purple-700 font-semibold' : 'text-gray-400' }}">
                                                {{ $custoKm ? number_format($custoKm, 3, ',', '.') : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-600">
                                                {{ $tiposComb[$entry->tipo_combustivel] ?? ($entry->tipo_combustivel ? ucfirst($entry->tipo_combustivel) : '-') }}
                                                @if($entry->tanque_cheio) <span class="text-xs text-blue-600">&bull; cheio</span> @endif
                                                @if($entry->posto) <span class="text-xs text-gray-400 block">{{ $entry->posto }}</span> @endif
                                            </td>
                                            <td class="px-3 py-2 text-right text-sm">
                                                <form action="{{ route('vehicles.fuel.destroy', [$vehicle, $entry]) }}" method="POST" class="inline" onsubmit="return confirm('Remover este abastecimento?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remover</button>
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

            {{-- Formulário com km_servico + lembrete integrado --}}
            <div class="lg:col-span-1"
                 x-data="{
                     tipo: '{{ old('tipo', '') }}',
                     criarLembrete: {{ old('criar_lembrete') ? 'true' : 'false' }},
                 }">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Nova despesa</h2>

                    <form action="{{ route('vehicles.expenses.store', $vehicle) }}" method="POST" class="space-y-3">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data</label>
                            <input type="date" name="data"
                                   value="{{ old('data', now()->toDateString()) }}"
                                   class="form-control mt-1" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo</label>
                            <select name="tipo" class="form-control mt-1" required x-model="tipo">
                                @php $tipos = ['manutencao'=>'Manutenção','seguro'=>'Seguro','impostos'=>'Impostos/IPVA','pedagio'=>'Pedágio/Estacionamento','outros'=>'Outros']; @endphp
                                <option value="" disabled>Selecione...</option>
                                @foreach($tipos as $v => $l)
                                    <option value="{{ $v }}" {{ old('tipo') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor (R$)</label>
                            <input type="number" step="0.01" name="valor"
                                   value="{{ old('valor') }}"
                                   class="form-control mt-1" min="0" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição</label>
                            <input type="text" name="descricao"
                                   value="{{ old('descricao') }}"
                                   class="form-control mt-1"
                                   placeholder="Ex: Troca de óleo, Seguro anual...">
                        </div>

                        {{-- KM do serviço — só aparece para manutenção --}}
                        <div x-show="tipo === 'manutencao'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700">KM no serviço</label>
                            <input type="number" name="km_servico"
                                   value="{{ old('km_servico', $vehicle->km_atual ?: '') }}"
                                   class="form-control mt-1" min="0"
                                   placeholder="Ex: 45.000">
                            <p class="text-xs text-gray-400 mt-1">Atualiza o hodômetro do veículo automaticamente.</p>
                        </div>

                        {{-- Toggle lembrete — só para manutenção --}}
                        <div x-show="tipo === 'manutencao'" x-cloak>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="criar_lembrete" value="1"
                                       x-model="criarLembrete"
                                       class="rounded text-blue-600">
                                <span class="text-sm font-medium text-gray-700">🔔 Criar lembrete para este serviço</span>
                            </label>
                        </div>

                        {{-- Painel do lembrete embutido --}}
                        <div x-show="tipo === 'manutencao' && criarLembrete" x-cloak
                             class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">

                            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">🔔 Lembrete integrado</p>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Descrição do lembrete</label>
                                <input type="text" name="lembrete_descricao"
                                       value="{{ old('lembrete_descricao') }}"
                                       class="form-control mt-1" maxlength="120"
                                       placeholder="Deixe em branco para usar a descrição da despesa">
                            </div>

                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Alertar novamente a cada…</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Km</label>
                                    <input type="number" name="lembrete_intervalo_km"
                                           value="{{ old('lembrete_intervalo_km') }}"
                                           class="form-control mt-1" min="100" max="200000"
                                           placeholder="Ex: 10000">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Meses</label>
                                    <input type="number" name="lembrete_intervalo_meses"
                                           value="{{ old('lembrete_intervalo_meses') }}"
                                           class="form-control mt-1" min="1" max="120"
                                           placeholder="Ex: 12">
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Preencha ao menos um. Ambos podem ser usados juntos.</p>
                        </div>

                        <button type="submit" class="btn-primary w-full mt-2">Salvar despesa</button>
                    </form>
                </div>
            </div>

            {{-- Histórico de despesas --}}
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
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">KM</th>
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
                                            <td class="px-4 py-2 text-sm text-gray-500 text-right tabular-nums">
                                                {{ $exp->km_servico ? number_format($exp->km_servico, 0, ',', '.') . ' km' : '-' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right">R$ {{ number_format($exp->valor, 2, ',', '.') }}</td>
                                            <td class="px-4 py-2 text-right text-sm">
                                                <form action="{{ route('vehicles.expenses.destroy', [$vehicle, $exp]) }}" method="POST" class="inline" onsubmit="return confirm('Remover esta despesa?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remover</button>
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

    {{-- TAB: LEMBRETES --}}
    <div x-show="tab === 'reminders'" id="reminders">
        @include('vehicles._reminders_tab')
    </div>

</div>

{{-- Chart.js --}}
@if(count($chartConsumo) >= 2)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pontos  = @json($chartConsumo);
    const labels  = pontos.map(p => p.label);

    function mediaLinha(arr) {
        const m = arr.reduce((a, b) => a + b, 0) / arr.length;
        return arr.map(() => parseFloat(m.toFixed(3)));
    }
    function formatBR(n, decimais) {
        return n.toFixed(decimais).replace('.', ',');
    }

    const consumos = pontos.map(p => p.consumo);
    new Chart(document.getElementById('chartConsumo').getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'km/L',
                    data: consumos,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#16a34a',
                    pointRadius: 5,
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Média (' + formatBR(consumos.reduce((a,b)=>a+b,0)/consumos.length, 1) + ' km/L)',
                    data: mediaLinha(consumos),
                    borderColor: '#f59e0b',
                    borderWidth: 1.5,
                    borderDash: [6, 3],
                    pointRadius: 0,
                    fill: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        afterBody(items) {
                            const p = pontos[items[0].dataIndex];
                            return [
                                'Litros: ' + formatBR(p.litros, 3),
                                'Valor: R$ ' + formatBR(p.valor, 2),
                                'KM: ' + p.km.toLocaleString('pt-BR'),
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'km/L', font: { size: 11 } },
                    ticks: { font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { ticks: { font: { size: 11 } } }
            }
        }
    });

    const custos = pontos.map(p => p.custo_km);
    new Chart(document.getElementById('chartCusto').getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'R$/km',
                    data: custos,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124,58,237,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#7c3aed',
                    pointRadius: 5,
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Média (R$ ' + formatBR(custos.reduce((a,b)=>a+b,0)/custos.length, 3) + '/km)',
                    data: mediaLinha(custos),
                    borderColor: '#f59e0b',
                    borderWidth: 1.5,
                    borderDash: [6, 3],
                    pointRadius: 0,
                    fill: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        afterBody(items) {
                            const p = pontos[items[0].dataIndex];
                            return [
                                'Valor: R$ ' + formatBR(p.valor, 2),
                                'km rodados: ' + (p.km_rodados ?? '-'),
                                'km/L: ' + formatBR(p.consumo, 1),
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'R$/km', font: { size: 11 } },
                    ticks: { font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { ticks: { font: { size: 11 } } }
            }
        }
    });
});
</script>
@endif
@endsection
