<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\FinanceCreditPurchase;
use App\Models\FinanceInstallment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CreditPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $mes   = Carbon::parse($request->get('mes', now()->format('Y-m')) . '-01');
        $cards = CreditCard::where('ativo', true)->orderBy('pessoa')->orderBy('nome')->get();

        // Parcelas do mês agrupadas por cartão
        $instsByCard = FinanceInstallment::with('purchase')
            ->doMes($mes)
            ->get()
            ->groupBy('credit_card_id');

        // Todas as compras (para listar histórico)
        $purchases = FinanceCreditPurchase::with(['card', 'installments'])
            ->orderByDesc('data_compra')
            ->paginate(30);

        // Meses disponíveis para navegação (6 meses passados + 6 futuros)
        $meses = collect();
        for ($i = -3; $i <= 6; $i++) {
            $meses->push(now()->startOfMonth()->addMonths($i));
        }

        return view('finance.credit_purchases.index', compact(
            'mes', 'cards', 'instsByCard', 'purchases', 'meses'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'credit_card_id' => 'required|exists:credit_cards,id',
            'descricao'      => 'required|string|max:120',
            'categoria'      => 'nullable|string|max:60',
            'pessoa'         => 'required|in:WIL,MAY,compartilhado',
            'valor_total'    => 'required|numeric|min:0.01',
            'parcelas_total' => 'required|integer|min:1|max:48',
            'data_compra'    => 'required|date',
            'observacao'     => 'nullable|string|max:255',
        ]);

        // Validação de limite do cartão
        $card = CreditCard::find($data['credit_card_id']);
        if ($card && $card->limite > 0) {
            $limiteDisponivel = $card->limiteDisponivel();
            if ($data['valor_total'] > $limiteDisponivel) {
                return back()
                    ->withErrors(['valor_total' => "Limite insuficiente! Disponível: R$ " . number_format($limiteDisponivel, 2, ',', '.')])
                    ->withInput();
            }
        }

        $purchase = FinanceCreditPurchase::create($data);
        $purchase->gerarParcelas();

        return back()->with('success', "Compra lançada! {$purchase->parcelas_total}x de R$ " . number_format($purchase->valor_parcela, 2, ',', '.'));
    }

    public function destroy(FinanceCreditPurchase $creditPurchase)
    {
        $creditPurchase->delete(); // cascade apaga as parcelas
        return back()->with('success', 'Compra removida.');
    }

    public function toggleInstallment(FinanceInstallment $installment)
    {
        $installment->update([
            'status' => $installment->status === 'pago' ? 'pendente' : 'pago',
        ]);
        return back()->with('success', 'Parcela atualizada.');
    }
}