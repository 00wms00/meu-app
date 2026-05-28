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
<div style="display:grid; grid-template-columns:repeat(6,1fr); gap:1rem; margin-bottom:1.5rem;">
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
        <p class="text-xs text-gray-500 uppercase tracking-wide">💳 Pago CC</p>
        <p class="text-xl font-bold text-indigo-600">R$ {{ number_format($totalPagoCC, 2, ',', '.') }}</p>
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

        {{-- DESPESAS FIXAS --}}
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
                    <button type="submit" onclick="return confirm('Duplicar despesas fixas do mês anterior?')"
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
                        @include('finance.expenses._row', [
                            'expense'           => $expense,
                            'mes'               => $mes,
                            'creditCards'       => $creditCards,
                            'expenseCategories' => $expenseCategories,
                        ])
                    @endforeach
                </div>
                <div class="px-5 py-3 bg-orange-50 border-t border-orange-100 flex justify-between">
                    <span class="text-sm font-semibold text-gray-700">Subtotal fixas</span>
                    <span class="text-sm font-bold text-orange-700 tabular-nums">R$ {{ number_format($totalFixas, 2, ',', '.') }}</span>
                </div>
            @endif
        </div>

        {{-- DESPESAS VARIÁVEIS --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between bg-blue-50">
                <h2 class="text-base font-semibold text-blue-800">🎯 Despesas Variáveis
                    <span class="ml-2 text-sm font-normal text-blue-600">
                        (R$ {{ number_format($totalVariaveis, 2, ',', '.') }})
                    </span>
                </h2>
            </div>

            {{-- Badges de categoria com cor + emoji corretos --}}
            @if($porCategoria->isNotEmpty())
                <div class="px-5 py-3 bg-gray-50 border-b flex flex-wrap gap-2">
                    @foreach($porCategoria as $cat => $val)
                        @php
                            $catObj = $expenseCategories->firstWhere('nome', $cat);
                            $cor    = $catObj ? '#' . ltrim($catObj->cor, '#') : '#6b7280';
                            $emoji  = $catObj ? ($catObj->emoji ?? '') : '';
                        @endphp
                        <span class="text-xs px-2 py-1 rounded-full bg-white border text-gray-700 flex items-center gap-1"
                              style="border-color:{{ $cor }}33; background:{{ $cor }}18;">
                            @if($emoji)<span>{{ $emoji }}</span>@endif
                            <span style="color:{{ $cor }}">{{ $cat ?: 'Sem categoria' }}</span>
                            <strong>R$ {{ number_format($val, 2, ',', '.') }}</strong>
                        </span>
                    @endforeach
                </div>
            @endif

            @if($variaveis->isEmpty() && $invoicesDoMes->isEmpty() && $vehicleExpensesDoMes->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">Nenhuma despesa variável neste mês.</div>
            @else
                {{-- Despesas Variáveis Manuais --}}
                @if($variaveis->isNotEmpty())
                    <div class="divide-y divide-gray-100">
                        @foreach($variaveis as $expense)
                            @include('finance.expenses._row', [
                                'expense'           => $expense,
                                'mes'               => $mes,
                                'creditCards'       => $creditCards,
                                'expenseCategories' => $expenseCategories,
                            ])
                        @endforeach
                    </div>
                @endif

                {{-- Mercado (acordeon) --}}
                @if($invoicesDoMes->isNotEmpty())
                    <div class="border-t border-gray-200" x-data="{ openMercado: false }">
                        <div class="px-5 py-3 bg-green-50 flex items-center justify-between cursor-pointer select-none hover:bg-green-100 transition" @click="openMercado = !openMercado">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-green-700 uppercase tracking-wide">🛒 Mercado &mdash; notas importadas</span>
                                <span class="text-xs text-green-600 bg-green-200 rounded-full px-2">{{ $invoicesDoMes->count() }} nota(s)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-green-700 tabular-nums">R$ {{ number_format($totalMercado, 2, ',', '.') }}</span>
                                <svg class="w-4 h-4 text-green-600 transition-transform duration-200" :class="openMercado ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <div x-show="openMercado" x-cloak>
                            @foreach($invoicesDoMes as $inv)
                                <div class="px-5 py-3 flex items-center gap-3 border-t border-gray-50 hover:bg-green-50/30">
                                    <span class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-xs shrink-0">🛒</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $inv['descricao'] }}</p>
                                        <p class="text-xs text-gray-400">{{ $inv['quantidade'] }} nota(s) &bull; Mercado / Alimentação</p>
                                    </div>
                                    <span class="text-sm font-bold tabular-nums text-green-700 whitespace-nowrap">R$ {{ number_format($inv['valor'], 2, ',', '.') }}</span>
                                    <span class="text-xs text-gray-300 bg-gray-100 px-2 py-0.5 rounded-full">auto</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Veículos (acordeon) --}}
                @if($vehicleExpensesDoMes->isNotEmpty())
                    <div class="border-t border-gray-200" x-data="{ openVeiculos: false }">
                        <div class="px-5 py-3 bg-green-50 flex items-center justify-between cursor-pointer select-none hover:bg-green-100 transition" @click="openVeiculos = !openVeiculos">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-green-700 uppercase tracking-wide">🚗 Veículos &mdash; despesas importadas</span>
                                <span class="text-xs text-green-600 bg-green-200 rounded-full px-2">{{ $vehicleExpensesDoMes->sum('quantidade') }} lançamento(s)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-green-700 tabular-nums">R$ {{ number_format($totalVeiculos, 2, ',', '.') }}</span>
                                <svg class="w-4 h-4 text-green-600 transition-transform duration-200" :class="openVeiculos ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <div x-show="openVeiculos" x-cloak>
                            @foreach($vehicleExpensesDoMes as $vexp)
                                <div class="border-t border-gray-50" x-data="{ open: false }">
                                    <div class="px-5 py-3 flex items-center gap-3 hover:bg-green-50/30 cursor-pointer select-none" @click="open = !open">
                                        <span class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-xs shrink-0">🚗</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800 truncate">{{ $vexp['descricao'] }}</p>
                                            <p class="text-xs text-gray-400">{{ $vexp['quantidade'] }} lançamento(s) &bull; Carro</p>
                                        </div>
                                        <span class="text-sm font-bold tabular-nums text-green-700 whitespace-nowrap">R$ {{ number_format($vexp['valor'], 2, ',', '.') }}</span>
                                        <span class="text-xs text-gray-300 bg-gray-100 px-2 py-0.5 rounded-full">auto</span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                    <div x-show="open" x-cloak class="pl-14 pr-5 pb-3 space-y-1 bg-green-50/20">
                                        @foreach($vexp['itens'] as $item)
                                            <div class="flex justify-between text-xs text-gray-500 py-1 border-b border-gray-50 last:border-0">
                                                <span class="truncate mr-4">{{ $item['data'] }} &mdash; {{ $item['tipo'] }}@if($item['descricao']) &bull; {{ $item['descricao'] }}@endif</span>
                                                <span class="tabular-nums whitespace-nowrap font-medium">R$ {{ number_format($item['valor'], 2, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Subtotal Variáveis --}}
                <div class="px-5 py-3 bg-blue-50 border-t border-blue-100 flex justify-between">
                    <span class="text-sm font-semibold text-gray-700">Subtotal variáveis</span>
                    <span class="text-sm font-bold text-blue-700 tabular-nums">R$ {{ number_format($totalVariaveis, 2, ',', '.') }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- FORMULÁRIO NOVA DESPESA --}}
    <div>
        <div
            class="bg-white rounded-lg shadow p-5 sticky top-4"
            x-data="novaDepesaForm()"
        >
            {{-- Cabeçalho do form + botão gerenciar categorias --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-800">➕ Nova Despesa</h2>
                <button type="button"
                        @click="$dispatch('open-categories')"
                        class="text-xs px-2.5 py-1.5 rounded border border-gray-200 text-gray-500
                               hover:border-blue-400 hover:text-blue-600 transition flex items-center gap-1">
                    🏷️ Categorias
                </button>
            </div>

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
                            <option value="fixa"    {{ old('tipo_despesa')==='fixa'    ?'selected':'' }}>Fixa</option>
                            <option value="variavel"{{ old('tipo_despesa','variavel')==='variavel'?'selected':'' }}>Variável</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Categoria</label>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="w-3 h-3 rounded-full flex-shrink-0 border border-black/10 transition-colors"
                                :style="'background:' + selectedCatCor"
                            ></span>
                            <select name="categoria"
                                    class="form-control text-sm flex-1"
                                    x-model="selectedCat"
                                    @change="syncCor()">
                                <option value="">-- Sem categoria --</option>
                                <template x-for="cat in cats" :key="cat.id">
                                    <option :value="cat.nome"
                                            :selected="cat.nome === '{{ old('categoria') }}'"
                                            x-text="(cat.emoji ? cat.emoji + ' ' : '') + cat.nome"></option>
                                </template>
                            </select>
                        </div>
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
                            <option value="WIL"          {{ old('pessoa')==='WIL'          ?'selected':'' }}>Willian</option>
                            <option value="MAY"          {{ old('pessoa')==='MAY'          ?'selected':'' }}>Mayara</option>
                            <option value="compartilhado"{{ old('pessoa')==='compartilhado'?'selected':'' }}>Compartilhado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Pagamento *</label>
                        <select name="forma_pagamento" required class="form-control text-sm w-full" x-model="formaPgto">
                            <option value="pix"     {{ old('forma_pagamento','pix')==='pix'    ?'selected':'' }}>Pix</option>
                            <option value="debito"  {{ old('forma_pagamento')==='debito' ?'selected':'' }}>Débito</option>
                            <option value="dinheiro"{{ old('forma_pagamento')==='dinheiro'?'selected':'' }}>Dinheiro</option>
                            <option value="credito" {{ old('forma_pagamento')==='credito'?'selected':'' }}>💳 Crédito</option>
                        </select>
                    </div>
                </div>

                {{-- Campos de crédito --}}
                <div x-show="formaPgto === 'credito'" x-cloak class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cartão de crédito</label>
                        <select name="credit_card_id" class="form-control text-sm w-full">
                            <option value="">Selecione o cartão...</option>
                            @foreach($creditCards as $card)
                                <option value="{{ $card->id }}" {{ old('credit_card_id')==$card->id?'selected':'' }}>
                                    {{ $card->nome }}@if($card->bandeira) ({{ $card->bandeira }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Parcelas</label>
                        <select name="parcelas_total" class="form-control text-sm w-full">
                            @for($i = 1; $i <= 48; $i++)
                                <option value="{{ $i }}" {{ old('parcelas_total', 1) == $i ? 'selected' : '' }}>
                                    {{ $i }}x @if($i === 1)(à vista)@endif
                                </option>
                            @endfor
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
                            <option value="pago"     {{ old('status')==='pago'    ?'selected':'' }}>Pago</option>
                            <option value="pgoCC"    {{ old('status')==='pgoCC'   ?'selected':'' }}>💳 Pago CC</option>
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
                        @foreach($errors->all() as $e) <p>&bull; {{ $e }}</p> @endforeach
                    </div>
                @endif

                <button type="submit" class="btn-primary w-full py-2 text-sm">Adicionar Despesa</button>
            </form>
        </div>
    </div>

</div>

{{-- Modal CRUD Categorias --}}
@include('finance.expenses._categories_crud')

@push('scripts')
<script>
function novaDepesaForm() {
    // normaliza cor: sempre retorna com #
    function corComHash(c) { return c ? '#' + c.replace('#','') : '#e5e7eb'; }

    return {
        formaPgto: '{{ old('forma_pagamento', 'pix') }}',
        cats: @json($expenseCategories),
        selectedCat: '{{ old('categoria') }}',
        selectedCatCor: '#e5e7eb',

        init() {
            this.syncCor();
            window.addEventListener('categories-updated', async () => {
                const r = await fetch('{{ route('finance.expense_categories.index') }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.cats = await r.json();
                window.__expenseCats = this.cats;
                this.syncCor();
            });
        },

        syncCor() {
            const found = this.cats.find(c => c.nome === this.selectedCat);
            this.selectedCatCor = found ? corComHash(found.cor) : '#e5e7eb';
        },
    };
}
</script>
@endpush

@endsection
