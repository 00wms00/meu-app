<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Services\ShoppingListItemService;
use App\Services\ShoppingPlanningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ShoppingListController extends Controller
{
    public function __construct(
        private ShoppingListItemService $itemService,
        private ShoppingPlanningService $planningService,
    ) {}

    // ==================== CRUD ====================

    public function index(): View
    {
        $listas = ShoppingList::where('user_id', Auth::id())
            ->withCount(['items', 'itemsComprados', 'itemsPendentes'])
            ->orderBy('ativa', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('shopping-lists.index', compact('listas'));
    }

    public function show(ShoppingList $lista): View
    {
        $this->authorize('view', $lista);

        $lista->load('items.product');

        $produtosFrequentes = Product::where('user_id', Auth::id())
            ->withCount('invoiceItems')
            ->orderBy('invoice_items_count', 'desc')
            ->take(20)
            ->get();

        return view('shopping-lists.show', compact('lista', 'produtosFrequentes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['nome' => 'required|string|max:255']);

        $lista = ShoppingList::create([
            'user_id' => Auth::id(),
            'nome'    => $request->nome,
            'ativa'   => true,
        ]);

        return redirect()->route('shopping-lists.show', $lista)->with('success', 'Lista criada!');
    }

    public function update(Request $request, ShoppingList $lista): RedirectResponse
    {
        $this->authorize('update', $lista);
        $request->validate(['nome' => 'required|string|max:255']);

        $lista->update(['nome' => $request->nome]);

        return back()->with('success', 'Atualizada!');
    }

    public function destroy(ShoppingList $lista): RedirectResponse
    {
        $this->authorize('delete', $lista);

        $lista->delete();

        return redirect()->route('shopping-lists.index')->with('success', 'Excluída!');
    }

    // ==================== ESTADO DA LISTA ====================

    public function finalizar(ShoppingList $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $total = $lista->items()->where('comprado', true)->sum('preco_estimado');

        $lista->update([
            'ativa'       => false,
            'data_compra' => now(),
            'valor_total' => $total,
        ]);

        return back()->with('success', 'Finalizada!');
    }

    public function reabrir(ShoppingList $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $lista->update(['ativa' => true, 'data_compra' => null]);

        return back()->with('success', 'Reaberta!');
    }

    // ==================== ITENS ====================

    public function addItem(Request $request, ShoppingList $lista): RedirectResponse
    {
        $this->authorize('update', $lista);
        $request->validate([
            'nome'       => 'required|string|max:255',
            'quantidade' => 'nullable|numeric|min:0.01',
            'unidade'    => 'nullable|string|max:10',
        ]);

        $this->itemService->criarItemManual(
            $lista,
            $request->nome,
            $request->quantidade ?? 1,
            $request->unidade    ?? 'UN',
        );

        return back()->with('success', 'Item adicionado!');
    }

    public function addFrequentes(Request $request, ShoppingList $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $count = 0;
        foreach ($request->produtos ?? [] as $id) {
            $produto = Product::find($id);
            if ($produto) {
                $this->itemService->criarItemDeProduto($lista, $produto);
                $count++;
            }
        }

        return back()->with('success', "{$count} adicionado(s)!");
    }

    public function sugerirItens(ShoppingList $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $frequentes = Product::where('user_id', Auth::id())
            ->withCount('invoiceItems')
            ->orderBy('invoice_items_count', 'desc')
            ->take(10)
            ->get();

        foreach ($frequentes as $produto) {
            if (! $lista->items()->where('product_id', $produto->id)->exists()) {
                $this->itemService->criarItemDeProduto($lista, $produto);
            }
        }

        return back()->with('success', 'Sugestões adicionadas!');
    }

    public function toggleItem(ShoppingListItem $item): RedirectResponse
    {
        $this->authorize('update', $item->shoppingList);

        $item->update(['comprado' => ! $item->comprado]);

        return back();
    }

    public function updateItem(Request $request, ShoppingListItem $item): RedirectResponse
    {
        $this->authorize('update', $item->shoppingList);
        $request->validate([
            'nome'       => 'sometimes|string|max:255',
            'quantidade' => 'sometimes|numeric|min:0.01',
            'unidade'    => 'sometimes|string|max:10',
        ]);

        $item->update($request->only(['nome', 'quantidade', 'unidade', 'preco_estimado']));

        return back()->with('success', 'Item atualizado!');
    }

    public function removeItem(ShoppingListItem $item): RedirectResponse
    {
        $this->authorize('update', $item->shoppingList);

        $item->delete();

        return back();
    }

    public function limparComprados(ShoppingList $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $lista->items()->where('comprado', true)->delete();

        return back();
    }

    // ==================== PLANEJAMENTO ====================

    public function planejamento()
    {
        $userId = Auth::id();

        $dados = Cache::remember("planejamento-{$userId}", 3600, function () use ($userId) {
            return [
                'cicloConsumo'                   => $this->planningService->analisarCicloConsumo($userId),
                'categoriasPorDia'               => $this->planningService->analisarComprasPorDia($userId),
                'estabelecimentosPorCategoria'   => $this->planningService->analisarEstabelecimentosPorCategoria($userId),
                'produtosFrequentesPorCategoria' => $this->planningService->getProdutosFrequentesPorCategoria($userId),
                'compraMensal'                   => $this->planningService->sugerirCompraMensal($userId),
                'economiaPotencial'              => $this->planningService->calcularEconomiaPotencial($userId),
                'tendencias'                     => $this->planningService->analisarTendencias($userId),
            ];
        });

        $cicloConsumo                   = $dados['cicloConsumo'];
        $reposicaoUrgente               = array_filter($cicloConsumo, fn($c) => $c['status'] === 'urgente');
        $categoriasPorDia               = $dados['categoriasPorDia'];
        $estabelecimentosPorCategoria   = $dados['estabelecimentosPorCategoria'];
        $produtosFrequentesPorCategoria = $dados['produtosFrequentesPorCategoria'];
        $sugestoesDias                  = $this->planningService->gerarSugestoesDias($categoriasPorDia);
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

    public function criarListaRapida(Request $request): RedirectResponse
    {
        $request->validate([
            'categoria_id' => 'required|exists:categories,id',
            'tipo'         => 'required|in:semanal,mensal',
        ]);

        $categoria = Category::findOrFail($request->categoria_id);

        $nome = $request->tipo === 'semanal'
            ? "🛒 {$categoria->emoji} {$categoria->nome} - " . now()->format('d/m')
            : "📦 Compra do Mês - " . now()->format('m/Y');

        $lista    = ShoppingList::create(['user_id' => Auth::id(), 'nome' => $nome, 'ativa' => true]);
        $produtos = $this->itemService->getProdutosParaCategoria($request->categoria_id, $request->tipo);

        foreach ($produtos as $prod) {
            $this->itemService->criarItemDeProduto($lista, $prod, $prod->quantidade_sugerida ?? 1);
        }

        return redirect()
            ->route('shopping-lists.show', $lista)
            ->with('success', 'Lista criada com ' . count($produtos) . ' itens!');
    }
}
