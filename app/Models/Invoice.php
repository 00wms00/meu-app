<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chave',
        'numero',
        'serie',
        'data_emissao',
        'cnpj',
        'nome_estabelecimento',
        'endereco_estabelecimento',
        'total_itens',
        'valor_total',
        'descontos',
        'valor_pago',
        'forma_pagamento',
        'consumidor_cpf',
        'consumidor_nome',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Invoice $invoice) {
            $invoice->loadMissing('items');
            $invoice->items->each->delete();
        });
    }

    // ==================== RELATIONS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Recalcula total_itens, valor_total e valor_pago a partir dos itens atuais.
     * Chame sempre após criar, atualizar ou remover um InvoiceItem.
     */
    public function recalcularTotais(): void
    {
        $this->refresh();

        $valorTotal = $this->items()->sum('valor_total');

        $this->update([
            'total_itens' => $this->items()->count(),
            'valor_total' => $valorTotal,
            'valor_pago'  => max(0, $valorTotal - ($this->descontos ?? 0)),
        ]);
    }
}
