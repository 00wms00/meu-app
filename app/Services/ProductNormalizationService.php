<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductNormalizationService
{
    private ProductNameNormalizer $normalizer;

    public function __construct()
    {
        $this->normalizer = new ProductNameNormalizer();
    }

    public function analyze(Product $product): array
    {
        $assinatura = $this->normalizer->extractSignature($product->nome);
        $componentes = $this->parseSignature($assinatura);
        $nomeExibicao = $this->generateDisplayName($componentes, $product->nome);
        
        return [
            'assinatura' => $assinatura,
            'componentes' => $componentes,
            'nome_exibicao_sugerido' => $nomeExibicao,
            'produto' => $product,
        ];
    }

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

    public function markForReview(Product $product): void
    {
        $analise = $this->analyze($product);
        
        $product->update([
            'nome_normalizado' => $analise['assinatura'],
            'normalizacao_status' => 'revisar',
            'assinatura_componentes' => $analise['componentes'],
        ]);
    }

    public function reject(Product $product): void
    {
        $product->update([
            'normalizacao_status' => 'pendente',
            'nome_normalizado' => null,
            'nome_exibicao' => null,
            'assinatura_componentes' => null,
        ]);
    }

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
                // Pular
            }
        }

        return $count;
    }

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

    private function generateDisplayName(array $componentes, string $nomeOriginal): string
    {
        $parts = [];

        // Tipo - CAIXA ALTA
        if (!empty($componentes['tipo']) && $componentes['tipo'] !== 'outro') {
            $parts[] = strtoupper($componentes['tipo']);
        }

        // Marca - CAIXA ALTA
        if (!empty($componentes['marca'])) {
            $parts[] = strtoupper($componentes['marca']);
        }

        // Caracteristica (sem repetir palavras do tipo) - CAIXA ALTA
        if (!empty($componentes['caracteristica'])) {
            $caract = $componentes['caracteristica'];
            $tipoWords = explode(' ', strtolower($componentes['tipo'] ?? ''));
            $caractWords = explode(' ', strtolower($caract));
            $filtered = array_diff($caractWords, $tipoWords);
            if (!empty($filtered)) {
                $parts[] = strtoupper(implode(' ', $filtered));
            }
        }

        // Quantidade - CAIXA ALTA
        if (!empty($componentes['quantidade'])) {
            $parts[] = strtoupper($componentes['quantidade']);
        }

        $sugerido = implode(' ', $parts);

        if (strlen($sugerido) < 10) {
            return strtoupper($nomeOriginal);
        }

        return $sugerido;
    }
}
