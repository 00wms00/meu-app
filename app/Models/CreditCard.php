<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CreditCard extends Model
{
    protected $table = 'credit_cards';

    protected $fillable = [
        'nome',
        'bandeira',
        'pessoa',
        'limite',
        'dia_vencimento',
        'dia_fechamento',
        'cor',
        'ativo',
    ];

    protected $casts = [
        'ativo'  => 'boolean',
        'limite' => 'decimal:2',
    ];

    // ==================== ACCESSORS ====================

    public function getBandeiraLabelAttribute(): string
    {
        return [
            'visa'       => 'Visa',
            'mastercard' => 'Mastercard',
            'elo'        => 'Elo',
            'amex'       => 'American Express',
            'hipercard'  => 'Hipercard',
            'outro'      => 'Outro',
        ][$this->bandeira] ?? $this->bandeira;
    }

    public function getPessoaLabelAttribute(): string
    {
        return [
            'WIL'           => 'Willian',
            'MAY'           => 'Mayara',
            'compartilhado' => 'Compartilhado',
        ][$this->pessoa] ?? $this->pessoa;
    }

    // ==================== RELACIONAMENTOS ====================

    /**
     * Compras parceladas registradas via CreditPurchaseController
     */
    public function purchases()
    {
        return $this->hasMany(FinanceCreditPurchase::class, 'credit_card_id');
    }

    /**
     * Parcelas individuais (geradas a partir das compras)
     */
    public function installments()
    {
        return $this->hasMany(FinanceInstallment::class, 'credit_card_id');
    }

    /**
     * Despesas avulsas pagas com este cartão (FinanceExpense)
     */
    public function expenses()
    {
        return $this->hasMany(FinanceExpense::class, 'credit_card_id');
    }

    // ==================== MÉTODOS DE NEGÓCIO ====================

    /**
     * Calcula a previsão de fatura para um mês de referência,
     * somando parcelas de compras + despesas avulsas do cartão.
     */
    public function previsaoFatura(?Carbon $mes = null): float
    {
        $mes ??= Carbon::now();

        $totalParcelas = (float) $this->installments()
            ->whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month)
            ->sum('valor');

        $totalAvulsas = (float) $this->expenses()
            ->whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month)
            ->where('forma_pagamento', 'credito')
            ->sum('valor');

        return $totalParcelas + $totalAvulsas;
    }

    /**
     * Retorna a data de fechamento da fatura para um determinado mês.
     * Ex: se dia_fechamento = 15 e mês = 2026-05, retorna 2026-05-15
     */
    public function dataFechamento(?Carbon $mes = null): Carbon
    {
        $mes ??= Carbon::now();
        return Carbon::create($mes->year, $mes->month, $this->dia_fechamento);
    }

    /**
     * Retorna a data de vencimento da fatura para um determinado mês.
     * Regra: se dia_vencimento <= dia_fechamento, o vencimento cai no mês seguinte.
     */
    public function dataVencimento(?Carbon $mes = null): Carbon
    {
        $mes ??= Carbon::now();
        $venc = Carbon::create($mes->year, $mes->month, $this->dia_vencimento);

        if ($this->dia_vencimento <= $this->dia_fechamento) {
            $venc->addMonth();
        }

        return $venc;
    }

    /**
     * Retorna o status da fatura do mês:
     * 'aberta' (ainda não fechou), 'fechada' (fechou mas não venceu), 'vencida'
     */
    public function statusFatura(?Carbon $mes = null): string
    {
        $hoje = Carbon::now();
        $fechamento = $this->dataFechamento($mes);
        $vencimento = $this->dataVencimento($mes);

        if ($hoje->lt($fechamento)) {
            return 'aberta';
        }

        if ($hoje->lte($vencimento)) {
            return 'fechada';
        }

        return 'vencida';
    }

    /**
     * Verifica se o limite do cartão está estourado.
     * Considera compras parceladas (todas as parcelas) + despesas avulsas.
     */
    public function limiteEstourado(): bool
    {
        if (! $this->limite || $this->limite <= 0) {
            return false;
        }

        $gastoTotal = (float) $this->purchases()
            ->whereHas('installments', fn($q) => $q->where('status', 'pendente'))
            ->sum('valor_total');

        return $gastoTotal >= $this->limite;
    }

    /**
     * Retorna o limite disponível (limite - gastos pendentes).
     */
    public function limiteDisponivel(): float
    {
        if (! $this->limite || $this->limite <= 0) {
            return 0;
        }

        $gastoPendente = (float) $this->purchases()
            ->whereHas('installments', fn($q) => $q->where('status', 'pendente'))
            ->sum('valor_total');

        return max(0, $this->limite - $gastoPendente);
    }

    /**
     * Scope para cartões ativos.
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope para cartões de uma pessoa específica.
     */
    public function scopeDaPessoa($query, string $pessoa)
    {
        return $query->where('pessoa', $pessoa);
    }
}