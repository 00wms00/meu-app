<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
    protected $table = 'credit_cards';

    protected $fillable = [
        'nome', 'bandeira', 'pessoa', 'limite',
        'dia_vencimento', 'dia_fechamento', 'cor', 'ativo',
    ];

    protected $casts = [
        'ativo'  => 'boolean',
        'limite' => 'decimal:2',
    ];

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

    public function purchases()
    {
        return $this->hasMany(CreditPurchase::class, 'credit_card_id');
    }
}
