@extends('layouts.app')

@section('title', 'Editar Item')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">✏️ Editar Item</h1>
    <p class="mt-1 text-gray-600">Nota #{{ $invoice->numero }} - {{ $invoice->nome_estabelecimento }}</p>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📦 {{ $item->product->nome }}</h2>
        
        <form action="{{ route('invoices.items.update', ['invoice' => $invoice, 'item' => $item]) }}" method="POST" id="editItemForm">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <!-- Nome do Produto -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto</label>
                    <input type="text" name="nome_produto" id="nome_produto"
                           value="{{ old('nome_produto', $item->product->nome) }}" 
                           class="form-control" required>
                </div>

                <!-- Quantidade e Unidade -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                        <input type="text" name="quantidade" id="quantidade"
                               value="{{ old('quantidade', number_format($item->quantidade, 3, '.', '')) }}" 
                               class="form-control" required
                               oninput="calcularValores()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unidade</label>
                        <select name="unidade" id="unidade" class="form-control" required>
                            <option value="UN" {{ $item->unidade == 'UN' ? 'selected' : '' }}>UN - Unidade</option>
                            <option value="KG" {{ $item->unidade == 'KG' ? 'selected' : '' }}>KG - Quilograma</option>
                            <option value="L" {{ $item->unidade == 'L' ? 'selected' : '' }}>L - Litro</option>
                            <option value="CX" {{ $item->unidade == 'CX' ? 'selected' : '' }}>CX - Caixa</option>
                            <option value="PC" {{ $item->unidade == 'PC' ? 'selected' : '' }}>PC - Peça</option>
                            <option value="FD" {{ $item->unidade == 'FD' ? 'selected' : '' }}>FD - Fardo</option>
                            <option value="LT" {{ $item->unidade == 'LT' ? 'selected' : '' }}>LT - Lata</option>
                        </select>
                    </div>
                </div>

                <!-- Valores -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Valor Unitário (R$)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">R$</span>
                                <input type="text" name="valor_unitario" id="valor_unitario"
                                       value="{{ old('valor_unitario', number_format($item->valor_unitario, 2, '.', '')) }}" 
                                       class="form-control pl-10" required
                                       oninput="calcularTotal()">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Valor Total (R$)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">R$</span>
                                <input type="text" name="valor_total" id="valor_total"
                                       value="{{ old('valor_total', number_format($item->valor_total, 2, '.', '')) }}" 
                                       class="form-control pl-10" required
                                       oninput="calcularUnitario()">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Indicador de cálculo -->
                    <div id="calculoInfo" class="text-xs text-gray-500 bg-white rounded p-2 border border-gray-100">
                        💡 <strong>Como funciona:</strong>
                        <ul class="mt-1 ml-4 list-disc">
                            <li>Ao editar o <strong>Valor Unitário</strong>, o Valor Total é recalculado automaticamente (Unitário × Quantidade)</li>
                            <li>Ao editar o <strong>Valor Total</strong>, o Valor Unitário é recalculado automaticamente (Total ÷ Quantidade)</li>
                        </ul>
                    </div>
                    
                    <!-- Último campo editado -->
                    <input type="hidden" name="ultimo_campo_editado" id="ultimo_campo_editado" value="">
                </div>
            </div>

            <div class="mt-6 flex gap-3 justify-between">
                <button type="submit" class="btn-primary" onclick="return validarFormulario()">
                    💾 Salvar Alterações
                </button>
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Flag para evitar loop infinito
let isCalculating = false;

// Formatar número brasileiro para float
function parseFloatBR(value) {
    if (!value) return 0;
    // Remove tudo exceto números, vírgula e ponto
    value = value.replace(/[^\d,.]/g, '');
    // Se tiver vírgula, substitui por ponto
    value = value.replace(',', '.');
    return parseFloat(value) || 0;
}

// Formatar para exibição (2 casas decimais)
function formatMoney(value) {
    return value.toFixed(2).replace('.', ',');
}

