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

    public function expenses()
    {
        return $this->hasMany(FinanceExpense::class, 'credit_card_id');
    }

    // ==================== MÉTODOS DE NEGÓCIO ====================

    /**
     * Calcula a previsão de fatura para um mês de referência.
     */
    public function previsaoFatura(?Carbon $mes = null): float
    {
        $mes ??= Carbon::now();
        return (float) $this->expenses()
            ->whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month)
            ->sum('valor');
    }

    /**
     * Retorna a data de fechamento da fatura para uma data base.
     */
    public function dataFechamento(?Carbon $dataBase = null): Carbon
    {
        $dataBase = $dataBase ?? Carbon::now();
        return Carbon::create($dataBase->year, $dataBase->month, $this->dia_fechamento);
    }


public function dataVencimento(?Carbon $mes = null): Carbon
{
    $mes = $mes ?? Carbon::now();
    return Carbon::create($mes->year, $mes->month, $this->dia_vencimento);
}

    /**
     * Status da fatura: aberta, fechada ou vencida.
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
     * Calcula o limite disponível subtraindo TODAS as despesas de crédito pendentes.
     */
    public function limiteDisponivel(): float
    {
        if (!$this->limite || $this->limite <= 0) {
            return 0;
        }

        $totalPendente = $this->expenses()
            ->where('forma_pagamento', 'credito')
            ->where('status', 'pendente')
            ->sum('valor');

        return max(0, $this->limite - $totalPendente);
    }

    /**
     * Verifica se o limite está estourado.
     */
    public function limiteEstourado(): bool
    {
        return $this->limiteDisponivel() <= 0;
    }

    /**
     * Determina o mês de referência correto para uma compra com base no ciclo de faturamento.
     * Se a compra ocorrer após o fechamento, a 1ª parcela cai no mês seguinte.
     */
    public function mesReferencia(Carbon $dataCompra): Carbon
    {
        $fechamento = $this->dataFechamento($dataCompra);
        return $dataCompra->gt($fechamento)
            ? $dataCompra->copy()->addMonth()->startOfMonth()
            : $dataCompra->copy()->startOfMonth();
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeDaPessoa($query, string $pessoa)
    {
        return $query->where('pessoa', $pessoa);
    }
}