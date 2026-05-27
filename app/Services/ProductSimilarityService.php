<?php

namespace App\Services;

use App\Models\Product;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSimilarityService
{
    // ==================== CACHE POR REQUISIÇÃO ====================
    // Preenchido por prepararCache() antes de qualquer loop de comparação.
    // Evita N×N queries para preço, frequência e estabelecimento.

    private array $cachePrecos       = [];  // product_id => float
    private array $cacheFrequencia   = [];  // product_id => int
    private array $cacheEstabelecimentos = []; // product_id => string[]
    private bool  $cachePreparado    = false;

    // Stop words otimizadas — inclui ruídos comuns de cupom fiscal
    private array $stopWords = [
        'kg', 'g', 'gr', 'l', 'ml', 'un', 'und', 'cx', 'pc', 'pct', 'fd', 'lt', 'dz',
        'de', 'da', 'do', 'das', 'dos', 'e', 'a', 'o', 'com', 'sem',
        'para', 'por', 'em', 'no', 'na', 'os', 'as', 'um', 'uma',
        'sache', 'saco', 'pacote', 'lata', 'garrafa', 'fardo', 'bandeja',
        'po', 'sab', 'lav', 'roupas', 'pet', 'bdj', 'und', 'maca',
        'trad', 'almof', 'mini', 'tixan', 'ype', 'omo', 'cola',
        'tipo', 'marca', 'qualidade', 'original', 'tamanho', 'grande', 'pequeno',
        'medio', 'media', 'pack', 'kit', 'lev', 'cada', 'unidade',
        'lv', 'pague', 'leve', 'pg', 'promo', 'promocao', 'oferta',
        'kgf', 'kgfr', 'emb',
    ];

    private array $sinonimos = [
        'refrigerante' => ['refri', 'refrigerante', 'coca', 'cola', 'pepsi', 'guarana'],
        'arroz'        => ['arroz', 'arrozagulinha', 'agulinha', 'arrozparboilizado', 'parboilizado'],
        'feijao'       => ['feijao', 'feijaocarioca', 'carioca', 'feijaopreto'],
        'leite'        => ['leite', 'leit', 'liquido', 'po'],
        'carne'        => ['carne', 'bovina', 'bovino', 'moida', 'moido', 'contrafile', 'picanha', 'alcatra'],
        'frango'       => ['frango', 'frang', 'sobrecoxa', 'coxinha', 'peito', 'file'],
        'macarrao'     => ['macarrao', 'espaguete', 'penne', 'fusilli', 'parafuso'],
        'sabao'        => ['sabao', 'detergente', 'saponaceo'],
        'amaciante'    => ['amaciante', 'amac', 'confort', 'downy', 'ype'],
        'agua'         => ['agua', 'mineral', 'copo', 'garrafa', 'galao'],
        'cerveja'      => ['cerveja', 'cervej', 'chopp', 'lata', 'longneck', 'garrafa'],
        'pao'          => ['pao', 'paozinho', 'frances', 'integral', 'forma'],
        'queijo'       => ['queijo', 'queij', 'mussarela', 'mozarela', 'prato', 'cheddar', 'coalho'],
        'presunto'     => ['presunto', 'presunt', 'apresuntado', 'mortadela'],
    ];

    private array $weights = [
        'tfidf'           => 0.40,
        'categoria'       => 0.20,
        'unidade'         => 0.08,
        'marca'           => 0.10,
        'embalagem'       => 0.10,
        'faixa_preco'     => 0.10,
        'frequencia'      => 0.05,
        'estabelecimento' => 0.02,
    ];

    private array $tiposEmbalagem = [
        'pet', 'lata', 'latinha', 'longneck', 'garrafa', 'sache', 'saco',
        'pacote', 'caixa', 'bandeja', 'pote', 'frasco', 'vidro', 'latao',
        'cartucho', 'refil', 'spray', 'aerosol', 'bombona', 'galao',
    ];

    private array $marcas = [
        'coca', 'cola', 'pepsi', 'guarana', 'fanta', 'sprite', 'kuat',
        'omo', 'ype', 'tixan', 'confort', 'downy', 'veja', 'brilhante',
        'nestle', 'parmalat', 'piracanjuba', 'italac', 'elege', 'batavo',
        'sadia', 'perdigao', 'friboi', 'swift', 'aurora',
        'unilever', 'procter', 'colgate', 'closeup', 'oralb',
        'bombril', 'assim', 'scotch', '3m',
    ];

    // ==================== CACHE ====================

    /**
     * Pré-carrega preços médios, frequências e estabelecimentos de TODOS os
     * produtos do usuário em 3 queries únicas antes de qualquer loop.
     * Isso reduz O(N²) queries para O(1) queries.
     */
    private function prepararCache(int $userId): void
    {
        if ($this->cachePreparado) {
            return;
        }

        $productIds = Product::where('user_id', $userId)->pluck('id')->toArray();

        if (empty($productIds)) {
            $this->cachePreparado = true;
            return;
        }

        // 1. Preço médio dos últimos 5 itens por produto (subquery com ROW_NUMBER)
        $precos = DB::table('invoice_items as ii')
            ->select('ii.product_id', DB::raw('AVG(ii.valor_unitario) as preco_medio'))
            ->whereIn('ii.product_id', $productIds)
            ->join(
                DB::raw('(
                    SELECT id, product_id,
                           ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY created_at DESC) as rn
                    FROM invoice_items
                    WHERE product_id IN (' . implode(',', $productIds) . ')
                ) as ranked'),
                function ($join) {
                    $join->on('ii.id', '=', 'ranked.id')->where('ranked.rn', '<=', 5);
                }
            )
            ->groupBy('ii.product_id')
            ->pluck('preco_medio', 'product_id')
            ->toArray();

        foreach ($productIds as $id) {
            $this->cachePrecos[$id] = isset($precos[$id]) ? (float) $precos[$id] : 0.0;
        }

        // 2. Frequência (contagem de invoice_items) por produto — 1 query
        $frequencias = InvoiceItem::whereIn('product_id', $productIds)
            ->select('product_id', DB::raw('COUNT(*) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id')
            ->toArray();

        foreach ($productIds as $id) {
            $this->cacheFrequencia[$id] = (int) ($frequencias[$id] ?? 0);
        }

        // 3. Estabelecimentos distintos por produto — 1 query
        $estabRows = InvoiceItem::whereIn('invoice_items.product_id', $productIds)
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereNotNull('invoices.nome_estabelecimento')
            ->select('invoice_items.product_id', 'invoices.nome_estabelecimento')
            ->distinct()
            ->get();

        foreach ($estabRows as $row) {
            $this->cacheEstabelecimentos[$row->product_id][] = $row->nome_estabelecimento;
        }

        $this->cachePreparado = true;
    }

    // ==================== NORMALIZAÇÃO ====================

    public function normalizarNome(string $nome): string
    {
        $nome = $this->removerAcentos(Str::lower(trim($nome)));
        $nome = preg_replace('/\b\d+[.,]?\d*\s*(kg|g|gr|l|ml|un|und|cx|pc|pct|lt|dz|x)\b/i', '', $nome);
        $nome = preg_replace('/\bc\/\d+\b/i', '', $nome);
        $nome = preg_replace('/[^a-z0-9\s]/', ' ', $nome);
        return trim(preg_replace('/\s+/', ' ', $nome));
    }

    // ==================== MÉTODO PRINCIPAL ====================

    public function encontrarSimilares(Product $produto, int $limit = 5): array
    {
        $userId = $produto->user_id;

        // Garante cache antes de qualquer comparação
        $this->prepararCache($userId);

        $todosProdutos = Product::where('user_id', $userId)
            ->where('id', '!=', $produto->id)
            ->with('category')
            ->get();

        if ($todosProdutos->isEmpty()) {
            return [];
        }

        $nomeNorm1 = $this->normalizarNome($produto->nome);
        $similares = [];

        foreach ($todosProdutos as $p) {
            $nomeNorm2 = $this->normalizarNome($p->nome);
            $score     = $this->calcularScoreCompleto($produto, $p, $nomeNorm1, $nomeNorm2);

            if ($score < 0.25) {
                continue;
            }

            $similares[] = [
                'product'      => $p,
                'similaridade' => round(min($score, 1) * 100, 1),
                'match'        => $score > 0.70 ? 'Alta' : ($score > 0.45 ? 'Média' : 'Baixa'),
                'detalhes'     => $this->explicarSimilaridade($produto, $p, $nomeNorm1, $nomeNorm2),
            ];
        }

        usort($similares, fn($a, $b) => $b['similaridade'] <=> $a['similaridade']);
        return array_slice($similares, 0, $limit);
    }

    // ==================== SCORE COMPLETO ====================

    private function calcularScoreCompleto(Product $p1, Product $p2, string $nomeNorm1 = '', string $nomeNorm2 = ''): float
    {
        $n1 = $nomeNorm1 ?: $this->normalizarNome($p1->nome);
        $n2 = $nomeNorm2 ?: $this->normalizarNome($p2->nome);

        $scores = [
            'tfidf'           => $this->similaridadeTextual($n1, $n2),
            'categoria'       => $this->mesmaCategoria($p1, $p2),
            'unidade'         => $this->mesmaUnidade($p1, $p2),
            'marca'           => $this->mesmaMarca($n1, $n2),
            'embalagem'       => $this->mesmoTipoEmbalagem($n1, $n2),
            'faixa_preco'     => $this->faixaPrecoSimilarCache($p1->id, $p2->id),
            'frequencia'      => $this->frequenciaSimilarCache($p1->id, $p2->id),
            'estabelecimento' => $this->mesmoEstabelecimentoCache($p1->id, $p2->id),
        ];

        $scoreFinal = 0;
        foreach ($scores as $key => $value) {
            $scoreFinal += $value * $this->weights[$key];
        }

        // Penalização suavizada: categoria E unidade divergem — reduz 30%
        if ($scores['categoria'] === 0.0 && $scores['unidade'] === 0.0) {
            $scoreFinal *= 0.70;
        }

        // Penalização suavizada: preço sem sobreposição + texto fraco — reduz 50%
        if ($scores['faixa_preco'] === 0.0 && $scores['tfidf'] < 0.50) {
            $scoreFinal *= 0.50;
        }

        return $scoreFinal;
    }

    // ==================== COMPONENTES DO SCORE ====================

    private function similaridadeTextual(string $nomeNorm1, string $nomeNorm2): float
    {
        $tokens1 = $this->tokenizarAvancado($nomeNorm1, preNormalizado: true);
        $tokens2 = $this->tokenizarAvancado($nomeNorm2, preNormalizado: true);

        if (empty($tokens1) || empty($tokens2)) {
            return 0;
        }

        $tf1         = array_count_values($tokens1);
        $tf2         = array_count_values($tokens2);
        $todosTermos = array_unique(array_merge(array_keys($tf1), array_keys($tf2)));

        $dotProduct = $norm1 = $norm2 = 0;

        foreach ($todosTermos as $termo) {
            $v1          = ($tf1[$termo] ?? 0) / max(count($tokens1), 1);
            $v2          = ($tf2[$termo] ?? 0) / max(count($tokens2), 1);
            $dotProduct += $v1 * $v2;
            $norm1      += $v1 * $v1;
            $norm2      += $v2 * $v2;
        }

        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }

        $cosseno        = $dotProduct / (sqrt($norm1) * sqrt($norm2));
        $bonusSinonimos = $this->bonusSinonimos($tokens1, $tokens2);

        return ($cosseno * 0.8) + ($bonusSinonimos * 0.2);
    }

    private function mesmaCategoria(Product $p1, Product $p2): float
    {
        return ($p1->category_id && $p2->category_id && $p1->category_id === $p2->category_id) ? 1.0 : 0.0;
    }

    private function mesmaUnidade(Product $p1, Product $p2): float
    {
        return ($p1->unidade_padrao && $p2->unidade_padrao
            && strtoupper($p1->unidade_padrao) === strtoupper($p2->unidade_padrao)) ? 1.0 : 0.0;
    }

    private function mesmaMarca(string $nomeNorm1, string $nomeNorm2): float
    {
        $marcas1    = $this->detectarMarcas($nomeNorm1);
        $marcas2    = $this->detectarMarcas($nomeNorm2);
        $intersecao = array_intersect($marcas1, $marcas2);
        return (empty($marcas1) || empty($marcas2) || empty($intersecao)) ? 0.0 : 1.0;
    }

    private function mesmoTipoEmbalagem(string $nomeNorm1, string $nomeNorm2): float
    {
        $emb1 = $this->detectarEmbalagem($nomeNorm1);
        $emb2 = $this->detectarEmbalagem($nomeNorm2);
        return (empty($emb1) || empty($emb2)) ? 0.0 : ($emb1 === $emb2 ? 1.0 : 0.0);
    }

    /** Usa cache — sem query */
    private function faixaPrecoSimilarCache(int $id1, int $id2): float
    {
        $preco1 = $this->cachePrecos[$id1] ?? 0.0;
        $preco2 = $this->cachePrecos[$id2] ?? 0.0;

        if ($preco1 <= 0 || $preco2 <= 0) {
            return 0.0;
        }

        $diferenca = abs($preco1 - $preco2) / max($preco1, $preco2);

        if ($diferenca <= 0.10) return 1.0;
        if ($diferenca <= 0.25) return 0.7;
        if ($diferenca <= 0.50) return 0.3;
        return 0.0;
    }

    /** Usa cache — sem query */
    private function frequenciaSimilarCache(int $id1, int $id2): float
    {
        $freq1 = $this->cacheFrequencia[$id1] ?? 0;
        $freq2 = $this->cacheFrequencia[$id2] ?? 0;

        if ($freq1 == 0 || $freq2 == 0) {
            return 0.0;
        }

        return min($freq1, $freq2) / max($freq1, $freq2);
    }

    /** Usa cache — sem query */
    private function mesmoEstabelecimentoCache(int $id1, int $id2): float
    {
        $estabs1 = $this->cacheEstabelecimentos[$id1] ?? [];
        $estabs2 = $this->cacheEstabelecimentos[$id2] ?? [];

        return (!empty(array_intersect($estabs1, $estabs2))) ? 1.0 : 0.0;
    }

    // ==================== DETECÇÃO ====================

    private function detectarMarcas(string $nomeNorm): array
    {
        $tokens = array_filter(explode(' ', $nomeNorm), fn($t) => strlen($t) > 1);
        return array_values(array_intersect($tokens, $this->marcas));
    }

    private function detectarEmbalagem(string $nomeNorm): ?string
    {
        $tokens = array_filter(explode(' ', $nomeNorm), fn($t) => strlen($t) > 1);
        foreach ($this->tiposEmbalagem as $emb) {
            if (in_array($emb, $tokens)) return $emb;
        }
        return null;
    }

    private function bonusSinonimos(array $tokens1, array $tokens2): float
    {
        foreach ($this->sinonimos as $grupo) {
            $e1 = false;
            $e2 = false;
            foreach ($tokens1 as $t) { if (in_array(Str::lower($t), $grupo)) { $e1 = true; break; } }
            foreach ($tokens2 as $t) { if (in_array(Str::lower($t), $grupo)) { $e2 = true; break; } }
            if ($e1 && $e2) return 1.0;
        }
        return 0.0;
    }

    // ==================== TOKENIZAÇÃO ====================

    private function tokenizarAvancado(string $nome, bool $preNormalizado = false): array
    {
        if (!$preNormalizado) {
            $nome = $this->normalizarNome($nome);
        }

        $nome = Str::upper(trim($nome));
        if (empty($nome)) return [];

        $nome = preg_replace('/[^A-Z0-9\s]/', ' ', $nome);
        $nome = preg_replace('/\s+/', ' ', trim($nome));
        if (empty($nome)) return [];

        $tokens = array_filter(explode(' ', $nome), function ($token) {
            $token = trim($token);
            return strlen($token) > 1
                && !is_numeric($token)
                && !in_array(Str::lower($token), $this->stopWords);
        });

        $bigramas    = [];
        $tokensArray = array_values($tokens);
        for ($i = 0; $i < count($tokensArray) - 1; $i++) {
            $bigramas[] = $tokensArray[$i] . '_' . $tokensArray[$i + 1];
        }

        return array_merge($tokensArray, $bigramas);
    }

    private function tokenizarBasico(string $nome): array
    {
        $nome = $this->normalizarNome($nome);
        return array_filter(explode(' ', $nome), fn($t) => strlen($t) > 1);
    }

    // ==================== UTILITÁRIOS ====================

    private function getPrecoMedio(Product $product): float
    {
        // Mantido para chamadas externas; internamente usamos cachePrecos
        return $this->cachePrecos[$product->id]
            ?? (float) InvoiceItem::where('product_id', $product->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->avg('valor_unitario');
    }

    private function removerAcentos(string $texto): string
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        return strtr(Str::lower($texto), $map);
    }

    public function explicarSimilaridade(Product $p1, Product $p2, string $nomeNorm1 = '', string $nomeNorm2 = ''): array
    {
        $razoes = [];

        $n1 = $nomeNorm1 ?: $this->normalizarNome($p1->nome);
        $n2 = $nomeNorm2 ?: $this->normalizarNome($p2->nome);

        $tokens1   = $this->tokenizarAvancado($n1, preNormalizado: true);
        $tokens2   = $this->tokenizarAvancado($n2, preNormalizado: true);
        $comuns    = array_intersect($tokens1, $tokens2);
        $comunsUni = array_filter($comuns, fn($t) => !str_contains($t, '_'));

        if (count($comunsUni) > 0) {
            $razoes[] = 'Palavras em comum: ' . implode(', ', array_map('strtolower', array_slice(array_values($comunsUni), 0, 5)));
        }

        if ($p1->category_id && $p2->category_id && $p1->category_id === $p2->category_id) {
            $razoes[] = 'Mesma categoria';
        }

        $marcasComuns = array_intersect($this->detectarMarcas($n1), $this->detectarMarcas($n2));
        if ($marcasComuns) {
            $razoes[] = 'Mesma marca: ' . implode(', ', $marcasComuns);
        }

        if ($p1->unidade_padrao && $p2->unidade_padrao
            && strtoupper($p1->unidade_padrao) === strtoupper($p2->unidade_padrao)) {
            $razoes[] = 'Mesma unidade (' . strtoupper($p1->unidade_padrao) . ')';
        }

        $preco1 = $this->getPrecoMedio($p1);
        $preco2 = $this->getPrecoMedio($p2);
        if ($preco1 > 0 && $preco2 > 0) {
            $dif = abs($preco1 - $preco2) / max($preco1, $preco2);
            if ($dif <= 0.25) {
                $razoes[] = 'Preço similar (R$ ' . number_format($preco1, 2, ',', '.') . ' / R$ ' . number_format($preco2, 2, ',', '.') . ')';
            }
        }

        return $razoes;
    }

    // ==================== API PÚBLICA ====================

    public function sugerirAgrupamentosML(int $userId): array
    {
        // Prepara cache uma única vez para todo o processo
        $this->prepararCache($userId);

        $orfaos    = Product::where('user_id', $userId)
            ->whereNull('canonical_product_id')
            ->where('is_canonical', false)
            ->with('category')
            ->get();

        $sugestoes = [];

        foreach ($orfaos as $produto) {
            $similares = $this->encontrarSimilares($produto, 3);

            if (empty($similares) || $similares[0]['similaridade'] < 50) {
                continue;
            }

            $sugestoes[] = [
                'produto'      => $produto,
                'similares'    => $similares,
                'melhor_match' => $similares[0],
            ];
        }

        usort($sugestoes, fn($a, $b) => $b['melhor_match']['similaridade'] <=> $a['melhor_match']['similaridade']);

        return $sugestoes;
    }

    public function agruparComML(int $userId): array
    {
        $sugestoes = $this->sugerirAgrupamentosML($userId);
        $agrupados = 0;

        foreach ($sugestoes as $s) {
            if ($s['melhor_match']['similaridade'] >= 70) {
                $p   = $s['produto'];
                $sim = $s['melhor_match']['product'];
                $c   = $sim->canonical_product_id
                    ? Product::find($sim->canonical_product_id)
                    : ($sim->is_canonical ? $sim : null);

                $g = app(ProductGrouperService::class);

                if (!$c) {
                    $g->tornarCanonico($sim);
                    $c = $sim;
                }

                $g->agrupar($p, $c);
                $agrupados++;
            }
        }

        return ['total_sugestoes' => count($sugestoes), 'agrupados' => $agrupados];
    }
}
