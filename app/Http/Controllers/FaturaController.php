<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\FinanceInstallment;
use App\Models\FinanceExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FaturaController extends Controller
{
    public function index(Request $request)
    {
        $cards = CreditCard::orderBy('pessoa')->orderBy('nome')->get();

        $cardId = $request->get('card_id', optional($cards->first())->id);
        $mesStr = $request->get('mes', Carbon::now()->format('Y-m'));

        $mes  = Carbon::createFromFormat('Y-m', $mesStr)->startOfMonth();
        $card = $cards->firstWhere('id', $cardId);

        // Parcelas do cartao neste mes
        $parcelas = FinanceInstallment::with('purchase')
            ->where('credit_card_id', $cardId)
            ->whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month)
            ->orderBy('mes_referencia')
            ->get()
            ->map(function ($inst) {
                return [
                    'descricao' => $inst->purchase->descricao ?? '—',
                    'parcela'   => $inst->numero . '/' . $inst->total,
                    'valor'     => (float) $inst->valor,
                    'tipo'      => 'parcela',
                    'status'    => $inst->status,
                ];
            });

        // Despesas avulsas no credito deste cartao neste mes
        $avulsas = FinanceExpense::where('forma_pagamento', 'credito')
            ->where('credit_card_id', $cardId)
            ->whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month)
            ->orderBy('descricao')
            ->get()
            ->map(function ($exp) {
                $parcela = $exp->parcelas_total > 1
                    ? '—/' . $exp->parcelas_total  // avulsa parcelada sem installment
                    : 'à vista';
                return [
                    'descricao' => $exp->descricao,
                    'parcela'   => $parcela,
                    'valor'     => (float) $exp->valor,
                    'tipo'      => 'avulsa',
                    'status'    => $exp->status,
                ];
            });

        $itens = $parcelas->concat($avulsas)->sortBy('descricao')->values();
        $total = $itens->sum('valor');

        // Meses disponíveis: 6 atrás e 6 à frente
        $meses = collect();
        $cursor = Carbon::now()->subMonths(6)->startOfMonth();
        for ($i = 0; $i <= 12; $i++) {
            $meses->push($cursor->copy());
            $cursor->addMonth();
        }

        return view('finance.faturas.index', compact(
            'cards', 'card', 'cardId', 'mes', 'mesStr', 'meses', 'itens', 'total'
        ));
    }
}
