<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\FinanceExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CreditCardController extends Controller
{
    public function index()
    {
        $cards = CreditCard::orderBy('pessoa')->orderBy('nome')->get();

        // Previsão de fatura: próximos 3 meses por cartão
        $hoje        = Carbon::now();
        $mesesFuturos = collect();
        for ($i = 0; $i <= 2; $i++) {
            $mesesFuturos->push($hoje->copy()->addMonths($i)->startOfMonth());
        }

        $previsaoFatura = [];
        foreach ($cards as $card) {
            $previsaoFatura[$card->id] = [];
            foreach ($mesesFuturos as $mes) {
                $total = FinanceExpense::where('credit_card_id', $card->id)
                    ->whereYear('mes_referencia', $mes->year)
                    ->whereMonth('mes_referencia', $mes->month)
                    ->sum('valor');
                $previsaoFatura[$card->id][] = [
                    'mes'   => $mes->translatedFormat('M/Y'),
                    'valor' => (float) $total,
                ];
            }
        }

        return view('finance.credit_cards.index', compact('cards', 'previsaoFatura'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'            => 'required|string|max:60',
            'bandeira'        => 'required|in:visa,mastercard,elo,amex,hipercard,outro',
            'pessoa'          => 'required|in:WIL,MAY,compartilhado',
            'limite'          => 'nullable|numeric|min:0',
            'dia_vencimento'  => 'required|integer|min:1|max:31',
            'dia_fechamento'  => 'required|integer|min:1|max:31',
            'cor'             => 'required|string|max:20',
        ]);

        CreditCard::create($data);

        return back()->with('success', 'Cartão cadastrado com sucesso!');
    }

    public function update(Request $request, CreditCard $creditCard)
    {
        $data = $request->validate([
            'nome'            => 'required|string|max:60',
            'bandeira'        => 'required|in:visa,mastercard,elo,amex,hipercard,outro',
            'pessoa'          => 'required|in:WIL,MAY,compartilhado',
            'limite'          => 'nullable|numeric|min:0',
            'dia_vencimento'  => 'required|integer|min:1|max:31',
            'dia_fechamento'  => 'required|integer|min:1|max:31',
            'cor'             => 'required|string|max:20',
        ]);

        $creditCard->update($data);

        return back()->with('success', 'Cartão atualizado!');
    }

    public function toggleAtivo(CreditCard $creditCard)
    {
        $creditCard->update(['ativo' => !$creditCard->ativo]);
        return back()->with('success', $creditCard->ativo ? 'Cartão ativado.' : 'Cartão desativado.');
    }

    public function destroy(CreditCard $creditCard)
    {
        $creditCard->delete();
        return back()->with('success', 'Cartão removido.');
    }
}
