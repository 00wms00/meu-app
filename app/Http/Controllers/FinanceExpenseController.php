<?php

namespace App\Http\Controllers;

use App\Models\FinanceExpense;
use App\Models\Invoice;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceExpenseController extends Controller
{
    public function index(Request $request)
    {
        $mes = $request->mes
            ? Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $mesInicio = $mes->copy()->startOfMonth();
        $mesFim    = $mes->copy()->endOfMonth();

        // ---- Despesas manuais do banco --------------------------------
        $expenses = FinanceExpense::doMes($mes)
            ->orderBy('tipo_despesa')
            ->orderBy('categoria')
            ->orderBy('descricao')
            ->get();

        $fixas    = $expenses->where('tipo_despesa', 'fixa');
        $variaveis = $expenses->where('tipo_despesa', 'variavel');

        // ---- Mercado: invoices do mês --------------------------------
        // Agrupa por loja (nome_estabelecimento), soma valor_pago
        $invoicesDoMes = Invoice::whereBetween('data_emissao', [$mesInicio, $mesFim])
            ->where('user_id', Auth::id())
            ->get()
            ->groupBy('nome_estabelecimento')
            ->map(function ($grupo) {
                return [
                    'descricao'       => $grupo->first()->nome_estabelecimento,
                    'valor'           => $grupo->sum('valor_pago'),
                    'quantidade'      => $grupo->count(),
                    'categoria'       => 'Mercado',
                    'forma_pagamento' => $grupo->first()->forma_pagamento ?? 'pix',
                    'origem'          => 'mercado',
                    'ids'             => $grupo->pluck('id'),
                ];
            })
            ->values();

        $totalMercado = $invoicesDoMes->sum('valor');

        // ---- Veículos: vehicle_expenses do mês ----------------------
        $vehicleExpensesDoMes = VehicleExpense::whereBetween('data', [$mesInicio, $mesFim])
            ->where('user_id', Auth::id())
            ->with('vehicle')
            ->get()
            ->groupBy(fn($e) => $e->vehicle->nome ?? 'Veículo')
            ->map(function ($grupo, $veiculo) {
                return [
                    'descricao'  => $veiculo,
                    'valor'      => $grupo->sum('valor'),
                    'quantidade' => $grupo->count(),
                    'categoria'  => 'Carro',
                    'origem'     => 'veiculo',
                    'itens'      => $grupo->map(fn($e) => [
                        'tipo'   => $e->tipo,
                        'valor'  => $e->valor,
                        'data'   => $e->data->format('d/m'),
                        'descricao' => $e->descricao,
                    ]),
                ];
            })
            ->values();

        $totalVeiculos = $vehicleExpensesDoMes->sum('valor');

        // ---- Totais --------------------------------------------------
        $totalFixas     = $fixas->sum('valor');
        $totalVariaveis = $variaveis->sum('valor') + $totalMercado + $totalVeiculos;
        $totalGeral     = $totalFixas + $totalVariaveis;
        $totalPago      = $expenses->where('status', 'pago')->sum('valor') + $totalMercado; // mercado = pago na hora
        $totalPendente  = $expenses->where('status', 'pendente')->sum('valor') + $totalVeiculos;

        // ---- Agrupamento por categoria (variáveis manuais + externas) --
        $porCategoria = $variaveis
            ->groupBy('categoria')
            ->map(fn($g) => $g->sum('valor'))
            ->sortByDesc(fn($v) => $v);

        if ($totalMercado > 0) {
            $porCategoria->put('Mercado', ($porCategoria->get('Mercado', 0)) + $totalMercado);
        }
        if ($totalVeiculos > 0) {
            $porCategoria->put('Carro', ($porCategoria->get('Carro', 0)) + $totalVeiculos);
        }
        $porCategoria = $porCategoria->sortByDesc(fn($v) => $v);

        $meses = collect(range(0, 11))
            ->map(fn($i) => Carbon::now()->startOfMonth()->subMonths($i));

        return view('finance.expenses.index', compact(
            'expenses', 'fixas', 'variaveis', 'mes',
            'totalFixas', 'totalVariaveis', 'totalGeral',
            'totalPago', 'totalPendente', 'porCategoria', 'meses',
            'invoicesDoMes', 'totalMercado',
            'vehicleExpensesDoMes', 'totalVeiculos'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'descricao'       => 'required|string|max:255',
            'tipo_despesa'    => 'required|in:fixa,variavel',
            'categoria'       => 'nullable|string|max:100',
            'forma_pagamento' => 'required|in:debito,pix,dinheiro',
            'pessoa'          => 'required|in:WIL,MAY,compartilhado',
            'valor'           => 'required|numeric|min:0.01',
            'mes_referencia'  => 'required|date_format:Y-m',
            'data_vencimento' => 'nullable|date',
            'data_pagamento'  => 'nullable|date',
            'status'          => 'required|in:pago,pendente',
            'observacao'      => 'nullable|string|max:500',
        ]);

        $data['mes_referencia'] = Carbon::createFromFormat('Y-m', $data['mes_referencia'])->startOfMonth();
        $data['origem']         = 'manual';

        FinanceExpense::create($data);

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
            'forma_pagamento' => 'required|in:debito,pix,dinheiro',
            'pessoa'          => 'required|in:WIL,MAY,compartilhado',
            'valor'           => 'required|numeric|min:0.01',
            'mes_referencia'  => 'required|date_format:Y-m',
            'data_vencimento' => 'nullable|date',
            'data_pagamento'  => 'nullable|date',
            'status'          => 'required|in:pago,pendente',
            'observacao'      => 'nullable|string|max:500',
        ]);

        $data['mes_referencia'] = Carbon::createFromFormat('Y-m', $data['mes_referencia'])->startOfMonth();

        $expense->update($data);

        return redirect()
            ->route('finance.expenses.index', ['mes' => $request->mes_referencia])
            ->with('success', 'Despesa atualizada!');
    }

    public function destroy(FinanceExpense $expense)
    {
        $mes = $expense->mes_referencia->format('Y-m');
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

        $fixas   = FinanceExpense::doMes($mesAnt)->fixas()->get();
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
                    'pessoa'          => $f->pessoa,
                    'valor'           => $f->valor,
                    'mes_referencia'  => $mes,
                    'data_vencimento' => $f->data_vencimento
                        ? $mes->copy()->day($f->data_vencimento->day)
                        : null,
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
