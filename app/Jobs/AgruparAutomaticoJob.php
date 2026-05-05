<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ProductGrouperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AgruparAutomaticoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tentativas antes de marcar como falho.
     * O loop pode ser longo; 1 tentativa evita reprocessamento parcial
     * que duplicaria agrupamentos já feitos.
     */
    public int $tries = 1;

    /**
     * Timeout generoso: 5 minutos para usuários com muitos produtos.
     */
    public int $timeout = 300;

    public function __construct(
        public readonly int $userId,
    ) {}

    public function handle(ProductGrouperService $grouperService): void
    {
        Product::where('user_id', $this->userId)
            ->where('is_canonical', false)
            ->whereNull('canonical_product_id')
            ->each(function (Product $produto) use ($grouperService): void {
                $canonico = $grouperService->encontrarCanonico($produto, $this->userId);

                $canonico
                    ? $grouperService->agrupar($produto, $canonico)
                    : $grouperService->tornarCanonico($produto);
            });
    }
}
