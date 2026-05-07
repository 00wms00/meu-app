<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class FinanceInstallment extends Model
{
    protected $fillable = [
        'purchase_id', 'credit_card_id',
        'numero', 'total', 'valor',
        'mes_referencia', 'status',
    ];

    protected $casts = [
        'mes_referencia' => 'date',
        'valor'          => 'decimal:2',
    ];

    // Relacionamentos
    public function purchase()
    {
        return $this->belongsTo(FinanceCreditPurchase::class, 'purchase_id');
    }

    public function card()
    {
        return $this->belongsTo(FinanceCreditCard::class, 'credit_card_id');
    }

    // Scopes
    public function scopeDoMes(Builder $q, Carbon $mes): Builder
    {
        return $q->whereYear('mes_referencia', $mes->year)
                 ->whereMonth('mes_referencia', $mes->month);
    }

    public function scopeDoCartao(Builder $q, int $cardId): Builder
    {
        return $q->where('credit_card_id', $cardId);
    }

    // Total de fatura de um cartão em um mês
    public static function totalFatura(int $cardId, Carbon $mes): float
    {
        return (float) self::doCartao($cardId)->doMes($mes)->sum('valor');
    }
}
