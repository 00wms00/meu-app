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

        // DEBUG: mostra despesas e o estado de $faturas apos loop
        $debug = [];
        foreach ($despesas as $d) {
            $key    = Carbon::parse($d->mes_referencia)->format('Y-m');
            $cardId = (int) $d->credit_card_id;
            $debug[] = [
                'descricao'      => $d->descricao,
                'valor'          => $d->valor,
                'credit_card_id' => $d->credit_card_id,
                'cardId_cast'    => $cardId,
                'key'            => $key,
                'isset_faturas'  => isset($faturas[$cardId][$key]) ? 'SIM' : 'NAO',
                'faturas_keys'   => array_keys($faturas),
            ];
        }
        dd($debug, $faturas);
    }
}
