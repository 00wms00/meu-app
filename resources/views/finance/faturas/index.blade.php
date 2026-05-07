@extends('layouts.app')

@section('title', 'Faturas de Cartões')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">💳 Faturas de Cartões</h1>
        <p class="text-sm text-gray-500 mt-0.5">Visão consolidada por cartão e mês</p>
    </div>
    <a href="{{ route('finance.credit_cards.index') }}" class="btn-back self-start sm:self-auto">← Cartões</a>
</div>

{{-- Filtro de período --}}
<form method="GET" class="mb-6 flex flex-wrap gap-3 items-end bg-white rounded-xl shadow p-4">
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Meses anteriores</label>
        <select name="passados" class="form-control text-sm">
            @foreach([0,1,2,3,6] as $v)
                <option value="{{ $v }}" {{ $mesesAtras == $v ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Meses futuros</label>
        <select name="futuros" class="form-control text-sm">
            @foreach([1,2,3,4,6] as $v)
                <option value="{{ $v }}" {{ $mesesFrente == $v ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn-primary text-sm px-4 py-2">Filtrar</button>
</form>

@if($cards->isEmpty())
    <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400">
        <p class="text-4xl mb-3">💳</p>
        <p class="text-sm font-medium">Nenhum cartão cadastrado.</p>
        <p class="text-xs mt-1"><a href="{{ route('finance.credit_cards.index') }}" class="text-blue-600 underline">Cadastrar cartão</a></p>
    </div>
@else

{{-- TABELA DESKTOP --}}
<div class="hidden md:block bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="text-left px-4 py-3 font-semibold text-gray-600 w-40">Cartão</th>
                @foreach($meses as $mes)
                    <th class="text-right px-4 py-3 font-semibold
                        {{ $mes->isSameMonth($hoje) ? 'text-blue-700 bg-blue-50' : 'text-gray-600' }}">
                        {{ $mes->translatedFormat('M/y') }}
                        @if($mes->isSameMonth($hoje))
                            <span class="ml-1 text-xs font-normal text-blue-500">atual</span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">

            @foreach($cards as $card)
            @php $temAlgo = collect($meses)->contains(fn($m) => ($faturas[$card->id][$m->format('Y-m')]['total'] ?? 0) > 0); @endphp
            <tr x-data="{ open: false }" class="hover:bg-gray-50 cursor-pointer" @click="open = !open">

                {{-- Nome do cartão --}}
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full flex-shrink-0" style="background:{{ $card->cor }}"></span>
                        <div>
                            <p class="font-medium text-gray-800 leading-tight">{{ $card->nome }}</p>
                            <p class="text-xs text-gray-400">{{ $card->pessoa_label }}</p>
                        </div>
                        <svg class="w-3 h-3 text-gray-400 ml-auto transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </td>

                {{-- Valores por mês --}}
                @foreach($meses as $mes)
                @php
                    $key   = $mes->format('Y-m');
                    $total = $faturas[$card->id][$key]['total'] ?? 0;
                    $atual = $mes->isSameMonth($hoje);
                @endphp
                <td class="px-4 py-3 text-right {{ $atual ? 'bg-blue-50' : '' }}">
                    @if($total > 0)
                        <span class="font-semibold {{ $atual ? 'text-blue-700' : 'text-gray-800' }}">
                            R$ {{ number_format($total, 2, ',', '.') }}
                        </span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                @endforeach
            </tr>

            {{-- Linha de detalhes expansível --}}
            <tr x-show="open" style="display:none" class="bg-gray-50">
                <td colspan="{{ $meses->count() + 1 }}" class="px-4 pb-4 pt-2">
                    <div class="grid grid-cols-1 md:grid-cols-{{ min($meses->count(), 4) }} gap-4">
                        @foreach($meses as $mes)
                        @php
                            $key   = $mes->format('Y-m');
                            $itens = $faturas[$card->id][$key]['itens'] ?? [];
                        @endphp
                        @if(count($itens) > 0)
                        <div class="bg-white rounded-lg border border-gray-100 p-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $mes->translatedFormat('F/Y') }}</p>
                            <ul class="space-y-1">
                                @foreach($itens as $item)
                                <li class="flex justify-between text-xs">
                                    <span class="text-gray-700 truncate max-w-[160px]">{{ $item->descricao }}</span>
                                    <span class="font-medium text-gray-900 ml-2 flex-shrink-0">R$ {{ number_format($item->valor, 2, ',', '.') }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </td>
            </tr>
            @endforeach

            {{-- Linha de totais --}}
            <tr class="bg-gray-100 font-semibold border-t-2 border-gray-300">
                <td class="px-4 py-3 text-gray-700">TOTAL</td>
                @foreach($meses as $mes)
                @php $total = $totalPorMes[$mes->format('Y-m')] ?? 0; @endphp
                <td class="px-4 py-3 text-right {{ $mes->isSameMonth($hoje) ? 'text-blue-700 bg-blue-100' : 'text-gray-800' }}">
                    @if($total > 0)
                        R$ {{ number_format($total, 2, ',', '.') }}
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                @endforeach
            </tr>

        </tbody>
    </table>
</div>

{{-- CARDS MOBILE --}}
<div class="md:hidden space-y-4">
    @foreach($cards as $card)
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
            <span class="inline-block w-3 h-3 rounded-full" style="background:{{ $card->cor }}"></span>
            <div>
                <p class="font-semibold text-gray-800">{{ $card->nome }}</p>
                <p class="text-xs text-gray-400">{{ $card->pessoa_label }}</p>
            </div>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($meses as $mes)
            @php
                $key   = $mes->format('Y-m');
                $total = $faturas[$card->id][$key]['total'] ?? 0;
                $itens = $faturas[$card->id][$key]['itens'] ?? [];
                $atual = $mes->isSameMonth($hoje);
            @endphp
            <div x-data="{ open: false }" class="{{ $atual ? 'bg-blue-50' : '' }}">
                <div class="flex items-center justify-between px-4 py-2 cursor-pointer" @click="open = !open">
                    <span class="text-sm {{ $atual ? 'font-semibold text-blue-700' : 'text-gray-600' }}">
                        {{ $mes->translatedFormat('F/Y') }}
                        @if($atual) <span class="text-xs font-normal">(atual)</span> @endif
                    </span>
                    <div class="flex items-center gap-2">
                        @if($total > 0)
                            <span class="font-semibold {{ $atual ? 'text-blue-700' : 'text-gray-800' }}">R$ {{ number_format($total, 2, ',', '.') }}</span>
                            <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                        @else
                            <span class="text-gray-300 text-sm">—</span>
                        @endif
                    </div>
                </div>
                @if(count($itens) > 0)
                <div x-show="open" style="display:none" class="px-4 pb-3">
                    <ul class="space-y-1">
                        @foreach($itens as $item)
                        <li class="flex justify-between text-xs text-gray-600">
                            <span class="truncate max-w-[200px]">{{ $item->descricao }}</span>
                            <span class="font-medium ml-2">R$ {{ number_format($item->valor, 2, ',', '.') }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

@endif

@endsection
