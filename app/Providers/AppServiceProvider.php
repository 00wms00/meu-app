<?php

namespace App\Providers;

use App\Models\InvoiceItem;
use App\Observers\InvoiceItemObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        InvoiceItem::observe(InvoiceItemObserver::class);

        // Se quiser registrar todos de uma vez no futuro:
        // Invoice::observe(InvoiceObserver::class);
        // Product::observe(ProductObserver::class);
    }
}