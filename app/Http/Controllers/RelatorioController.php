<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    public function mensal(Request $request)
    {
        $userId = Auth::id();
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);
        
        return $this->gerarRelatorio($userId, $mes, $ano, null, null, 'mensal');
    }

    public function periodo(Request $request)
    {
        $userId = Auth::id();
        
        $dataInicio = $request->input('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->input('data_fim', now()->format('Y-m-d'));
        
        return $this->gerarRelatorio($userId, null, null, $dataInicio, $dataFim, 'periodo');
    }

    private function gerarRelatorio(int $userId, ?int $mes, ?int $ano, ?string $dataInicio, ?string $dataFim, string $tipo)
    {
        // Query base para filtrar por período
        $query = InvoiceItem::select(
                DB::raw('COALESCE(canonico.id, products.id) as produto_id'),
                DB::raw('COALESCE(canonico.nome, products.nome) as produto_nome'),
                DB::raw('COALESCE(canonico.unidade_padrao, products.unidade_padrao) as unidade'),
                DB::raw('SUM(invoice_items.quantidade) as quantidade_total'),
                DB::raw('COUNT(DISTINCT invoice_items.invoice_id) as num_compras'),
                DB::raw('SUM(invoice_items.valor_total) as gasto_total'),
                DB::raw('MIN(invoice_items.valor_unitario) as preco_minimo'),
                DB::raw('MAX(invoice_items.valor_unitario) as preco_maximo')
            )
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('products as canonico', 'products.canonical_product_id', '=', 'canonico.id')
            ->where('invoices.user_id', $userId);

        // Aplicar filtro de período
        if ($tipo === 'mensal') {
            $query->whereMonth('invoices.data_emissao', $mes)
                  ->whereYear('invoices.data_emissao', $ano);
        } else {
            $query->whereBetween('invoices.data_emissao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
        }

        $produtos = $query->groupBy(
                DB::raw('COALESCE(canonico.id, products.id)'),
                DB::raw('COALESCE(canonico.nome, products.nome)'),
                DB::raw('COALESCE(canonico.unidade_padrao, products.unidade_padrao)')
            )
            ->orderByDesc('gasto_total')
            ->get();

        $produtos->transform(function ($produto) {
            $produto->preco_medio = $produto->quantidade_total > 0 
                ? $produto->gasto_total / $produto->quantidade_total 
                : 0;
            return $produto;
        });

        // Gastos por categoria
        $queryCat = InvoiceItem::select(
                'categories.id as categoria_id',
                'categories.nome as categoria_nome',
                'categories.emoji as categoria_emoji',
                'categories.cor as categoria_cor',
                DB::raw('SUM(invoice_items.valor_total) as gasto_total'),
                DB::raw('COUNT(DISTINCT invoice_items.invoice_id) as num_compras')
            )
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('products as canonico', 'products.canonical_product_id', '=', 'canonico.id')
            ->leftJoin('categories', function($join) {
                $join->on(DB::raw('COALESCE(canonico.category_id, products.category_id)'), '=', 'categories.id');
            })
            ->where('invoices.user_id', $userId);

        if ($tipo === 'mensal') {
            $queryCat->whereMonth('invoices.data_emissao', $mes)
                     ->whereYear('invoices.data_emissao', $ano);
        } else {
            $queryCat->whereBetween('invoices.data_emissao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
        }

        $gastosPorCategoria = $queryCat->groupBy('categories.id', 'categories.nome', 'categories.emoji', 'categories.cor')
            ->orderByDesc('gasto_total')
            ->get();

        $totalGeral = $gastosPorCategoria->sum('gasto_total');
        $gastosPorCategoria->transform(function ($cat) use ($totalGeral) {
            $cat->porcentagem = $totalGeral > 0 ? ($cat->gasto_total / $totalGeral) * 100 : 0;
            if (!$cat->categoria_nome) {
                $cat->categoria_nome = 'Sem categoria';
                $cat->categoria_emoji = '📦';
                $cat->categoria_cor = '#9ca3af';
            }
            return $cat;
        });

        // Totais
        $totais = [
            'gasto_total' => $produtos->sum('gasto_total'),
            'itens_total' => $produtos->sum('quantidade_total'),
            'produtos_diferentes' => $produtos->count(),
            'num_notas' => Invoice::where('user_id', $userId)
                ->when($tipo === 'mensal', function($q) use ($mes, $ano) {
                    $q->whereMonth('data_emissao', $mes)->whereYear('data_emissao', $ano);
                })
                ->when($tipo === 'periodo', function($q) use ($dataInicio, $dataFim) {
                    $q->whereBetween('data_emissao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
                })
                ->count(),
        ];

        // Dados para os filtros
        $meses = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
                  7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
        
        $anos = Invoice::where('user_id', $userId)
            ->selectRaw('EXTRACT(YEAR FROM data_emissao) as ano')
            ->distinct()->orderBy('ano', 'desc')->pluck('ano')->toArray();
        if (empty($anos)) $anos = [now()->year];

        // Maior e menor preço por produto (para destaque)
        $maiorGasto = $produtos->sortByDesc('gasto_total')->first();
        $maisComprado = $produtos->sortByDesc('quantidade_total')->first();

        return view('relatorios.' . $tipo, compact(
            'produtos', 'totais', 'meses', 'anos', 'gastosPorCategoria',
            'maiorGasto', 'maisComprado'
        ) + ($tipo === 'mensal' ? ['mes' => $mes, 'ano' => $ano] : ['dataInicio' => $dataInicio, 'dataFim' => $dataFim]));
    }
}
