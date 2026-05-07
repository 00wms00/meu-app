<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\FinanceExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FaturaController extends Controller
{
    public function index(Request $request)
    {
        $mesesAtras  = max(0, min(11, (int)($request->get('passados', 2))));
        $mesesFrente = max(0, min(11, (int)($request->get('futuros',  3))));

        $hoje     = Carbon::now()->startOfMonth();
        $mesInicio = $hoje->copy()->subMonths($mesesAtras);
        $mesFim    = $hoje->copy()->addMonths($mesesFrente);

        // Gera lista de meses no intervalo
        $meses = collect();
        $cursor = $mesInicio->copy();
        while ($cursor->lte($mesFim)) {
            $meses->push($cursor->copy());
            $cursor->addMonth();
        }

        $cards = CreditCard::orderBy('pessoa')->orderBy('nome')->get();

        // Busca todas as despesas de crédito no período de uma vez
        $despesas = FinanceExpense::where('forma_pagamento', 'credito')
            ->whereIn('credit_card_id', $cards->pluck('id'))
            ->whereBetween('mes_referencia', [$mesInicio, $mesFim])
            ->orderBy('mes_referencia')
            ->orderBy('descricao')
            ->get();

        // Monta matriz: [card_id][mes_key] => ['total' => x, 'itens' => [...]]
        $faturas = [];
        foreach ($cards as $card) {
            foreach ($meses as $mes) {
                $key = $mes->format('Y-m');
                $faturas[$card->id][$key] = ['total' => 0, 'itens' => []];
            }
        }

        foreach ($despesas as $d) {
            $key = $d->mes_referencia->format('Y-m');
            if (!isset($faturas[$d->credit_card_id][$key])) continue;
            $faturas[$d->credit_card_id][$key]['total']  += (float) $d->valor;
            $faturas[$d->credit_card_id][$key]['itens'][] = $d;
        }

        // Total geral por mês (todos os cartões)
        $totalPorMes = [];
        foreach ($meses as $mes) {
            $key = $mes->format('Y-m');
            $totalPorMes[$key] = collect($cards)->sum(fn($c) => $faturas[$c->id][$key]['total'] ?? 0);
        }

        return view('finance.faturas.index', compact(
            'cards', 'meses', 'faturas', 'totalPorMes',
            'hoje', 'mesesAtras', 'mesesFrente'
        ));
    }
}
