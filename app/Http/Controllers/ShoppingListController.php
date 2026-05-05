<?php

namespace App\Http\Controllers;

use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShoppingListController extends Controller
{
    public function index()
    {
        $listas = ShoppingList::where('user_id', Auth::id())
            ->withCount(['items', 'itemsComprados', 'itemsPendentes'])
            ->orderBy('ativa', 'desc')->orderBy('updated_at', 'desc')->get();
        return view('shopping-lists.index', compact('listas'));
    }

    public function show(ShoppingList $shoppingList)
    {
        $this->authorize('view', $shoppingList);
        $shoppingList->load('items.product');
        $produtosFrequentes = Product::where('user_id', Auth::id())->withCount('invoiceItems')->orderBy('invoice_items_count', 'desc')->take(20)->get();
        return view('shopping-lists.show', compact('shoppingList', 'produtosFrequentes'));
    }

    public function store(Request $request)
    { $lista = ShoppingList::create(['user_id' => Auth::id(), 'nome' => $request->nome, 'ativa' => true]); return redirect()->route('shopping-lists.show', $lista)->with('success', 'Lista criada!'); }

    public function update(Request $request, ShoppingList $shoppingList)
    { 
        $this->authorize('update', $shoppingList);
        $shoppingList->update(['nome' => $request->nome]); return back()->with('success', 'Atualizada!'); }

    public function destroy(ShoppingList $shoppingList)
    { 
        $this->authorize('delete', $shoppingList);    
        $shoppingList->delete(); return redirect()->route('shopping-lists.index')->with('success', 'Excluída!'); }

    public function finalizar(ShoppingList $shoppingList)
    { 
        $this->authorize('update', $shoppingList);
         $total = $shoppingList->items()->where('comprado', true)->sum('preco_estimado'); $shoppingList->update(['ativa' => false, 'data_compra' => now(), 'valor_total' => $total]); return back()->with('success', 'Finalizada!'); }

    public function reabrir(ShoppingList $shoppingList)
    { if ($shoppingList->user_id !== Auth::id()) abort(403); $shoppingList->update(['ativa' => true, 'data_compra' => null]); return back()->with('success', 'Reaberta!'); }

    public function addItem(Request $request, ShoppingList $shoppingList)
    { if ($shoppingList->user_id !== Auth::id()) abort(403); ShoppingListItem::create(['shopping_list_id' => $shoppingList->id, 'nome' => $request->nome, 'quantidade' => $request->quantidade ?? 1, 'unidade' => $request->unidade ?? 'UN', 'preco_estimado' => $this->getPrecoEstimado($request->nome), 'ordem' => $shoppingList->items()->count()]); return back()->with('success', 'Item adicionado!'); }

    public function addFrequentes(Request $request, ShoppingList $shoppingList)
    { if ($shoppingList->user_id !== Auth::id()) abort(403); $count = 0; foreach ($request->produtos ?? [] as $id) { $p = Product::find($id); if ($p) { ShoppingListItem::create(['shopping_list_id' => $shoppingList->id, 'product_id' => $p->id, 'nome' => $p->nome, 'quantidade' => 1, 'unidade' => $p->unidade_padrao ?? 'UN', 'preco_estimado' => $this->getPrecoEstimado($p->nome), 'ordem' => $shoppingList->items()->count()]); $count++; } } return back()->with('success', "$count adicionado(s)!"); }

    public function toggleItem(ShoppingListItem $item)
    { if ($item->shoppingList->user_id !== Auth::id()) abort(403); $item->update(['comprado' => !$item->comprado]); return back(); }

    public function removeItem(ShoppingListItem $item)
    { if ($item->shoppingList->user_id !== Auth::id()) abort(403); $item->delete(); return back(); }

    public function limparComprados(ShoppingList $shoppingList)
    { if ($shoppingList->user_id !== Auth::id()) abort(403); $shoppingList->items()->where('comprado', true)->delete(); return back(); }

    public function sugerirItens(ShoppingList $shoppingList)
    { if ($shoppingList->user_id !== Auth::id()) abort(403); $frequentes = Product::where('user_id', Auth::id())->withCount('invoiceItems')->orderBy('invoice_items_count', 'desc')->take(10)->get(); foreach ($frequentes as $p) { if (!$shoppingList->items()->where('product_id', $p->id)->exists()) { ShoppingListItem::create(['shopping_list_id' => $shoppingList->id, 'product_id' => $p->id, 'nome' => $p->nome, 'quantidade' => 1, 'unidade' => $p->unidade_padrao ?? 'UN', 'preco_estimado' => $this->getPrecoEstimado($p->nome), 'ordem' => $shoppingList->items()->count()]); } } return back()->with('success', 'Sugestões adicionadas!'); }

    // ==================== PLANEJAMENTO ====================
/*
    public function planejamento()
    {
        $userId = Auth::id();
        $cicloConsumo = $this->analisarCicloConsumo($userId);
        $reposicaoUrgente = array_filter($cicloConsumo, fn($c) => $c['status'] === 'urgente');
        $categoriasPorDia = $this->analisarComprasPorDia($userId);
        $estabelecimentosPorCategoria = $this->analisarEstabelecimentosPorCategoria($userId);
        $produtosFrequentesPorCategoria = $this->getProdutosFrequentesPorCategoria($userId);
        $sugestoesDias = $this->gerarSugestoesDias($categoriasPorDia);
        $compraMensal = $this->sugerirCompraMensal($userId);
        $economiaPotencial = $this->calcularEconomiaPotencial($userId);
        $tendencias = $this->analisarTendencias($userId);
        $listasAtivas = ShoppingList::where('user_id', $userId)->where('ativa', true)->withCount(['items', 'itemsComprados'])->get();
        return view('shopping-lists.planejamento', compact('cicloConsumo','reposicaoUrgente','categoriasPorDia','estabelecimentosPorCategoria','produtosFrequentesPorCategoria','sugestoesDias','compraMensal','economiaPotencial','tendencias','listasAtivas'));
    }
*/
 public function planejamento()
    {
        $userId = Auth::id();

        // Cache de 1 hora — dados mudam apenas quando NF-e é importada
        $dados = Cache::remember("planejamento-{$userId}", 3600, function () use ($userId) {
            return [
                'cicloConsumo'                  => $this->analisarCicloConsumo($userId),
                'categoriasPorDia'              => $this->analisarComprasPorDia($userId),
                'estabelecimentosPorCategoria'  => $this->analisarEstabelecimentosPorCategoria($userId),
                'produtosFrequentesPorCategoria'=> $this->getProdutosFrequentesPorCategoria($userId),
                'compraMensal'                  => $this->sugerirCompraMensal($userId),
                'economiaPotencial'             => $this->calcularEconomiaPotencial($userId),
                'tendencias'                    => $this->analisarTendencias($userId),
            ];
        });

        // Extrair sub-conjuntos calculados
        $cicloConsumo                   = $dados['cicloConsumo'];
        $reposicaoUrgente               = array_filter($cicloConsumo, fn($c) => $c['status'] === 'urgente');
        $categoriasPorDia               = $dados['categoriasPorDia'];
        $estabelecimentosPorCategoria   = $dados['estabelecimentosPorCategoria'];
        $produtosFrequentesPorCategoria = $dados['produtosFrequentesPorCategoria'];
        $sugestoesDias                  = $this->gerarSugestoesDias($categoriasPorDia);
        $compraMensal                   = $dados['compraMensal'];
        $economiaPotencial              = $dados['economiaPotencial'];
        $tendencias                     = $dados['tendencias'];

        $listasAtivas = ShoppingList::where('user_id', $userId)
            ->where('ativa', true)
            ->withCount(['items', 'itemsComprados'])
            ->get();

        return view('shopping-lists.planejamento', compact(
            'cicloConsumo', 'reposicaoUrgente', 'categoriasPorDia',
            'estabelecimentosPorCategoria', 'produtosFrequentesPorCategoria',
            'sugestoesDias', 'compraMensal', 'economiaPotencial',
            'tendencias', 'listasAtivas'
        ));
    }

    public function criarListaRapida(Request $request)
    {
        $request->validate(['categoria_id' => 'required|exists:categories,id', 'tipo' => 'required|in:semanal,mensal']);
        $categoria = Category::find($request->categoria_id);
        $nome = $request->tipo === 'semanal' ? "🛒 {$categoria->emoji} {$categoria->nome} - " . now()->format('d/m') : "📦 Compra do Mês - " . now()->format('m/Y');
        $lista = ShoppingList::create(['user_id' => Auth::id(), 'nome' => $nome, 'ativa' => true]);
        $produtos = $this->getProdutosParaCategoria($request->categoria_id, $request->tipo);
        foreach ($produtos as $prod) {
            ShoppingListItem::create(['shopping_list_id' => $lista->id, 'product_id' => $prod->id, 'nome' => $prod->nome, 'quantidade' => $prod->quantidade_sugerida ?? 1, 'unidade' => $prod->unidade_padrao ?? 'UN', 'preco_estimado' => $prod->preco_medio, 'ordem' => 0]);
        }
        return redirect()->route('shopping-lists.show', $lista)->with('success', "Lista criada com " . count($produtos) . " itens!");
    }

    // ==================== ANÁLISES ====================
/*
    private function analisarCicloConsumo(int $userId): array
    {
        // ✅ CORRIGIDO: buscar todos e filtrar em PHP (sem having)
        $produtos = Product::where('user_id', $userId)
            ->withCount('invoiceItems')
            ->orderBy('invoice_items_count', 'desc')
            ->take(50)
            ->get()
            ->filter(fn($p) => $p->invoice_items_count >= 2); // Filtrar em PHP

        $ciclos = [];
        foreach ($produtos as $produto) {
            $datasCompras = InvoiceItem::where('product_id', $produto->id)
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoices.user_id', $userId)
                ->orderBy('invoices.data_emissao', 'asc')
                ->pluck('invoices.data_emissao')
                ->toArray();

            if (count($datasCompras) < 2) continue;

            $intervalos = [];
            for ($i = 1; $i < count($datasCompras); $i++) {
                $intervalos[] = Carbon::parse($datasCompras[$i-1])->diffInDays(Carbon::parse($datasCompras[$i]));
            }

            if (empty($intervalos)) continue;

            $intervaloMedio = round(array_sum($intervalos) / count($intervalos));
            $ultimaCompra = Carbon::parse(end($datasCompras));
            $diasDesdeUltima = (int) now()->diffInDays($ultimaCompra);
            $diasAteProxima = max(0, $intervaloMedio - $diasDesdeUltima);
            $status = $diasDesdeUltima > $intervaloMedio * 1.3 ? 'urgente' : ($diasDesdeUltima > $intervaloMedio * 0.8 ? 'atencao' : 'ok');

            $ciclos[] = [
                'produto' => $produto,
                'intervalo_medio' => $intervaloMedio,
                'ultima_compra' => $ultimaCompra->format('d/m/Y'),
                'dias_desde_ultima' => $diasDesdeUltima,
                'dias_ate_proxima' => $diasAteProxima,
                'status' => $status,
                'quantidade_media' => $this->getQuantidadeMedia($produto->id),
                'unidade' => $produto->unidade_padrao ?? 'UN',
                'preco_estimado' => $this->getPrecoEstimado($produto->nome),
            ];
        }

        usort($ciclos, function($a, $b) {
            if ($a['status'] === 'urgente' && $b['status'] !== 'urgente') return -1;
            if ($b['status'] === 'urgente' && $a['status'] !== 'urgente') return 1;
            return $a['dias_ate_proxima'] <=> $b['dias_ate_proxima'];
        });

        return $ciclos;
    }
*/
    private function analisarCicloConsumo(int $userId): array
    {
        // 1 query única: busca todas as datas de compra por produto,
        // agrupadas e ordenadas — sem loop de queries.
        $registros = InvoiceItem::select(
                'invoice_items.product_id',
                DB::raw('MIN(invoices.data_emissao) as primeira_compra'),
                DB::raw('MAX(invoices.data_emissao) as ultima_compra'),
                DB::raw('COUNT(DISTINCT invoices.id) as num_compras'),
                DB::raw('AVG(invoice_items.quantidade) as quantidade_media'),
                DB::raw('AVG(invoice_items.valor_unitario) as preco_medio')
            )
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->groupBy('invoice_items.product_id')
            ->having(DB::raw('COUNT(DISTINCT invoices.id)'), '>=', 2)
            ->get()
            ->keyBy('product_id');

        // 1 query para os intervalos reais: todas as datas por produto de uma vez
        $todasDatas = InvoiceItem::select(
                'invoice_items.product_id',
                'invoices.data_emissao'
            )
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->whereIn('invoice_items.product_id', $registros->keys())
            ->orderBy('invoices.data_emissao', 'asc')
            ->get()
            ->groupBy('product_id');

        // 1 query para os produtos
        $produtos = Product::where('user_id', $userId)
            ->whereIn('id', $registros->keys())
            ->get()
            ->keyBy('id');

        $ciclos = [];
        foreach ($registros as $productId => $reg) {
            $produto = $produtos->get($productId);
            if (!$produto) continue;

            $datas = $todasDatas->get($productId, collect())
                ->pluck('data_emissao')
                ->map(fn($d) => Carbon::parse($d))
                ->values();

            if ($datas->count() < 2) continue;

            // Calcular intervalo médio em PHP (sem queries adicionais)
            $intervalos = [];
            for ($i = 1; $i < $datas->count(); $i++) {
                $intervalos[] = $datas[$i - 1]->diffInDays($datas[$i]);
            }

            $intervaloMedio   = (int) round(array_sum($intervalos) / count($intervalos));
            $ultimaCompra     = Carbon::parse($reg->ultima_compra);
            $diasDesdeUltima  = (int) now()->diffInDays($ultimaCompra);
            $diasAteProxima   = max(0, $intervaloMedio - $diasDesdeUltima);

            $status = match(true) {
                $diasDesdeUltima > $intervaloMedio * 1.3 => 'urgente',
                $diasDesdeUltima > $intervaloMedio * 0.8 => 'atencao',
                default                                  => 'ok',
            };

            $ciclos[] = [
                'produto'          => $produto,
                'intervalo_medio'  => $intervaloMedio,
                'ultima_compra'    => $ultimaCompra->format('d/m/Y'),
                'dias_desde_ultima'=> $diasDesdeUltima,
                'dias_ate_proxima' => $diasAteProxima,
                'status'           => $status,
                'quantidade_media' => round((float) $reg->quantidade_media, 2),
                'unidade'          => $produto->unidade_padrao ?? 'UN',
                'preco_estimado'   => round((float) $reg->preco_medio, 2),
            ];
        }

        usort($ciclos, function ($a, $b) {
            $ordem = ['urgente' => 0, 'atencao' => 1, 'ok' => 2];
            if ($ordem[$a['status']] !== $ordem[$b['status']]) {
                return $ordem[$a['status']] <=> $ordem[$b['status']];
            }
            return $a['dias_ate_proxima'] <=> $b['dias_ate_proxima'];
        });

        return $ciclos;
    }
    /*
    private function calcularEconomiaPotencial(int $userId): array
    {
        $produtos = Product::where('user_id', $userId)->withCount('invoiceItems')->orderBy('invoice_items_count', 'desc')->take(20)->get()->filter(fn($p) => $p->invoice_items_count >= 3);
        $economias = [];
        foreach ($produtos as $produto) {
            $precosPorEstab = InvoiceItem::where('product_id', $produto->id)->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')->where('invoices.user_id', $userId)->select('invoices.nome_estabelecimento', DB::raw('AVG(invoice_items.valor_unitario) as preco_medio'))->groupBy('invoices.nome_estabelecimento')->orderBy('preco_medio')->get();
            if ($precosPorEstab->count() < 2) continue;
            $maisBarato = $precosPorEstab->first(); $maisCaro = $precosPorEstab->last();
            $diferenca = $maisCaro->preco_medio - $maisBarato->preco_medio;
            if ($diferenca > 0) {
                $economias[] = ['produto'=>$produto->nome,'mais_barato'=>$maisBarato->nome_estabelecimento,'preco_barato'=>round($maisBarato->preco_medio,2),'mais_caro'=>$maisCaro->nome_estabelecimento,'preco_caro'=>round($maisCaro->preco_medio,2),'diferenca'=>round($diferenca,2),'diferenca_percentual'=>round(($diferenca/$maisBarato->preco_medio)*100,1)];
            }
        }
        usort($economias, fn($a,$b) => $b['diferenca'] <=> $a['diferenca']);
        return array_slice($economias, 0, 10);
    }
        */
     private function calcularEconomiaPotencial(int $userId): array
    {
        // 1 query: preço médio por produto × estabelecimento
        $rows = InvoiceItem::select(
                'invoice_items.product_id',
                'products.nome as produto_nome',
                'invoices.nome_estabelecimento',
                DB::raw('AVG(invoice_items.valor_unitario) as preco_medio'),
                DB::raw('COUNT(DISTINCT invoices.id) as num_compras')
            )
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->groupBy(
                'invoice_items.product_id',
                'products.nome',
                'invoices.nome_estabelecimento'
            )
            ->having(DB::raw('COUNT(DISTINCT invoices.id)'), '>=', 2)
            ->get()
            ->groupBy('product_id');

        $economias = [];

        foreach ($rows as $productId => $grupo) {
            if ($grupo->count() < 2) continue;

            $sorted   = $grupo->sortBy('preco_medio');
            $barato   = $sorted->first();
            $caro     = $sorted->last();
            $diferenca = round((float)$caro->preco_medio - (float)$barato->preco_medio, 2);

            if ($diferenca <= 0) continue;

            $economias[] = [
                'produto'              => $barato->produto_nome,
                'mais_barato'          => $barato->nome_estabelecimento,
                'preco_barato'         => round((float)$barato->preco_medio, 2),
                'mais_caro'            => $caro->nome_estabelecimento,
                'preco_caro'           => round((float)$caro->preco_medio, 2),
                'diferenca'            => $diferenca,
                'diferenca_percentual' => round(
                    ($diferenca / (float)$barato->preco_medio) * 100, 1
                ),
            ];
        }

        usort($economias, fn($a, $b) => $b['diferenca'] <=> $a['diferenca']);

        return array_slice($economias, 0, 10);
    }

    private function analisarTendencias(int $userId): array
    {
        $gastoAtual = Invoice::where('user_id', $userId)->whereMonth('data_emissao', now()->month)->whereYear('data_emissao', now()->year)->sum('valor_pago');
        $gastoAnterior = Invoice::where('user_id', $userId)->whereMonth('data_emissao', now()->subMonth()->month)->whereYear('data_emissao', now()->subMonth()->year)->sum('valor_pago');
        $variacao = $gastoAnterior > 0 ? (($gastoAtual - $gastoAnterior) / $gastoAnterior) * 100 : 0;
        $diasMes = now()->day;
        $mediaDiaria = $diasMes > 0 ? $gastoAtual / $diasMes : 0;
        $diasRestantes = now()->daysInMonth - now()->day;
        $projecao = $gastoAtual + ($mediaDiaria * $diasRestantes);
        return ['gasto_atual'=>$gastoAtual,'gasto_anterior'=>$gastoAnterior,'variacao'=>round($variacao,1),'media_diaria'=>round($mediaDiaria,2),'projecao'=>round($projecao,2),'dias_restantes'=>$diasRestantes];
    }

    private function getQuantidadeMedia(int $productId): float
    { $media = InvoiceItem::where('product_id', $productId)->orderBy('created_at', 'desc')->take(5)->avg('quantidade'); return $media ? round($media, 2) : 1; }

    private function getPrecoEstimado(string $nome): ?float
    { $produto = Product::where('user_id', Auth::id())->where('nome', 'ilike', $nome)->first(); if ($produto) { $media = InvoiceItem::where('product_id', $produto->id)->orderBy('created_at', 'desc')->take(5)->avg('valor_unitario'); return $media ? round($media, 2) : null; } return null; }


    private function getProdutosParaCategoria(int $categoriaId, string $tipo): array
    { $produtos = Product::where('user_id', Auth::id())->where('category_id', $categoriaId)->withCount('invoiceItems')->orderBy('invoice_items_count', 'desc')->take($tipo==='semanal'?15:30)->get(); if ($tipo==='semanal') $produtos = $produtos->filter(fn($p) => $p->invoice_items_count >= 2); return $produtos->map(function($p) { $media = InvoiceItem::where('product_id', $p->id)->orderBy('created_at', 'desc')->take(5)->avg('valor_unitario'); $p->preco_medio = $media ? round($media, 2) : null; $p->quantidade_sugerida = 1; return $p; })->values()->all(); }

    private function getProdutosFrequentesPorCategoria(int $userId): array
    {
        // Preços médios de TODOS os produtos em 1 query
        $precosMedios = InvoiceItem::select(
                'invoice_items.product_id',
                DB::raw('AVG(invoice_items.valor_unitario) as preco_medio')
            )
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->groupBy('invoice_items.product_id')
            ->pluck('preco_medio', 'product_id');

        // Todos os produtos com categorias em 1 query
        $produtos = Product::where('user_id', $userId)
            ->whereNotNull('category_id')
            ->withCount('invoiceItems')
            ->orderBy('invoice_items_count', 'desc')
            ->get()
            ->each(function ($p) use ($precosMedios) {
                $p->preco_medio = $precosMedios->has($p->id)
                    ? round((float) $precosMedios[$p->id], 2)
                    : null;
            })
            ->groupBy('category_id');

        // Limitar a 10 por categoria em PHP (sem queries extras)
        return $produtos->map(fn($grupo) => $grupo->take(10))->all();
    }

    private function analisarComprasPorDia(int $userId): array
    { $categorias = Category::where('user_id', $userId)->ordenado()->get(); $dados = []; foreach ($categorias as $cat) { $compras = InvoiceItem::whereHas('invoice', fn($q) => $q->where('user_id', $userId))->whereHas('product', fn($q) => $q->where('category_id', $cat->id))->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')->selectRaw("EXTRACT(DOW FROM invoices.data_emissao) as dia, COUNT(DISTINCT invoices.id) as total")->groupBy('dia')->orderBy('total', 'desc')->get(); if ($compras->count() > 0) $dados[$cat->id] = ['categoria' => $cat, 'dias' => $compras->pluck('total', 'dia')->toArray(), 'dia_mais_frequente' => (int) $compras->first()->dia]; } return $dados; }

    private function analisarEstabelecimentosPorCategoria(int $userId): array
    { $categorias = Category::where('user_id', $userId)->ordenado()->get(); $dados = []; foreach ($categorias as $cat) { $estabs = InvoiceItem::whereHas('invoice', fn($q) => $q->where('user_id', $userId))->whereHas('product', fn($q) => $q->where('category_id', $cat->id))->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')->select('invoices.nome_estabelecimento', DB::raw('COUNT(DISTINCT invoices.id) as total'))->groupBy('invoices.nome_estabelecimento')->orderBy('total', 'desc')->take(3)->get(); if ($estabs->count() > 0) $dados[$cat->id] = $estabs->toArray(); } return $dados; }
/*
    private function getProdutosFrequentesPorCategoria(int $userId): array
    { $categorias = Category::where('user_id', $userId)->ordenado()->get(); $dados = []; foreach ($categorias as $cat) { $produtos = Product::where('user_id', $userId)->where('category_id', $cat->id)->withCount('invoiceItems')->orderBy('invoice_items_count', 'desc')->take(10)->get()->map(function($p) { $media = InvoiceItem::where('product_id', $p->id)->orderBy('created_at', 'desc')->take(5)->avg('valor_unitario'); $p->preco_medio = $media ? round($media, 2) : null; return $p; }); if ($produtos->count() > 0) $dados[$cat->id] = $produtos; } return $dados; }
*/
    private function gerarSugestoesDias(array $categoriasPorDia): array
    { $diasSemana = [0=>'Domingo',1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado']; $sugestoes = []; foreach ($categoriasPorDia as $dados) { $dia = $dados['dia_mais_frequente'] ?? null; if ($dia === null) continue; $proximo = $this->proximoDiaSemana((int)$dia); $diasAte = (int) now()->startOfDay()->diffInDays($proximo->startOfDay()); if ($diasAte < 0) $diasAte = 0; $sugestoes[] = ['categoria'=>$dados['categoria'],'dia_nome'=>$diasSemana[(int)$dia],'proxima_data'=>$proximo->format('d/m/Y'),'dias_ate'=>$diasAte]; } usort($sugestoes, fn($a,$b) => $a['dias_ate'] <=> $b['dias_ate']); return $sugestoes; }

    private function proximoDiaSemana(int $dia): Carbon
    { $hoje = now()->startOfDay(); $diaAtual = (int)$hoje->format('w'); if ($diaAtual === $dia) return $hoje; return $diaAtual < $dia ? $hoje->copy()->addDays($dia - $diaAtual) : $hoje->copy()->addDays(7 - $diaAtual + $dia); }

   /* private function sugerirCompraMensal(int $userId): array
    { $ultima = Invoice::where('user_id', $userId)->where('total_itens', '>=', 5)->orderBy('data_emissao', 'desc')->first(); $dias = $ultima ? (int) now()->diffInDays($ultima->data_emissao) : 999; return ['dias_desde_ultima'=>$dias, 'sugerir'=>$dias>=25, 'ultima_data'=>$ultima?->data_emissao->format('d/m/Y'), 'estabelecimento'=>$ultima?->nome_estabelecimento]; }
     */
    private function sugerirCompraMensal(int $userId): array
{
    $ultima = Invoice::where('user_id', $userId)
        ->where('total_itens', '>=', 5)
        ->orderBy('data_emissao', 'desc')
        ->first();

    $dias = $ultima ? (int) now()->diffInDays($ultima->data_emissao) : 999;

    return [
        'dias_desde_ultima' => $dias,
        'sugerir'           => $dias >= 25,
        'ultima_data'       => $ultima?->data_emissao?->format('d/m/Y'),
        'estabelecimento'   => $ultima?->nome_estabelecimento,
    ];
}
}
