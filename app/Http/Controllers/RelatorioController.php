<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function mensal(Request $request): View
    {
        return $this->gerarRelatorio(
            userId:  Auth::id(),
            tipo:    'mensal',
            mes:     (int) $request->input('mes', now()->month),
            ano:     (int) $request->input('ano', now()->year),
        );
    }

    public function periodo(Request $request): View
    {
        return $this->gerarRelatorio(
            userId:      Auth::id(),
            tipo:        'periodo',
            dataInicio:  $request->input('data_inicio', now()->startOfMonth()->format('Y-m-d')),
            dataFim:     $request->input('data_fim',    now()->format('Y-m-d')),
        );
    }

    // ==================== PRIVATE ====================

    private function gerarRelatorio(
        int     $userId,
        string  $tipo,
        ?int    $mes        = null,
        ?int    $ano        = null,
        ?string $dataInicio = null,
        ?string $dataFim    = null,
    ): View {
        $produtos           = $this->queryProdutos($userId, $tipo, $mes, $ano, $dataInicio, $dataFim);
        $gastosPorCategoria = $this->queryGastosPorCategoria($userId, $tipo, $mes, $ano, $dataInicio, $dataFim);

        $totalGeral = $gastosPorCategoria->sum('gasto_total');
        $gastosPorCategoria->transform(function ($cat) use ($totalGeral) {
            $cat->porcentagem = $totalGeral > 0 ? ($cat->gasto_total / $totalGeral) * 100 : 0;
            if (! $cat->categoria_nome) {
                $cat->categoria_nome  = 'Sem categoria';
                $cat->categoria_emoji = '📦';
                $cat->categoria_cor   = '#9ca3af';
            }
            return $cat;
        });

        $totais = [
            'gasto_total'         => $produtos->sum('gasto_total'),
            'itens_total'         => $produtos->sum('quantidade_total'),
            'produtos_diferentes' => $produtos->count(),
            'num_notas'           => $this->contarNotas($userId, $tipo, $mes, $ano, $dataInicio, $dataFim),
        ];

        $extras = $tipo === 'mensal'
            ? ['mes' => $mes, 'ano' => $ano]
            : ['dataInicio' => $dataInicio, 'dataFim' => $dataFim];

        return view('relatorios.' . $tipo, array_merge(
            compact('produtos', 'totais', 'gastosPorCategoria'),
            ['meses' => $this->arrayMeses(), 'anos' => $this->arrayAnos($userId)],
            ['maiorGasto'    => $produtos->sortByDesc('gasto_total')->first()],
            ['maisComprado'  => $produtos->sortByDesc('quantidade_total')->first()],
            $extras,
        ));
    }

    /**
     * Aplica o filtro de período (mensal ou por datas) em qualquer query de InvoiceItem.
     */
    private function aplicarFiltroPeriodo(
        Builder $query,
        string  $tipo,
        ?int    $mes,
        ?int    $ano,
        ?string $dataInicio,
        ?string $dataFim,
    ): Builder {
        if ($tipo === 'mensal') {
            return $query
                ->whereMonth('invoices.data_emissao', $mes)
                ->whereYear('invoices.data_emissao',  $ano);
        }

        return $query->whereBetween(
            'invoices.data_emissao',
            [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']
        );
    }

    private function queryProdutos(int $userId, string $tipo, ?int $mes, ?int $ano, ?string $dataInicio, ?string $dataFim)
    {
        $query = InvoiceItem::select(
                DB::raw('COALESCE(canonico.id,               products.id)               as produto_id'),
                DB::raw('COALESCE(canonico.nome,             products.nome)             as produto_nome'),
                DB::raw('COALESCE(canonico.unidade_padrao,   products.unidade_padrao)   as unidade'),
                DB::raw('SUM(invoice_items.quantidade)                                  as quantidade_total'),
                DB::raw('COUNT(DISTINCT invoice_items.invoice_id)                       as num_compras'),
                DB::raw('SUM(invoice_items.valor_total)                                 as gasto_total'),
                DB::raw('MIN(invoice_items.valor_unitario)                              as preco_minimo'),
                DB::raw('MAX(invoice_items.valor_unitario)                              as preco_maximo'),
            )
            ->join('products',               'invoice_items.product_id',        '=', 'products.id')
            ->join('invoices',               'invoice_items.invoice_id',         '=', 'invoices.id')
            ->leftJoin('products as canonico', 'products.canonical_product_id', '=', 'canonico.id')
            ->where('invoices.user_id', $userId);

        $this->aplicarFiltroPeriodo($query, $tipo, $mes, $ano, $dataInicio, $dataFim);

        return $query
            ->groupBy(
                DB::raw('COALESCE(canonico.id,             products.id)'),
                DB::raw('COALESCE(canonico.nome,           products.nome)'),
                DB::raw('COALESCE(canonico.unidade_padrao, products.unidade_padrao)'),
            )
            ->orderByDesc('gasto_total')
            ->get()
            ->transform(function ($produto) {
                $produto->preco_medio = $produto->quantidade_total > 0
                    ? $produto->gasto_total / $produto->quantidade_total
                    : 0;
                return $produto;
            });
    }

    private function queryGastosPorCategoria(int $userId, string $tipo, ?int $mes, ?int $ano, ?string $dataInicio, ?string $dataFim)
    {
        $query = InvoiceItem::select(
                'categories.id   as categoria_id',
                'categories.nome as categoria_nome',
                'categories.emoji as categoria_emoji',
                'categories.cor  as categoria_cor',
                DB::raw('SUM(invoice_items.valor_total)           as gasto_total'),
                DB::raw('COUNT(DISTINCT invoice_items.invoice_id) as num_compras'),
            )
            ->join('products',               'invoice_items.product_id',        '=', 'products.id')
            ->join('invoices',               'invoice_items.invoice_id',         '=', 'invoices.id')
            ->leftJoin('products as canonico', 'products.canonical_product_id', '=', 'canonico.id')
            ->leftJoin('categories', fn ($join) =>
                $join->on(DB::raw('COALESCE(canonico.category_id, products.category_id)'), '=', 'categories.id')
            )
            ->where('invoices.user_id', $userId);

        $this->aplicarFiltroPeriodo($query, $tipo, $mes, $ano, $dataInicio, $dataFim);

        return $query
            ->groupBy('categories.id', 'categories.nome', 'categories.emoji', 'categories.cor')
            ->orderByDesc('gasto_total')
            ->get();
    }

    private function contarNotas(int $userId, string $tipo, ?int $mes, ?int $ano, ?string $dataInicio, ?string $dataFim): int
    {
        return Invoice::where('user_id', $userId)
            ->when($tipo === 'mensal', fn ($q) =>
                $q->whereMonth('data_emissao', $mes)->whereYear('data_emissao', $ano)
            )
            ->when($tipo === 'periodo', fn ($q) =>
                $q->whereBetween('data_emissao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            )
            ->count();
    }

    private function arrayMeses(): array
    {
        return [
            1  => 'Janeiro',   2  => 'Fevereiro', 3  => 'Março',
            4  => 'Abril',     5  => 'Maio',      6  => 'Junho',
            7  => 'Julho',     8  => 'Agosto',    9  => 'Setembro',
            10 => 'Outubro',   11 => 'Novembro',  12 => 'Dezembro',
        ];
    }

    private function arrayAnos(int $userId): array
    {
        $anos = Invoice::where('user_id', $userId)
            ->selectRaw('EXTRACT(YEAR FROM data_emissao) as ano')
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->toArray();

        return empty($anos) ? [now()->year] : $anos;
    }
}
