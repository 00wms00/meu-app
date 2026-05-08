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
        $cards = CreditCard::orderBy('pessoa')->orderBy('nome')->get();

        $cardId = $request->query('card_id') ?? $cards->first()?->id;
        $mesStr = $request->query('mes', Carbon::now()->format('Y-m'));

        $mes  = Carbon::createFromFormat('Y-m', $mesStr)->startOfMonth();
        $card = $cards->firstWhere('id', $cardId);

        $itens = collect();

        if ($card) {
            // Busca despesas de crédito do cartão no mês (com grupo de parcelas)
            $itens = FinanceExpense::where('forma_pagamento', 'credito')
                ->where('credit_card_id', $cardId)
                ->whereYear('mes_referencia', $mes->year)
                ->whereMonth('mes_referencia', $mes->month)
                ->orderBy('descricao')
                ->get()
                ->map(function ($exp) {
                    return [
                        'id'       => $exp->id,
                        'nome'     => preg_replace('/\s*\(\d+\/\d+\)/', '', $exp->descricao),
                        'parcela'  => $exp->numero_parcela,
                        'valor'    => (float) $exp->valor,
                        'status'   => $exp->status ?? 'pendente',
                        'is_pago'  => $exp->isPago(),
                        'grupo_parcelas' => $exp->grupo_parcelas,
                    ];
                });
        }

        $total = $itens->sum('valor');

        // Total pago e pendente
        $totalPago     = $itens->where('is_pago', true)->sum('valor');
        $totalPendente = $itens->where('is_pago', false)->sum('valor');

        // Meses disponíveis para navegação: 6 atrás, atual, 5 à frente
        $meses = collect();
        $cursor = Carbon::now()->subMonths(6)->startOfMonth();
        for ($i = 0; $i <= 12; $i++) {
            $meses->push($cursor->copy());
            $cursor->addMonth();
        }

        return view('finance.faturas.index', compact(
            'cards',
            'card',
            'cardId',
            'mes',
            'mesStr',
            'meses',
            'itens',
            'total',
            'totalPago',
            'totalPendente'
        ));
    }
}