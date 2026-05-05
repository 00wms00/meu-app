<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantidade',
        'unidade',
        'valor_unitario',
        'valor_total',
        'codigo_produto',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'float',
            'valor_unitario' => 'float',
            'valor_total' => 'float',
        ];
    }
/*
    protected static function booted()
    {
        static::deleting(function ($item) {
            $productId = $item->product_id;
            
            // Após excluir o item, verificar se o produto ficou órfão
            // Usamos um closure para executar depois que o item for deletado
            static::deleted(function ($item) use ($productId) {
                $outrosItens = InvoiceItem::where('product_id', $productId)->count();
                
                if ($outrosItens === 0) {
                    // Verificar se não há outros itens antes de excluir
                    $produto = Product::find($productId);
                    if ($produto && $produto->invoiceItems()->count() === 0) {
                        $produto->delete();
                    }
                }
            });
        });
    }
*/
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
