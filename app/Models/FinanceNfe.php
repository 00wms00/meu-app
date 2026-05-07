<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceNfe extends Model
{
    protected $fillable = [
        'chave_acesso', 'emitente', 'cnpj_emitente',
        'valor_total', 'data_emissao',
        'origem', 'origem_id',
        'itens', 'url_consulta',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'valor_total'  => 'decimal:2',
        'itens'        => 'array',
    ];

    // Relacionamentos polimórficos por origem
    public function expense()
    {
        return $this->hasOne(FinanceExpense::class, 'nfe_id');
    }

    public function creditPurchase()
    {
        return $this->hasOne(FinanceCreditPurchase::class, 'nfe_id');
    }
}