// Formatar quantidade (3 casas)
function formatQuantity(value) {
    return value.toFixed(3).replace('.', ',');
}

// Calcular Valor Total = Unitário × Quantidade
function calcularTotal() {
    if (isCalculating) return;
    isCalculating = true;
    
    const unitario = parseFloatBR(document.getElementById('valor_unitario').value);
    const quantidade = parseFloatBR(document.getElementById('quantidade').value);
    
    if (unitario > 0 && quantidade > 0) {
        const total = unitario * quantidade;
        document.getElementById('valor_total').value = total.toFixed(2).replace('.', ',');
    }
    
    document.getElementById('ultimo_campo_editado').value = 'unitario';
    
    isCalculating = false;
}

// Calcular Valor Unitário = Total ÷ Quantidade
function calcularUnitario() {
    if (isCalculating) return;
    isCalculating = true;
    
    const total = parseFloatBR(document.getElementById('valor_total').value);
    const quantidade = parseFloatBR(document.getElementById('quantidade').value);
    
    if (total > 0 && quantidade > 0) {
        const unitario = total / quantidade;
        document.getElementById('valor_unitario').value = unitario.toFixed(2).replace('.', ',');
    }
    
    document.getElementById('ultimo_campo_editado').value = 'total';
    
    isCalculating = false;
}

// Calcular ambos quando a quantidade mudar
function calcularValores() {
    if (isCalculating) return;
    isCalculating = true;
    
    const ultimoCampo = document.getElementById('ultimo_campo_editado').value;
    const quantidade = parseFloatBR(document.getElementById('quantidade').value);
    
    if (quantidade > 0) {
        if (ultimoCampo === 'unitario') {
            // Recalcular total baseado no unitário
            const unitario = parseFloatBR(document.getElementById('valor_unitario').value);
            if (unitario > 0) {
                const total = unitario * quantidade;
                document.getElementById('valor_total').value = total.toFixed(2).replace('.', ',');
            }
        } else if (ultimoCampo === 'total') {
            // Recalcular unitário baseado no total
            const total = parseFloatBR(document.getElementById('valor_total').value);
            if (total > 0) {
                const unitario = total / quantidade;
                document.getElementById('valor_unitario').value = unitario.toFixed(2).replace('.', ',');
            }
        }
    }
    
    isCalculating = false;
}

// Validação antes de enviar
function validarFormulario() {
    const quantidade = parseFloatBR(document.getElementById('quantidade').value);
    const unitario = parseFloatBR(document.getElementById('valor_unitario').value);
    const total = parseFloatBR(document.getElementById('valor_total').value);
    
    if (quantidade <= 0) {
        alert('A quantidade deve ser maior que zero.');
        return false;
    }
    
    if (unitario <= 0) {
        alert('O valor unitário deve ser maior que zero.');
        return false;
    }
    
    if (total <= 0) {
        alert('O valor total deve ser maior que zero.');
        return false;
    }
    
    // Garantir que os valores estejam consistentes
    const totalCalculado = unitario * quantidade;
    const diferenca = Math.abs(total - totalCalculado);
    
    if (diferenca > 0.02) {
        const confirmar = confirm(
            'Os valores não estão batendo:\n\n' +
            'Valor Unitário: R$ ' + formatMoney(unitario) + '\n' +
            'Quantidade: ' + formatQuantity(quantidade) + '\n' +
            'Total Calculado: R$ ' + formatMoney(totalCalculado) + '\n' +
            'Total Informado: R$ ' + formatMoney(total) + '\n\n' +
            'Deseja usar o valor total informado?'
        );
        if (!confirmar) {
            return false;
        }
    }
    
    // Converter vírgulas para pontos antes de enviar
    document.getElementById('quantidade').value = quantidade.toString().replace(',', '.');
    document.getElementById('valor_unitario').value = unitario.toString().replace(',', '.');
    document.getElementById('valor_total').value = total.toString().replace(',', '.');
    
    return true;
}

// Inicializar com os valores atuais
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('ultimo_campo_editado').value = 'unitario';
});
</script>
@endpush
