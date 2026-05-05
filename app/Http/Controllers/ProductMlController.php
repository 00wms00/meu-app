<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmarAgrupamentoRequest;
use App\Models\Product;
use App\Services\ProductGrouperService;
use App\Services\ProductSimilarityService;
use Illuminate\Http\JsonResponse;
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

    /**
     * ANTES: Request $request com validate() inline — sem verificação
     * de ownership. Qualquer produto_id válido no banco era aceito.
     * AGORA: ConfirmarAgrupamentoRequest valida ownership via Rule::exists
     * com where('user_id') antes de qualquer lógica de negocio.
     */
    public function confirmarAgrupamento(ConfirmarAgrupamentoRequest $request): JsonResponse
    {
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
