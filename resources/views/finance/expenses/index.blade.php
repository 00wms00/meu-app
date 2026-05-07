@extends('layouts.app')

@section('title', 'Despesas')

@section('content')

{{-- Cabeçalho --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">📋 Despesas</h1>
        <p class="text-sm text-gray-500 mt-0.5">Fixas e variáveis &mdash;
            <span class="font-medium text-gray-700">{{ $mes->translatedFormat('F \d\e Y') }}</span>
        </p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn-back self-start sm:self-auto">← Dashboard</a>
</div>

{{-- Navegação por mês --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="flex flex-wrap gap-2 items-center">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1">Mês:</span>
        @foreach($meses as $m)
            <a href="{{ route('finance.expenses.index', ['mes' => $m->format('Y-m')]) }}"
               class="px-3 py-1 text-xs rounded-full border transition
                      {{ $mes->format('Y-m') === $m->format('Y-m')
                         ? 'bg-red-600 text-white border-red-600 font-semibold'
                         : 'border-gray-200 text-gray-500 hover:border-red-400 hover:text-red-700' }}">
                {{ $m->translatedFormat('M/y') }}
            </a>
        @endforeach
    </div>
</div>

{{-- KPIs --}}
<div style="display:grid; grid-template-columns:repeat(5,1fr); gap:1rem; margin-bottom:1.5rem;">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total geral</p>
        <p class="text-xl font-bold text-gray-900">R$ {{ number_format($totalGeral, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">🔒 Fixas</p>
        <p class="text-xl font-bold text-orange-600">R$ {{ number_format($totalFixas, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">🎯 Variáveis</p>
        <p class="text-xl font-bold text-blue-600">R$ {{ number_format($totalVariaveis, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">✅ Pago</p>
        <p class="text-xl font-bold text-green-600">R$ {{ number_format($totalPago, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">⏳ Pendente</p>
        <p class="text-xl font-bold text-yellow-600">R$ {{ number_format($totalPendente, 2, ',', '.') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LISTAS DE DESPESAS --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- ===== DESPESAS FIXAS ===== --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between bg-orange-50">
                <h2 class="text-base font-semibold text-orange-800">🔒 Despesas Fixas
                    <span class="ml-2 text-sm font-normal text-orange-600">
                        ({{ $fixas->count() }} &mdash; R$ {{ number_format($totalFixas, 2, ',', '.') }})
                    </span>
                </h2>
                <form method="POST" action="{{ route('finance.expenses.duplicar') }}">
                    @csrf
                    <input type="hidden" name="mes" value="{{ $mes->format('Y-m') }}">
                    <button type="submit"
                            onclick="return confirm('Duplicar despesas fixas do mês anterior?')"
                            class="text-xs px-3 py-1.5 rounded border border-orange-300 text-orange-700 hover:bg-orange-100 transition">
                        ↻ Importar fixas
                    </button>
                </form>
            </div>

            @if($fixas->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">Nenhuma despesa fixa neste mês.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($fixas as $expense)
                        @include('finance.expenses._row', ['expense' => $expense, 'mes' => $mes])
                    @endforeach
                </div>
                <div class="px-5 py-3 bg-orange-50 border-t border-orange-100 flex justify-between">
                    <span class="text-sm font-semibold text-gray-700">Subtotal fixas</span>
                    <span class="text-sm font-bold text-orange-700 tabular-nums">R$ {{ number_format($totalFixas, 2, ',', '.') }}</span>
                </div>
            @endif
        </div>

        {{-- ===== DESPESAS VARIÁVEIS ===== --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between bg-blue-50">
                <h2 class="text-base font-semibold text-blue-800">🎯 Despesas Variáveis
                    <span class="ml-2 text-sm font-normal text-blue-600">
                        ({{ $variaveis->count() }} &mdash; R$ {{ number_format($totalVariaveis, 2, ',', '.') }})
                    </span>
                </h2>
            </div>

            {{-- Por categoria --}}
            @if($porCategoria->isNotEmpty())
                <div class="px-5 py-3 bg-gray-50 border-b flex flex-wrap gap-2">
                    @foreach($porCategoria as $cat => $val)
                        <span class="text-xs px-2 py-1 rounded-full bg-white border border-gray-200 text-gray-600">
                            {{ $cat ?: 'Sem categoria' }}: <strong>R$ {{ number_format($val, 2, ',', '.') }}</strong>
                        </span>
                    @endforeach
                </div>
            @endif

            @if($variaveis->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">Nenhuma despesa variável neste mês.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($variaveis as $expense)
                        @include('finance.expenses._row', ['expense' => $expense, 'mes' => $mes])
                    @endforeach
                </div>
                <div class="px-5 py-3 bg-blue-50 border-t border-blue-100 flex justify-between">
                    <span class="text-sm font-semibold text-gray-700">Subtotal variáveis</span>
                    <span class="text-sm font-bold text-blue-700 tabular-nums">R$ {{ number_format($totalVariaveis, 2, ',', '.') }}</span>
                </div>
            @endif
        </div>

    </div>

    {{-- FORMULÁRIO NOVA DESPESA --}}
    <div>
        <div class="bg-white rounded-lg shadow p-5 sticky top-4">
            <h2 class="text-base font-semibold text-gray-800 mb-4">➕ Nova Despesa</h2>
            <form method="POST" action="{{ route('finance.expenses.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Descrição *</label>
                    <input type="text" name="descricao" required placeholder="Ex: Aluguel, Netflix..."
                           value="{{ old('descricao') }}" class="form-control text-sm w-full">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo *</label>
                        <select name="tipo_despesa" required class="form-control text-sm w-full">
                            <option value="fixa" {{ old('tipo_despesa')==='fixa'?'selected':'' }}>Fixa</option>
                            <option value="variavel" {{ old('tipo_despesa','variavel')==='variavel'?'selected':'' }}>Variável</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Categoria</label>
                        <input type="text" name="categoria" placeholder="Ex: Moradia, Lazer"
                               value="{{ old('categoria') }}" class="form-control text-sm w-full"
                               list="categorias-list">
                        <datalist id="categorias-list">
                            <option value="Moradia"><option value="Alimentação">
                            <option value="Transporte"><option value="Saúde">
                            <option value="Educação"><option value="Lazer">
                            <option value="Roupas"><option value="Assinaturas">
                            <option value="Carro"><option value="Outros">
                        </datalist>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Valor (R$) *</label>
                        <input type="number" name="valor" step="0.01" min="0.01" required
                               placeholder="0,00" value="{{ old('valor') }}" class="form-control text-sm w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Mês *</label>
                        <input type="month" name="mes_referencia" required
                               value="{{ old('mes_referencia', $mes->format('Y-m')) }}"
                               class="form-control text-sm w-full">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Pessoa *</label>
                        <select name="pessoa" required class="form-control text-sm w-full">
                            <option value="WIL" {{ old('pessoa')==='WIL'?'selected':'' }}>Willian</option>
                            <option value="MAY" {{ old('pessoa')==='MAY'?'selected':'' }}>Mayara</option>
                            <option value="compartilhado" {{ old('pessoa')==='compartilhado'?'selected':'' }}>Compartilhado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Pagamento *</label>
                        <select name="forma_pagamento" required class="form-control text-sm w-full">
                            <option value="pix" {{ old('forma_pagamento','pix')==='pix'?'selected':'' }}>Pix</option>
                            <option value="debito" {{ old('forma_pagamento')==='debito'?'selected':'' }}>Débito</option>
                            <option value="dinheiro" {{ old('forma_pagamento')==='dinheiro'?'selected':'' }}>Dinheiro</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Vencimento</label>
                        <input type="date" name="data_vencimento"
                               value="{{ old('data_vencimento') }}" class="form-control text-sm w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status *</label>
                        <select name="status" required class="form-control text-sm w-full">
                            <option value="pendente" {{ old('status','pendente')==='pendente'?'selected':'' }}>Pendente</option>
                            <option value="pago" {{ old('status')==='pago'?'selected':'' }}>Pago</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Data pagamento</label>
                    <input type="date" name="data_pagamento"
                           value="{{ old('data_pagamento') }}" class="form-control text-sm w-full">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observação</label>
                    <input type="text" name="observacao" placeholder="Opcional"
                           value="{{ old('observacao') }}" class="form-control text-sm w-full">
                </div>

                @if($errors->any())
                    <div class="text-xs text-red-600">
                        @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
                    </div>
                @endif

                <button type="submit" class="btn-primary w-full py-2 text-sm">Adicionar Despesa</button>
            </form>
        </div>
    </div>

</div>
@endsection
