<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinanceExpense extends Model
{
    protected $fillable = [
        'grupo_parcelas',
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
        'grupo_parcelas'  => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (FinanceExpense $expense) {
            // Gera UUID automaticamente se for crédito parcelado e não tiver grupo
            if ($expense->forma_pagamento === 'credito' 
                && ($expense->parcelas_total > 1) 
                && empty($expense->grupo_parcelas)) {
                $expense->grupo_parcelas = (string) Str::uuid();
            }
        });
    }

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

    public function scopeDoGrupo($query, string $grupoUuid)
    {
        return $query->where('grupo_parcelas', $grupoUuid);
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

    /**
     * Retorna o label amigável da forma de pagamento.
     * ATENÇÃO: o banco armazena 'credito' (não 'cartao').
     */
    public function getFormaPagamentoLabelAttribute(): string
    {
        return match ($this->forma_pagamento) {
            'debito'   => 'Débito',
            'pix'      => 'Pix',
            'dinheiro' => 'Dinheiro',
            'credito'  => $this->creditCard ? '💳 ' . $this->creditCard->nome : '💳 Crédito',
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

    /**
     * Retorna todas as parcelas irmãs (mesmo grupo_parcelas).
     */
    public function getParcelasIrma(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->grupo_parcelas) {
            return collect([$this]);
        }

        return self::doGrupo($this->grupo_parcelas)
            ->orderBy('mes_referencia')
            ->get();
    }

    /**
     * Retorna o número da parcela formatado (ex: "2/3").
     */
    public function getNumeroParcelaAttribute(): string
    {
        if (! $this->isParcelada()) {
            return 'à vista';
        }

        if ($this->grupo_parcelas) {
            $irmas = $this->getParcelasIrma();
            $index = $irmas->search(fn($item) => $item->id === $this->id);
            return ($index !== false ? $index + 1 : '?') . '/' . $this->parcelas_total;
        }

        // Fallback: tenta extrair do nome
        if (preg_match('/\((\d+)\/(\d+)\)/', $this->descricao, $m)) {
            return $m[1] . '/' . $m[2];
        }

        return '?/' . $this->parcelas_total;
    }
}