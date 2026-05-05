<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductGrouperService
{
    // Palavras irrelevantes para comparação
    private array $stopWords = [
        'kg', 'g', 'l', 'ml', 'un', 'cx', 'pc', 'pct', 'fd', 'lt',
        'de', 'da', 'do', 'das', 'dos', 'e', 'a', 'o', 'com', 'sem',
        'para', 'por', 'em', 'no', 'na', 'os', 'as', 'um', 'uma',
        'sache', 'saco', 'pacote', 'lata', 'garrafa', 'fardo',
    ];

    /**
     * Normaliza o nome do produto para comparação
     */
    public function normalizarNome(string $nome): string
    {
        // Converter para maiúsculas
        $nome = Str::upper($nome);
        
        // Remover acentos
        $nome = $this->removerAcentos($nome);
        
        // Remover caracteres especiais
        $nome = preg_replace('/[^A-Z0-9\s]/', ' ', $nome);
        
        // Remover espaços extras
        $nome = preg_replace('/\s+/', ' ', trim($nome));
        
        return $nome;
    }

    /**
     * Extrai palavras-chave do nome do produto
     */
    public function extrairKeywords(string $nome): array
    {
        $nome = $this->normalizarNome($nome);
        $palavras = explode(' ', $nome);
        
        // Filtrar stop words e palavras muito curtas
        $keywords = array_filter($palavras, function($palavra) {
            $palavra = trim($palavra);
            return strlen($palavra) > 2 && !in_array(Str::lower($palavra), $this->stopWords);
        });
        
        // Remover duplicatas e reindexar
        return array_values(array_unique($keywords));
    }

    /**
     * Calcula similaridade entre dois nomes de produtos
     */
    public function calcularSimilaridade(string $nome1, string $nome2): float
    {
        $keywords1 = $this->extrairKeywords($nome1);
        $keywords2 = $this->extrairKeywords($nome2);
        
        if (empty($keywords1) || empty($keywords2)) {
            return 0;
        }
        
        // Interseção de palavras-chave
        $intersecao = array_intersect($keywords1, $keywords2);
        $uniao = array_unique(array_merge($keywords1, $keywords2));
        
        // Coeficiente de Jaccard
        $jaccard = count($intersecao) / count($uniao);
        
        // Similaridade de palavras
        $similaridadePalavras = $this->similaridadePalavras($keywords1, $keywords2);
        
        // Peso: 60% Jaccard + 40% similaridade de palavras
        return ($jaccard * 0.6) + ($similaridadePalavras * 0.4);
    }

    /**
     * Encontra o melhor produto canônico para agrupar
     */
    public function encontrarCanonico(Product $produto, int $userId): ?Product
    {
        // Buscar produtos canônicos do usuário
        $canonicos = Product::where('user_id', $userId)
            ->where('is_canonical', true)
            ->get();
        
        $melhorSimilaridade = 0;
        $melhorCanonico = null;
        
        foreach ($canonicos as $canonico) {
            $similaridade = $this->calcularSimilaridade($produto->nome, $canonico->nome);
            
            if ($similaridade > $melhorSimilaridade && $similaridade >= 0.6) {
                $melhorSimilaridade = $similaridade;
                $melhorCanonico = $canonico;
            }
        }
        
        return $melhorCanonico;
    }

    /**
     * Sugere agrupamentos para um produto
     */
    public function sugerirAgrupamentos(Product $produto, int $userId): array
    {
        $outros = Product::where('user_id', $userId)
            ->where('id', '!=', $produto->id)
            ->where('is_canonical', true)
            ->get();
        
        $sugestoes = [];
        
        foreach ($outros as $outro) {
            $similaridade = $this->calcularSimilaridade($produto->nome, $outro->nome);
            
            if ($similaridade >= 0.5) {
                $sugestoes[] = [
                    'product' => $outro,
                    'similaridade' => round($similaridade * 100, 1),
                    'keywords_comuns' => $this->getKeywordsComuns($produto->nome, $outro->nome),
                ];
            }
        }
        
        // Ordenar por similaridade
        usort($sugestoes, function($a, $b) {
            return $b['similaridade'] <=> $a['similaridade'];
        });
        
        return array_slice($sugestoes, 0, 5);
    }

    /**
     * Agrupa um produto a um canônico
     */
    public function agrupar(Product $produto, Product $canonico): void
    {
        $produto->update([
            'canonical_product_id' => $canonico->id,
            'is_canonical' => false,
        ]);
    }

    /**
     * Desagrupa um produto
     */
    public function desagrupar(Product $produto): void
    {
        $produto->update([
            'canonical_product_id' => null,
        ]);
    }

    /**
     * Torna um produto canônico
     */
    public function tornarCanonico(Product $produto): void
    {
        // Desagrupar de qualquer canônico anterior
        $produto->update([
            'canonical_product_id' => null,
            'is_canonical' => true,
            'nome_normalizado' => $this->normalizarNome($produto->nome),
            'keywords' => $this->extrairKeywords($produto->nome),
        ]);
    }

    /**
     * Obtém palavras-chave comuns entre dois nomes
     */
    private function getKeywordsComuns(string $nome1, string $nome2): array
    {
        $k1 = $this->extrairKeywords($nome1);
        $k2 = $this->extrairKeywords($nome2);
        return array_values(array_intersect($k1, $k2));
    }

    /**
     * Remove acentos
     */
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
     * Similaridade entre listas de palavras
     */
    private function similaridadePalavras(array $palavras1, array $palavras2): float
    {
        $total = 0;
        $count = 0;
        
        foreach ($palavras1 as $p1) {
            $melhorDistancia = 1.0;
            foreach ($palavras2 as $p2) {
                $distancia = $this->distanciaLevenshteinNormalizada($p1, $p2);
                $melhorDistancia = min($melhorDistancia, $distancia);
            }
            $total += (1 - $melhorDistancia);
            $count++;
        }
        
        return $count > 0 ? $total / $count : 0;
    }

    /**
     * Distância de Levenshtein normalizada
     */
    private function distanciaLevenshteinNormalizada(string $str1, string $str2): float
    {
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) return 0;
        
        return levenshtein($str1, $str2) / $maxLen;
    }
}
