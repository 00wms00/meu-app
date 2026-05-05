<?php

namespace App\Services;

use App\Models\Product;
use App\Services\ProductNameNormalizer;
use Illuminate\Support\Facades\Auth;

class ProductNormalizationService
{
    private ProductNameNormalizer $normalizer;

    public function __construct()
    {
        $this->normalizer = new ProductNameNormalizer();
    }

    /**
     * Analisa um produto e retorna a sugestão de normalização
     */
    public function analyze(Product $product): array
    {
        $assinatura = $this->normalizer->extractSignature($product->nome);
        $componentes = $this->parseSignature($assinatura);
        
        // Gerar nome amigável sugerido
        $nomeExibicao = $this->generateDisplayName($componentes, $product->nome);
        
        return [
            'assinatura' => $assinatura,
            'componentes' => $componentes,
            'nome_exibicao_sugerido' => $nomeExibicao,
            'produto' => $product,
        ];
    }

    /**
     * Aplica a normalização aprovada
     */
    public function approve(Product $product, ?string $nomeExibicao = null): void
    {
        $analise = $this->analyze($product);
        
        $product->update([
            'nome_normalizado' => $analise['assinatura'],
            'nome_exibicao' => $nomeExibicao ?: $analise['nome_exibicao_sugerido'],
            'normalizacao_status' => 'aprovado',
            'assinatura_componentes' => $analise['componentes'],
            'normalizado_por' => Auth::id(),
            'normalizado_em' => now(),
        ]);
    }

    /**
     * Marca para revisão manual
     */
    public function markForReview(Product $product): void
    {
        $analise = $this->analyze($product);
        
        $product->update([
            'nome_normalizado' => $analise['assinatura'],
            'normalizacao_status' => 'revisar',
            'assinatura_componentes' => $analise['componentes'],
        ]);
    }

    /**
     * Rejeita a normalização automática (mantém como pendente)
     */
    public function reject(Product $product): void
    {
        $product->update([
            'normalizacao_status' => 'pendente',
            'nome_normalizado' => null,
            'nome_exibicao' => null,
            'assinatura_componentes' => null,
        ]);
    }

    /**
     * Atualiza manualmente o nome de exibição
     */
    public function updateDisplayName(Product $product, string $nomeExibicao): void
    {
        $assinatura = $this->normalizer->extractSignature($product->nome);
        
        $product->update([
            'nome_exibicao' => $nomeExibicao,
            'nome_normalizado' => $assinatura,
            'normalizacao_status' => 'aprovado',
            'normalizado_por' => Auth::id(),
            'normalizado_em' => now(),
        ]);
    }

    /**
     * Processa TODOS os produtos pendentes e gera sugestões
     */
    public function processAllPending(int $userId): array
    {
        $produtos = Product::where('user_id', $userId)
            ->where(function($q) {
                $q->whereNull('normalizacao_status')
                  ->orWhere('normalizacao_status', 'pendente');
            })
            ->get();

        $analises = [];
        foreach ($produtos as $produto) {
            $analises[] = $this->analyze($produto);
        }

        return $analises;
    }

    /**
     * Aprova todos os pendentes automaticamente
     */
    public function approveAllPending(int $userId): int
    {
        $produtos = Product::where('user_id', $userId)
            ->where(function($q) {
                $q->whereNull('normalizacao_status')
                  ->orWhere('normalizacao_status', 'pendente');
            })
            ->get();

        $count = 0;
        foreach ($produtos as $produto) {
            try {
                $this->approve($produto);
                $count++;
            } catch (\Exception $e) {
                // Pular produtos com erro
            }
        }

        return $count;
    }

    /**
     * Analisa a assinatura em componentes
     */
    private function parseSignature(string $assinatura): array
    {
        $parts = explode('|', $assinatura);
        
        return [
            'tipo' => $parts[0] ?? '',
            'marca' => $parts[1] ?? '',
            'caracteristica' => $parts[2] ?? '',
            'quantidade' => $parts[3] ?? '',
        ];
    }

    /**
     * Gera nome amigável baseado nos componentes
     */
    private function generateDisplayName(array $componentes, string $nomeOriginal): string
    {
        $parts = [];
        
        // Se tem tipo, capitalizar
        if (!empty($componentes['tipo']) && $componentes['tipo'] !== 'outro') {
            $parts[] = ucfirst($componentes['tipo']);
        }
        
        // Se tem marca, capitalizar
        if (!empty($componentes['marca'])) {
            $parts[] = strtoupper($componentes['marca']);
        }
        
        // Se tem característica
        if (!empty($componentes['caracteristica'])) {
            $parts[] = ucfirst($componentes['caracteristica']);
        }
        
        // Se tem quantidade
        if (!empty($componentes['quantidade'])) {
            $parts[] = $componentes['quantidade'];
        }

        $sugerido = implode(' ', $parts);

        // Se a sugestão ficou muito curta, usar nome original
        if (strlen($sugerido) < 10) {
            return $nomeOriginal;
        }

        return $sugerido;
    }
}
