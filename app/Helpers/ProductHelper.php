<?php

namespace App\Helpers;

use App\Models\Product;

class ProductHelper
{
    /**
     * Retorna o nome de exibição do produto (normalizado se existir, senão o original)
     */
    public static function displayName(Product $product): string
    {
        // Se o produto tem nome_exibicao aprovado, usar ele
        if ($product->nome_exibicao && $product->normalizacao_status === 'aprovado') {
            return $product->nome_exibicao;
        }
        
        // Se está agrupado a um canônico, usar o nome do canônico
        if ($product->canonical_product_id) {
            $canonico = Product::find($product->canonical_product_id);
            if ($canonico && $canonico->nome_exibicao) {
                return $canonico->nome_exibicao;
            }
            if ($canonico) {
                return $canonico->nome;
            }
        }
        
        // Se o próprio produto é canônico e tem agrupados
        if ($product->is_canonical && $product->nome_exibicao) {
            return $product->nome_exibicao;
        }
        
        // Fallback: nome original
        return $product->nome;
    }
}
