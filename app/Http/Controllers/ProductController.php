<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InvoiceItem;
use App\Models\PriceAlert;
use App\Models\Product;
use App\Services\ProductNormalizationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
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
        $userId = Auth::id();
        $query  = Product::where('user_id', $userId)->with('category');

        if ($request->filled('categoria')) {
            $query->where('category_id', $request->categoria);
        }
        if ($request->filled('search')) {
            $query->where('nome', 'ilike', "%{$request->search}%");
        }

        $allProducts = $query->orderBy('nome')->get();

        $grouped = $allProducts->groupBy(function ($p) {
            return $p->category?->nome ?? 'Sem categoria';
        })->sortKeys();

        $categorias = Category::where('user_id', $userId)->ordenado()->get();
        $total      = $allProducts->count();

        return view('products.index', compact('grouped', 'categorias', 'total'));
    }

    /**
     * Autocomplete: retorna os 5 primeiros produtos que batem com o termo.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Product::where('user_id', Auth::id())
            ->where(function ($query) use ($q) {
                $query->where('nome', 'ilike', "%{$q}%")
                      ->orWhere('nome_exibicao', 'ilike', "%{$q}%");
            })
            ->with('category')
            ->orderBy('nome')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                return [
                    'id'        => $p->id,
                    'nome'      => $p->nome_exibicao ?: $p->nome,
                    'categoria' => $p->category?->nome ?? 'Sem categoria',
                    'url'       => route('products.show', $p),
                ];
            });

        return response()->json($results);
    }

    public function show(Request $request, Product $product): View
    {
        $this->authorize('view', $product);

        $produtoExibicao = $product->canonical_product_id && ! $product->is_canonical
            ? Product::findOrFail($product->canonical_product_id)
            : $product;

        $produtoIds = Product::where(function ($q) use ($produtoExibicao) {
            $q->where('id', $produtoExibicao->id)
              ->orWhere('canonical_product_id', $produtoExibicao->id);
        })->pluck('id');

        // Serie historica completa
        $items = InvoiceItem::with(['invoice', 'product'])
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereIn('invoice_items.product_id', $produtoIds)
            ->where('invoices.user_id', Auth::id())
            ->orderBy('invoices.data_emissao')
            ->select('invoice_items.*')
            ->get();

        $serie = $items->map(function ($i) {
            return [
                'data'           => $i->invoice->data_emissao->format('Y-m-d'),
                'valor_unitario' => (float) $i->valor_unitario,
                'unidade'        => $i->unidade,
                'nome_produto'   => $i->product?->nome_exibicao ?? $i->product?->nome ?? $i->descricao ?? '',
                'mercado'        => $i->invoice->nome_estabelecimento ?? '-',
            ];
        })->values();

        // Variacao primeira -> ultima compra
        $variacao = null;
        if ($serie->count() >= 2) {
            $primeiro = $serie->first()['valor_unitario'];
            $ultimo   = $serie->last()['valor_unitario'];
            if ($primeiro > 0) {
                $variacao = (($ultimo - $primeiro) / $primeiro) * 100;
            }
        }

        // Filtro de periodo para analise
        $periodoAtivo = $request->input('periodo', '30d');
        [$dataInicioAnalise, $dataFimAnalise] = $this->resolverPeriodo(
            $periodoAtivo,
            $request->input('data_inicio'),
            $request->input('data_fim')
        );

        $estatisticas   = $this->calcularEstatisticas($produtoIds, Auth::id(), $dataInicioAnalise, $dataFimAnalise);
        $stats30d       = $this->calcularEstatisticas($produtoIds, Auth::id(), now()->subDays(30)->startOfDay(), now()->endOfDay());
        $stats6m        = $this->calcularEstatisticas($produtoIds, Auth::id(), now()->subMonths(6)->startOfDay(), now()->endOfDay());
        $statsHistorico = $this->calcularEstatisticas($produtoIds, Auth::id(), null, null);

        $agrupados = Product::where('canonical_product_id', $produtoExibicao->id)->get();

        $alertaExistente = PriceAlert::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        return view('products.show', compact(
            'product', 'produtoExibicao', 'serie', 'variacao',
            'agrupados', 'alertaExistente',
            'estatisticas', 'stats30d', 'stats6m', 'statsHistorico',
            'periodoAtivo', 'dataInicioAnalise', 'dataFimAnalise'
        ));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'nome_exibicao'       => 'nullable|string|max:255',
            'normalizacao_status' => 'nullable|in:pendente,revisar,aprovado',
            'unidade_padrao'      => 'nullable|string|max:10',
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produto atualizado!');
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

        Cache::forget('contagem-categorias-' . Auth::id());

        return back()->with('success', count($ids) . ' produto(s) categorizado(s)!');
    }

    public function atualizarCategoria(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update(['category_id' => $request->categoria ?: null]);

        Cache::forget('contagem-categorias-' . Auth::id());

        return back()->with('success', 'Categoria atualizada!');
    }

    // ==================== NORMALIZACAO ====================

    public function normalizacao(Request $request, ProductNormalizationService $service): View
    {
        $userId = Auth::id();
        $status = $request->input('status', 'pendente');

        $produtos = Product::where('user_id', $userId)
            ->when($status === 'pendente', function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('normalizacao_status')
                      ->orWhere('normalizacao_status', 'pendente');
                });
            })
            ->when($status === 'revisar',  fn($q) => $q->where('normalizacao_status', 'revisar'))
            ->when($status === 'aprovado', fn($q) => $q->where('normalizacao_status', 'aprovado'))
            ->when($request->filled('search'), fn($q) => $q->where('nome', 'ilike', "%{$request->search}%"))
            ->orderBy('nome')
            ->paginate(50);

        $analises = [];
        foreach ($produtos as $produto) {
            if (! $produto->nome_normalizado) {
                $analises[$produto->id] = $service->analyze($produto);
            }
        }

        return view('products.normalizacao', compact('produtos', 'analises', 'status'));
    }

    public function aprovarNormalizacao(Product $product, Request $request, ProductNormalizationService $service): RedirectResponse
    {
        $this->authorize('update', $product);
        $service->approve($product, $request->input('nome_exibicao'));
        return back()->with('success', 'Produto normalizado: ' . $product->nome_exibicao);
    }

    public function aprovarTodasNormalizacoes(ProductNormalizationService $service): RedirectResponse
    {
        $count = $service->approveAllPending(Auth::id());
        return back()->with('success', "{$count} produtos aprovados automaticamente!");
    }

    // ==================== FOTOS ====================

    public function uploadFoto(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate(['foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048']);

        if ($product->foto) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->foto);
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
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->foto);
        }

        $product->update(['foto' => null]);
        return back()->with('success', 'Foto removida!');
    }

    // ==================== SIMILARES ====================

    public function similares(Product $product): View
    {
        $this->authorize('view', $product);
        $mlService = app(\App\Services\ProductSimilarityService::class);
        $similares = $mlService->encontrarSimilares($product, 10);
        return view('products.similares', compact('product', 'similares'));
    }

    // ==================== ANALISE DE PRECOS ====================

    private function resolverPeriodo(string $periodo, ?string $dataInicio, ?string $dataFim): array
    {
        return match ($periodo) {
            '30d'       => [now()->subDays(30)->startOfDay(),  now()->endOfDay()],
            '6m'        => [now()->subMonths(6)->startOfDay(), now()->endOfDay()],
            'historico' => [null, null],
            'custom'    => [
                $dataInicio ? Carbon::parse($dataInicio)->startOfDay() : now()->subDays(30)->startOfDay(),
                $dataFim    ? Carbon::parse($dataFim)->endOfDay()      : now()->endOfDay(),
            ],
            default     => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
        };
    }

    private function calcularEstatisticas($produtoIds, int $userId, ?Carbon $inicio, ?Carbon $fim): array
    {
        $query = InvoiceItem::join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereIn('invoice_items.product_id', $produtoIds)
            ->where('invoices.user_id', $userId)
            ->where('invoice_items.valor_unitario', '>', 0);

        if ($inicio && $fim) {
            $query->whereBetween('invoices.data_emissao', [$inicio, $fim]);
        }

        $agg = (clone $query)
            ->selectRaw('COUNT(*) AS total, AVG(invoice_items.valor_unitario) AS media, MIN(invoice_items.valor_unitario) AS minimo, MAX(invoice_items.valor_unitario) AS maximo')
            ->first();

        $modaRow = (clone $query)
            ->select('invoice_items.valor_unitario')
            ->selectRaw('COUNT(*) as freq')
            ->groupBy('invoice_items.valor_unitario')
            ->orderByDesc('freq')
            ->first();

        return [
            'total'  => (int)   ($agg->total  ?? 0),
            'media'  => (float) ($agg->media  ?? 0),
            'minimo' => (float) ($agg->minimo ?? 0),
            'maximo' => (float) ($agg->maximo ?? 0),
            'moda'   => $modaRow ? (float) $modaRow->valor_unitario : null,
        ];
    }

    // ==================== HELPERS ====================

    private function contagemPorCategoria(int $userId): array
    {
        return Cache::remember('contagem-categorias-' . $userId, 300, function () use ($userId) {
            $rows = Product::where('user_id', $userId)
                ->selectRaw("CASE WHEN category_id IS NULL THEN 'sem' ELSE CAST(category_id AS TEXT) END AS chave, COUNT(*) AS total")
                ->groupByRaw("CASE WHEN category_id IS NULL THEN 'sem' ELSE CAST(category_id AS TEXT) END")
                ->pluck('total', 'chave')
                ->toArray();

            return array_merge(['sem' => 0], $rows);
        });
    }
}
