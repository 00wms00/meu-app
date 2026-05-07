{{-- Linha de uma despesa --}}
<div x-data="{ editing: false }">

    {{-- === LINHA PRINCIPAL === --}}
    <div class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50"
         :class="editing ? 'bg-blue-50' : ''">

        {{-- Toggle pago --}}
        <form method="POST" action="{{ route('finance.expenses.toggle', $expense) }}" class="shrink-0">
            @csrf @method('PATCH')
            <button type="submit"
                    title="{{ $expense->isPago() ? 'Marcar como pendente' : 'Marcar como pago' }}"
                    class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition
                           {{ $expense->isPago()
                              ? 'bg-green-500 border-green-500 text-white'
                              : ($expense->isAtrasada() ? 'border-red-400 text-red-400' : 'border-gray-300 text-gray-300') }}">
                @if($expense->isPago())
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                @endif
            </button>
        </form>

        {{-- Pessoa --}}
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold shrink-0
            {{ $expense->pessoa === 'WIL' ? 'bg-blue-100 text-blue-700'
             : ($expense->pessoa === 'MAY' ? 'bg-pink-100 text-pink-700'
             : 'bg-purple-100 text-purple-700') }}">
            {{ $expense->pessoa === 'compartilhado' ? 'C' : substr($expense->pessoa, 0, 1) }}
        </span>

        {{-- Descrição + meta --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate
                      {{ $expense->isPago() ? 'line-through text-gray-400' : '' }}">
                {{ $expense->descricao }}
            </p>
            <p class="text-xs text-gray-400 flex gap-2 flex-wrap">
                @if($expense->categoria)
                    <span class="bg-gray-100 rounded px-1">{{ $expense->categoria }}</span>
                @endif
                <span>{{ ['debito'=>'Débito','pix'=>'Pix','dinheiro'=>'Dinheiro'][$expense->forma_pagamento] }}</span>
                @if($expense->data_vencimento)
                    <span class="{{ $expense->isAtrasada() ? 'text-red-500 font-semibold' : '' }}">
                        vence {{ $expense->data_vencimento->format('d/m') }}
                    </span>
                @endif
                @if($expense->data_pagamento)
                    <span class="text-green-600">pago {{ $expense->data_pagamento->format('d/m') }}</span>
                @endif
            </p>
        </div>

        {{-- Valor --}}
        <span class="text-sm font-bold tabular-nums whitespace-nowrap
                     {{ $expense->isPago() ? 'text-green-600' : ($expense->isAtrasada() ? 'text-red-500' : 'text-gray-800') }}">
            R$ {{ number_format($expense->valor, 2, ',', '.') }}
        </span>

        {{-- Botões ação --}}
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
            <form method="POST" action="{{ route('finance.expenses.destroy', $expense) }}" id="del-{{ $expense->id }}">
                @csrf @method('DELETE')
                <button type="button"
                        onclick="if(confirm('Remover \'{{ addslashes($expense->descricao) }}\'?')) document.getElementById('del-{{ $expense->id }}').submit()"
                        class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                        title="Remover">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- === FORMULÁRIO INLINE DE EDIÇÃO (oculto por padrão) === --}}
    <div x-show="editing"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         style="display:none"
         class="px-5 pb-5 pt-4 bg-blue-50 border-t border-blue-100">

        <form method="POST" action="{{ route('finance.expenses.update', $expense) }}"
              class="grid grid-cols-2 gap-3">
            @csrf @method('PUT')
            <input type="hidden" name="mes_referencia" value="{{ $mes->format('Y-m') }}">

            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Descrição</label>
                <input type="text" name="descricao" value="{{ $expense->descricao }}" required
                       class="form-control text-sm w-full">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Valor (R$)</label>
                <input type="number" name="valor" value="{{ $expense->valor }}" step="0.01" min="0.01" required
                       class="form-control text-sm w-full">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Categoria</label>
                <input type="text" name="categoria" value="{{ $expense->categoria }}"
                       class="form-control text-sm w-full" list="categorias-list">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                <select name="tipo_despesa" class="form-control text-sm w-full">
                    <option value="fixa"     {{ $expense->tipo_despesa==='fixa'    ?'selected':'' }}>Fixa</option>
                    <option value="variavel" {{ $expense->tipo_despesa==='variavel'?'selected':'' }}>Variável</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Forma pagamento</label>
                <select name="forma_pagamento" class="form-control text-sm w-full">
                    <option value="pix"      {{ $expense->forma_pagamento==='pix'     ?'selected':'' }}>Pix</option>
                    <option value="debito"   {{ $expense->forma_pagamento==='debito'  ?'selected':'' }}>Débito</option>
                    <option value="dinheiro" {{ $expense->forma_pagamento==='dinheiro'?'selected':'' }}>Dinheiro</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Pessoa</label>
                <select name="pessoa" class="form-control text-sm w-full">
                    <option value="WIL"           {{ $expense->pessoa==='WIL'          ?'selected':'' }}>Willian</option>
                    <option value="MAY"           {{ $expense->pessoa==='MAY'          ?'selected':'' }}>Mayara</option>
                    <option value="compartilhado" {{ $expense->pessoa==='compartilhado'?'selected':'' }}>Compartilhado</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status" class="form-control text-sm w-full">
                    <option value="pendente" {{ $expense->status==='pendente'?'selected':'' }}>Pendente</option>
                    <option value="pago"     {{ $expense->status==='pago'    ?'selected':'' }}>Pago</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Vencimento</label>
                <input type="date" name="data_vencimento"
                       value="{{ $expense->data_vencimento?->format('Y-m-d') }}"
                       class="form-control text-sm w-full">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Data pagamento</label>
                <input type="date" name="data_pagamento"
                       value="{{ $expense->data_pagamento?->format('Y-m-d') }}"
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
