<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class FinanceIncome extends Model
{
    protected $fillable = [
        'descricao', 'pessoa', 'tipo', 'valor',
        'mes_referencia', 'data_recebimento',
        'recorrente', 'observacao',
    ];

    protected $casts = [
        'mes_referencia'    => 'date',
        'data_recebimento'  => 'date',
        'recorrente'        => 'boolean',
        'valor'             => 'decimal:2',
    ];

    // Scopes
    public function scopeDoMes(Builder $q, Carbon $mes): Builder
    {
        return $q->whereYear('mes_referencia', $mes->year)
                 ->whereMonth('mes_referencia', $mes->month);
    }

    public function scopeDaPessoa(Builder $q, string $pessoa): Builder
    {
        return $q->where('pessoa', $pessoa);
    }

    // Helpers
    public static function totalDoMes(Carbon $mes): float
    {
        return (float) self::doMes($mes)->sum('valor');
    }
}
