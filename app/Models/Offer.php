<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',        // ← ADICIONADO
        'estabelecimento',
        'nome_produto',
        'preco_oferta',
        'unidade',
        'quantidade',
        'validade_inicio',
        'validade_fim',
        'fonte',
        'observacao',
        'ativa',
    ];

    protected $casts = [
        'preco_oferta'    => 'float',
        'quantidade'      => 'float',
        'validade_inicio' => 'date',
        'validade_fim'    => 'date',
        'ativa'           => 'boolean',
    ];

    // ── Relacionamentos ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);  // ← ADICIONADO
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeAtivas($query)
    {
        return $query->where('ativa', true)
                     ->where(function ($q) {
                         $q->whereNull('validade_fim')
                           ->orWhere('validade_fim', '>=', now());
                     });
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    /**
     * Calcula quanto o usuário economiza comparado ao último preço pago
     * pelo mesmo produto em qualquer NF-e.
     * Retorna null se não houver histórico suficiente.
     */
    public function getEconomiaPotencialAttribute(): ?float
    {
        if (!$this->product_id) {
            return null;
        }

        $ultimoPreco = InvoiceItem::where('product_id', $this->product_id)
            ->latest('created_at')
            ->value('valor_unitario');

        if (!$ultimoPreco || $ultimoPreco <= $this->preco_oferta) {
            return null;
        }

        return round($ultimoPreco - $this->preco_oferta, 2);
    }

    /**
     * Percentual de desconto em relação ao último preço pago.
     */
    public function getDescontoPercentualAttribute(): ?float
    {
        if (!$this->product_id) {
            return null;
        }

        $ultimoPreco = InvoiceItem::where('product_id', $this->product_id)
            ->latest('created_at')
            ->value('valor_unitario');

        if (!$ultimoPreco || $ultimoPreco <= $this->preco_oferta) {
            return null;
        }

        return round((($ultimoPreco - $this->preco_oferta) / $ultimoPreco) * 100, 1);
    }
}
