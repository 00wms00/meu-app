<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Services\ShoppingListItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShoppingListController extends Controller
{
    public function __construct(
        private ShoppingListItemService $itemService,
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

    public function show(ShoppingList $shoppingList): View
    {
        $this->authorize('view', $shoppingList);

        $shoppingList->load('items.product');

        $produtosFrequentes = Product::where('user_id', Auth::id())
            ->withCount('invoiceItems')
            ->orderBy('invoice_items_count', 'desc')
            ->take(20)
            ->get();

        $lista = $shoppingList;

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

    public function update(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('update', $shoppingList);
        $request->validate(['nome' => 'required|string|max:255']);

        $shoppingList->update(['nome' => $request->nome]);

        return back()->with('success', 'Atualizada!');
    }

    public function destroy(ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('delete', $shoppingList);

        $shoppingList->delete();

        return redirect()->route('shopping-lists.index')->with('success', 'Excluída!');
    }

    // ==================== ESTADO DA LISTA ====================

    public function finalizar(ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('update', $shoppingList);

        $total = $shoppingList->items()->where('comprado', true)->sum('preco_estimado');

        $shoppingList->update([
            'ativa'       => false,
            'data_compra' => now(),
            'valor_total' => $total,
        ]);

        return back()->with('success', 'Finalizada!');
    }

    public function reabrir(ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('update', $shoppingList);

        $shoppingList->update(['ativa' => true, 'data_compra' => null]);

        return back()->with('success', 'Reaberta!');
    }

    // ==================== ITENS ====================

    public function addItem(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('update', $shoppingList);
        $request->validate([
            'nome'       => 'required|string|max:255',
            'quantidade' => 'nullable|numeric|min:0.01',
            'unidade'    => 'nullable|string|max:10',
        ]);

        $this->itemService->criarItemManual(
            $shoppingList,
            $request->nome,
            $request->quantidade ?? 1,
            $request->unidade    ?? 'UN',
        );

        return back()->with('success', 'Item adicionado!');
    }

    public function addFrequentes(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('update', $shoppingList);

        $count = 0;
        foreach ($request->produtos ?? [] as $id) {
            $produto = Product::find($id);
            if ($produto) {
                $this->itemService->criarItemDeProduto($shoppingList, $produto);
                $count++;
            }
        }

        return back()->with('success', "{$count} adicionado(s)!");
    }

    public function sugerirItens(ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('update', $shoppingList);

        $frequentes = Product::where('user_id', Auth::id())
            ->withCount('invoiceItems')
            ->orderBy('invoice_items_count', 'desc')
            ->take(10)
            ->get();

        foreach ($frequentes as $produto) {
            if (! $shoppingList->items()->where('product_id', $produto->id)->exists()) {
                $this->itemService->criarItemDeProduto($shoppingList, $produto);
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

    public function limparComprados(ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorize('update', $shoppingList);

        $shoppingList->items()->where('comprado', true)->delete();

        return back();
    }

    // ==================== PLANEJAMENTO ====================

    public function planejamento(): View
    {
        $userId = Auth::id();

        // --- Tendências (cards do topo) ---
        $mesAtual    = now()->month;
        $anoAtual    = now()->year;
        $mesAnterior = now()->subMonth()->month;
        $anoAnterior = now()->subMonth()->year;

        $gastoAtual = ShoppingList::where('user_id', $userId)
            ->whereMonth('data_compra', $mesAtual)
            ->whereYear('data_compra', $anoAtual)
            ->whereNotNull('data_compra')
            ->sum('valor_total');

        $gastoAnterior = ShoppingList::where('user_id', $userId)
            ->whereMonth('data_compra', $mesAnterior)
            ->whereYear('data_compra', $anoAnterior)
            ->whereNotNull('data_compra')
            ->sum('valor_total');

        $variacao = $gastoAnterior > 0
            ? round((($gastoAtual - $gastoAnterior) / $gastoAnterior) * 100, 1)
            : 0;

        $totalListas = ShoppingList::where('user_id', $userId)->count();
        $mediaLista  = $totalListas > 0
            ? ShoppingList::where('user_id', $userId)->whereNotNull('valor_total')->avg('valor_total') ?? 0
            : 0;

        $tendencias = [
            'gasto_atual'  => $gastoAtual,
            'variacao'     => $variacao,
            'media_lista'  => $mediaLista,
            'total_listas' => $totalListas,
        ];

        // --- Próximas Compras sugeridas ---
        // Sugere categorias com listas finalizadas, ordenando pelas mais frequentes
        $proximasCompras = collect();
        $categorias = Category::where('user_id', $userId)->get();

        foreach ($categorias as $categoria) {
            $listasCategoria = ShoppingList::where('user_id', $userId)
                ->where('nome', 'like', "%{$categoria->nome}%")
                ->whereNotNull('data_compra')
                ->orderBy('data_compra', 'desc')
                ->take(5)
                ->get();

            if ($listasCategoria->isEmpty()) {
                continue;
            }

            $mediaGasto  = $listasCategoria->avg('valor_total') ?? 0;
            $totalListas = $listasCategoria->count();
            $ultimaCompra = $listasCategoria->first()->data_compra;
            $diasDesdeUltima = now()->diffInDays($ultimaCompra);

            // Score baseado em frequência e tempo desde a última compra
            $score = min(10, round(($diasDesdeUltima / 7) + ($totalListas / 2)));

            $urgencia = match (true) {
                $score >= 7 => 'alta',
                $score >= 4 => 'media',
                default     => 'baixa',
            };

            $proximasCompras->push([
                'categoria'     => $categoria,
                'tipo'          => $diasDesdeUltima >= 20 ? 'mensal' : 'semanal',
                'valor_previsto' => $mediaGasto,
                'score'         => $score,
                'urgencia'      => $urgencia,
            ]);
        }

        $proximasCompras = $proximasCompras->sortByDesc('score')->take(6)->values();

        // --- Análise por Categoria ---
        $analiseCategoria = collect();

        foreach ($categorias as $categoria) {
            $listas = ShoppingList::where('user_id', $userId)
                ->where('nome', 'like', "%{$categoria->nome}%")
                ->whereNotNull('data_compra')
                ->get();

            if ($listas->isEmpty()) {
                continue;
            }

            $totalGasto  = $listas->sum('valor_total');
            $mediaGasto  = $listas->avg('valor_total') ?? 0;
            $totalListas = $listas->count();

            $frequencia = match (true) {
                $totalListas >= 4 => 'alta',
                $totalListas >= 2 => 'media',
                default           => 'baixa',
            };

            $analiseCategoria->push([
                'categoria'   => $categoria,
                'total_listas' => $totalListas,
                'gasto_total' => $totalGasto,
                'media_gasto' => $mediaGasto,
                'frequencia'  => $frequencia,
            ]);
        }

        $analiseCategoria = $analiseCategoria->sortByDesc('gasto_total')->values();

        // --- Sazonalidade (histórico mensal) ---
        $sazonalidade = ShoppingList::where('user_id', $userId)
            ->whereNotNull('data_compra')
            ->select(
                DB::raw('YEAR(data_compra) as ano'),
                DB::raw('MONTH(data_compra) as mes'),
                DB::raw('COUNT(*) as total_listas'),
                DB::raw('SUM(valor_total) as total_gasto')
            )
            ->groupBy('ano', 'mes')
            ->orderBy('ano', 'desc')
            ->orderBy('mes', 'desc')
            ->take(12)
            ->get()
            ->map(fn ($row) => [
                'mes_nome'    => \Carbon\Carbon::create($row->ano, $row->mes, 1)->translatedFormat('F Y'),
                'total_listas' => $row->total_listas,
                'total_gasto' => $row->total_gasto ?? 0,
            ]);

        return view('shopping-lists.planejamento', compact(
            'tendencias',
            'proximasCompras',
            'analiseCategoria',
            'sazonalidade',
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
