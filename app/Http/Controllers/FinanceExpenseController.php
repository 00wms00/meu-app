<?php

namespace App\Http\Controllers;

use App\Models\FinanceExpense;
use App\Models\FuelEntry;
use App\Models\Invoice;
use App\Models\VehicleExpense;
use App\Models\CreditCard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FinanceExpenseController extends Controller
{
public function index(Request $request)
{
    $mes = $request->mes
        ? Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth()
        : Carbon::now()->startOfMonth();

    $mesInicio = $mes->copy()->startOfMonth();
    $mesFim    = $mes->copy()->endOfMonth();

    $expenses = FinanceExpense::doMes($mes)
        ->orderBy('tipo_despesa')
        ->orderBy('categoria')
        ->orderBy('descricao')
        ->get();

    $fixas     = $expenses->where('tipo_despesa', 'fixa');
    $variaveis = $expenses->where('tipo_despesa', 'variavel');

    $creditCards = CreditCard::orderBy('nome')->get();

    // Despesas de mercado (notas fiscais importadas)
    $invoicesDoMes = Invoice::whereBetween('data_emissao', [$mesInicio, $mesFim])
        ->where('user_id', Auth::id())
        ->get()
        ->groupBy('nome_estabelecimento')
        ->map(fn($grupo) => [
            'descricao'  => $grupo->first()->nome_estabelecimento,
            'valor'      => $grupo->sum('valor_pago'),
            'quantidade' => $grupo->count(),
        ])
        ->values();

    $totalMercado = $invoicesDoMes->sum('valor');

    // Despesas de veículos (abastecimentos + manutenções)
    $vexpRaw = VehicleExpense::whereBetween('data', [$mesInicio, $mesFim])
        ->whereHas('vehicle', fn($q) => $q->where('user_id', Auth::id()))
        ->with('vehicle')
        ->get();

    $fuelRaw = FuelEntry::whereBetween('data', [$mesInicio, $mesFim])
        ->where('user_id', Auth::id())
        ->with('vehicle')
        ->get();

    $vehicleIds = $vexpRaw->pluck('vehicle_id')
        ->merge($fuelRaw->pluck('vehicle_id'))
        ->unique();

    $vehicleExpensesDoMes = $vehicleIds->map(function ($vehicleId) use ($vexpRaw, $fuelRaw) {
        $vexps = $vexpRaw->where('vehicle_id', $vehicleId);
        $fuels = $fuelRaw->where('vehicle_id', $vehicleId);
        $nomeVeiculo = ($vexps->first()?->vehicle->apelido ?? $fuels->first()?->vehicle->apelido ?? 'Veículo');
        $itens = collect();
        foreach ($vexps as $e) {
            $itens->push(['tipo' => $e->tipo ?? 'Manutenção', 'descricao' => $e->descricao ?? '', 'valor' => (float)$e->valor, 'data' => $e->data->format('d/m'), 'icone' => '🔧']);
        }
        foreach ($fuels as $f) {
            $litros = $f->litros ? number_format((float)$f->litros, 2, ',', '.') . 'L' : '';
            $itens->push(['tipo' => 'Combustível' . ($f->tipo_combustivel ? ' (' . $f->tipo_combustivel . ')' : ''), 'descricao' => trim(($f->posto ?? '') . ($litros ? ' • ' . $litros : '')), 'valor' => (float)$f->valor, 'data' => $f->data->format('d/m'), 'icone' => '⛽']);
        }
        return [
            'descricao'  => $nomeVeiculo,
            'valor'      => $vexps->sum(fn($e) => (float)$e->valor) + $fuels->sum(fn($f) => (float)$f->valor),
            'quantidade' => $itens->count(),
            'itens'      => $itens->sortBy('data')->values()->toArray(),
        ];
    })->values();

    $totalVeiculos  = $vehicleExpensesDoMes->sum('valor');

    // === CORREÇÃO AQUI: totais considerando importações como PAGAS ===
    $totalFixas     = $fixas->sum('valor');
    $totalVariaveis = $variaveis->sum('valor') + $totalMercado + $totalVeiculos;
    $totalGeral     = $totalFixas + $totalVariaveis;
    
    // Despesas manuais pagas + todas as importações (mercado + veículos são sempre "pagas")
    $totalPago      = $expenses->where('status', 'pago')->sum('valor') + $totalMercado + $totalVeiculos;
    $totalPendente  = $expenses->where('status', 'pendente')->sum('valor');

    $porCategoria = $variaveis->groupBy('categoria')->map(fn($g) => $g->sum('valor'))->sortByDesc(fn($v) => $v);
    if ($totalMercado > 0) $porCategoria->put('Mercado', $porCategoria->get('Mercado', 0) + $totalMercado);
    if ($totalVeiculos > 0) $porCategoria->put('Carro',   $porCategoria->get('Carro', 0)   + $totalVeiculos);
    $porCategoria = $porCategoria->sortByDesc(fn($v) => $v);

    $meses = collect(range(0, 11))->map(fn($i) => Carbon::now()->startOfMonth()->subMonths($i));

    return view('finance.expenses.index', compact(
        'expenses', 'fixas', 'variaveis', 'mes',
        'totalFixas', 'totalVariaveis', 'totalGeral',
        'totalPago', 'totalPendente', 'porCategoria', 'meses',
        'invoicesDoMes', 'totalMercado',
        'vehicleExpensesDoMes', 'totalVeiculos',
        'creditCards'
    ));
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'descricao'       => 'required|string|max:255',
            'tipo_despesa'    => 'required|in:fixa,variavel',
            'categoria'       => 'nullable|string|max:100',
            'forma_pagamento' => 'required|in:debito,pix,dinheiro,credito',
            'credit_card_id'  => 'nullable|exists:credit_cards,id',
            'parcelas_total'  => 'nullable|integer|min:1|max:48',
            'pessoa'          => 'required|in:WIL,MAY,compartilhado',
            'valor'           => 'required|numeric|min:0.01',
            'mes_referencia'  => 'required|date_format:Y-m',
            'data_vencimento' => 'nullable|date',
            'data_pagamento'  => 'nullable|date',
            'status'          => 'required|in:pago,pendente',
            'observacao'      => 'nullable|string|max:500',
        ]);

        $mesBase = Carbon::createFromFormat('Y-m', $data['mes_referencia'])->startOfMonth();

        if ($data['forma_pagamento'] !== 'credito') {
            // Pagamento à vista: salva normalmente
            $data['credit_card_id'] = null;
            $data['parcelas_total'] = 1;
            $data['mes_referencia'] = $mesBase;
            $data['origem']         = 'manual';
            FinanceExpense::create($data);
        } else {
            // Crédito parcelado: gera parcelas com UUID de grupo
            $parcelas    = max(1, (int)($data['parcelas_total'] ?? 1));
            $valorParc   = round((float)$data['valor'] / $parcelas, 2);
            $cardId      = $data['credit_card_id'];
            $grupoId     = (string) Str::uuid();
            $nomeBase    = $data['descricao'];

            // Distribui centavos na primeira parcela
            $somaParcelas = $valorParc * $parcelas;
            $diferenca    = round((float)$data['valor'] - $somaParcelas, 2);

            for ($i = 0; $i < $parcelas; $i++) {
                $mes = $mesBase->copy()->addMonths($i);
                $valor = $valorParc;
                
                // Adiciona a diferença na primeira parcela
                if ($i === 0) {
                    $valor += $diferenca;
                }

                FinanceExpense::create([
                    'grupo_parcelas'  => $grupoId,
                    'descricao'       => $nomeBase . ($parcelas > 1 ? ' (' . ($i + 1) . '/' . $parcelas . ')' : ''),
                    'tipo_despesa'    => $data['tipo_despesa'],
                    'categoria'       => $data['categoria'] ?? null,
                    'forma_pagamento' => 'credito',
                    'credit_card_id'  => $cardId,
                    'parcelas_total'  => $parcelas,
                    'pessoa'          => $data['pessoa'],
                    'valor'           => round($valor, 2),
                    'mes_referencia'  => $mes,
                    'data_vencimento' => $data['data_vencimento'] ?? null,
                    'data_pagamento'  => $i === 0 ? ($data['data_pagamento'] ?? null) : null,
                    'status'          => $i === 0 ? $data['status'] : 'pendente',
                    'observacao'      => $data['observacao'] ?? null,
                    'origem'          => 'manual',
                ]);
            }
        }

        return redirect()
            ->route('finance.expenses.index', ['mes' => $request->mes_referencia])
            ->with('success', 'Despesa adicionada!');
    }

    public function update(Request $request, FinanceExpense $expense)
    {
        $data = $request->validate([
            'descricao'       => 'required|string|max:255',
            'tipo_despesa'    => 'required|in:fixa,variavel',
            'categoria'       => 'nullable|string|max:100',
            'forma_pagamento' => 'required|in:debito,pix,dinheiro,credito',
            'credit_card_id'  => 'nullable|exists:credit_cards,id',
            'parcelas_total'  => 'nullable|integer|min:1|max:48',
            'pessoa'          => 'required|in:WIL,MAY,compartilhado',
            'valor'           => 'required|numeric|min:0.01',
            'mes_referencia'  => 'required|date_format:Y-m',
            'data_vencimento' => 'nullable|date',
            'data_pagamento'  => 'nullable|date',
            'status'          => 'required|in:pago,pendente',
            'observacao'      => 'nullable|string|max:500',
        ]);

        if ($data['forma_pagamento'] !== 'credito') {
            $data['credit_card_id'] = null;
            $data['parcelas_total'] = 1;
            $data['grupo_parcelas'] = null;
        } else {
            $data['parcelas_total'] = $data['parcelas_total'] ?? 1;
        }

        $data['mes_referencia'] = Carbon::createFromFormat('Y-m', $data['mes_referencia'])->startOfMonth();
        $expense->update($data);

        return redirect()
            ->route('finance.expenses.index', ['mes' => $request->mes_referencia])
            ->with('success', 'Despesa atualizada!');
    }

    public function destroy(FinanceExpense $expense)
    {
        $mes = $expense->mes_referencia->format('Y-m');
        
        // Se tem grupo de parcelas, exclui TODAS as parcelas irmãs
        if ($expense->grupo_parcelas) {
            $totalExcluidas = FinanceExpense::doGrupo($expense->grupo_parcelas)->delete();
            
            return redirect()
                ->route('finance.expenses.index', ['mes' => $mes])
                ->with('success', "Todas as {$totalExcluidas} parcelas foram removidas.");
        }
        
        // Despesa não parcelada: exclui normalmente
        $expense->delete();
        
        return redirect()
            ->route('finance.expenses.index', ['mes' => $mes])
            ->with('success', 'Despesa removida.');
    }

    public function togglePago(FinanceExpense $expense)
    {
        $expense->update([
            'status'         => $expense->isPago() ? 'pendente' : 'pago',
            'data_pagamento' => $expense->isPago() ? null : now()->toDateString(),
        ]);
        return redirect()->back()->with('success', 'Status atualizado!');
    }

    public function duplicarFixas(Request $request)
    {
        $mes    = Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth();
        $mesAnt = $mes->copy()->subMonth();
        $fixas  = FinanceExpense::doMes($mesAnt)->fixas()->get();
        $criadas = 0;

        foreach ($fixas as $f) {
            $existe = FinanceExpense::doMes($mes)
                ->where('descricao', $f->descricao)
                ->where('pessoa', $f->pessoa)
                ->where('tipo_despesa', 'fixa')
                ->exists();

            if (!$existe) {
                FinanceExpense::create([
                    'descricao'       => $f->descricao,
                    'tipo_despesa'    => 'fixa',
                    'categoria'       => $f->categoria,
                    'forma_pagamento' => $f->forma_pagamento,
                    'credit_card_id'  => $f->credit_card_id,
                    'parcelas_total'  => $f->parcelas_total,
                    'pessoa'          => $f->pessoa,
                    'valor'           => $f->valor,
                    'mes_referencia'  => $mes,
                    'data_vencimento' => $f->data_vencimento ? $mes->copy()->day($f->data_vencimento->day) : null,
                    'data_pagamento'  => null,
                    'status'          => 'pendente',
                    'origem'          => 'manual',
                    'observacao'      => $f->observacao,
                ]);
                $criadas++;
            }
        }

        return redirect()
            ->route('finance.expenses.index', ['mes' => $mes->format('Y-m')])
            ->with('success', "{$criadas} despesa(s) fixa(s) duplicada(s) do mês anterior.");
    }
}