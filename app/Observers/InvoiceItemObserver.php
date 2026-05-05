<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Models\Product;

class InvoiceItemObserver
{
    /**
     * Após deletar um InvoiceItem, verifica se o produto ficou órfão
     * (sem nenhum outro item associado) e o remove se necessário.
     *
     * Separado em deleted() (não deleting()) para garantir que o item
     * já foi removido antes de contar os restantes.
     */
    public function deleted(InvoiceItem $item): void
    {
        if (!$item->product_id) {
            return;
        }

        $produto = Product::find($item->product_id);

        if (!$produto) {
            return;
        }

        $outrosItens = InvoiceItem::where('product_id', $item->product_id)->count();

        if ($outrosItens === 0) {
            $produto->delete();
        }
    }
}
