<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceExpense extends Model
{
    protected $fillable = [
        'descricao',
        'tipo_despesa',
        'categoria',
        'forma_pagamento',
        'credit_card_id',
        'parcelas_total',
        'installment_id',
        'pessoa',
        'valor',
        'mes_referencia',
        'data_vencimento',
        'data_pagamento',
        'status',
        'origem',
        'origem_id',
        'nfe_id',
        'observacao',
    ];

    protected $casts = [
        'mes_referencia'  => 'date',
        'data_vencimento' => 'date',
        'data_pagamento'  => 'date',
        'valor'           => 'decimal:2',
    ];

    // ---- Relacionamentos ------------------------------------------------

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
    }

    public function installment()
    {
        return $this->belongsTo(FinanceInstallment::class, 'installment_id');
    }

    // ---- Scopes ---------------------------------------------------------

    public function scopeDoMes($query, \Carbon\Carbon $mes)
    {
        return $query
            ->whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month);
    }

    public function scopeFixas($query)
    {
        return $query->where('tipo_despesa', 'fixa');
    }

    public function scopeVariaveis($query)
    {
        return $query->where('tipo_despesa', 'variavel');
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    // ---- Helpers --------------------------------------------------------

    public function getPessoaLabelAttribute(): string
    {
        return match ($this->pessoa) {
            'WIL'           => 'Willian',
            'MAY'           => 'Mayara',
            'compartilhado' => 'Compartilhado',
            default         => $this->pessoa,
        };
    }

    public function getFormaPagamentoLabelAttribute(): string
    {
        return match ($this->forma_pagamento) {
            'debito'   => 'Débito',
            'pix'      => 'Pix',
            'dinheiro' => 'Dinheiro',
            'cartao'   => $this->creditCard ? $this->creditCard->nome : 'Cartão',
            default    => $this->forma_pagamento,
        };
    }

    public function isPago(): bool
    {
        return $this->status === 'pago';
    }

    public function isAtrasada(): bool
    {
        return $this->status === 'pendente'
            && $this->data_vencimento
            && $this->data_vencimento->isPast();
    }

    public function isParcelada(): bool
    {
        return $this->parcelas_total > 1;
    }
}
