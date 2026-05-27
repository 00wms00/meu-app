<?php

namespace App\Services;

use App\Models\Product;
use App\Models\InvoiceItem;
use Illuminate\Support\Str;

class ProductSimilarityService
{
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
        // Ruídos adicionais de cupom fiscal
        'lv', 'pague', 'leve', 'pg', 'promo', 'promocao', 'oferta',
        'kgf', 'kgfr', 'emb',
    ];

    // Mapeamento de sinônimos e variações
    private array $sinonimos = [
        'refrigerante' => ['refri', 'refrigerante', 'coca', 'cola', 'pepsi', 'guarana'],
        'arroz' => ['arroz', 'arrozagulinha', 'agulinha', 'arrozparboilizado', 'parboilizado'],
        'feijao' => ['feijao', 'feijao', 'feijaocarioca', 'carioca', 'feijaopreto'],
        'leite' => ['leite', 'leit', 'liquido', 'po'],
        'carne' => ['carne', 'bovina', 'bovino', 'moida', 'moido', 'contrafile', 'picanha', 'alcatra'],
        'frango' => ['frango', 'frang', 'sobrecoxa', 'coxinha', 'peito', 'file'],
        'macarrao' => ['macarrao', 'macarrao', 'espaguete', 'penne', 'fusilli', 'parafuso'],
        'sabao' => ['sabao', 'sabao', 'detergente', 'saponaceo'],
        'amaciante' => ['amaciante', 'amac', 'confort', 'downy', 'ype'],
        'agua' => ['agua', 'mineral', 'copo', 'garrafa', 'galao'],
        'cerveja' => ['cerveja', 'cervej', 'chopp', 'lata', 'longneck', 'garrafa'],
        'pao' => ['pao', 'paozinho', 'frances', 'integral', 'forma', 'leite'],
        'queijo' => ['queijo', 'queij', 'mussarela', 'mozarela', 'prato', 'cheddar', 'coalho'],
        'presunto' => ['presunto', 'presunt', 'apresuntado', 'mortadela'],
    ];

    // Pesos configuráveis
    private array $weights = [
        'tfidf'           => 0.40,  // 40% — similaridade textual (TF-IDF + Cosseno)
        'categoria'       => 0.20,  // 20% — mesma categoria
        'unidade'         => 0.08,  // 8%  — mesma unidade
        'marca'           => 0.10,  // 10% — mesma marca detectada
        'embalagem'       => 0.10,  // 10% — mesmo tipo de embalagem
        'faixa_preco'     => 0.10,  // 10% — faixa de preço similar
        'frequencia'      => 0.05,  // 5%  — frequência de compra similar
        'estabelecimento' => 0.02,  // 2%  — comprado no mesmo lugar
    ];

    // Tipos de embalagem
    private array $tiposEmbalagem = [
        'pet', 'lata', 'latinha', 'longneck', 'garrafa', 'sache', 'saco',
        'pacote', 'caixa', 'bandeja', 'pote', 'frasco', 'vidro', 'latao',
        'cartucho', 'refil', 'spray', 'aerosol', 'bombona', 'galao',
    ];

    // Marcas conhecidas
    private array $marcas = [
        'coca', 'cola', 'pepsi', 'guarana', 'fanta', 'sprite', 'kuat',
        'omo', 'ype', 'tixan', 'confort', 'downy', 'veja', 'brilhante',
        'nestle', 'parmalat', 'piracanjuba', 'italac', 'elege', 'batavo',
        'sadia', 'perdigao', 'friboi', 'swift', 'aurora',
        'unilever', 'procter', 'colgate', 'closeup', 'oralb',
        'bombril', 'assim', 'scotch', '3m',
    ];

    // ==================== NORMALIZAÇÃO ====================

    /**
     * Normaliza o nome do produto para comparação:
     * - remove acentos
     * - lowercase
     * - remove quantidades + unidades (500g, 2l, cx c/12…)
     * - remove caracteres especiais
     * - colapsa espaços duplos
     */
    public function normalizarNome(string $nome): string
    {
        $nome = $this->removerAcentos(Str::lower(trim($nome)));
        // Remove quantidades com unidades: 500g, 2l, 1.5kg, cx c/12, x un, etc.
        $nome = preg_replace('/\b\d+[.,]?\d*\s*(kg|g|gr|l|ml|un|und|cx|pc|pct|lt|dz|x)\b/i', '', $nome);
        $nome = preg_replace('/\bc\/\d+\b/i', '', $nome);
        // Remove caracteres não alfanuméricos (mantém espaço)
        $nome = preg_replace('/[^a-z0-9\s]/', ' ', $nome);
        // Colapsa espaços
        return trim(preg_replace('/\s+/', ' ', $nome));
    }

    // ==================== MÉTODO PRINCIPAL ====================

    public function encontrarSimilares(Product $produto, int $limit = 5): array
    {
        $userId = $produto->user_id;
        $todosProdutos = Product::where('user_id', $userId)
            ->where('id', '!=', $produto->id)
            ->with('category')
            ->get();

        if ($todosProdutos->isEmpty()) return [];

        // Normaliza o nome do produto base uma única vez
        $nomeNorm1 = $this->normalizarNome($produto->nome);

        $similares = [];

        foreach ($todosProdutos as $p) {
            $nomeNorm2 = $this->normalizarNome($p->nome);
            $score = $this->calcularScoreCompleto($produto, $p, $nomeNorm1, $nomeNorm2);

            // Corte mínimo reduzido de 0.35 para 0.25
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
        // Usa nomes normalizados se fornecidos, senão normaliza na hora
        $n1 = $nomeNorm1 ?: $this->normalizarNome($p1->nome);
        $n2 = $nomeNorm2 ?: $this->normalizarNome($p2->nome);

        $scores = [
            'tfidf'           => $this->similaridadeTextual($n1, $n2),
            'categoria'       => $this->mesmaCategoria($p1, $p2),
            'unidade'         => $this->mesmaUnidade($p1, $p2),
            'marca'           => $this->mesmaMarca($n1, $n2),
            'embalagem'       => $this->mesmoTipoEmbalagem($n1, $n2),
            'faixa_preco'     => $this->faixaPrecoSimilar($p1, $p2),
            'frequencia'      => $this->frequenciaSimilar($p1, $p2),
            'estabelecimento' => $this->mesmoEstabelecimento($p1, $p2),
        ];

        $scoreFinal = 0;
        foreach ($scores as $key => $value) {
            $scoreFinal += $value * $this->weights[$key];
        }

        // PENALIZAÇÃO SUAVIZADA: categoria E unidade divergem — reduz 30% (era 60%)
        if ($scores['categoria'] === 0.0 && $scores['unidade'] === 0.0) {
            $scoreFinal *= 0.70;
        }

        // PENALIZAÇÃO SUAVIZADA: preço sem sobreposição + texto fraco — reduz 50% (antes zeravam)
        if ($scores['faixa_preco'] === 0.0 && $scores['tfidf'] < 0.50) {
            $scoreFinal *= 0.50;
        }

        return $scoreFinal;
    }

    // ==================== COMPONENTES DO SCORE ====================

    /**
     * 1. Similaridade textual (TF-IDF + Cosseno) sobre nome já normalizado — 40%
     */
    private function similaridadeTextual(string $nomeNorm1, string $nomeNorm2): float
    {
        $tokens1 = $this->tokenizarAvancado($nomeNorm1, preNormalizado: true);
        $tokens2 = $this->tokenizarAvancado($nomeNorm2, preNormalizado: true);

        if (empty($tokens1) || empty($tokens2)) return 0;

        $tf1 = array_count_values($tokens1);
        $tf2 = array_count_values($tokens2);

        $todosTermos = array_unique(array_merge(array_keys($tf1), array_keys($tf2)));

        $dotProduct = 0; $norm1 = 0; $norm2 = 0;

        foreach ($todosTermos as $termo) {
            $v1 = ($tf1[$termo] ?? 0) / max(count($tokens1), 1);
            $v2 = ($tf2[$termo] ?? 0) / max(count($tokens2), 1);
            $dotProduct += $v1 * $v2;
            $norm1 += $v1 * $v1;
            $norm2 += $v2 * $v2;
        }

        if ($norm1 == 0 || $norm2 == 0) return 0;

        $cosseno = $dotProduct / (sqrt($norm1) * sqrt($norm2));
        $bonusSinonimos = $this->bonusSinonimos($tokens1, $tokens2);

        return ($cosseno * 0.8) + ($bonusSinonimos * 0.2);
    }

    /**
     * 2. Mesma categoria - 20%
     */
    private function mesmaCategoria(Product $p1, Product $p2): float
    {
        if ($p1->category_id && $p2->category_id && $p1->category_id === $p2->category_id) {
            return 1.0;
        }
        return 0;
    }

    /**
     * 3. Mesma unidade - 8%
     */
    private function mesmaUnidade(Product $p1, Product $p2): float
    {
        if ($p1->unidade_padrao && $p2->unidade_padrao &&
            strtoupper($p1->unidade_padrao) === strtoupper($p2->unidade_padrao)) {
            return 1.0;
        }
        return 0;
    }

    /**
     * 4. Mesma marca detectada sobre nome normalizado - 10%
     */
    private function mesmaMarca(string $nomeNorm1, string $nomeNorm2): float
    {
        $marcas1 = $this->detectarMarcas($nomeNorm1);
        $marcas2 = $this->detectarMarcas($nomeNorm2);

        if (empty($marcas1) || empty($marcas2)) return 0;

        $intersecao = array_intersect($marcas1, $marcas2);
        return count($intersecao) > 0 ? 1.0 : 0;
    }

    /**
     * 5. Mesmo tipo de embalagem sobre nome normalizado - 10%
     */
    private function mesmoTipoEmbalagem(string $nomeNorm1, string $nomeNorm2): float
    {
        $emb1 = $this->detectarEmbalagem($nomeNorm1);
        $emb2 = $this->detectarEmbalagem($nomeNorm2);

        if (empty($emb1) || empty($emb2)) return 0;
        return $emb1 === $emb2 ? 1.0 : 0;
    }

    /**
     * 6. Faixa de preço similar - 10%
     */
    private function faixaPrecoSimilar(Product $p1, Product $p2): float
    {
        $preco1 = $this->getPrecoMedio($p1);
        $preco2 = $this->getPrecoMedio($p2);

        if ($preco1 <= 0 || $preco2 <= 0) return 0;

        $diferenca = abs($preco1 - $preco2) / max($preco1, $preco2);

        if ($diferenca <= 0.10) return 1.0;
        if ($diferenca <= 0.25) return 0.7;
        if ($diferenca <= 0.50) return 0.3;
        return 0;
    }

    /**
     * 7. Frequência de compra similar - 5%
     */
    private function frequenciaSimilar(Product $p1, Product $p2): float
    {
        $freq1 = $p1->invoiceItems()->count();
        $freq2 = $p2->invoiceItems()->count();

        if ($freq1 == 0 || $freq2 == 0) return 0;

        return min($freq1, $freq2) / max($freq1, $freq2);
    }

    /**
     * 8. Mesmo estabelecimento - 2%
     */
    private function mesmoEstabelecimento(Product $p1, Product $p2): float
    {
        $estabs1 = InvoiceItem::where('product_id', $p1->id)
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->distinct('invoices.nome_estabelecimento')
            ->pluck('invoices.nome_estabelecimento')
            ->toArray();

        $estabs2 = InvoiceItem::where('product_id', $p2->id)
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->distinct('invoices.nome_estabelecimento')
            ->pluck('invoices.nome_estabelecimento')
            ->toArray();

        $comuns = array_intersect($estabs1, $estabs2);
        return count($comuns) > 0 ? 1.0 : 0;
    }

    // ==================== DETECÇÃO (recebe nome já normalizado) ====================

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
            $encontrou1 = false; $encontrou2 = false;
            foreach ($tokens1 as $t) { if (in_array(Str::lower($t), $grupo)) { $encontrou1 = true; break; } }
            foreach ($tokens2 as $t) { if (in_array(Str::lower($t), $grupo)) { $encontrou2 = true; break; } }
            if ($encontrou1 && $encontrou2) return 1.0;
        }
        return 0;
    }

    // ==================== TOKENIZAÇÃO ====================

    /**
     * @param bool $preNormalizado  quando true, pula normalização (já feita fora)
     */
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

        $tokens = explode(' ', $nome);

        $tokens = array_filter($tokens, function ($token) {
            $token = trim($token);
            if (strlen($token) <= 1) return false;
            if (is_numeric($token)) return false;
            if (in_array(Str::lower($token), $this->stopWords)) return false;
            return true;
        });

        $bigramas = [];
        $tokensArray = array_values($tokens);
        for ($i = 0; $i < count($tokensArray) - 1; $i++) {
            $bigramas[] = $tokensArray[$i] . '_' . $tokensArray[$i + 1];
        }

        return array_merge(array_values($tokens), $bigramas);
    }

    private function tokenizarBasico(string $nome): array
    {
        $nome = $this->normalizarNome($nome);
        return array_filter(explode(' ', $nome), fn($t) => strlen($t) > 1);
    }

    // ==================== UTILITÁRIOS ====================

    private function getPrecoMedio(Product $product): float
    {
        return (float) InvoiceItem::where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->avg('valor_unitario') ?? 0;
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

    /**
     * Explica por que dois produtos são similares (usa nomes normalizados)
     */
    public function explicarSimilaridade(Product $p1, Product $p2, string $nomeNorm1 = '', string $nomeNorm2 = ''): array
    {
        $razoes = [];

        $n1 = $nomeNorm1 ?: $this->normalizarNome($p1->nome);
        $n2 = $nomeNorm2 ?: $this->normalizarNome($p2->nome);

        $tokens1 = $this->tokenizarAvancado($n1, preNormalizado: true);
        $tokens2 = $this->tokenizarAvancado($n2, preNormalizado: true);
        $comuns  = array_intersect($tokens1, $tokens2);

        // Filtra bigramas da exibição (apenas unigramas nas razões)
        $comunsUni = array_filter($comuns, fn($t) => !str_contains($t, '_'));

        if (count($comunsUni) > 0) {
            $razoes[] = 'Palavras em comum: ' . implode(', ', array_map('strtolower', array_slice($comunsUni, 0, 5)));
        }

        if ($p1->category_id && $p2->category_id && $p1->category_id === $p2->category_id) {
            $razoes[] = 'Mesma categoria';
        }

        $marcas1 = $this->detectarMarcas($n1);
        $marcas2 = $this->detectarMarcas($n2);
        $marcasComuns = array_intersect($marcas1, $marcas2);
        if ($marcasComuns) {
            $razoes[] = 'Mesma marca: ' . implode(', ', $marcasComuns);
        }

        if ($p1->unidade_padrao && $p2->unidade_padrao &&
            strtoupper($p1->unidade_padrao) === strtoupper($p2->unidade_padrao)) {
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

    public function sugerirAgrupamentosML(int $userId): array
    {
        $orfao = Product::where('user_id', $userId)
            ->whereNull('canonical_product_id')
            ->where('is_canonical', false)
            ->get();

        $sugestoes = [];

        foreach ($orfao as $produto) {
            $similares = $this->encontrarSimilares($produto, 3);

            if (empty($similares)) {
                continue;
            }

            $melhor = $similares[0];

            // Threshold reduzido de 60% para 50%
            if ($melhor['similaridade'] < 50) {
                continue;
            }

            $sugestoes[] = [
                'produto'      => $produto,
                'similares'    => $similares,
                'melhor_match' => $melhor,
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
                if (!$c) {
                    $g = app(ProductGrouperService::class);
                    $g->tornarCanonico($sim);
                    $c = $sim;
                }
                $g = app(ProductGrouperService::class);
                $g->agrupar($p, $c);
                $agrupados++;
            }
        }
        return ['total_sugestoes' => count($sugestoes), 'agrupados' => $agrupados];
    }
}
