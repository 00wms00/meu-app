<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShoppingPlanningService
{
    /**
     * Analisa o ciclo de consumo de cada produto e retorna status de reposição.
     * Usa 3 queries fixas independente da quantidade de produtos.
     */
    public function analisarCicloConsumo(int $userId): array
    {
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

        $todasDatas = InvoiceItem::select('invoice_items.product_id', 'invoices.data_emissao')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->whereIn('invoice_items.product_id', $registros->keys())
            ->orderBy('invoices.data_emissao')
            ->get()
            ->groupBy('product_id');

        $produtos = Product::where('user_id', $userId)
            ->whereIn('id', $registros->keys())
            ->get()
            ->keyBy('id');

        $ciclos = [];

        foreach ($registros as $productId => $reg) {
            $produto = $produtos->get($productId);
            if (! $produto) continue;

            $datas = $todasDatas->get($productId, collect())
                ->pluck('data_emissao')
                ->map(fn($d) => Carbon::parse($d))
                ->values();

            if ($datas->count() < 2) continue;

            $intervalos = [];
            for ($i = 1; $i < $datas->count(); $i++) {
                $intervalos[] = $datas[$i - 1]->diffInDays($datas[$i]);
            }

            $intervaloMedio  = (int) round(array_sum($intervalos) / count($intervalos));
            $ultimaCompra    = Carbon::parse($reg->ultima_compra);
            $diasDesdeUltima = (int) now()->diffInDays($ultimaCompra);
            $diasAteProxima  = max(0, $intervaloMedio - $diasDesdeUltima);

            $status = match (true) {
                $diasDesdeUltima > $intervaloMedio * 1.3 => 'urgente',
                $diasDesdeUltima > $intervaloMedio * 0.8 => 'atencao',
                default                                  => 'ok',
            };

            $ciclos[] = [
                'produto'           => $produto,
                'intervalo_medio'   => $intervaloMedio,
                'ultima_compra'     => $ultimaCompra->format('d/m/Y'),
                'dias_desde_ultima' => $diasDesdeUltima,
                'dias_ate_proxima'  => $diasAteProxima,
                'status'            => $status,
                'quantidade_media'  => round((float) $reg->quantidade_media, 2),
                'unidade'           => $produto->unidade_padrao ?? 'UN',
                'preco_estimado'    => round((float) $reg->preco_medio, 2),
            ];
        }

        usort($ciclos, function ($a, $b) {
            $ordem = ['urgente' => 0, 'atencao' => 1, 'ok' => 2];
            return $ordem[$a['status']] !== $ordem[$b['status']]
                ? $ordem[$a['status']] <=> $ordem[$b['status']]
                : $a['dias_ate_proxima'] <=> $b['dias_ate_proxima'];
        });

        return $ciclos;
    }

    /**
     * Calcula a economia potencial comprando cada produto no estabelecimento mais barato.
     */
    public function calcularEconomiaPotencial(int $userId): array
    {
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
            ->groupBy('invoice_items.product_id', 'products.nome', 'invoices.nome_estabelecimento')
            ->having(DB::raw('COUNT(DISTINCT invoices.id)'), '>=', 2)
            ->get()
            ->groupBy('product_id');

        $economias = [];

        foreach ($rows as $grupo) {
            if ($grupo->count() < 2) continue;

            $sorted   = $grupo->sortBy('preco_medio');
            $barato   = $sorted->first();
            $caro     = $sorted->last();
            $diferenca = round((float) $caro->preco_medio - (float) $barato->preco_medio, 2);

            if ($diferenca <= 0) continue;

            $economias[] = [
                'produto'              => $barato->produto_nome,
                'mais_barato'          => $barato->nome_estabelecimento,
                'preco_barato'         => round((float) $barato->preco_medio, 2),
                'mais_caro'            => $caro->nome_estabelecimento,
                'preco_caro'           => round((float) $caro->preco_medio, 2),
                'diferenca'            => $diferenca,
                'diferenca_percentual' => round(($diferenca / (float) $barato->preco_medio) * 100, 1),
            ];
        }

        usort($economias, fn($a, $b) => $b['diferenca'] <=> $a['diferenca']);

        return array_slice($economias, 0, 10);
    }

    /**
     * Analisa tendências de gasto do mês atual vs. anterior.
     */
    public function analisarTendencias(int $userId): array
    {
        $gastoAtual = Invoice::where('user_id', $userId)
            ->whereMonth('data_emissao', now()->month)
            ->whereYear('data_emissao', now()->year)
            ->sum('valor_pago');

        $mesAnterior  = now()->subMonth();
        $gastoAnterior = Invoice::where('user_id', $userId)
            ->whereMonth('data_emissao', $mesAnterior->month)
            ->whereYear('data_emissao', $mesAnterior->year)
            ->sum('valor_pago');

        $variacao      = $gastoAnterior > 0 ? (($gastoAtual - $gastoAnterior) / $gastoAnterior) * 100 : 0;
        $diasMes       = now()->day;
        $mediaDiaria   = $diasMes > 0 ? $gastoAtual / $diasMes : 0;
        $diasRestantes = now()->daysInMonth - now()->day;

        return [
            'gasto_atual'    => $gastoAtual,
            'gasto_anterior' => $gastoAnterior,
            'variacao'       => round($variacao, 1),
            'media_diaria'   => round($mediaDiaria, 2),
            'projecao'       => round($gastoAtual + ($mediaDiaria * $diasRestantes), 2),
            'dias_restantes' => $diasRestantes,
        ];
    }

    /**
     * Retorna distribuição de compras por dia da semana para cada categoria.
     */
    public function analisarComprasPorDia(int $userId): array
    {
        $categorias = Category::where('user_id', $userId)->ordenado()->get();
        $dados = [];

        foreach ($categorias as $cat) {
            $compras = InvoiceItem::whereHas('invoice', fn($q) => $q->where('user_id', $userId))
                ->whereHas('product', fn($q) => $q->where('category_id', $cat->id))
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->selectRaw("EXTRACT(DOW FROM invoices.data_emissao) as dia, COUNT(DISTINCT invoices.id) as total")
                ->groupBy('dia')
                ->orderBy('total', 'desc')
                ->get();

            if ($compras->count() > 0) {
                $dados[$cat->id] = [
                    'categoria'         => $cat,
                    'dias'              => $compras->pluck('total', 'dia')->toArray(),
                    'dia_mais_frequente' => (int) $compras->first()->dia,
                ];
            }
        }

        return $dados;
    }

    /**
     * Retorna os 3 principais estabelecimentos por categoria.
     */
    public function analisarEstabelecimentosPorCategoria(int $userId): array
    {
        $categorias = Category::where('user_id', $userId)->ordenado()->get();
        $dados = [];

        foreach ($categorias as $cat) {
            $estabs = InvoiceItem::whereHas('invoice', fn($q) => $q->where('user_id', $userId))
                ->whereHas('product', fn($q) => $q->where('category_id', $cat->id))
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->select('invoices.nome_estabelecimento', DB::raw('COUNT(DISTINCT invoices.id) as total'))
                ->groupBy('invoices.nome_estabelecimento')
                ->orderBy('total', 'desc')
                ->take(3)
                ->get();

            if ($estabs->count() > 0) {
                $dados[$cat->id] = $estabs->toArray();
            }
        }

        return $dados;
    }

    /**
     * Retorna top 10 produtos por categoria com preço médio.
     */
    public function getProdutosFrequentesPorCategoria(int $userId): array
    {
        $precosMedios = InvoiceItem::select(
                'invoice_items.product_id',
                DB::raw('AVG(invoice_items.valor_unitario) as preco_medio')
            )
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $userId)
            ->groupBy('invoice_items.product_id')
            ->pluck('preco_medio', 'product_id');

        return Product::where('user_id', $userId)
            ->whereNotNull('category_id')
            ->withCount('invoiceItems')
            ->orderBy('invoice_items_count', 'desc')
            ->get()
            ->each(function ($p) use ($precosMedios) {
                $p->preco_medio = $precosMedios->has($p->id)
                    ? round((float) $precosMedios[$p->id], 2)
                    : null;
            })
            ->groupBy('category_id')
            ->map(fn($grupo) => $grupo->take(10))
            ->all();
    }

    /**
     * Sugere se está na hora de fazer a compra mensal grande.
     */
    public function sugerirCompraMensal(int $userId): array
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

    /**
     * Gera sugestões de próximo dia de compra para cada categoria.
     */
    public function gerarSugestoesDias(array $categoriasPorDia): array
    {
        $diasSemana = [0 => 'Domingo', 1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];
        $sugestoes  = [];

        foreach ($categoriasPorDia as $dados) {
            $dia = $dados['dia_mais_frequente'] ?? null;
            if ($dia === null) continue;

            $proximo  = $this->proximoDiaSemana((int) $dia);
            $diasAte  = max(0, (int) now()->startOfDay()->diffInDays($proximo->startOfDay()));

            $sugestoes[] = [
                'categoria'   => $dados['categoria'],
                'dia_nome'    => $diasSemana[(int) $dia],
                'proxima_data' => $proximo->format('d/m/Y'),
                'dias_ate'    => $diasAte,
            ];
        }

        usort($sugestoes, fn($a, $b) => $a['dias_ate'] <=> $b['dias_ate']);

        return $sugestoes;
    }

    private function proximoDiaSemana(int $dia): \Carbon\Carbon
    {
        $hoje     = now()->startOfDay();
        $diaAtual = (int) $hoje->format('w');

        if ($diaAtual === $dia) return $hoje;

        return $diaAtual < $dia
            ? $hoje->copy()->addDays($dia - $diaAtual)
            : $hoje->copy()->addDays(7 - $diaAtual + $dia);
    }
}
