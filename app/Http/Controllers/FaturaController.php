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

        $cardId = $request->get('card_id') ?? $cards->first()?->id;
        $mesStr = $request->get('mes', Carbon::now()->format('Y-m'));

        $mes  = Carbon::createFromFormat('Y-m', $mesStr)->startOfMonth();
        $card = $cards->firstWhere('id', $cardId);

        $itens = collect();

        if ($card) {
            // 1. Parcelas de compras parceladas
            $parcelas = FinanceInstallment::with('purchase')
                ->where('credit_card_id', $cardId)
                ->whereYear('mes_referencia', $mes->year)
                ->whereMonth('mes_referencia', $mes->month)
                ->get()
                ->map(function ($inst) {
                    return [
                        'nome'     => $inst->purchase->descricao ?? 'Compra sem descrição',
                        'parcela'  => $inst->numero . '/' . $inst->total,
                        'valor'    => (float) $inst->valor,
                        'tipo'     => 'compra_parcelada',
                        'status'   => $inst->status,
                    ];
                });

            // 2. Despesas avulsas no crédito
            $avulsas = FinanceExpense::where('forma_pagamento', 'credito')
                ->where('credit_card_id', $cardId)
                ->whereYear('mes_referencia', $mes->year)
                ->whereMonth('mes_referencia', $mes->month)
                ->get()
                ->map(function ($exp) {
                    $parcela = ($exp->parcelas_total ?? 1) > 1
                        ? '1/' . $exp->parcelas_total
                        : 'à vista';
                    
                    return [
                        'nome'     => $exp->descricao,
                        'parcela'  => $parcela,
                        'valor'    => (float) $exp->valor,
                        'tipo'     => 'despesa_avulsa',
                        'status'   => $exp->status ?? 'pendente',
                    ];
                });

            $itens = $parcelas->concat($avulsas)
                ->sortBy('nome')
                ->values();
        }

        $total = $itens->sum('valor');

        // Meses disponíveis para navegação
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