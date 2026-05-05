<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InvoiceItem;
use App\Models\PriceAlert;
use App\Models\Product;
use App\Services\PriceAlertService;
use App\Services\ProductGrouperService;
use App\Services\ProductSimilarityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductGrouperService   $grouperService,
        protected ProductSimilarityService $mlService,
        protected PriceAlertService        $alertService,
    ) {}

    // ==================== CRUD ====================

    public function index(Request $request): View
    {
        $query = Product::where('user_id', Auth::id());

        if ($request->filled('categoria')) {
            $query->where('category_id', $request->categoria);
        }
        if ($request->filled('search')) {
            $query->where('nome', 'ilike', "%{$request->search}%");
        }

        $products   = $query->orderBy('nome')->paginate(50);
        $categorias = Category::where('user_id', Auth::id())->ordenado()->get();

        return view('products.index', compact('products', 'categorias'));
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $produtoExibicao = $product->canonical_product_id && ! $product->is_canonical
            ? Product::findOrFail($product->canonical_product_id)
            : $product;

        $produtoIds = Product::where(fn ($q) =>
            $q->where('id', $produtoExibicao->id)
              ->orWhere('canonical_product_id', $produtoExibicao->id)
        )->pluck('id');

        $items = InvoiceItem::with('invoice')
            ->whereIn('product_id', $produtoIds)
            ->whereHas('invoice', fn ($q) => $q->where('user_id', Auth::id()))
            ->orderBy('created_at')
            ->get();

        $serie = $items->map(fn ($i) => [
            'data'           => $i->invoice->data_emissao->format('Y-m-d'),
            'valor_unitario' => $i->valor_unitario,
            'unidade'        => $i->unidade,
        ])->values();

        $variacao  = null;
        $primeiro  = $serie->first();
        $ultimo    = $serie->last();

        if ($primeiro && $ultimo && $primeiro['valor_unitario'] > 0) {
            $variacao = (($ultimo['valor_unitario'] - $primeiro['valor_unitario']) / $primeiro['valor_unitario']) * 100;
        }

        $agrupados = Product::where('canonical_product_id', $produtoExibicao->id)->get();

        return view('products.show', compact('product', 'produtoExibicao', 'serie', 'variacao', 'agrupados'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validate([
            'nome'            => 'required|string|max:255',
            'unidade_padrao'  => 'nullable|string|max:10',
        ]));

        return redirect()->route('products.show', $product)
            ->with('success', 'Produto atualizado!');
    }

    // ==================== FOTO ====================

    public function uploadFoto(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate(['foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048']);

        if ($product->foto) {
            Storage::disk('public')->delete($product->foto);
        }

        $product->update([
            'foto' => $request->file('foto')->store('produtos/' . Auth::id(), 'public'),
        ]);

        return back()->with('success', 'Foto atualizada!');
    }

    public function removerFoto(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($product->foto) {
            Storage::disk('public')->delete($product->foto);
        }

        $product->update(['foto' => null]);

        return back()->with('success', 'Foto removida!');
    }

    // ==================== CATEGORIAS ====================

    public function categorias(Request $request): View
    {
        $userId = Auth::id();
        $cf     = $request->input('categoria'); // $cf = "categoria filtro"

        $query = Product::where('user_id', $userId)->with('category');

        if ($cf === 'sem') {
            $query->whereNull('category_id');
        } elseif ($cf) {
            $query->where('category_id', $cf);
        }

        if ($request->filled('search')) {
            $query->where('nome', 'ilike', '%' . $request->search . '%');
        }

        $produtos           = $query->orderBy('nome')->paginate(50);
        $categorias         = Category::where('user_id', $userId)->ordenado()->get();
        $contagemCategorias = $this->contagemPorCategoria($userId);

        return view('products.categorias', compact('produtos', 'categorias', 'cf', 'contagemCategorias'));
    }

    /**
     * Retorna contagem de produtos por category_id + chave 'sem'.
     */
    private function contagemPorCategoria(int $userId): array
    {
        $porCategoria = Product::where('user_id', $userId)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->toArray();

        $porCategoria['sem'] = Product::where('user_id', $userId)
            ->whereNull('category_id')
            ->count();

        return $porCategoria;
    }

    public function categorizarLote(Request $request): RedirectResponse
    {
        $ids = $request->produto_ids ?? [];

        Product::whereIn('id', $ids)
            ->where('user_id', Auth::id())
            ->update(['category_id' => $request->categoria ?: null]);

        return back()->with('success', count($ids) . ' produto(s) categorizado(s)!');
    }

    public function atualizarCategoria(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update(['category_id' => $request->categoria ?: null]);

        return back()->with('success', 'Categoria atualizada!');
    }

    // ==================== AGRUPAMENTOS ====================

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

        $produtos  = Product::whereIn('id', $request->produto_ids)
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

    // ==================== ALERTAS ====================

    public function alertas(): View
    {
        $alertas   = PriceAlert::where('user_id', Auth::id())
            ->with('product')
            ->orderBy('variacao_percentual', 'desc')
            ->get();

        $disparados = $this->alertService->verificarTodos(Auth::id());

        return view('products.alertas', compact('alertas', 'disparados'));
    }

    public function criarAlerta(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('view', $product);

        $this->alertService->criarOuAtualizar(Auth::id(), $product->id, $request->limite_alerta);

        return back()->with('success', 'Alerta criado!');
    }

    public function removerAlerta(PriceAlert $alerta): RedirectResponse
    {
        $this->authorize('delete', $alerta);
        $alerta->delete();

        return back()->with('success', 'Alerta removido!');
    }

    public function toggleAlerta(PriceAlert $alerta): RedirectResponse
    {
        $this->authorize('update', $alerta);
        $alerta->update(['ativo' => ! $alerta->ativo]);

        return back()->with('success', 'Alerta alternado!');
    }

    // ==================== MACHINE LEARNING ====================

    public function similares(Request $request, Product $product): View
    {
        $this->authorize('view', $product);

        $similares = $this->mlService->encontrarSimilares($product, 10);

        return view('products.similares', compact('product', 'similares'));
    }

    public function mlSugestoesInterativo(): View
    {
        $sugestoes = $this->mlService->sugerirAgrupamentosML(Auth::id());

        return view('products.ml-interativo', compact('sugestoes'));
    }

    public function mlConfirmarAgrupamento(Request $request): JsonResponse
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

    public function mlAgrupar(): RedirectResponse
    {
        $resultado = $this->mlService->agruparComML(Auth::id());

        return redirect()->route('products.agrupamentos')
            ->with('success', "ML: {$resultado['agrupados']} agrupado(s)!");
    }

    public function mlSugestoes(): View
    {
        $sugestoes = $this->mlService->sugerirAgrupamentosML(Auth::id());

        return view('products.ml-sugestoes', compact('sugestoes'));
    }
}
