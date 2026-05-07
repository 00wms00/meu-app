<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FinanceCreditCard extends Model
{
    protected $fillable = [
        'nome', 'pessoa', 'limite',
        'dia_fechamento', 'dia_vencimento',
        'cor', 'ativo',
    ];

    protected $casts = [
        'limite' => 'decimal:2',
        'ativo'  => 'boolean',
    ];

    // Relacionamentos
    public function purchases()
    {
        return $this->hasMany(FinanceCreditPurchase::class, 'credit_card_id');
    }

    public function installments()
    {
        return $this->hasMany(FinanceInstallment::class, 'credit_card_id');
    }

    // Calcula a previsão de fatura para um mês de referência
    public function previsaoFatura(Carbon $mes = null): float
    {
        $mes = $mes ?? Carbon::now();
        return (float) $this->installments()
            ->whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month)
            ->sum('valor');
    }

    // Retorna a data de fechamento do mês atual
    public function dataFechamento(Carbon $mes = null): Carbon
    {
        $mes = $mes ?? Carbon::now();
        return Carbon::create($mes->year, $mes->month, $this->dia_fechamento);
    }

    // Retorna a data de vencimento da fatura do mês
    public function dataVencimento(Carbon $mes = null): Carbon
    {
        $mes = $mes ?? Carbon::now();
        // Se o vencimento é depois do fechamento, cai no mesmo mês; senão no próximo
        $venc = Carbon::create($mes->year, $mes->month, $this->dia_vencimento);
        if ($this->dia_vencimento <= $this->dia_fechamento) {
            $venc->addMonth();
        }
        return $venc;
    }
}
