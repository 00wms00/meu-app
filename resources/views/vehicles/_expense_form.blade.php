{{--
    Partial: formulário de nova despesa
    Variáveis esperadas: $vehicle
    Usado pela aba de Despesas no show.blade.php
--}}
<div class="bg-white rounded-lg shadow p-6" x-data="{
    tipo: '{{ old('tipo', '') }}',
    criarLembrete: {{ old('criar_lembrete') ? 'true' : 'false' }},
}">
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

        {{-- KM do serviço: aparece só para manutenção --}}
        <div x-show="tipo === 'manutencao'" x-cloak>
            <label class="block text-sm font-medium text-gray-700">KM no serviço</label>
            <input type="number" name="km_servico"
                   value="{{ old('km_servico', $vehicle->km_atual ?: '') }}"
                   class="form-control mt-1" min="0"
                   placeholder="Ex: 45.000">
            <p class="text-xs text-gray-400 mt-1">Atualiza o hodometro do veículo automaticamente.</p>
        </div>

        {{-- Toggle: criar lembrete (só manutenção) --}}
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
