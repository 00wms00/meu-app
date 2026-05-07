@extends('layouts.app')

@section('title', 'Cartões de Crédito')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">💳 Cartões de Crédito</h1>
        <p class="text-sm text-gray-500 mt-0.5">Gerencie seus cartões e limites</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn-back self-start sm:self-auto">← Dashboard</a>
</div>

@if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LISTA DE CARTÕES --}}
    <div class="lg:col-span-2 space-y-4">

        @forelse($cards as $card)
        <div x-data="{ editing: false }"
             class="bg-white rounded-xl shadow overflow-hidden
                    {{ $card->ativo ? '' : 'opacity-60' }}">

            {{-- CARD VISUAL --}}
            <div class="relative p-5 text-white rounded-t-xl"
                 style="background: {{ $card->cor }}">

                {{-- Bandeira + pessoa --}}
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium opacity-75 uppercase tracking-widest">{{ $card->bandeira_label }}</p>
                        <p class="text-xl font-bold mt-0.5">{{ $card->nome }}</p>
                    </div>
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/20 text-sm font-bold">
                        {{ $card->pessoa === 'compartilhado' ? 'C' : substr($card->pessoa, 0, 1) }}
                    </span>
                </div>

                {{-- Limite + datas --}}
                <div class="mt-4 flex gap-6 text-sm">
                    <div>
                        <p class="opacity-70 text-xs">Limite</p>
                        <p class="font-semibold">
                            {{ $card->limite ? 'R$ '.number_format($card->limite, 2, ',', '.') : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="opacity-70 text-xs">Fechamento</p>
                        <p class="font-semibold">Dia {{ $card->dia_fechamento }}</p>
                    </div>
                    <div>
                        <p class="opacity-70 text-xs">Vencimento</p>
                        <p class="font-semibold">Dia {{ $card->dia_vencimento }}</p>
                    </div>
                </div>

                {{-- Status badge --}}
                @if(!$card->ativo)
                    <span class="absolute top-3 right-12 text-xs bg-white/30 rounded-full px-2 py-0.5">Inativo</span>
                @endif
            </div>

            {{-- AÇÕES --}}
            <div class="px-5 py-3 bg-gray-50 flex items-center gap-2 border-t border-gray-100">
                {{-- Editar --}}
                <button @click="editing = !editing"
                        :class="editing ? 'text-blue-600 bg-blue-100' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50'"
                        class="p-1.5 rounded transition text-sm flex items-center gap-1"
                        title="Editar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span class="text-xs">Editar</span>
                </button>

                {{-- Ativar/Desativar --}}
                <form method="POST" action="{{ route('finance.credit_cards.toggle', $card) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="p-1.5 rounded transition text-sm flex items-center gap-1
                                   {{ $card->ativo ? 'text-gray-500 hover:text-yellow-600 hover:bg-yellow-50' : 'text-gray-400 hover:text-green-600 hover:bg-green-50' }}"
                            title="{{ $card->ativo ? 'Desativar' : 'Ativar' }}">
                        @if($card->ativo)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            <span class="text-xs">Desativar</span>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-xs">Ativar</span>
                        @endif
                    </button>
                </form>

                {{-- Excluir --}}
                <form method="POST" action="{{ route('finance.credit_cards.destroy', $card) }}" id="del-card-{{ $card->id }}" class="ml-auto">
                    @csrf @method('DELETE')
                    <button type="button"
                            onclick="if(confirm('Remover o cartão \'{{ addslashes($card->nome) }}\'?')) document.getElementById('del-card-{{ $card->id }}').submit()"
                            class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 transition flex items-center gap-1"
                            title="Remover">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span class="text-xs">Remover</span>
                    </button>
                </form>
            </div>

            {{-- FORMULÁRIO INLINE DE EDIÇÃO --}}
            <div x-show="editing"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 style="display:none"
                 class="px-5 pb-5 pt-4 border-t border-blue-100 bg-blue-50">

                <form method="POST" action="{{ route('finance.credit_cards.update', $card) }}"
                      class="grid grid-cols-2 gap-3">
                    @csrf @method('PUT')

                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nome do cartão</label>
                        <input type="text" name="nome" value="{{ $card->nome }}" required
                               class="form-control text-sm w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bandeira</label>
                        <select name="bandeira" class="form-control text-sm w-full">
                            @foreach(['visa'=>'Visa','mastercard'=>'Mastercard','elo'=>'Elo','amex'=>'American Express','hipercard'=>'Hipercard','outro'=>'Outro'] as $val => $label)
                                <option value="{{ $val }}" {{ $card->bandeira===$val?'selected':'' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Pessoa</label>
                        <select name="pessoa" class="form-control text-sm w-full">
                            <option value="WIL"           {{ $card->pessoa==='WIL'          ?'selected':'' }}>Willian</option>
                            <option value="MAY"           {{ $card->pessoa==='MAY'          ?'selected':'' }}>Mayara</option>
                            <option value="compartilhado" {{ $card->pessoa==='compartilhado'?'selected':'' }}>Compartilhado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Limite (R$)</label>
                        <input type="number" name="limite" value="{{ $card->limite }}" step="0.01" min="0"
                               placeholder="Opcional" class="form-control text-sm w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cor do card</label>
                        <div class="flex gap-2 flex-wrap pt-1">
                            @foreach([
                                '#6366f1','#8b5cf6','#ec4899','#ef4444',
                                '#f97316','#eab308','#22c55e','#14b8a6',
                                '#3b82f6','#1e293b','#475569'
                            ] as $cor)
                                <label class="cursor-pointer">
                                    <input type="radio" name="cor" value="{{ $cor }}" class="sr-only"
                                           {{ $card->cor===$cor?'checked':'' }}>
                                    <span class="block w-7 h-7 rounded-full border-2 transition"
                                          style="background:{{ $cor }}; border-color: {{ $card->cor===$cor ? '#1d4ed8' : 'transparent' }}"></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Dia fechamento</label>
                        <input type="number" name="dia_fechamento" value="{{ $card->dia_fechamento }}" min="1" max="31" required
                               class="form-control text-sm w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Dia vencimento</label>
                        <input type="number" name="dia_vencimento" value="{{ $card->dia_vencimento }}" min="1" max="31" required
                               class="form-control text-sm w-full">
                    </div>
                    <div class="col-span-2 flex gap-2 pt-1">
                        <button type="submit" class="btn-primary text-sm px-5 py-2">Salvar</button>
                        <button type="button" @click="editing = false"
                                class="text-sm px-4 py-2 rounded border border-gray-300 text-gray-600 hover:bg-gray-100 transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>

        </div>
        @empty
            <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400">
                <p class="text-4xl mb-3">💳</p>
                <p class="text-sm font-medium">Nenhum cartão cadastrado ainda.</p>
                <p class="text-xs mt-1">Use o formulário ao lado para adicionar.</p>
            </div>
        @endforelse
    </div>

    {{-- FORMULÁRIO NOVO CARTÃO --}}
    <div>
        <div class="bg-white rounded-xl shadow p-5 sticky top-4">
            <h2 class="text-base font-semibold text-gray-800 mb-4">➕ Novo Cartão</h2>

            <form method="POST" action="{{ route('finance.credit_cards.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nome *</label>
                    <input type="text" name="nome" required placeholder="Ex: NU WIL, C6 MAY"
                           value="{{ old('nome') }}" class="form-control text-sm w-full">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bandeira *</label>
                        <select name="bandeira" required class="form-control text-sm w-full">
                            <option value="visa"       {{ old('bandeira')==='visa'       ?'selected':'' }}>Visa</option>
                            <option value="mastercard" {{ old('bandeira')==='mastercard' ?'selected':'' }}>Mastercard</option>
                            <option value="elo"        {{ old('bandeira')==='elo'        ?'selected':'' }}>Elo</option>
                            <option value="amex"       {{ old('bandeira')==='amex'       ?'selected':'' }}>Amex</option>
                            <option value="hipercard"  {{ old('bandeira')==='hipercard'  ?'selected':'' }}>Hipercard</option>
                            <option value="outro"      {{ old('bandeira')==='outro'      ?'selected':'' }}>Outro</option>
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

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Limite (R$)</label>
                    <input type="number" name="limite" step="0.01" min="0" placeholder="Opcional"
                           value="{{ old('limite') }}" class="form-control text-sm w-full">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Dia fechamento *</label>
                        <input type="number" name="dia_fechamento" min="1" max="31" required
                               value="{{ old('dia_fechamento') }}" placeholder="Ex: 23"
                               class="form-control text-sm w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Dia vencimento *</label>
                        <input type="number" name="dia_vencimento" min="1" max="31" required
                               value="{{ old('dia_vencimento') }}" placeholder="Ex: 1"
                               class="form-control text-sm w-full">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cor do card</label>
                    <div class="flex gap-2 flex-wrap pt-1">
                        @foreach([
                            '#6366f1','#8b5cf6','#ec4899','#ef4444',
                            '#f97316','#eab308','#22c55e','#14b8a6',
                            '#3b82f6','#1e293b','#475569'
                        ] as $i => $cor)
                            <label class="cursor-pointer">
                                <input type="radio" name="cor" value="{{ $cor }}" class="sr-only"
                                       {{ (old('cor','#6366f1')===$cor)?'checked':'' }}>
                                <span class="block w-7 h-7 rounded-full border-2 transition"
                                      style="background:{{ $cor }}; border-color: {{ (old('cor','#6366f1')===$cor) ? '#1d4ed8' : 'transparent' }}"></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                @if($errors->any())
                    <div class="text-xs text-red-600">
                        @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
                    </div>
                @endif

                <button type="submit" class="btn-primary w-full py-2 text-sm">Adicionar Cartão</button>
            </form>
        </div>
    </div>

</div>

@endsection
