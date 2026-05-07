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
        $mesFim    = $hoje->copy()->addMonths($mesesFrente);

        // Lista de meses no intervalo
        $meses  = collect();
        $cursor = $mesInicio->copy();
        while ($cursor->lte($mesFim)) {
            $meses->push($cursor->copy());
            $cursor->addMonth();
        }

        $cards = CreditCard::orderBy('pessoa')->orderBy('nome')->get();

        // forma_pagamento = 'cartao' (nao 'credito')
        // inclui despesas com credit_card_id preenchido OU forma_pagamento = 'cartao'
        $despesas = FinanceExpense::where(function ($q) use ($cards) {
                $q->where('forma_pagamento', 'cartao')
                  ->orWhereIn('credit_card_id', $cards->pluck('id'));
            })
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

        // Chave especial para despesas 'cartao' sem credit_card_id
        $semCartao = [];
        foreach ($meses as $mes) {
            $semCartao[$mes->format('Y-m')] = ['total' => 0, 'itens' => []];
        }

        foreach ($despesas as $d) {
            $key = $d->mes_referencia->format('Y-m');
            if ($d->credit_card_id && isset($faturas[$d->credit_card_id][$key])) {
                $faturas[$d->credit_card_id][$key]['total']  += (float) $d->valor;
                $faturas[$d->credit_card_id][$key]['itens'][] = $d;
            } elseif (!$d->credit_card_id && isset($semCartao[$key])) {
                $semCartao[$key]['total']  += (float) $d->valor;
                $semCartao[$key]['itens'][] = $d;
            }
        }

        // Total geral por mes
        $totalPorMes = [];
        foreach ($meses as $mes) {
            $key = $mes->format('Y-m');
            $totalPorMes[$key] = collect($cards)->sum(fn($c) => $faturas[$c->id][$key]['total'] ?? 0)
                + ($semCartao[$key]['total'] ?? 0);
        }

        return view('finance.faturas.index', compact(
            'cards', 'meses', 'faturas', 'totalPorMes', 'semCartao',
            'hoje', 'mesesAtras', 'mesesFrente'
        ));
    }
}
