{{--
    Aba de Lembretes de Manutenção
    Variáveis esperadas: $vehicle, $reminders
--}}
<div id="reminders" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Formulário: novo lembrete manual --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">🔔 Novo lembrete</h2>
            <form action="{{ route('vehicles.reminders.store', $vehicle) }}" method="POST" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <input type="text" name="descricao" value="{{ old('descricao') }}"
                           placeholder="Ex: Troca de óleo, Revisão 20k"
                           class="form-control mt-1" required maxlength="120">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">KM do último serviço</label>
                    <input type="number" name="km_ultimo_servico"
                           value="{{ old('km_ultimo_servico', $vehicle->km_atual ?: '') }}"
                           class="form-control mt-1" min="0" placeholder="Ex: 44000">
                    <p class="text-xs text-gray-400 mt-1">Deixe em branco se nunca foi feito.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data do último serviço</label>
                    <input type="date" name="data_ultimo_servico"
                           value="{{ old('data_ultimo_servico') }}"
                           class="form-control mt-1">
                </div>

                <hr class="border-gray-100">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Alertar a cada…</p>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Km</label>
                        <input type="number" name="intervalo_km" value="{{ old('intervalo_km') }}"
                               class="form-control mt-1" min="100"
                               placeholder="Ex: 10000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Meses</label>
                        <input type="number" name="intervalo_meses" value="{{ old('intervalo_meses') }}"
                               class="form-control mt-1" min="1" max="120"
                               placeholder="Ex: 12">
                    </div>
                </div>
                <p class="text-xs text-gray-400">Preencha ao menos um dos dois.</p>

                <button type="submit" class="btn-primary w-full mt-2">Criar lembrete</button>
            </form>
        </div>
    </div>

    {{-- Lista de lembretes --}}
    <div class="lg:col-span-2">
        @if($reminders->isEmpty())
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                <p class="text-3xl mb-3">🔔</p>
                <p class="font-medium">Nenhum lembrete cadastrado ainda.</p>
                <p class="text-sm mt-1">Crie um lembrete ou registre uma manutenção com lembrete integrado.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($reminders as $reminder)
                    @php
                        $status    = $reminder->statusAlerta($vehicle->km_atual);
                        $kmRest    = $reminder->kmRestantes($vehicle->km_atual);
                        $diasRest  = $reminder->diasRestantes();
                        $motivos   = $reminder->motivoAlerta($vehicle->km_atual);
                        $cfg = [
                            'ok'      => ['border'=>'border-green-200',  'bg'=>'bg-green-50',  'badge'=>'bg-green-100 text-green-800',  'icon'=>'✅', 'label'=>'Em dia'],
                            'proximo' => ['border'=>'border-yellow-200', 'bg'=>'bg-yellow-50', 'badge'=>'bg-yellow-100 text-yellow-800','icon'=>'⚠️', 'label'=>'Próximo'],
                            'vencido' => ['border'=>'border-red-200',    'bg'=>'bg-red-50',    'badge'=>'bg-red-100 text-red-800',      'icon'=>'🔴', 'label'=>'Vencido'],
                            'sem_ref' => ['border'=>'border-gray-200',   'bg'=>'bg-gray-50',   'badge'=>'bg-gray-100 text-gray-600',   'icon'=>'❓', 'label'=>'Sem referência'],
                        ][$status] ?? ['border'=>'border-gray-200','bg'=>'bg-gray-50','badge'=>'bg-gray-100 text-gray-600','icon'=>'❓','label'=>$status];
                    @endphp

                    <div class="bg-white border {{ $cfg['border'] }} {{ $cfg['bg'] }} rounded-lg shadow-sm p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">

                                {{-- Título + badge --}}
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-gray-900 truncate">{{ $reminder->descricao }}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap {{ $cfg['badge'] }}">
                                        {{ $cfg['icon'] }} {{ $cfg['label'] }}
                                    </span>
                                </div>

                                {{-- Detalhes --}}
                                <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
                                    @if($reminder->intervalo_km)
                                        <span>📐 A cada <strong>{{ number_format($reminder->intervalo_km, 0, ',', '.') }} km</strong></span>
                                    @endif
                                    @if($reminder->intervalo_meses)
                                        <span>📅 A cada <strong>{{ $reminder->intervalo_meses }} {{ $reminder->intervalo_meses === 1 ? 'mês' : 'meses' }}</strong></span>
                                    @endif
                                    @if($reminder->km_ultimo_servico)
                                        <span>🔧 Último: <strong>{{ number_format($reminder->km_ultimo_servico, 0, ',', '.') }} km</strong></span>
                                    @endif
                                    @if($reminder->km_alerta)
                                        <span>🚨 Alerta km: <strong>{{ number_format($reminder->km_alerta, 0, ',', '.') }}</strong></span>
                                    @endif
                                    @if($reminder->data_alerta)
                                        <span>🚨 Alerta data: <strong>{{ $reminder->data_alerta->format('d/m/Y') }}</strong></span>
                                    @endif
                                    @if($reminder->data_ultimo_servico)
                                        <span>📅 Último serv.: <strong>{{ $reminder->data_ultimo_servico->format('d/m/Y') }}</strong></span>
                                    @endif
                                </div>

                                {{-- Motivos do alerta --}}
                                @if(count($motivos))
                                    <div class="mt-2 space-y-0.5">
                                        @foreach($motivos as $motivo)
                                            <p class="text-sm font-semibold {{ $status === 'vencido' ? 'text-red-700' : 'text-yellow-700' }}">
                                                ⚠️ {{ $motivo }}
                                            </p>
                                        @endforeach
                                    </div>
                                @elseif($status === 'ok')
                                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-green-700">
                                        @if($kmRest !== null)
                                            <span>Faltam <strong>{{ number_format($kmRest, 0, ',', '.') }} km</strong></span>
                                        @endif
                                        @if($diasRest !== null)
                                            <span>Faltam <strong>{{ $diasRest }} dia(s)</strong></span>
                                        @endif
                                    </div>
                                @endif

                            </div>

                            {{-- Ações --}}
                            <div class="flex flex-col items-end gap-2 shrink-0" x-data="{ open: false }">
                                <button @click="open = !open" type="button"
                                        class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md transition whitespace-nowrap">
                                    ✓ Marcar feito
                                </button>

                                <div x-show="open" x-cloak
                                     class="mt-1 bg-white border border-gray-200 rounded-md shadow p-3 w-48">
                                    <form action="{{ route('vehicles.reminders.feito', [$vehicle, $reminder]) }}"
                                          method="POST" class="space-y-2">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">KM realizado</label>
                                            <input type="number" name="km_realizado"
                                                   value="{{ $vehicle->km_atual ?: ($reminder->km_alerta ?? '') }}"
                                                   min="0"
                                                   class="form-control mt-0.5 text-sm py-1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Data</label>
                                            <input type="date" name="data_realizado"
                                                   value="{{ now()->toDateString() }}"
                                                   class="form-control mt-0.5 text-sm py-1">
                                        </div>
                                        <button type="submit" class="btn-primary text-xs py-1 w-full">Salvar</button>
                                    </form>
                                </div>

                                <form action="{{ route('vehicles.reminders.destroy', [$vehicle, $reminder]) }}"
                                      method="POST"
                                      onsubmit="return confirm('Remover este lembrete?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
