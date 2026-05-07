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

        $hoje      = Carbon::now()->startOfMonth();
        $mesInicio = $hoje->copy()->subMonths($mesesAtras);
        $mesFim    = $hoje->copy()->addMonths($mesesFrente)->endOfMonth();

        $meses  = collect();
        $cursor = $hoje->copy()->subMonths($mesesAtras);
        $fim    = $hoje->copy()->addMonths($mesesFrente);
        while ($cursor->lte($fim)) {
            $meses->push($cursor->copy()->startOfMonth());
            $cursor->addMonth();
        }

        $cards = CreditCard::orderBy('pessoa')->orderBy('nome')->get();

        $despesas = FinanceExpense::where('forma_pagamento', 'credito')
            ->where('mes_referencia', '>=', $mesInicio->format('Y-m-01'))
            ->where('mes_referencia', '<=', $mesFim->format('Y-m-t'))
            ->orderBy('mes_referencia')
            ->orderBy('descricao')
            ->get();

        // Monta estrutura indexada por (int) card id
        $faturas = [];
        foreach ($cards as $card) {
            $cardId = (int) $card->id;
            foreach ($meses as $mes) {
                $faturas[$cardId][$mes->format('Y-m')] = ['total' => 0, 'itens' => []];
            }
        }

        $semCartao = [];
        foreach ($meses as $mes) {
            $semCartao[$mes->format('Y-m')] = ['total' => 0, 'itens' => []];
        }

        foreach ($despesas as $d) {
            $key    = Carbon::parse($d->mes_referencia)->format('Y-m');
            $cardId = (int) $d->credit_card_id;

            if ($cardId && isset($faturas[$cardId][$key])) {
                $faturas[$cardId][$key]['total']  += (float) $d->valor;
                $faturas[$cardId][$key]['itens'][] = $d;
            } else {
                if (!isset($semCartao[$key])) {
                    $semCartao[$key] = ['total' => 0, 'itens' => []];
                }
                $semCartao[$key]['total']  += (float) $d->valor;
                $semCartao[$key]['itens'][] = $d;
            }
        }

        $totalPorMes = [];
        foreach ($meses as $mes) {
            $key = $mes->format('Y-m');
            $totalPorMes[$key] = collect($cards)->sum(fn($c) => $faturas[(int)$c->id][$key]['total'] ?? 0)
                + ($semCartao[$key]['total'] ?? 0);
        }

        $temSemCartao = collect($semCartao)->contains(fn($v) => $v['total'] > 0);

        return view('finance.faturas.index', compact(
            'cards', 'meses', 'faturas', 'totalPorMes', 'semCartao', 'temSemCartao',
            'hoje', 'mesesAtras', 'mesesFrente'
        ));
    }
}
