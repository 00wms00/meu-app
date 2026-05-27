@extends('layouts.app')

@section('title', 'Receitas')

@section('content')

{{-- Cabeçalho --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">💰 Receitas</h1>
        <p class="text-sm text-gray-500 mt-0.5">Salários, freelances e outras entradas &mdash;
            <span class="font-medium text-gray-700">{{ $mes->translatedFormat('F \\de Y') }}</span>
        </p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn-back self-start sm:self-auto">← Dashboard</a>
</div>

{{-- Navegação por mês --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="flex flex-wrap gap-2 items-center">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide mr-1">Mês:</span>
        @foreach($meses as $m)
            @php $ativo = $mes->format('Y-m') === $m->format('Y-m'); @endphp
            <a href="{{ route('finance.incomes.index', ['mes' => $m->format('Y-m')]) }}"
               class="px-3 py-1 text-xs rounded-full border transition
                      {{ $ativo
                         ? 'bg-green-600 !text-white border-green-600 font-semibold'
                         : 'border-gray-200 text-gray-500 hover:border-green-400 hover:text-green-700' }}">
                {{ $m->translatedFormat('M/y') }}
            </a>
        @endforeach
    </div>
</div>

{{-- KPIs --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem;">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total do mês</p>
        <p class="text-xl font-bold text-gray-900">R$ {{ number_format($totalGeral, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">👤 Willian</p>
        <p class="text-xl font-bold text-blue-700">R$ {{ number_format($totalWil, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">👤 Mayara</p>
        <p class="text-xl font-bold text-pink-600">R$ {{ number_format($totalMay, 2, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">👥 Compartilhado</p>
        <p class="text-xl font-bold text-purple-600">R$ {{ number_format($totalComp, 2, ',', '.') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LISTA DE RECEITAS --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Receitas de {{ $mes->translatedFormat('F/Y') }}</h2>
                {{-- Botão duplicar recorrentes --}}
                <form method="POST" action="{{ route('finance.incomes.duplicar') }}">
                    @csrf
                    <input type="hidden" name="mes" value="{{ $mes->format('Y-m') }}">
                    <button type="submit"
                            onclick="return confirm('Duplicar receitas recorrentes do mês anterior?')"
                            class="text-xs px-3 py-1.5 rounded border border-blue-300 text-blue-600 hover:bg-blue-50 transition">
                        ↻ Importar recorrentes
                    </button>
                </form>
            </div>

            @if($incomes->isEmpty())
                <div class="p-10 text-center text-gray-400">
                    <p class="text-3xl mb-2">💵</p>
                    <p class="text-sm font-medium">Nenhuma receita registrada neste mês.</p>
                    <p class="text-xs mt-1">Use o formulário ao lado para adicionar.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($incomes as $income)
                    <div x-data="{ editing: false }">

                        {{-- LINHA PRINCIPAL --}}
                        <div class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50"
                             :class="editing ? '!bg-blue-50' : ''">

                            {{-- Indicador de pessoa --}}
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold shrink-0
                                {{ $income->pessoa === 'WIL' ? 'bg-blue-100 text-blue-700'
                                 : ($income->pessoa === 'MAY' ? 'bg-pink-100 text-pink-700'
                                 : 'bg-purple-100 text-purple-700') }}">
                                {{ $income->pessoa === 'compartilhado' ? 'C' : substr($income->pessoa, 0, 1) }}
                            </span>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $income->descricao }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ ['salario'=>'Salário','freelance'=>'Freelance','presente'=>'Presente','aluguel'=>'Aluguel','outros'=>'Outros'][$income->tipo] }}
                                    @if($income->recorrente)
                                        <span class="ml-1 text-green-600">↻ recorrente</span>
                                    @endif
                                    @if($income->data_recebimento)
                                        &middot; recebido {{ $income->data_recebimento->format('d/m') }}
                                    @endif
                                </p>
                            </div>

                            <span class="text-sm font-bold text-green-700 tabular-nums whitespace-nowrap">
                                R$ {{ number_format($income->valor, 2, ',', '.') }}
                            </span>

                            {{-- Botões ação — SEMPRE VISÍVEIS --}}
                            <div class="flex gap-1 shrink-0">
                                {{-- Editar --}}
                                <button @click="editing = !editing"
                                        :title="editing ? 'Fechar edição' : 'Editar'"
                                        :class="editing ? 'text-blue-600 bg-blue-100' : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50'"
                                        class="p-1.5 rounded transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>

                                {{-- Excluir --}}
                                <form method="POST" action="{{ route('finance.incomes.destroy', $income) }}" id="del-inc-{{ $income->id }}">
                                    @csrf @method('DELETE')
                                    <button type="button"
                                            onclick="if(confirm('Remover \'{{ addslashes($income->descricao) }}\'?')) document.getElementById('del-inc-{{ $income->id }}').submit()"
                                            class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                                            title="Remover">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
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
                             class="px-5 pb-5 pt-4 bg-blue-50 border-t border-blue-100">

                            <form method="POST" action="{{ route('finance.incomes.update', $income) }}" class="grid grid-cols-2 gap-3">
                                @csrf @method('PUT')
                                <input type="hidden" name="mes_referencia" value="{{ $mes->format('Y-m') }}">

                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Descrição</label>
                                    <input type="text" name="descricao" value="{{ $income->descricao }}" required
                                           class="form-control text-sm w-full">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Valor (R$)</label>
                                    <input type="number" name="valor" value="{{ $income->valor }}" step="0.01" min="0.01" required
                                           class="form-control text-sm w-full">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Pessoa</label>
                                    <select name="pessoa" class="form-control text-sm w-full">
                                        <option value="WIL"           {{ $income->pessoa==='WIL'          ?'selected':'' }}>Willian</option>
                                        <option value="MAY"           {{ $income->pessoa==='MAY'          ?'selected':'' }}>Mayara</option>
                                        <option value="compartilhado" {{ $income->pessoa==='compartilhado'?'selected':'' }}>Compartilhado</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                                    <select name="tipo" class="form-control text-sm w-full">
                                        <option value="salario"   {{ $income->tipo==='salario'  ?'selected':'' }}>Salário</option>
                                        <option value="freelance" {{ $income->tipo==='freelance'?'selected':'' }}>Freelance</option>
                                        <option value="presente"  {{ $income->tipo==='presente' ?'selected':'' }}>Presente</option>
                                        <option value="aluguel"   {{ $income->tipo==='aluguel'  ?'selected':'' }}>Aluguel</option>
                                        <option value="outros"    {{ $income->tipo==='outros'   ?'selected':'' }}>Outros</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Data recebimento</label>
                                    <input type="date" name="data_recebimento"
                                           value="{{ $income->data_recebimento?->format('Y-m-d') }}"
                                           class="form-control text-sm w-full">
                                </div>
                                <div class="flex items-center">
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer mt-4">
                                        <input type="checkbox" name="recorrente" value="1"
                                               {{ $income->recorrente ? 'checked' : '' }}
                                               class="rounded">
                                        Recorrente
                                    </label>
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
                    @endforeach
                </div>

                {{-- Rodapé total --}}
                <div class="px-5 py-3 bg-green-50 border-t border-green-100 flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-700">Total do mês</span>
                    <span class="text-base font-bold text-green-700 tabular-nums">
                        R$ {{ number_format($totalGeral, 2, ',', '.') }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- FORMULÁRIO NOVA RECEITA --}}
    <div>
        <div class="bg-white rounded-lg shadow p-5 sticky top-4">
            <h2 class="text-base font-semibold text-gray-800 mb-4">➕ Nova Receita</h2>
            <form method="POST" action="{{ route('finance.incomes.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Descrição *</label>
                    <input type="text" name="descricao" required placeholder="Ex: Salário Willian"
                           value="{{ old('descricao') }}"
                           class="form-control text-sm w-full">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Valor (R$) *</label>
                        <input type="number" name="valor" step="0.01" min="0.01" required
                               placeholder="0,00" value="{{ old('valor') }}"
                               class="form-control text-sm w-full">
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
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo *</label>
                        <select name="tipo" required class="form-control text-sm w-full">
                            <option value="salario">Salário</option>
                            <option value="freelance">Freelance</option>
                            <option value="presente">Presente</option>
                            <option value="aluguel">Aluguel</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Data de recebimento</label>
                    <input type="date" name="data_recebimento"
                           value="{{ old('data_recebimento') }}"
                           class="form-control text-sm w-full">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observação</label>
                    <input type="text" name="observacao" placeholder="Opcional"
                           value="{{ old('observacao') }}"
                           class="form-control text-sm w-full">
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="recorrente" value="1"
                               {{ old('recorrente') ? 'checked' : '' }}
                               class="rounded">
                        Recorrente (repete todo mês)
                    </label>
                </div>

                @if($errors->any())
                    <div class="text-xs text-red-600">
                        @foreach($errors->all() as $e) <p>&bull; {{ $e }}</p> @endforeach
                    </div>
                @endif

                <button type="submit" class="btn-primary w-full py-2 text-sm">
                    Adicionar Receita
                </button>
            </form>
        </div>
    </div>

</div>

@endsection
