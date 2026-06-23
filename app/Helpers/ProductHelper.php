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

    /**
     * Normaliza um nome de produto para fins de comparação e exibição.
     * Espelha a função normalizarNome() do JavaScript da view agrupamentos.
     */
    public static function normalizar(string $nome): string
    {
        $s = mb_strtolower($nome, 'UTF-8');

        // Remove acentos
        $s = str_replace(
            ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç','ñ'],
            ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'],
            $s
        );

        // Remove quantidades e unidades de medida (ex: "200g", "1,5kg", "c/3")
        $s = preg_replace('/\b\d+[.,]?\d*\s*(kg|g|gr|l|ml|un|und|cx|pc|pct|lt|dz|x)\b/i', '', $s);
        $s = preg_replace('/\bc\/\d+\b/i', '', $s);

        // Remove caracteres especiais
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);

        // Colapsa espaços extras
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}
