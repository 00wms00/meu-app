<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Auth;

class ShoppingListItemService
{
    /**
     * Retorna o preço médio estimado das últimas 5 compras do produto.
     */
    public function getPrecoEstimado(string $nome): ?float
    {
        $produto = Product::where('user_id', Auth::id())
            ->where('nome', 'ilike', $nome)
            ->first();

        if (! $produto) {
            return null;
        }

        $media = InvoiceItem::where('product_id', $produto->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->avg('valor_unitario');

        return $media ? round($media, 2) : null;
    }

    /**
     * Retorna a quantidade média comprada nas últimas 5 ocorrências.
     */
    public function getQuantidadeMedia(int $productId): float
    {
        $media = InvoiceItem::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->avg('quantidade');

        return $media ? round($media, 2) : 1;
    }

    /**
     * Cria um ShoppingListItem para um produto dado.
     */
    public function criarItemDeProduto(ShoppingList $lista, Product $produto, float $quantidade = 1): ShoppingListItem
    {
        return ShoppingListItem::create([
            'shopping_list_id' => $lista->id,
            'product_id'       => $produto->id,
            'nome'             => $produto->nome,
            'quantidade'       => $quantidade,
            'unidade'          => $produto->unidade_padrao ?? 'UN',
            'preco_estimado'   => $this->getPrecoEstimado($produto->nome),
            'ordem'            => $lista->items()->count(),
        ]);
    }

    /**
     * Cria um ShoppingListItem a partir de dados brutos (ex: formulário).
     */
    public function criarItemManual(ShoppingList $lista, string $nome, float $quantidade = 1, string $unidade = 'UN'): ShoppingListItem
    {
        return ShoppingListItem::create([
            'shopping_list_id' => $lista->id,
            'nome'             => $nome,
            'quantidade'       => $quantidade,
            'unidade'          => $unidade,
            'preco_estimado'   => $this->getPrecoEstimado($nome),
            'ordem'            => $lista->items()->count(),
        ]);
    }

    /**
     * Retorna produtos de uma categoria preparados para criação de lista.
     */
    public function getProdutosParaCategoria(int $categoriaId, string $tipo): array
    {
        $limite = $tipo === 'semanal' ? 15 : 30;

        $produtos = Product::where('user_id', Auth::id())
            ->where('category_id', $categoriaId)
            ->withCount('invoiceItems')
            ->orderBy('invoice_items_count', 'desc')
            ->take($limite)
            ->get();

        if ($tipo === 'semanal') {
            $produtos = $produtos->filter(fn($p) => $p->invoice_items_count >= 2);
        }

        $ids = $produtos->pluck('id');

        $precosMedios = InvoiceItem::whereIn('product_id', $ids)
            ->selectRaw('product_id, AVG(valor_unitario) as preco_medio')
            ->groupBy('product_id')
            ->pluck('preco_medio', 'product_id');

        return $produtos->map(function ($p) use ($precosMedios) {
            $p->preco_medio        = isset($precosMedios[$p->id]) ? round((float) $precosMedios[$p->id], 2) : null;
            $p->quantidade_sugerida = 1;
            return $p;
        })->values()->all();
    }
}
