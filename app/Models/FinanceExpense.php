<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class FinanceExpense extends Model
{
    protected $fillable = [
        'descricao', 'tipo_despesa', 'categoria', 'forma_pagamento',
        'pessoa', 'valor', 'mes_referencia', 'data_vencimento',
        'data_pagamento', 'status', 'origem', 'origem_id',
        'nfe_id', 'observacao',
    ];

    protected $casts = [
        'mes_referencia'  => 'date',
        'data_vencimento' => 'date',
        'data_pagamento'  => 'date',
        'valor'           => 'decimal:2',
    ];

    // Relacionamentos
    public function nfe()
    {
        return $this->belongsTo(FinanceNfe::class, 'nfe_id');
    }

    // Scopes
    public function scopeDoMes(Builder $q, Carbon $mes): Builder
    {
        return $q->whereYear('mes_referencia', $mes->year)
                 ->whereMonth('mes_referencia', $mes->month);
    }

    public function scopeFixas(Builder $q): Builder
    {
        return $q->where('tipo_despesa', 'fixa');
    }

    public function scopeVariaveis(Builder $q): Builder
    {
        return $q->where('tipo_despesa', 'variavel');
    }

    public function scopePendentes(Builder $q): Builder
    {
        return $q->where('status', 'pendente');
    }

    // Helpers
    public static function totalDoMes(Carbon $mes, string $tipo = null): float
    {
        $q = self::doMes($mes);
        if ($tipo) $q->where('tipo_despesa', $tipo);
        return (float) $q->sum('valor');
    }

    public static function aPagarDoMes(Carbon $mes): float
    {
        return (float) self::doMes($mes)->pendentes()->sum('valor');
    }
}
