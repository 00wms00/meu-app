<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\InvoiceItem;
use App\Services\ProductGrouperService;
use App\Services\ProductSimilarityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected $grouperService;

    public function __construct(ProductGrouperService $grouperService)
    {
        $this->grouperService = $grouperService;
    }

    // ==================== CRUD ====================
    public function index(Request $request)
    {
        $query = Product::where('user_id', Auth::id());
        if ($request->filled('categoria')) $query->where('category_id', $request->categoria);
        if ($request->filled('search')) $query->where('nome', 'ilike', "%{$request->search}%");
        $products = $query->orderBy('nome')->paginate(50);
        $categorias = Category::where('user_id', Auth::id())->ordenado()->get();
        return view('products.index', compact('products', 'categorias'));
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $produtoExibicao = $product;
        if ($product->canonical_product_id && !$product->is_canonical) $produtoExibicao = Product::find($product->canonical_product_id);
        $produtoIds = Product::where(fn($q) => $q->where('id', $produtoExibicao->id)->orWhere('canonical_product_id', $produtoExibicao->id))->pluck('id');
        $items = InvoiceItem::with('invoice')->whereIn('product_id', $produtoIds)->whereHas('invoice', fn($q) => $q->where('user_id', Auth::id()))->orderBy('created_at')->get();
        $serie = $items->map(fn($i) => ['data'=>$i->invoice->data_emissao->format('Y-m-d'),'valor_unitario'=>$i->valor_unitario,'unidade'=>$i->unidade])->values();
        $variacao = null; $p = $serie->first(); $u = $serie->last();
        if ($p && $u && $p['valor_unitario']>0) $variacao = (($u['valor_unitario']-$p['valor_unitario'])/$p['valor_unitario'])*100;
        $agrupados = Product::where('canonical_product_id', $produtoExibicao->id)->get();
        return view('products.show', compact('product','produtoExibicao','serie','variacao','agrupados'));
    }

    public function edit(Product $product)
    {
              $this->authorize('update', $product);

    
    return view('products.edit', compact('product')); }

    public function update(Request $request, Product $product)
    { 
        $this->authorize('update', $product);

    
    $product->update($request->validate(['nome'=>'required|string|max:255','unidade_padrao'=>'nullable'])); return redirect()->route('products.show',$product)->with('success','Atualizado!'); }

    // ==================== FOTO ====================
    public function uploadFoto(Request $request, Product $product)
    { if ($product->user_id !== Auth::id()) abort(403); $request->validate(['foto'=>'required|image|mimes:jpeg,png,jpg,webp|max:2048']);
      if ($product->foto) Storage::disk('public')->delete($product->foto);
      $product->update(['foto'=>$request->file('foto')->store('produtos/'.Auth::id(),'public')]); return back()->with('success','Foto atualizada!'); }

    public function removerFoto(Product $product)
    { if ($product->user_id !== Auth::id()) abort(403); if ($product->foto) Storage::disk('public')->delete($product->foto);
      $product->update(['foto'=>null]); return back()->with('success','Foto removida!'); }

    // ==================== CATEGORIAS ====================
    public function categorias(Request $request)
    { $userId=Auth::id(); $cf=$request->input('categoria'); $q=Product::where('user_id',$userId)->with('category');
      if($cf==='sem')$q->whereNull('category_id'); elseif($cf)$q->where('category_id',$cf);
      if($request->filled('search'))$q->where('nome','ilike','%'.$request->search.'%');
      $produtos=$q->orderBy('nome')->paginate(50); $categorias=Category::where('user_id',$userId)->ordenado()->get();
      return view('products.categorias',compact('produtos','categorias','cf')); }

    public function categorizarLote(Request $request)
    { Product::whereIn('id',$request->produto_ids)->where('user_id',Auth::id())->update(['category_id'=>$request->categoria?:null]);
      return back()->with('success',count($request->produto_ids).' produto(s) categorizado(s)!'); }

    public function atualizarCategoria(Request $request, Product $product)
    { if($product->user_id!==Auth::id())abort(403); $product->update(['category_id'=>$request->categoria?:null]); return back()->with('success','OK!'); }

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
    { $alertas=\App\Models\PriceAlert::where('user_id',Auth::id())->with('product')->orderBy('variacao_percentual','desc')->get();
      $s=app(\App\Services\PriceAlertService::class); $disparados=$s->verificarTodos(Auth::id());
      return view('products.alertas',compact('alertas','disparados')); }

    public function criarAlerta(Request $request, Product $product)
    { $s=app(\App\Services\PriceAlertService::class); $s->criarOuAtualizar(Auth::id(),$product->id,$request->limite_alerta);
      return back()->with('success','Alerta criado!'); }

    public function removerAlerta(\App\Models\PriceAlert $alerta)
    { if($alerta->user_id!==Auth::id())abort(403); $alerta->delete(); return back()->with('success','Removido!'); }

    public function toggleAlerta(\App\Models\PriceAlert $alerta)
    { if($alerta->user_id!==Auth::id())abort(403); $alerta->update(['ativo'=>!$alerta->ativo]); return back()->with('success','Alternado!'); }

    // ==================== MACHINE LEARNING ====================
    
    public function similares(Request $request, Product $product, ProductSimilarityService $ml)
    { if($product->user_id!==Auth::id())abort(403); $similares=$ml->encontrarSimilares($product,10);
      return view('products.similares',compact('product','similares')); }

    public function mlSugestoesInterativo(ProductSimilarityService $ml)
    { $sugestoes=$ml->sugerirAgrupamentosML(Auth::id());
      return view('products.ml-interativo',compact('sugestoes')); }

    public function mlConfirmarAgrupamento(Request $request, ProductSimilarityService $ml)
    { $request->validate(['produto_id'=>'required|exists:products,id','canonico_id'=>'nullable|exists:products,id','acao'=>'required|in:agrupar,pular,ignorar']);
      if($request->acao==='agrupar'){$p=Product::find($request->produto_id);$c=Product::find($request->canonico_id);
        if(!$c->is_canonical)$this->grouperService->tornarCanonico($c); $this->grouperService->agrupar($p,$c);
        return response()->json(['status'=>'agrupado','message'=>"{$p->nome} → {$c->nome}"]);}
      return response()->json(['status'=>$request->acao]); }

    public function mlAgrupar(ProductSimilarityService $ml)
    { $r=$ml->agruparComML(Auth::id()); return redirect()->route('products.agrupamentos')->with('success',"ML: {$r['agrupados']} agrupado(s)!"); }

    public function mlSugestoes(ProductSimilarityService $ml)
    { $sugestoes=$ml->sugerirAgrupamentosML(Auth::id()); return view('products.ml-sugestoes',compact('sugestoes')); }
}
