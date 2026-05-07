<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FinanceCreditPurchase extends Model
{
    protected $fillable = [
        'credit_card_id', 'descricao', 'categoria', 'pessoa',
        'valor_total', 'parcelas_total',
        'data_compra', 'observacao',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
        'data_compra' => 'date',
    ];

    public function card()
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
    }

    public function installments()
    {
        return $this->hasMany(FinanceInstallment::class, 'purchase_id')->orderBy('numero');
    }

    /**
     * Gera as parcelas mensais automaticamente.
     * Regra: se a compra foi após o fechamento, a 1ª parcela cai no mês seguinte.
     */
    public function gerarParcelas(): void
    {
        $this->installments()->delete();

        $card       = $this->card;
        $valorParc  = round($this->valor_total / $this->parcelas_total, 2);
        $dataCompra = $this->data_compra;

        $fechamento = Carbon::create($dataCompra->year, $dataCompra->month, $card->dia_fechamento);
        $mesParcela = $dataCompra->gt($fechamento)
            ? $dataCompra->copy()->startOfMonth()->addMonth()
            : $dataCompra->copy()->startOfMonth();

        for ($i = 1; $i <= $this->parcelas_total; $i++) {
            FinanceInstallment::create([
                'purchase_id'    => $this->id,
                'credit_card_id' => $this->credit_card_id,
                'numero'         => $i,
                'total'          => $this->parcelas_total,
                'valor'          => $valorParc,
                'mes_referencia' => $mesParcela->copy()->startOfMonth(),
                'status'         => 'pendente',
            ]);
            $mesParcela->addMonth();
        }
    }

    public function getValorParcelaAttribute(): float
    {
        return round($this->valor_total / $this->parcelas_total, 2);
    }

    public function getLabelParcelasAttribute(): string
    {
        if ($this->parcelas_total === 1) return 'à vista';
        $pago = $this->installments->where('status', 'pago')->count();
        return ($pago + 1) . '/' . $this->parcelas_total;
    }
}
