<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    /**
     * Invalida o cache de contagem de categorias sempre que um produto
     * é criado, atualizado ou removido — independente do caminho de escrita
     * (controller, seeder, tinker, job, etc.).
     *
     * A chave é a mesma usada em ProductController::contagemPorCategoria()
     * e nas escritas explícitas em categorizarLote() / atualizarCategoria().
     */
    private function invalidarCache(Product $product): void
    {
        Cache::forget('contagem-categorias-' . $product->user_id);
    }

    public function created(Product $product): void
    {
        $this->invalidarCache($product);
    }

    public function updated(Product $product): void
    {
        // Só invalida se category_id mudou, evitando forget() em cada
        // save() que não afeta a contagem (ex: renomear produto).
        if ($product->wasChanged('category_id')) {
            $this->invalidarCache($product);
        }
    }

    public function deleted(Product $product): void
    {
        $this->invalidarCache($product);
    }
}
