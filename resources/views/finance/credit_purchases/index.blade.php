@extends('layouts.app')

@section('title', 'Compras no Crédito')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">💳 Compras no Crédito</h1>
        <p class="text-sm text-gray-500 mt-0.5">Parcelas e previsão de fatura &mdash;
            <span class="font-medium text-gray-700">{{ $mes->translatedFormat('F \\d\\e Y') }}</span>
        </p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn-back self-start sm:self-auto">← Dashboard</a>
</div>

{{-- Navegação por mês --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="flex flex-wrap gap-2 items-center">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1">Mês:</span>
        @foreach($meses as $m)
            <a href="{{ route('finance.credit_purchases.index', ['mes' => $m->format('Y-m')]) }}"
               class="px-3 py-1 text-xs rounded-full border transition
                      {{ $mes->format('Y-m') === $m->format('Y-m')
                         ? 'bg-indigo-600 text-white border-indigo-600 font-semibold'
                         : 'border-gray-200 text-gray-500 hover:border-indigo-400 hover:text-indigo-700' }}">
                {{ $m->translatedFormat('M/y') }}
            </a>
        @endforeach
    </div>
</div>

@if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- COLUNA PRINCIPAL --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- PREVISÃO DE FATURA POR CARTÃO --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Previsão de Fatura &mdash; {{ $mes->translatedFormat('F/Y') }}</h2>

            @if($cards->isEmpty())
                <div class="bg-white rounded-lg shadow p-6 text-center text-gray-400 text-sm">
                    Nenhum cartão cadastrado.
                    <a href="{{ route('finance.credit_cards.index') }}" class="text-indigo-600 underline">Cadastrar cartões</a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($cards as $card)
                        @php
                            $insts    = $instsByCard->get($card->id, collect());
                            $total    = $insts->sum('valor');
                            $pagas    = $insts->where('status','pago')->sum('valor');
                            $pendente = $total - $pagas;
                            $vencDia  = $card->dia_vencimento;
                            $vencDate = \Carbon\Carbon::create($mes->year, $mes->month, min($vencDia, $mes->daysInMonth));
                        @endphp
                        <div class="bg-white rounded-xl shadow overflow-hidden">
                            {{-- Header colorido --}}
                            <div class="px-4 py-3 text-white flex items-center justify-between"
                                 style="background: {{ $card->cor }}">
                                <div>
                                    <p class="text-xs opacity-75">{{ $card->bandeira_label }}</p>
                                    <p class="font-bold">{{ $card->nome }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs opacity-75">Vence dia {{ $vencDia }}</p>
                                    <p class="text-xs opacity-75">{{ $vencDate->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            {{-- Valores --}}
                            <div class="px-4 py-3">
                                <div class="flex justify-between items-end">
                                    <div>
                                        <p class="text-xs text-gray-400">Total da fatura</p>
                                        <p class="text-xl font-bold text-gray-900 tabular-nums">
                                            R$ {{ number_format($total, 2, ',', '.') }}
                                        </p>
                                    </div>
                                    @if($total > 0)
                                    <div class="text-right text-xs">
                                        <p class="text-green-600">✓ R$ {{ number_format($pagas, 2, ',', '.') }} pago</p>
                                        <p class="text-orange-500">⏳ R$ {{ number_format($pendente, 2, ',', '.') }} pendente</p>
                                    </div>
                                    @endif
                                </div>

                                @if($card->limite && $total > 0)
                                    @php $pct = min(100, round($total / $card->limite * 100)) @endphp
                                    <div class="mt-2">
                                        <div class="flex justify-between text-xs text-gray-400 mb-1">
                                            <span>{{ $pct }}% do limite</span>
                                            <span>R$ {{ number_format($card->limite, 2, ',', '.') }}</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $pct >= 80 ? 'bg-red-500' : ($pct >= 50 ? 'bg-yellow-400' : 'bg-green-500') }}"
                                                 style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Parcelas do mês --}}
                                @if($insts->isNotEmpty())
                                    <div class="mt-3 space-y-1">
                                        @foreach($insts as $inst)
                                            <div class="flex items-center gap-2 text-xs">
                                                <form method="POST" action="{{ route('finance.credit_purchases.toggle_installment', $inst) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                            class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition
                                                                   {{ $inst->status === 'pago' ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 text-gray-300' }}">
                                                        @if($inst->status === 'pago')
                                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                                        @endif
                                                    </button>
                                                </form>
                                                <span class="flex-1 truncate {{ $inst->status === 'pago' ? 'line-through text-gray-400' : '' }}">
                                                    {{ $inst->purchase->descricao }}
                                                </span>
                                                <span class="text-gray-400">{{ $inst->numero }}/{{ $inst->total }}</span>
                                                <span class="font-semibold tabular-nums {{ $inst->status === 'pago' ? 'text-green-600' : 'text-gray-700' }}">
                                                    R$ {{ number_format($inst->valor, 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-3 text-xs text-gray-400 text-center">Sem lançamentos neste mês</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- HISTÓRICO DE COMPRAS --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Histórico de Compras</h2>
            <div class="bg-white rounded-xl shadow overflow-hidden">
                @if($purchases->isEmpty())
                    <div class="p-10 text-center text-gray-400">
                        <p class="text-3xl mb-2">💳</p>
                        <p class="text-sm">Nenhuma compra lançada ainda.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($purchases as $purchase)
                        <div x-data="{ open: false }" class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                {{-- Cartão indicador --}}
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold text-white shrink-0"
                                      style="background: {{ $purchase->card->cor }}">
                                    {{ substr($purchase->card->nome, 0, 2) }}
                                </span>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $purchase->descricao }}</p>
                                    <p class="text-xs text-gray-400">
                                        {{ $purchase->card->nome }} &middot;
                                        {{ $purchase->data_compra->format('d/m/Y') }} &middot;
                                        @if($purchase->parcelas_total > 1)
                                            {{ $purchase->parcelas_total }}x de R$ {{ number_format($purchase->valor_parcela, 2, ',', '.') }}
                                        @else
                                            à vista
                                        @endif
                                        @if($purchase->categoria)
                                            &middot; {{ $purchase->categoria }}
                                        @endif
                                    </p>
                                </div>

                                <span class="text-sm font-bold text-gray-800 tabular-nums">
                                    R$ {{ number_format($purchase->valor_total, 2, ',', '.') }}
                                </span>

                                <div class="flex gap-1 shrink-0">
                                    {{-- Ver parcelas --}}
                                    <button @click="open = !open"
                                            :class="open ? 'text-indigo-600 bg-indigo-50' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50'"
                                            class="p-1.5 rounded transition text-xs" title="Ver parcelas">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    {{-- Excluir --}}
                                    <form method="POST" action="{{ route('finance.credit_purchases.destroy', $purchase) }}" id="del-p-{{ $purchase->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button"
                                                onclick="if(confirm('Remover compra e todas as {{ $purchase->parcelas_total }} parcela(s)?')) document.getElementById('del-p-{{ $purchase->id }}').submit()"
                                                class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Remover">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Parcelas expandidas --}}
                            <div x-show="open" style="display:none"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 class="mt-3 ml-11 space-y-1">
                                @foreach($purchase->installments as $inst)
                                    <div class="flex items-center gap-2 text-xs py-1 px-3 rounded
                                                {{ $inst->status === 'pago' ? 'bg-green-50' : 'bg-gray-50' }}">
                                        <span class="w-16 text-gray-400">{{ $inst->mes_referencia->translatedFormat('M/Y') }}</span>
                                        <span class="text-gray-500">Parcela {{ $inst->numero }}/{{ $inst->total }}</span>
                                        <span class="flex-1"></span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                     {{ $inst->status === 'pago' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ $inst->status === 'pago' ? 'Pago' : 'Pendente' }}
                                        </span>
                                        <span class="font-semibold tabular-nums">R$ {{ number_format($inst->valor, 2, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="px-5 py-3">
                        {{ $purchases->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- FORMULÁRIO NOVA COMPRA --}}
    <div>
        <div class="bg-white rounded-xl shadow p-5 sticky top-4">
            <h2 class="text-base font-semibold text-gray-800 mb-4">💳 Nova Compra</h2>

            <form method="POST" action="{{ route('finance.credit_purchases.store') }}"
                  class="space-y-3" x-data="{ parcelas: {{ old('parcelas_total', 1) }} }">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Descrição *</label>
                    <input type="text" name="descricao" required placeholder="Ex: TV Samsung"
                           value="{{ old('descricao') }}" class="form-control text-sm w-full">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cartão *</label>
                        <select name="credit_card_id" required class="form-control text-sm w-full">
                            <option value="">Selecione</option>
                            @foreach($cards as $card)
                                <option value="{{ $card->id }}" {{ old('credit_card_id')==$card->id?'selected':'' }}>
                                    {{ $card->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Pessoa *</label>
                        <select name="pessoa" required class="form-control text-sm w-full">
                            <option value="WIL"           {{ old('pessoa')==='WIL'          ?'selected':'' }}>Willian</option>
                            <option value="MAY"           {{ old('pessoa')==='MAY'          ?'selected':'' }}>Mayara</option>
                            <option value="compartilhado" {{ old('pessoa')==='compartilhado'?'selected':'' }}>Compartilhado</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Valor total *</label>
                        <input type="number" name="valor_total" step="0.01" min="0.01" required
                               placeholder="0,00" value="{{ old('valor_total') }}"
                               class="form-control text-sm w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Parcelas *</label>
                        <input type="number" name="parcelas_total" min="1" max="48" required
                               x-model="parcelas" value="{{ old('parcelas_total', 1) }}"
                               class="form-control text-sm w-full">
                    </div>
                </div>

                {{-- Preview parcela --}}
                <div class="bg-indigo-50 rounded-lg px-3 py-2 text-xs text-indigo-700" x-show="parcelas > 1">
                    ℹ️ As parcelas serão geradas automaticamente mes a mês.
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Data da compra *</label>
                        <input type="date" name="data_compra" required
                               value="{{ old('data_compra', now()->format('Y-m-d')) }}"
                               class="form-control text-sm w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Categoria</label>
                        <input type="text" name="categoria" placeholder="Opcional"
                               value="{{ old('categoria') }}" class="form-control text-sm w-full">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observação</label>
                    <input type="text" name="observacao" placeholder="Opcional"
                           value="{{ old('observacao') }}" class="form-control text-sm w-full">
                </div>

                @if($errors->any())
                    <div class="text-xs text-red-600">
                        @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                    </div>
                @endif

                <button type="submit" class="btn-primary w-full py-2 text-sm">
                    Lançar Compra → Gerar Parcelas
                </button>
            </form>
        </div>

        {{-- Link para cartões --}}
        <a href="{{ route('finance.credit_cards.index') }}"
           class="mt-3 block text-center text-xs text-indigo-600 hover:underline">
            ⚙️ Gerenciar Cartões
        </a>
    </div>

</div>

@endsection
