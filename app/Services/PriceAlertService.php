<?php

namespace App\Services;

use App\Models\InvoiceItem;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class PriceAlertService
{
    /**
     * Verifica e atualiza alertas de preço para todos os produtos
     */
    public function verificarTodos(int $userId): array
    {
        $alertas = PriceAlert::where('user_id', $userId)->ativos()->get();
        $disparados = [];
        
        foreach ($alertas as $alerta) {
            if ($this->verificarAlerta($alerta)) {
                $disparados[] = $alerta;
            }
        }
        
        return $disparados;
    }

    /**
     * Verifica um alerta específico
     */
    public function verificarAlerta(PriceAlert $alerta): bool
    {
        // Buscar último preço do produto
        $ultimoItem = InvoiceItem::where('product_id', $alerta->product_id)
            ->whereHas('invoice', function($q) use ($alerta) {
                $q->where('user_id', $alerta->user_id);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$ultimoItem) return false;

        $precoAtual = $ultimoItem->valor_unitario;
        
        if ($alerta->preco_referencia > 0) {
            $variacao = (($precoAtual - $alerta->preco_referencia) / $alerta->preco_referencia) * 100;
        } else {
            $variacao = 0;
        }

        $alerta->update([
            'preco_atual' => $precoAtual,
            'variacao_percentual' => round($variacao, 2),
        ]);

        // Disparar se variação >= limite
        if ($variacao >= $alerta->limite_alerta) {
            $alerta->update(['ultimo_alerta' => now()]);
            return true;
        }

        return false;
    }

    /**
     * Cria ou atualiza alerta para um produto
     */
    public function criarOuAtualizar(int $userId, int $productId, float $limiteAlerta = 10): PriceAlert
    {
        // Buscar preço de referência (média dos últimos 3 meses ou último preço)
        $precoRef = $this->calcularPrecoReferencia($userId, $productId);

        return PriceAlert::updateOrCreate(
            ['user_id' => $userId, 'product_id' => $productId],
            [
                'preco_referencia' => $precoRef,
                'preco_atual' => $precoRef,
                'limite_alerta' => $limiteAlerta,
                'variacao_percentual' => 0,
                'ativo' => true,
            ]
        );
    }

    /**
     * Calcula preço de referência (média dos últimos preços)
     */
    private function calcularPrecoReferencia(int $userId, int $productId): float
    {
        $precos = InvoiceItem::where('product_id', $productId)
            ->whereHas('invoice', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->pluck('valor_unitario');

        if ($precos->count() === 0) return 0;
        
        return $precos->avg();
    }

    /**
     * Busca produtos com maiores variações
     */
    public function getMaioresVariacoes(int $userId, int $limit = 5): array
    {
        $alertas = PriceAlert::where('user_id', $userId)
            ->ativos()
            ->orderBy('variacao_percentual', 'desc')
            ->take($limit)
            ->get();

        return $alertas->map(function($alerta) {
            return [
                'produto' => $alerta->product->nome,
                'preco_ref' => $alerta->preco_referencia,
                'preco_atual' => $altera->preco_atual,
                'variacao' => $alerta->variacao_percentual,
                'status' => $alerta->variacao_percentual >= $alerta->limite_alerta ? 'disparado' : 'normal',
            ];
        })->toArray();
    }
}
