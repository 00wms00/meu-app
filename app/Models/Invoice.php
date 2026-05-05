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
/*
    // Quando a nota for excluída, excluir também os itens
    protected static function booted()
    {
        static::deleting(function ($invoice) {
            // Excluir todos os itens da nota
            $invoice->items()->delete();
        });
    }
*/
    protected static function booted()
    {
        static::deleting(function (Invoice $invoice) {
            // Carrega os itens se ainda não estiverem carregados
            // e deleta cada um acionando seus model events
            $invoice->loadMissing('items');
            $invoice->items->each->delete();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
