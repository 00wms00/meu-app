<?php

namespace App\Http\Controllers;

use App\Services\ProductNormalizationService;
use App\Models\Category;
use App\Models\InvoiceItem;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ProductController extends Controller
{
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
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereIn('invoice_items.product_id', $produtoIds)
            ->where('invoices.user_id', Auth::id())
            ->orderBy('invoices.data_emissao')
            ->select('invoice_items.*')
            ->get();

        $serie = $items
            ->map(fn ($i) => [
                'data'           => $i->invoice->data_emissao->format('Y-m-d'),
                'valor_unitario' => $i->valor_unitario,
                'unidade'        => $i->unidade,
            ])
            ->values();

        $variacao = null;
        $primeiro = $serie->first();
        $ultimo   = $serie->last();

        if ($primeiro && $ultimo && $primeiro['valor_unitario'] > 0) {
            $variacao = (($ultimo['valor_unitario'] - $primeiro['valor_unitario']) / $primeiro['valor_unitario']) * 100;
        }

        $agrupados = Product::where('canonical_product_id', $produtoExibicao->id)->get();

        $alertaExistente = PriceAlert::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        return view('products.show', compact(
            'product', 'produtoExibicao', 'serie', 'variacao', 'agrupados', 'alertaExistente'
        ));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
{ 
    if ($product->user_id !== Auth::id()) abort(403); 
    $product->update($request->validate([
        'nome_exibicao' => 'nullable|string|max:255',
        'normalizacao_status' => 'nullable|string',
        'unidade_padrao' => 'nullable',
    ])); 
    return redirect()->route('products.show', $product)->with('success', 'Produto atualizado!'); 
}

    // ==================== CATEGORIAS ====================

    public function categorias(Request $request): View
    {
        $userId = Auth::id();
        $cf     = $request->input('categoria');

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

    public function categorizarLote(Request $request): RedirectResponse
    {
        $ids = $request->produto_ids ?? [];

        Product::whereIn('id', $ids)
            ->where('user_id', Auth::id())
            ->update(['category_id' => $request->categoria ?: null]);

        // Cache::forget removido — o ProductObserver já invalida
        // automaticamente em todo updated() com category_id alterado.

        return back()->with('success', count($ids) . ' produto(s) categorizado(s)!');
    }

    public function atualizarCategoria(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update(['category_id' => $request->categoria ?: null]);

        // Cache::forget removido — coberto pelo ProductObserver.

        return back()->with('success', 'Categoria atualizada!');
    }

    // ==================== HELPERS ====================

    private function contagemPorCategoria(int $userId): array
    {
        return Cache::remember(
            'contagem-categorias-' . $userId,
            300,
            function () use ($userId): array {
                /*
                 * ANTES: duas queries separadas — um GROUP BY para produtos
                 * com categoria e um COUNT() para produtos sem categoria.
                 *
                 * AGORA: query única com CASE WHEN que agrupa tudo de uma vez,
                 * eliminando um roundtrip ao banco a cada miss de cache.
                 *
                 * A chave 'sem' é preservada para compatibilidade com a view.
                 */
                $rows = Product::where('user_id', $userId)
                    ->selectRaw("
                        CASE
                            WHEN category_id IS NULL THEN 'sem'
                            ELSE category_id::text
                        END AS chave,
                        COUNT(*) AS total
                    ")
                    ->groupByRaw("
                        CASE
                            WHEN category_id IS NULL THEN 'sem'
                            ELSE category_id::text
                        END
                    ")
                    ->pluck('total', 'chave')
                    ->toArray();

                // Garante que 'sem' sempre existe, mesmo que todos
                // os produtos tenham categoria.
                return array_merge(['sem' => 0], $rows);
            }
        );
    }
public function normalizacao(Request $request, ProductNormalizationService $service)
{
    $userId = Auth::id();
    
    $status = $request->input('status', 'pendente');
    
    $produtos = Product::where('user_id', $userId)
        ->when($status === 'pendente', fn($q) => $q
            ->where(function($q) {
                $q->whereNull('normalizacao_status')
                  ->orWhere('normalizacao_status', 'pendente');
            })
        )
        ->when($status === 'revisar', fn($q) => $q->where('normalizacao_status', 'revisar'))
        ->when($status === 'aprovado', fn($q) => $q->where('normalizacao_status', 'aprovado'))
        ->when($request->filled('search'), fn($q) => $q->where('nome', 'ilike', "%{$request->search}%"))
        ->orderBy('nome')
        ->paginate(50);

    // Gerar análises para os pendentes
    $analises = [];
    foreach ($produtos as $produto) {
        if (!$produto->nome_normalizado) {
            $analises[$produto->id] = $service->analyze($produto);
        }
    }

    return view('products.normalizacao', compact('produtos', 'analises', 'status'));
}

public function aprovarNormalizacao(Product $product, Request $request, ProductNormalizationService $service)
{
    if ($product->user_id !== Auth::id()) abort(403);
    
    $service->approve($product, $request->input('nome_exibicao'));
    
    return back()->with('success', "Produto normalizado: " . $product->nome_exibicao);
}

public function aprovarTodasNormalizacoes(ProductNormalizationService $service)
{
    $count = $service->approveAllPending(Auth::id());
    
    return back()->with('success', "{$count} produtos aprovados automaticamente!");
}
    

    // ==================== AGRUPAMENTOS ====================
    public function agrupamentos(Request $request)
    { 
        $userId=Auth::id(); 
        $grupos=Product::where('user_id',$userId)->where('is_canonical',true)->with(['groupedProducts'=>fn($q)=>$q->orderBy('nome')])->orderBy('nome')->get();
        $naoAgrupados=Product::where('user_id',$userId)->where('is_canonical',false)->whereNull('canonical_product_id')->orderBy('nome')->get();
        $search = $request->input('search');
        if ($search) {
            $grupos = $grupos->filter(fn($g) => stripos($g->nome, $search) !== false || $g->groupedProducts->contains(fn($p) => stripos($p->nome, $search) !== false));
            $naoAgrupados = $naoAgrupados->filter(fn($p) => stripos($p->nome, $search) !== false);
        }
        return view('products.agrupamentos',compact('grupos','naoAgrupados','search')); 
    }

    public function agrupar(Request $request, Product $product)
    { if($product->user_id!==Auth::id())abort(403); $c=Product::findOrFail($request->canonical_id);
      if(!$c->is_canonical)$this->grouperService->tornarCanonico($c); $this->grouperService->agrupar($product,$c); return back()->with('success','Agrupado!'); }

    public function desagrupar(Product $product)
    { if($product->user_id!==Auth::id())abort(403); $this->grouperService->desagrupar($product); return back()->with('success','Desagrupado!'); }

    public function tornarCanonico(Product $product)
    { if($product->user_id!==Auth::id())abort(403); $this->grouperService->tornarCanonico($product); return back()->with('success','Principal!'); }

    public function criarGrupo(Request $request)
    { $request->validate(['produto_ids'=>'required|array|min:2']); $prods=Product::whereIn('id',$request->produto_ids)->where('user_id',Auth::id())->get();
      $c=$prods->first(); $this->grouperService->tornarCanonico($c); if($request->nome_grupo)$c->update(['nome'=>$request->nome_grupo]);
      foreach($prods as $i=>$p){if($i===0)continue; $this->grouperService->agrupar($p,$c);} return back()->with('success','Grupo criado!'); }

    public function renomearGrupo(Request $request, Product $product)
    { if($product->user_id!==Auth::id())abort(403); $product->update(['nome'=>$request->nome]); return back()->with('success','Renomeado!'); }

    public function desfazerGrupo(Product $product)
    { if($product->user_id!==Auth::id())abort(403); Product::where('canonical_product_id',$product->id)->update(['canonical_product_id'=>null]);
      $product->update(['is_canonical'=>false]); return back()->with('success','Desfeito!'); }

    public function adicionarAoGrupo(Request $request, Product $product)
    { if($product->user_id!==Auth::id())abort(403); $c=0; foreach($request->produto_ids??[] as $id){$p=Product::find($id); if($p){$this->grouperService->agrupar($p,$product);$c++;}}
      return back()->with('success',"$c adicionado(s)!"); }

    public function agruparAutomatico()
    { $userId=Auth::id(); foreach(Product::where('user_id',$userId)->get() as $p){
        if($p->canonical_product_id||$p->is_canonical)continue; $c=$this->grouperService->encontrarCanonico($p,$userId);
        if($c)$this->grouperService->agrupar($p,$c); else $this->grouperService->tornarCanonico($p);}
      return back()->with('success','Agrupamento automático concluído!'); }

    // ==================== ALERTAS ====================
    public function alertas()
    { 
        $alertas = \App\Models\PriceAlert::where('user_id', Auth::id())
            ->with('product')
            ->orderBy('variacao_percentual', 'desc')
            ->get();
        $s = app(\App\Services\PriceAlertService::class);
        $disparados = $s->verificarTodos(Auth::id());
        return view('products.alertas', compact('alertas', 'disparados')); 
    }

    public function criarAlerta(Request $request, Product $product)
    { 
        $s = app(\App\Services\PriceAlertService::class);
        $s->criarOuAtualizar(Auth::id(), $product->id, $request->limite_alerta);
        return back()->with('success', 'Alerta criado!'); 
    }

    public function removerAlerta(\App\Models\PriceAlert $alerta)
    { 
        if ($alerta->user_id !== Auth::id()) abort(403);
        $alerta->delete();
        return back()->with('success', 'Removido!'); 
    }

    public function toggleAlerta(\App\Models\PriceAlert $alerta)
    { 
        if ($alerta->user_id !== Auth::id()) abort(403);
        $alerta->update(['ativo' => !$alerta->ativo]);
        return back()->with('success', 'Alternado!'); 
    }
}