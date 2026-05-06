{{--
    Aba de Lembretes de Manutenção
    Variáveis esperadas: $vehicle, $reminders
--}}
<div id="reminders" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Formulário: novo lembrete --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">🔔 Novo lembrete</h2>
            <form action="{{ route('vehicles.reminders.store', $vehicle) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <input type="text" name="descricao" value="{{ old('descricao') }}" placeholder="Ex: Troca de óleo"
                           class="form-control mt-1" required maxlength="120">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">KM do último serviço</label>
                    <input type="number" name="km_ultimo_servico"
                           value="{{ old('km_ultimo_servico', $vehicle->km_atual ?: '') }}"
                           class="form-control mt-1" min="0"
                           placeholder="Ex: 44000">
                    <p class="text-xs text-gray-400 mt-1">Deixe em branco se o serviço ainda nunca foi feito.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Intervalo (km)</label>
                    <input type="number" name="intervalo_km" value="{{ old('intervalo_km') }}"
                           class="form-control mt-1" min="100" required
                           placeholder="Ex: 10000">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data do último serviço</label>
                    <input type="date" name="data_ultimo_servico"
                           value="{{ old('data_ultimo_servico') }}"
                           class="form-control mt-1">
                </div>
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
                <p class="text-sm mt-1">Crie um lembrete para receber alertas de manutenção por km.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($reminders as $reminder)
                    @php
                        $status     = $reminder->statusAlerta($vehicle->km_atual);
                        $restantes  = $reminder->kmRestantes($vehicle->km_atual);
                        $statusCfg  = [
                            'ok'      => ['bg' => 'bg-green-50 border-green-200',  'badge' => 'bg-green-100 text-green-800',  'icon' => '✅', 'label' => 'Em dia'],
                            'proximo' => ['bg' => 'bg-yellow-50 border-yellow-200','badge' => 'bg-yellow-100 text-yellow-800','icon' => '⚠️', 'label' => 'Próximo'],
                            'vencido' => ['bg' => 'bg-red-50 border-red-200',      'badge' => 'bg-red-100 text-red-800',      'icon' => '🔴', 'label' => 'Vencido'],
                            'sem_km'  => ['bg' => 'bg-gray-50 border-gray-200',   'badge' => 'bg-gray-100 text-gray-600',   'icon' => '❓', 'label' => 'Sem km'],
                        ][$status];
                    @endphp
                    <div class="bg-white border {{ $statusCfg['bg'] }} rounded-lg shadow-sm p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-gray-900">{{ $reminder->descricao }}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusCfg['badge'] }}">
                                        {{ $statusCfg['icon'] }} {{ $statusCfg['label'] }}
                                    </span>
                                </div>
                                <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-1 text-sm text-gray-600">
                                    <span>📐 Intervalo: <strong>{{ number_format($reminder->intervalo_km, 0, ',', '.') }} km</strong></span>
                                    @if($reminder->km_ultimo_servico)
                                        <span>🔧 Último serv.: <strong>{{ number_format($reminder->km_ultimo_servico, 0, ',', '.') }} km</strong></span>
                                        <span>🚨 Alerta em: <strong>{{ number_format($reminder->km_alerta, 0, ',', '.') }} km</strong></span>
                                    @endif
                                    @if($restantes !== null)
                                        <span class="col-span-2 sm:col-span-3 {{ $status === 'vencido' ? 'text-red-700 font-semibold' : ($status === 'proximo' ? 'text-yellow-700 font-semibold' : '') }}">
                                            @if($restantes < 0)
                                                ⚠️ Passou {{ number_format(abs($restantes), 0, ',', '.') }} km do prazo!
                                            @else
                                                Faltam {{ number_format($restantes, 0, ',', '.') }} km
                                            @endif
                                        </span>
                                    @endif
                                    @if($reminder->data_ultimo_servico)
                                        <span>📅 Data: {{ $reminder->data_ultimo_servico->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Ações --}}
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                {{-- Botão: Marcar como feito --}}
                                <button
                                    x-data="{ open: false }"
                                    @click="open = !open"
                                    type="button"
                                    class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md transition">
                                    ✓ Feito
                                </button>

                                {{-- Formulário inline: Feito --}}
                                <div x-data="{ open: false }" class="w-full">
                                    <button @click="open = !open" type="button"
                                            class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md transition w-full">
                                        ✓ Marcar feito
                                    </button>
                                    <div x-show="open" x-cloak class="mt-2 bg-white border border-gray-200 rounded-md shadow p-3">
                                        <form action="{{ route('vehicles.reminders.feito', [$vehicle, $reminder]) }}" method="POST" class="space-y-2">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">KM realizado</label>
                                                <input type="number" name="km_realizado"
                                                       value="{{ $vehicle->km_atual ?: $reminder->km_alerta }}"
                                                       min="0" required
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
                                </div>

                                {{-- Remover --}}
                                <form action="{{ route('vehicles.reminders.destroy', [$vehicle, $reminder]) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('Remover este lembrete?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
