<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\FinanceExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaturaController extends Controller
{
    public function index(Request $request)
    {
        $cards = CreditCard::orderBy('pessoa')->orderBy('nome')->get();

        // Pegar parâmetros da URL
        $cardId = $request->query('card_id') ?? $cards->first()?->id;
        $mesStr = $request->query('mes', Carbon::now()->format('Y-m'));

        $mes  = Carbon::createFromFormat('Y-m', $mesStr)->startOfMonth();
        $card = $cards->firstWhere('id', $cardId);

        // Debug: ver o que está chegando
        Log::info('FaturaController', [
            'card_id_param' => $request->query('card_id'),
            'cardId' => $cardId,
            'mesStr' => $mesStr,
            'card_encontrado' => $card ? $card->nome : 'NÃO ENCONTRADO',
        ]);

        $itens = collect();

        if ($card) {
            // Buscar TODAS as despesas do cartão primeiro (sem filtro de mês)
            $todasDespesas = FinanceExpense::where('credit_card_id', $cardId)
                ->where('forma_pagamento', 'credito')
                ->get();
            
            Log::info('Total despesas crédito cartão', [
                'total' => $todasDespesas->count(),
                'ids' => $todasDespesas->pluck('id')->toArray(),
            ]);

            // Agora filtra pelo mês
            $itens = FinanceExpense::where('forma_pagamento', 'credito')
                ->where('credit_card_id', $cardId)
                ->whereYear('mes_referencia', $mes->year)
                ->whereMonth('mes_referencia', $mes->month)
                ->orderBy('descricao')
                ->get()
                ->map(function ($exp) {
                    // Extrai número da parcela do nome
                    $parcela = 'à vista';
                    if (preg_match('/\((\d+)\/(\d+)\)/', $exp->descricao, $matches)) {
                        $parcela = $matches[1] . '/' . $matches[2];
                    }
                    
                    return [
                        'nome'     => preg_replace('/\s*\(\d+\/\d+\)/', '', $exp->descricao),
                        'parcela'  => $parcela,
                        'valor'    => (float) $exp->valor,
                        'status'   => $exp->status ?? 'pendente',
                    ];
                });
            
            Log::info('Itens encontrados para fatura', [
                'count' => $itens->count(),
                'itens' => $itens->toArray(),
            ]);
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