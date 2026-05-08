<x-app-layout>
    <div style="background:yellow; padding:20px; margin:20px;">
    DEBUG: Itens count = {{ $itens->count() }}<br>
    Card ID = {{ $cardId }}<br>
    Mês = {{ $mesStr }}<br>
    @foreach($itens as $item)
        Item: {{ $item['nome'] }} - {{ $item['parcela'] }} - R$ {{ number_format($item['valor'], 2, ',', '.') }}<br>
    @endforeach
</div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Faturas de Cartão</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4">
            
            {{-- Filtros --}}
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    {{-- Cartão --}}
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cartão</label>
                        <select name="card_id" class="w-full rounded-md border-gray-300">
                            @foreach($cards as $c)
                                <option value="{{ $c->id }}" {{ $cardId == $c->id ? 'selected' : '' }}>
                                    {{ $c->bandeira_label }} - {{ $c->nome }} ({{ $c->pessoa_label }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Mês --}}
                    <div class="w-[160px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                        <select name="mes" class="w-full rounded-md border-gray-300">
                            @foreach($meses as $m)
                                <option value="{{ $m->format('Y-m') }}" {{ $mesStr == $m->format('Y-m') ? 'selected' : '' }}>
                                    {{ $m->translatedFormat('F/Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Filtrar
                    </button>
                </form>
            </div>

            {{-- Detalhes do Cartão --}}
            @if($card)
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full" style="background-color: {{ $card->cor }}"></div>
                    <div>
                        <h3 class="text-lg font-semibold">{{ $card->bandeira_label }} - {{ $card->nome }}</h3>
                        <p class="text-sm text-gray-500">
                            Fechamento: dia {{ $card->dia_fechamento }} | 
                            Vencimento: dia {{ $card->dia_vencimento }} |
                            Limite: R$ {{ number_format($card->limite, 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Itens da Fatura --}}
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold">
                        Fatura {{ \Carbon\Carbon::createFromFormat('Y-m', $mesStr)->translatedFormat('F/Y') }}
                    </h3>
                </div>

                @if($itens->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        Nenhuma despesa encontrada nesta fatura.
                    </div>
                @else
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">Descrição</th>
                                <th class="text-center px-4 py-3 text-sm font-medium text-gray-700 w-[100px]">Parcela</th>
                                <th class="text-center px-4 py-3 text-sm font-medium text-gray-700 w-[80px]">Status</th>
                                <th class="text-right px-4 py-3 text-sm font-medium text-gray-700 w-[120px]">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($itens as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $item['nome'] }}</td>
                                <td class="px-4 py-3 text-center text-sm">{{ $item['parcela'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $item['status'] === 'pago' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $item['status'] === 'pago' ? 'Pago' : 'Pendente' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    R$ {{ number_format($item['valor'], 2, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td class="px-4 py-3" colspan="3">Total da Fatura</td>
                                <td class="px-4 py-3 text-right text-blue-600">
                                    R$ {{ number_format($itens->sum('valor'), 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>