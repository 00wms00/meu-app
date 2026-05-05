<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductGrouperService;
use App\Services\ProductSimilarityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductMlController extends Controller
{
    public function __construct(
        protected ProductSimilarityService $mlService,
        protected ProductGrouperService    $grouperService,
    ) {}

    public function similares(Product $product): View
    {
        $this->authorize('view', $product);

        $similares = $this->mlService->encontrarSimilares($product, 10);

        return view('products.similares', compact('product', 'similares'));
    }

    public function sugestoesInterativo(): View
    {
        $sugestoes = $this->mlService->sugerirAgrupamentosML(Auth::id());

        return view('products.ml-interativo', compact('sugestoes'));
    }

    public function confirmarAgrupamento(Request $request): JsonResponse
    {
        $request->validate([
            'produto_id'  => 'required|exists:products,id',
            'canonico_id' => 'nullable|exists:products,id',
            'acao'        => 'required|in:agrupar,pular,ignorar',
        ]);

        if ($request->acao === 'agrupar') {
            $produto  = Product::findOrFail($request->produto_id);
            $canonico = Product::findOrFail($request->canonico_id);

            if (! $canonico->is_canonical) {
                $this->grouperService->tornarCanonico($canonico);
            }

            $this->grouperService->agrupar($produto, $canonico);

            return response()->json([
                'status'  => 'agrupado',
                'message' => "{$produto->nome} → {$canonico->nome}",
            ]);
        }

        return response()->json(['status' => $request->acao]);
    }
}
