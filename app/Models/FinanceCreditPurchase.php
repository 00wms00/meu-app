<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class FinanceCreditPurchase extends Model
{
    protected $fillable = [
        'credit_card_id', 'descricao', 'categoria',
        'valor_total', 'parcelas_total',
        'data_compra', 'nfe_id', 'observacao',
    ];

    protected $casts = [
        'valor_total'  => 'decimal:2',
        'data_compra'  => 'date',
    ];

    // Relacionamentos
    public function card()
    {
        return $this->belongsTo(FinanceCreditCard::class, 'credit_card_id');
    }

    public function installments()
    {
        return $this->hasMany(FinanceInstallment::class, 'purchase_id');
    }

    public function nfe()
    {
        return $this->belongsTo(FinanceNfe::class, 'nfe_id');
    }

    // Gera as parcelas automaticamente ao criar a compra
    public function gerarParcelas(): void
    {
        $this->installments()->delete(); // limpa se já existir

        $card      = $this->card;
        $valorParc = round($this->valor_total / $this->parcelas_total, 2);
        $dataCompra = $this->data_compra;

        // Determina o mês da primeira parcela:
        // Se a compra foi feita APÓS o fechamento, cai no mês seguinte
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

    // Label de parcelas estilo "2/4"
    public function labelParcelas(): string
    {
        if ($this->parcelas_total === 1) return 'à vista';
        $atual = $this->installments()->where('status', 'pendente')->min('numero') ?? 1;
        return "{$atual}/{$this->parcelas_total}";
    }
}
