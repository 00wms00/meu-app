<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Traits\ParsesFloatInput;
use Carbon\Carbon;
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

        $mesAnteriorData = Carbon::create($ano, $mes, 1)->subMonth();
        $temMesAnterior  = Budget::where('user_id', $userId)
            ->where('ano', $mesAnteriorData->year)
            ->where('mes', $mesAnteriorData->month)
            ->whereHas('budgetCategories')
            ->exists();

        $nomeMesAnterior = $meses[$mesAnteriorData->month];

        return view('budgets.index', compact(
            'budget', 'dadosCategorias', 'categorias', 'meses', 'mes', 'ano',
            'totalOrcado', 'totalGasto', 'budgetCategories',
            'temMesAnterior', 'nomeMesAnterior', 'mesAnteriorData'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();

        if (! empty($input['categorias'])) {
            foreach ($input['categorias'] as $key => $cat) {
                $input['categorias'][$key]['valor_limite'] = $this->parseFloat($cat['valor_limite'] ?? 0);
            }
        }

        $validator = Validator::make($input, [
            'ano'                              => 'required|integer|min:2020|max:2100',
            'mes'                              => 'required|integer|min:1|max:12',
            'categorias'                       => 'nullable|array',
            'categorias.*.category_id'         => 'required|exists:categories,id',
            'categorias.*.valor_limite'        => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // valor_total = soma dos limites das categorias
        $valorTotal = collect($validated['categorias'] ?? [])
            ->sum(fn($cat) => (float) ($cat['valor_limite'] ?? 0));

        $budget = Budget::updateOrCreate(
            ['user_id' => Auth::id(), 'ano' => $validated['ano'], 'mes' => $validated['mes']],
            ['valor_total' => $valorTotal]
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

    public function copiarMesAnterior(Request $request): RedirectResponse
    {
        $request->validate([
            'ano' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        $userId = Auth::id();
        $mes    = (int) $request->mes;
        $ano    = (int) $request->ano;

        $anterior = Carbon::create($ano, $mes, 1)->subMonth();

        $budgetAnterior = Budget::where('user_id', $userId)
            ->where('ano', $anterior->year)
            ->where('mes', $anterior->month)
            ->with('budgetCategories')
            ->first();

        if (! $budgetAnterior) {
            return redirect()
                ->route('budgets.index', compact('mes', 'ano'))
                ->with('warning', 'Nenhum orçamento encontrado para o mês anterior.');
        }

        // valor_total = soma dos limites copiados
        $valorTotal = $budgetAnterior->budgetCategories->sum(fn($c) => (float) $c->valor_limite);

        $budgetAtual = Budget::updateOrCreate(
            ['user_id' => $userId, 'ano' => $ano, 'mes' => $mes],
            ['valor_total' => $valorTotal]
        );

        foreach ($budgetAnterior->budgetCategories as $catAnterior) {
            BudgetCategory::updateOrCreate(
                ['budget_id' => $budgetAtual->id, 'category_id' => $catAnterior->category_id],
                ['valor_limite' => $catAnterior->valor_limite]
            );
        }

        $meses = [
            1 => 'Janeiro',  2 => 'Fevereiro', 3 => 'Março',    4 => 'Abril',
            5 => 'Maio',     6 => 'Junho',     7 => 'Julho',    8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro',  11 => 'Novembro', 12 => 'Dezembro',
        ];

        return redirect()
            ->route('budgets.index', compact('mes', 'ano'))
            ->with('success', "Orçamento copiado de {$meses[$anterior->month]}/{$anterior->year} com sucesso!");
    }
}
