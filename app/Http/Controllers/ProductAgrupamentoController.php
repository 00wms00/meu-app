<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductGrouperService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductAgrupamentoController extends Controller
{
    public function __construct(
        protected ProductGrouperService $grouperService,
    ) {}

    public function agrupamentos(Request $request): View
    {
        $userId = Auth::id();
        $search = $request->input('search');

        $grupos = Product::where('user_id', $userId)
            ->where('is_canonical', true)
            ->with(['groupedProducts' => fn ($q) => $q->orderBy('nome')])
            ->orderBy('nome')
            ->get();

        $naoAgrupados = Product::where('user_id', $userId)
            ->where('is_canonical', false)
            ->whereNull('canonical_product_id')
            ->orderBy('nome')
            ->get();

        if ($search) {
            $grupos = $grupos->filter(
                fn ($g) => stripos($g->nome, $search) !== false
                        || $g->groupedProducts->contains(fn ($p) => stripos($p->nome, $search) !== false)
            );
            $naoAgrupados = $naoAgrupados->filter(fn ($p) => stripos($p->nome, $search) !== false);
        }

        return view('products.agrupamentos', compact('grupos', 'naoAgrupados', 'search'));
    }

    public function agrupar(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $canonico = Product::findOrFail($request->canonical_id);

        if (! $canonico->is_canonical) {
            $this->grouperService->tornarCanonico($canonico);
        }

        $this->grouperService->agrupar($product, $canonico);

        return back()->with('success', 'Agrupado!');
    }

    public function desagrupar(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $this->grouperService->desagrupar($product);

        return back()->with('success', 'Desagrupado!');
    }

    public function tornarCanonico(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $this->grouperService->tornarCanonico($product);

        return back()->with('success', 'Definido como produto principal!');
    }

    public function criarGrupo(Request $request): RedirectResponse
    {
        $request->validate(['produto_ids' => 'required|array|min:2']);

        $produtos = Product::whereIn('id', $request->produto_ids)
            ->where('user_id', Auth::id())
            ->get();

        $canonico = $produtos->first();
        $this->grouperService->tornarCanonico($canonico);

        if ($request->filled('nome_grupo')) {
            $canonico->update(['nome' => $request->nome_grupo]);
        }

        foreach ($produtos->skip(1) as $produto) {
            $this->grouperService->agrupar($produto, $canonico);
        }

        return back()->with('success', 'Grupo criado!');
    }

    public function renomearGrupo(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $product->update(['nome' => $request->nome]);

        return back()->with('success', 'Renomeado!');
    }

    public function desfazerGrupo(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        Product::where('canonical_product_id', $product->id)
            ->update(['canonical_product_id' => null]);

        $product->update(['is_canonical' => false]);

        return back()->with('success', 'Grupo desfeito!');
    }

    public function adicionarAoGrupo(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $count = 0;
        foreach ($request->produto_ids ?? [] as $id) {
            $p = Product::find($id);
            if ($p) {
                $this->grouperService->agrupar($p, $product);
                $count++;
            }
        }

        return back()->with('success', "{$count} produto(s) adicionado(s)!");
    }

    public function agruparAutomatico(): RedirectResponse
    {
        $userId = Auth::id();

        Product::where('user_id', $userId)->each(function (Product $produto) use ($userId) {
            if ($produto->canonical_product_id || $produto->is_canonical) return;

            $canonico = $this->grouperService->encontrarCanonico($produto, $userId);

            $canonico
                ? $this->grouperService->agrupar($produto, $canonico)
                : $this->grouperService->tornarCanonico($produto);
        });

        return back()->with('success', 'Agrupamento automático concluído!');
    }
}
