<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Traits\ParsesFloatInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class BudgetController extends Controller
{
    use ParsesFloatInput;

    public function index(Request $request): View
    {
        $userId = Auth::id();
        $mes    = $request->integer('mes', now()->month);
        $ano    = $request->integer('ano', now()->year);

        $budget = Budget::firstOrCreate(
            ['user_id' => $userId, 'ano' => $ano, 'mes' => $mes],
            ['valor_total' => 0]
        );

        $budgetCategories = BudgetCategory::where('budget_id', $budget->id)
            ->with('category')
            ->get();

        $gastosPorCategoria = InvoiceItem::select(
                'categories.id as categoria_id',
                'categories.nome as categoria_nome',
                'categories.emoji as categoria_emoji',
                'categories.cor as categoria_cor',
                DB::raw('SUM(invoice_items.valor_total) as gasto_total')
            )
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('products as canonico', 'products.canonical_product_id', '=', 'canonico.id')
            ->leftJoin('categories', function ($join) {
                $join->on(DB::raw('COALESCE(canonico.category_id, products.category_id)'), '=', 'categories.id');
            })
            ->where('invoices.user_id', $userId)
            ->whereMonth('invoices.data_emissao', $mes)
            ->whereYear('invoices.data_emissao', $ano)
            ->groupBy('categories.id', 'categories.nome', 'categories.emoji', 'categories.cor')
            ->get()
            ->keyBy('categoria_id');

        $categorias      = Category::where('user_id', $userId)->ordenado()->get();
        $dadosCategorias = [];
        $totalOrcado     = 0;
        $totalGasto      = 0;

        foreach ($categorias as $cat) {
            $orcamento  = $budgetCategories->firstWhere('category_id', $cat->id);
            $gasto      = $gastosPorCategoria->get($cat->id);
            $limite     = $orcamento ? (float) $orcamento->valor_limite : 0;
            $gastoValor = $gasto ? (float) $gasto->gasto_total : 0;
            $porcentagem = $limite > 0 ? min(100, ($gastoValor / $limite) * 100) : 0;

            $totalOrcado += $limite;
            $totalGasto  += $gastoValor;

            $dadosCategorias[] = [
                'category_id' => $cat->id,
                'nome'        => $cat->nome,
                'emoji'       => $cat->emoji,
                'cor'         => $cat->cor,
                'limite'      => $limite,
                'gasto'       => $gastoValor,
                'porcentagem' => $porcentagem,
                'status'      => $limite > 0
                    ? ($gastoValor > $limite ? 'excedido' : ($porcentagem >= 80 ? 'alerta' : 'ok'))
                    : 'sem_orcamento',
            ];
        }

        usort($dadosCategorias, function ($a, $b) {
            if ($a['status'] === 'excedido' && $b['status'] !== 'excedido') return -1;
            if ($b['status'] === 'excedido' && $a['status'] !== 'excedido') return 1;
            return $b['porcentagem'] <=> $a['porcentagem'];
        });

        $meses = [
            1 => 'Janeiro',  2 => 'Fevereiro', 3 => 'Março',    4 => 'Abril',
            5 => 'Maio',     6 => 'Junho',     7 => 'Julho',    8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro',  11 => 'Novembro', 12 => 'Dezembro',
        ];

        return view('budgets.index', compact(
            'budget', 'dadosCategorias', 'categorias', 'meses', 'mes', 'ano',
            'totalOrcado', 'totalGasto', 'budgetCategories'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        // Converte vírgulas para pontos antes da validação
        $input = $request->all();

        if (! empty($input['valor_total'])) {
            $input['valor_total'] = $this->parseFloat($input['valor_total']);
        }

        if (! empty($input['categorias'])) {
            foreach ($input['categorias'] as $key => $cat) {
                $input['categorias'][$key]['valor_limite'] = $this->parseFloat($cat['valor_limite'] ?? 0);
            }
        }

        $validator = Validator::make($input, [
            'ano'                              => 'required|integer|min:2020|max:2100',
            'mes'                              => 'required|integer|min:1|max:12',
            'valor_total'                      => 'nullable|numeric|min:0',
            'categorias'                       => 'nullable|array',
            'categorias.*.category_id'         => 'required|exists:categories,id',
            'categorias.*.valor_limite'        => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $budget = Budget::updateOrCreate(
            ['user_id' => Auth::id(), 'ano' => $validated['ano'], 'mes' => $validated['mes']],
            ['valor_total' => $validated['valor_total'] ?? 0]
        );

        foreach ($validated['categorias'] ?? [] as $cat) {
            BudgetCategory::updateOrCreate(
                ['budget_id' => $budget->id, 'category_id' => $cat['category_id']],
                ['valor_limite' => $cat['valor_limite'] ?? 0]
            );
        }

        return redirect()
            ->route('budgets.index', ['mes' => $validated['mes'], 'ano' => $validated['ano']])
            ->with('success', 'Orçamento salvo com sucesso!');
    }
}
